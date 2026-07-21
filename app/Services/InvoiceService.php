<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CenterSequence;
use App\Models\Invoice;
use App\Models\InvoiceTemplate;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Models\WorkOrderItemPart;
use App\Services\Optimization\TaxCalculator;
use Exception;
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
    public function createFromWorkOrder(WorkOrder $workOrder, $user, array $additionalData = []): Invoice
    {
        return DB::transaction(function () use ($workOrder, $additionalData) {
            // Re-fetch items + parts directly to avoid stale/empty relations
            // (the caller's $workOrder may not have eager-loaded them).
            $activeItems = WorkOrderItem::where('work_order_id', $workOrder->id)
                ->where('status', '!=', WorkOrderItem::STATUS_CANCELLED)
                ->get();
            $activeParts = WorkOrderItemPart::where('work_order_id', $workOrder->id)
                ->whereNotIn('status', [
                    WorkOrderItemPart::STATUS_CANCELLED,
                    WorkOrderItemPart::STATUS_REVERSED,
                ])
                ->get();
            $activeItemIds = $activeItems->pluck('id')->all();

            $customer = $workOrder->customer;
            $customerAddress = null;

            if ($customer) {
                $addressParts = array_filter([
                    $customer->building_number ? __('common.building').' '.$customer->building_number : null,
                    $customer->address_line ?: null,
                    $customer->district ? __('common.district').' '.$customer->district : null,
                    $customer->city ?: null,
                    $customer->postal_code ? __('common.postal_code').' '.$customer->postal_code : null,
                ]);
                $customerAddress = ! empty($addressParts) ? implode('، ', $addressParts) : null;
            }

            // 1. Create Invoice Draft (No Number yet)
            $invoice = Invoice::create([
                'tenant_id' => $workOrder->tenant_id,
                'center_id' => $workOrder->center_id,
                'customer_id' => $workOrder->customer_id,
                'work_order_id' => $workOrder->id,
                'invoice_number' => 'DRAFT-'.$workOrder->code, // Temporary
                'issue_date' => now(),
                'supply_date' => now(),
                'due_date' => $additionalData['due_date'] ?? null,
                'notes' => $additionalData['notes'] ?? null,
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
            foreach ($activeItems as $item) {
                $isStandard = $item->service &&
                    trim($item->service->name_ar) !== 'أخرى' &&
                    strtolower(trim($item->service->name_en)) !== 'other';

                if ($isStandard) {
                    $description = $item->service->name;
                } else {
                    $description = $item->title;
                }

                // Append warranty if exists
                if ($item->warranty_value_snapshot && $item->warranty_unit_snapshot) {
                    $locale = app()->getLocale();
                    $units = [
                        'ar' => [
                            'days' => $item->warranty_value_snapshot >= 3 && $item->warranty_value_snapshot <= 10 ? 'أيام' : 'يوم',
                            'weeks' => $item->warranty_value_snapshot >= 3 && $item->warranty_value_snapshot <= 10 ? 'أسابيع' : 'أسبوع',
                            'months' => $item->warranty_value_snapshot >= 3 && $item->warranty_value_snapshot <= 10 ? 'أشهر' : 'شهر',
                            'years' => $item->warranty_value_snapshot >= 3 && $item->warranty_value_snapshot <= 10 ? 'سنوات' : 'سنة',
                        ],
                        'en' => [
                            'days' => $item->warranty_value_snapshot == 1 ? 'day' : 'days',
                            'weeks' => $item->warranty_value_snapshot == 1 ? 'week' : 'weeks',
                            'months' => $item->warranty_value_snapshot == 1 ? 'month' : 'months',
                            'years' => $item->warranty_value_snapshot == 1 ? 'year' : 'years',
                        ],
                    ];

                    $unitLabel = $units[$locale][$item->warranty_unit_snapshot] ?? $item->warranty_unit_snapshot;
                    $label = $locale === 'ar' ? 'ضمان الخدمة' : 'Service Warranty';
                    $description .= " - {$label} {$item->warranty_value_snapshot} {$unitLabel}";
                }

                if ($item->is_warranty) {
                    $locale = app()->getLocale();
                    $label = $locale === 'ar' ? 'تحت الضمان' : 'Under Warranty';
                    $description .= " ({$label})";
                }

                // Compute line totals locally so we don't depend on stale
                // WorkOrderItem.line_total_excl_tax / line_total_incl_tax columns
                // (those have been observed as 0 on the WO side, which produced
                // empty "amount" cells in the invoice cost box).
                $qty = (float) $item->qty;
                $unitPrice = (float) $item->unit_price;
                $discountAmt = (float) ($item->discount_amount ?? 0);
                $taxEnabled = (bool) ($workOrder->tax_enabled_snapshot ?? false);
                $taxRate = $taxEnabled ? (float) ($item->tax_rate_snapshot ?? 0) : 0.0;
                $isInclusive = ($workOrder->pricing_mode_snapshot ?? 'exclusive') === 'inclusive';

                $net = max(0, ($qty * $unitPrice) - $discountAmt);

                if ($isInclusive && $taxEnabled) {
                    $lineIncl = round($net, 2);
                    $lineExcl = $taxRate > 0 ? round($net / (1 + ($taxRate / 100)), 2) : $net;
                    $taxAmt = round($lineIncl - $lineExcl, 2);
                } else {
                    $lineExcl = round($net, 2);
                    $taxAmt = $taxEnabled ? round($lineExcl * ($taxRate / 100), 2) : 0.0;
                    $lineIncl = round($lineExcl + $taxAmt, 2);
                }

                $invoice->lines()->create([
                    'is_part' => false,
                    'part_id' => null,
                    'description' => $description,
                    'qty' => $qty,
                    'unit_price' => $unitPrice,
                    'discount_type' => $item->discount_type,
                    'discount_value' => $item->discount_value,
                    'discount_amount' => $discountAmt,
                    'is_taxable' => $taxEnabled && ($item->is_taxable !== false),
                    'tax_category_code' => $item->tax_category_code,
                    'tax_rate_snapshot' => $taxRate,
                    'tax_amount' => $taxAmt,
                    'line_total_excl_tax' => $lineExcl,
                    'line_total_incl_tax' => $lineIncl,
                    'is_warranty' => (bool) $item->is_warranty,
                ]);
            }

            // 3. Convert WO Parts to Invoice Lines
            foreach ($activeParts as $part) {
                if ($part->work_order_item_id !== null && ! in_array($part->work_order_item_id, $activeItemIds)) {
                    continue;
                }

                if ($part->hide_on_print) {
                    continue;
                }

                // Same defensive recompute as services — don't trust WO part totals.
                $qty = (float) $part->qty;
                $unitPrice = (float) $part->unit_price;
                $discountAmt = (float) ($part->discount ?? 0);
                $taxEnabled = (bool) ($workOrder->tax_enabled_snapshot ?? false);
                $taxRate = $taxEnabled ? (float) ($workOrder->tax_rate_snapshot ?? 15.00) : 0.0;
                $isInclusive = ($workOrder->pricing_mode_snapshot ?? 'exclusive') === 'inclusive';

                $net = max(0, ($qty * $unitPrice) - $discountAmt);

                if ($isInclusive && $taxEnabled) {
                    $lineIncl = round($net, 2);
                    $lineExcl = $taxRate > 0 ? round($net / (1 + ($taxRate / 100)), 2) : $net;
                    $taxAmt = round($lineIncl - $lineExcl, 2);
                } else {
                    $lineExcl = round($net, 2);
                    $taxAmt = $taxEnabled ? round($lineExcl * ($taxRate / 100), 2) : 0.0;
                    $lineIncl = round($lineExcl + $taxAmt, 2);
                }

                $invoice->lines()->create([
                    'is_part' => true,
                    'part_id' => $part->part_id,
                    'description' => $part->name,
                    'qty' => $qty,
                    'unit_price' => $unitPrice,
                    'discount_type' => null,
                    'discount_value' => null,
                    'discount_amount' => $discountAmt,
                    'is_taxable' => $taxEnabled,
                    'tax_category_code' => 'S', // Standard — parts don't have a category code on the WO side
                    'tax_rate_snapshot' => $taxRate,
                    'tax_amount' => $taxAmt,
                    'line_total_excl_tax' => $lineExcl,
                    'line_total_incl_tax' => $lineIncl,
                ]);
            }

            // 4. Link existing work order payments to the new invoice
            $workOrder->payments()->update(['invoice_id' => $invoice->id]);

            // 5. Recalculate invoice totals from actual line sums (not WO snapshot)
            // This prevents stale WorkOrder totals from propagating to the invoice.
            $invoice->refresh();
            $lines = $invoice->lines()->get();
            $sumExcl = $lines->sum('line_total_excl_tax');
            $sumIncl = $lines->sum('line_total_incl_tax');
            $sumTax = $lines->sum('tax_amount');

            $invoice->update([
                'total_excl_tax' => round($sumExcl, 2),
                'total_tax' => round($sumTax, 2),
                'total_incl_tax' => round($sumIncl, 2),
                'total_taxable_amount' => round($sumExcl, 2),
            ]);

            // 6. Recalculate and sync invoice payment status
            $invoice->refresh();
            $invoice->updatePaymentStatus();

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
            throw new Exception('Only draft invoices can be issued.');
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
        $workOrder->load([
            'customer',
            'items.service',
            'items.parts.part',
            'items.parts.warehouse',
            'parts.part',
            'parts.warehouse',
            'center',
            'vehicle.make',
            'vehicle.model',
            'tenant',
        ]);

        // Filter out cancelled items
        $filteredItems = $workOrder->items->filter(fn ($item) => $item->status !== WorkOrderItem::STATUS_CANCELLED);
        $workOrder->setRelation('items', $filteredItems->values());

        $activeItemIds = $filteredItems->pluck('id')->all();
        $activeParts = $workOrder->parts->filter(function ($part) use ($activeItemIds) {
            if (in_array($part->status, [WorkOrderItemPart::STATUS_CANCELLED, WorkOrderItemPart::STATUS_REVERSED])) {
                return false;
            }

            if ($part->work_order_item_id !== null && ! in_array($part->work_order_item_id, $activeItemIds)) {
                return false;
            }

            return true;
        })->values();

        $tenant = $workOrder->tenant;
        $taxSettings = $tenant->taxSettings;
        $template = InvoiceTemplate::getDefault($tenant->id, 'proforma');

        $servicesTotal = $filteredItems->sum(fn ($item) => $item->line_total ?? ($item->qty * $item->unit_price));
        $partsTotal = $activeParts->sum(fn ($part) => ((float) $part->unit_price * (float) $part->qty) - (float) ($part->discount ?? 0));
        $grandTotal = $servicesTotal + $partsTotal;

        return [
            'workOrder' => $workOrder,
            'work_order' => $workOrder,
            'customer' => $workOrder->customer,
            'vehicle' => $workOrder->vehicle,
            'items' => $workOrder->items,
            'allParts' => $activeParts,
            'servicesTotal' => $servicesTotal,
            'partsTotal' => $partsTotal,
            'grandTotal' => $grandTotal,
            'center' => $workOrder->center,
            'tenant' => $tenant,
            'taxSettings' => $taxSettings,
            'tax_settings' => $taxSettings,
            'template' => $template,
            'labels' => $template->getAllLabels(),
            'is_proforma' => true,
            'totals' => [
                'subtotal' => $grandTotal,
                'tax' => $workOrder->total_tax ?? 0,
                'total' => $workOrder->total_incl_tax ?? $grandTotal,
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
        $tlv .= $this->tlvEncode(4, number_format((float) $invoice->total_incl_tax, 2, '.', '')); // Total with VAT
        $tlv .= $this->tlvEncode(5, number_format((float) $invoice->total_tax, 2, '.', '')); // VAT Amount

        return base64_encode($tlv);
    }

    /**
     * TLV encoding helper
     */
    protected function tlvEncode(int $tag, string $value): string
    {
        return chr($tag).chr(strlen($value)).$value;
    }
}
