<?php

namespace App\Services;

use App\Models\CenterSequence;
use App\Models\Invoice;
use App\Models\WorkOrder;
use App\Services\Optimization\TaxCalculator;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    protected TaxCalculator $taxCalculator;

    public function __construct(TaxCalculator $taxCalculator)
    {
        $this->taxCalculator = $taxCalculator;
    }

    /**
     * Create a Draft Invoice from a Work Order.
     */
    public function createFromWorkOrder(WorkOrder $workOrder, $user): Invoice
    {
        return DB::transaction(function () use ($workOrder, $user) {
            $customer = $workOrder->customer;
            $customerAddress = null;
            if ($customer) {
                $addressParts = array_filter([
                    $customer->building_number ? __('common.building') . ' ' . $customer->building_number : null,
                    $customer->address_line ?: null,
                    $customer->district ? __('common.district') . ' ' . $customer->district : null,
                    $customer->city ?: null,
                    $customer->postal_code ? __('common.postal_code') . ' ' . $customer->postal_code : null,
                ]);
                $customerAddress = !empty($addressParts) ? implode('، ', $addressParts) : null;
            }

            // 1. Create Invoice Draft (No Number yet)
            $invoice = Invoice::create([
                'tenant_id' => $workOrder->tenant_id,
                'center_id' => $workOrder->center_id,
                'customer_id' => $workOrder->customer_id,
                'work_order_id' => $workOrder->id,
                'invoice_number' => 'DRAFT-' . $workOrder->code, // Temporary
                'issue_date' => now(),
                'supply_date' => now(),
                'status' => 'draft',
                'payment_status' => 'unpaid',
                // Snapshots
                'customer_name_snapshot' => $customer?->name,
                'customer_vat_snapshot' => $customer?->tax_number,
                'customer_address_snapshot' => $customerAddress,
                
                // Tax settings
                'tax_enabled_snapshot' => $workOrder->tax_enabled_snapshot,
                'pricing_mode_snapshot' => $workOrder->pricing_mode_snapshot,
                'tax_rate_snapshot' => $workOrder->tax_rate_snapshot,
                'currency_code' => $workOrder->currency_code,

                // Totals
                'total_excl_tax' => $workOrder->total_excl_tax,
                'total_tax' => $workOrder->total_tax,
                'total_incl_tax' => $workOrder->total_incl_tax,
                'total_taxable_amount' => $workOrder->total_excl_tax,
                'total_paid' => 0,
            ]);

            // 2. Convert WO Items (Services) to Invoice Lines
            foreach ($workOrder->items as $item) {
                $invoice->lines()->create([
                    'is_part' => false,
                    'part_id' => null,
                    'description' => $item->title ?? $item->service->name,
                    'qty' => $item->qty,
                    'unit_price' => $item->unit_price,
                    'is_taxable' => $item->is_taxable,
                    'tax_category_code' => $item->tax_category_code,
                    'tax_rate_snapshot' => $item->tax_rate_snapshot,
                    'tax_amount' => $item->tax_amount,
                    'line_total_excl_tax' => $item->line_total_excl_tax ?? $item->line_total,
                    'line_total_incl_tax' => $item->line_total_incl_tax ?? $item->line_total,
                ]);
            }

            // 3. Convert WO Parts to Invoice Lines
            foreach ($workOrder->parts as $part) {
                $invoice->lines()->create([
                    'is_part' => true,
                    'part_id' => $part->part_id,
                    'description' => $part->name,
                    'qty' => $part->qty,
                    'unit_price' => $part->unit_price,
                    'is_taxable' => $workOrder->tax_enabled_snapshot,
                    'tax_category_code' => null,
                    'tax_rate_snapshot' => $workOrder->tax_rate_snapshot ?? 15.00,
                    'tax_amount' => $part->tax_amount,
                    'line_total_excl_tax' => $part->total,
                    'line_total_incl_tax' => $part->grand_total,
                ]);
            }
            
            return $invoice;
        });
    }

    /**
     * Issue an invoice (Transition to Valid).
     * assign sequential number and ICV.
     */
    public function issueInvoice(Invoice $invoice): Invoice
    {
        if ($invoice->status !== 'draft') {
            throw new \Exception("Only draft invoices can be issued.");
        }

        return DB::transaction(function () use ($invoice) {
            $tenantId = $invoice->tenant_id;
            $centerId = $invoice->center_id;

            // 1. Generate Sequential Number
            // Format: INV-{Center}-{Year}-{Seq}
            $year = now()->year;
            $seq = CenterSequence::getNextValue($tenantId, $centerId, 'invoice', $year);
            $invoiceNumber = sprintf('INV-%s-%s-%06d', $centerId, $year, $seq);

            // 2. Generate ICV (Invoice Counter Value) for ZATCA
            // Must be strictly increasing per center (no gaps allowed in the chain)
            $icv = CenterSequence::getNextValue($tenantId, $centerId, 'icv'); 
            
            // 3. Update Invoice
            $invoice->update([
                'status' => 'valid',
                'invoice_number' => $invoiceNumber,
                'issue_date' => now(),
                // 'icv' => $icv, // Add column if needed in schema, currently storing in ZATCA payload
            ]);
            
            // TODO: ZATCA Chaining logic (Prev Hash) would happen here.

            return $invoice;
        });
    }

    /**
     * Get proforma data (for printing without creating invoice record)
     */
    public function getProformaData(WorkOrder $workOrder): array
    {
        $workOrder->load(['customer', 'items.service', 'center', 'vehicle']);
        
        $tenant = $workOrder->tenant;
        $taxSettings = $tenant->taxSettings;
        $template = \App\Models\InvoiceTemplate::getDefault($tenant->id, 'proforma');

        return [
            'work_order' => $workOrder,
            'customer' => $workOrder->customer,
            'vehicle' => $workOrder->vehicle,
            'items' => $workOrder->items,
            'center' => $workOrder->center,
            'tenant' => $tenant,
            'tax_settings' => $taxSettings,
            'template' => $template,
            'labels' => $template->getAllLabels(),
            'is_proforma' => true,
            'totals' => [
                'subtotal' => $workOrder->total_excl_tax ?? 0,
                'tax' => $workOrder->total_tax ?? 0,
                'total' => $workOrder->total_incl_tax ?? 0,
            ],
        ];
    }

    /**
     * Generate ZATCA Phase 1 QR code TLV
     */
    public function generateZatcaQr(Invoice $invoice): string
    {
        $tenant = $invoice->tenant;
        
        // TLV encoding for ZATCA
        $tlv = '';
        $tlv .= $this->tlvEncode(1, $tenant->legal_name ?? $tenant->name); // Seller Name
        $tlv .= $this->tlvEncode(2, $tenant->taxSettings?->tax_number ?? ''); // VAT Number
        $tlv .= $this->tlvEncode(3, $invoice->issue_date->toIso8601String()); // Timestamp
        $tlv .= $this->tlvEncode(4, number_format($invoice->total_incl_tax, 2, '.', '')); // Total with VAT
        $tlv .= $this->tlvEncode(5, number_format($invoice->total_tax, 2, '.', '')); // VAT Amount

        return base64_encode($tlv);
    }

    /**
     * TLV encoding helper
     */
    protected function tlvEncode(int $tag, string $value): string
    {
        return chr($tag) . chr(strlen($value)) . $value;
    }
}
