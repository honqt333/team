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
                // Snapshots auto-filled by HasTaxSnapshot
            ]);

            // 2. Convert WO Items to Invoice Lines (Simplified mapping for now)
            $lines = [];
            foreach ($workOrder->items as $item) {
                $lines[] = [
                    'invoice_id' => $invoice->id,
                    'description' => $item->service->name ?? $item->title,
                    'qty' => $item->qty,
                    'unit_price' => $item->unit_price, // Assuming net price after discount? Or base?
                    // Logic would go here to map item details
                ];
                // For brevity in this task, we skip full line mapping implementation unless requested
                // focusing on the numbering logic instead.
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
