<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CenterSequence;
use App\Models\Customer;
use App\Models\InventoryBalance;
use App\Models\InventoryMove;
use App\Models\Invoice;
use App\Models\InvoiceTemplate;
use App\Models\Part;
use App\Models\Tenant;
use App\Models\TenantTaxSetting;
use App\Models\Warehouse;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Models\WorkOrderItemPart;
use App\Services\Inventory\InventoryService;
use App\Services\Optimization\TaxCalculator;
use App\Support\CountryCurrencyRegistry;
use DateTimeInterface;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
            // Lock the WorkOrder row first to serialize concurrent invoice creation
            // attempts (manual, status-completion, or payment-triggered auto-invoicing).
            $workOrder = WorkOrder::withoutGlobalScopes()
                ->where('tenant_id', $workOrder->tenant_id)
                ->whereKey($workOrder->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Re-check inside the lock so stale WorkOrder instances or concurrent
            // transactions cannot create duplicate work-order invoices.
            $existingInvoiceExists = Invoice::withoutGlobalScopes()
                ->where('tenant_id', $workOrder->tenant_id)
                ->where('work_order_id', $workOrder->id)
                ->where('type', 'invoice')
                ->where('status', '!=', 'cancelled')
                ->whereNull('deleted_at')
                ->exists();

            if ($existingInvoiceExists) {
                throw new Exception(__('invoices.already_exists'));
            }

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
            $dealer = ($workOrder->is_paid_by_dealer && $workOrder->dealer_id) ? $workOrder->dealer : null;
            $billedParty = $dealer ?? $customer;
            $customerAddress = null;

            if ($billedParty) {
                $addressParts = array_filter([
                    $billedParty->building_number ? __('common.building').' '.$billedParty->building_number : null,
                    $billedParty->address_line ?: null,
                    $billedParty->district ? __('common.district').' '.$billedParty->district : null,
                    $billedParty->city ?: null,
                    $billedParty->postal_code ? __('common.postal_code').' '.$billedParty->postal_code : null,
                ]);
                $customerAddress = ! empty($addressParts) ? implode('، ', $addressParts) : null;
            }

            // 1. Create Invoice Draft (No Number yet)
            $invoice = Invoice::create([
                'tenant_id' => $workOrder->tenant_id,
                'center_id' => $workOrder->center_id,
                'customer_id' => $workOrder->customer_id,
                'dealer_id' => $workOrder->dealer_id,
                'is_paid_by_dealer' => (bool) ($workOrder->dealer_id && $workOrder->is_paid_by_dealer),
                'work_order_id' => $workOrder->id,
                'invoice_number' => 'DRAFT-'.$workOrder->code, // Temporary
                'issue_date' => now(),
                'supply_date' => now(),
                'due_date' => $additionalData['due_date'] ?? null,
                'notes' => $additionalData['notes'] ?? null,
                'status' => 'draft',
                'payment_status' => 'unpaid',
                // Snapshots (bills Dealer when is_paid_by_dealer is true)
                'customer_name_snapshot' => $billedParty?->name,
                'customer_vat_snapshot' => $billedParty?->tax_number,
                'customer_address_snapshot' => $customerAddress,

                // Tax settings
                'tax_enabled_snapshot' => $workOrder->tax_enabled_snapshot,
                'pricing_mode_snapshot' => $workOrder->pricing_mode_snapshot,
                'tax_rate_snapshot' => $workOrder->tax_rate_snapshot,
                'currency_code' => $workOrder->currency_code ?: CountryCurrencyRegistry::tenantCurrency($workOrder->tenant),

                // Totals
                'total_excl_tax' => $workOrder->total_excl_tax,
                'total_tax' => $workOrder->total_tax,
                'total_incl_tax' => $workOrder->total_incl_tax,
                'total_taxable_amount' => $workOrder->total_excl_tax,
                'total_paid' => 0,
            ]);

            $taxEnabled = (bool) ($workOrder->tax_enabled_snapshot ?? false);
            $isInclusive = ($workOrder->pricing_mode_snapshot ?? 'exclusive') === 'inclusive';
            $defaultVat = CountryCurrencyRegistry::defaultVatRate(CountryCurrencyRegistry::tenantCountry($workOrder->tenant));
            $defaultTaxRate = $taxEnabled ? (float) ($workOrder->tax_rate_snapshot ?? $defaultVat) : 0.0;

            $calculatedLines = [];
            $lineMeta = [];

            // 2. Convert WO Items (Services) to TaxCalculator lines
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

                $qty = (float) $item->qty;
                $unitPrice = (float) $item->unit_price;
                $discountAmt = (float) ($item->discount_amount ?? 0);
                $itemTaxable = $taxEnabled && ($item->is_taxable !== false);
                $taxRate = $itemTaxable ? (float) ($item->tax_rate_snapshot ?? $defaultTaxRate) : 0.0;
                $taxCategory = $item->tax_category_code ?: ($taxEnabled ? ($taxRate > 0 ? 'S' : 'Z') : 'O');

                $lineCalc = $this->taxCalculator->calculateLine(
                    unitPrice: $unitPrice,
                    qty: $qty,
                    discount: $discountAmt,
                    taxRate: $taxRate,
                    isInclusive: $isInclusive,
                );
                $lineCalc['tax_category_code'] = $taxCategory;

                $calculatedLines[] = $lineCalc;
                $lineMeta[] = [
                    'is_part' => false,
                    'part_id' => null,
                    'description' => $description,
                    'qty' => $qty,
                    'unit_price' => $unitPrice,
                    'discount_type' => $item->discount_type,
                    'discount_value' => $item->discount_value,
                    'discount_amount' => $discountAmt,
                    'is_taxable' => $itemTaxable,
                    'tax_category_code' => $taxCategory,
                    'tax_rate_snapshot' => $taxRate,
                    'is_warranty' => (bool) $item->is_warranty,
                ];
            }

            // 3. Convert WO Parts to TaxCalculator lines
            foreach ($activeParts as $part) {
                if ($part->work_order_item_id !== null && ! in_array($part->work_order_item_id, $activeItemIds)) {
                    continue;
                }

                if ($part->hide_on_print) {
                    continue;
                }

                $qty = (float) $part->qty;
                $unitPrice = (float) $part->unit_price;
                $discountAmt = (float) ($part->discount ?? 0);
                $taxRate = $defaultTaxRate;
                $taxCategory = $taxEnabled ? ($taxRate > 0 ? 'S' : 'Z') : 'O';

                $lineCalc = $this->taxCalculator->calculateLine(
                    unitPrice: $unitPrice,
                    qty: $qty,
                    discount: $discountAmt,
                    taxRate: $taxRate,
                    isInclusive: $isInclusive,
                );
                $lineCalc['tax_category_code'] = $taxCategory;

                $calculatedLines[] = $lineCalc;
                $lineMeta[] = [
                    'is_part' => true,
                    'part_id' => $part->part_id,
                    'description' => $part->name,
                    'qty' => $qty,
                    'unit_price' => $unitPrice,
                    'discount_type' => null,
                    'discount_value' => null,
                    'discount_amount' => $discountAmt,
                    'is_taxable' => $taxEnabled,
                    'tax_category_code' => $taxCategory,
                    'tax_rate_snapshot' => $taxRate,
                    'is_warranty' => false,
                ];
            }

            // 4. Reconcile document-level tax (penny rounding) and persist lines
            $documentTax = ! empty($calculatedLines)
                ? $this->taxCalculator->calculateDocument($calculatedLines)
                : [
                    'total_excl_tax' => 0.0,
                    'total_tax' => 0.0,
                    'total_incl_tax' => 0.0,
                    'total_taxable_amount' => 0.0,
                    'reconciled_lines' => [],
                ];

            foreach ($documentTax['reconciled_lines'] as $idx => $reconciledLine) {
                $meta = $lineMeta[$idx];

                $invoice->lines()->create([
                    'is_part' => $meta['is_part'],
                    'part_id' => $meta['part_id'],
                    'description' => $meta['description'],
                    'qty' => $meta['qty'],
                    'unit_price' => $meta['unit_price'],
                    'discount_type' => $meta['discount_type'],
                    'discount_value' => $meta['discount_value'],
                    'discount_amount' => $meta['discount_amount'],
                    'is_taxable' => $meta['is_taxable'],
                    'tax_category_code' => $meta['tax_category_code'],
                    'tax_rate_snapshot' => $meta['tax_rate_snapshot'],
                    'tax_amount' => round((float) $reconciledLine['tax_amount'], 2),
                    'line_total_excl_tax' => round((float) $reconciledLine['line_total_excl_tax'], 2),
                    'line_total_incl_tax' => round((float) $reconciledLine['line_total_incl_tax'], 2),
                    'is_warranty' => $meta['is_warranty'],
                ]);
            }

            // 5. Link existing work order payments to the new invoice
            $workOrder->payments()->update(['invoice_id' => $invoice->id]);

            // 6. Stamp document totals from TaxCalculator::calculateDocument()
            $invoice->forceFill([
                'total_excl_tax' => $documentTax['total_excl_tax'],
                'total_tax' => $documentTax['total_tax'],
                'total_incl_tax' => $documentTax['total_incl_tax'],
                'total_taxable_amount' => $documentTax['total_taxable_amount'],
            ])->save();

            // 6. Recalculate and sync invoice payment status
            $invoice->refresh();
            $invoice->updatePaymentStatus();

            return $invoice;
        });
    }

    /**
     * Issue an invoice (Transition to Valid).
     * assign sequential number and ICV.
     *
     * The issue date is the moment the document is issued, and it is
     * fixed from that moment on — the invoice immutability guard
     * rejects any later attempt to move it.
     *
     * A caller that issues a document ON BEHALF OF an earlier business
     * event (a financial transaction approved with a back-dated
     * `approved_at`, for example) passes that date in, so the document
     * carries the business date it was approved on rather than the
     * wall-clock moment the approval happened to be processed.
     *
     * @param Carbon|string|null $issueDate the business date to stamp; defaults to now
     */
    public function issueInvoice(Invoice $invoice, $issueDate = null): Invoice
    {
        $issuedAt = $issueDate instanceof DateTimeInterface
            ? Carbon::parse($issueDate)
            : ($issueDate ? Carbon::parse($issueDate) : Carbon::now());

        return DB::transaction(function () use ($invoice, $issuedAt) {
            $locked = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);

            if ($locked->status !== 'draft') {
                throw new Exception(__('invoices.only_draft_can_be_issued'));
            }
            $invoice->setRawAttributes($locked->getAttributes(), true);
            $tenantId = $invoice->tenant_id;
            $centerId = $invoice->center_id;

            // 1. Generate Sequential Number
            $year = now()->year;
            $seq = CenterSequence::getNextValue($tenantId, $centerId, 'invoice', $year);
            $tenant = Tenant::find($tenantId);
            $invoiceNumber = $tenant
                ? $tenant->formatDocumentCode('invoice', $centerId, $seq, $year)
                : sprintf('INV-%s-%s-%06d', $centerId, $year, $seq);

            // 2. Generate ICV (Invoice Counter Value) for ZATCA
            // Must be strictly increasing per center (no gaps allowed in the chain)
            $icv = CenterSequence::getNextValue($tenantId, $centerId, 'icv');

            // 3. Update Invoice
            $invoice->forceFill([
                'status' => 'valid',
                'invoice_number' => $invoiceNumber,
                'issue_date' => $issuedAt,
                'currency_code' => $invoice->currency_code ?: CountryCurrencyRegistry::tenantCurrency($tenant),
                // 'icv' => $icv, // Add column if needed in schema, currently storing in ZATCA payload
            ])->save();

            // 4. Generate ZATCA Phase 1 QR TLV strictly when VAT is enabled and tenant country is SA
            if ($invoice->tax_enabled_snapshot && CountryCurrencyRegistry::isZatcaApplicable($tenant?->country)) {
                $invoice->update([
                    'zatca_qr_tlv' => $this->generateZatcaQr($invoice),
                ]);
            }

            $invoice->logActivity('issued');

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
        $invoice->loadMissing('tenant.taxSettings');
        $tenant = $invoice->tenant;

        $sellerName = (string) ($invoice->seller_name_snapshot ?: ($tenant?->legal_name ?: ($tenant?->trade_name ?: ($tenant?->name ?? ''))));
        $vatNumber = (string) ($invoice->seller_vat_snapshot ?: ($tenant?->taxSettings?->tax_number ?? $tenant?->vat_number ?? ''));
        $timestamp = ($invoice->issue_date ?? $invoice->created_at ?? now())->toIso8601String();

        // TLV encoding for ZATCA
        $tlv = '';
        $tlv .= $this->tlvEncode(1, $sellerName); // Seller Name
        $tlv .= $this->tlvEncode(2, $vatNumber); // VAT Number
        $tlv .= $this->tlvEncode(3, $timestamp); // Timestamp
        $tlv .= $this->tlvEncode(4, number_format((float) $invoice->total_incl_tax, 2, '.', '')); // Total with VAT
        $tlv .= $this->tlvEncode(5, number_format((float) $invoice->total_tax, 2, '.', '')); // VAT Amount

        return base64_encode($tlv);
    }

    /**
     * Create and issue a direct sales invoice (without a Work Order).
     * All prices, discounts, taxes, totals, sequential numbering, inventory
     * deductions, and payments are computed and enforced server-side.
     */
    public function createDirectSalesInvoice(
        int $tenantId,
        int $centerId,
        int $userId,
        array $validated,
        bool $allowNegative = false,
        bool $allowCustomPricing = false
    ): Invoice {
        return DB::transaction(function () use ($tenantId, $centerId, $userId, $validated, $allowNegative, $allowCustomPricing) {
            $tenant = Tenant::with('taxSettings')->findOrFail($tenantId);
            $customer = Customer::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->findOrFail($validated['customer_id']);

            $taxSetting = TenantTaxSetting::where('tenant_id', $tenantId)->first();
            $taxEnabled = (bool) ($taxSetting?->vat_enabled ?? true);
            $partsInclusive = (bool) ($taxSetting?->parts_inclusive ?? false);
            $taxRate = $taxEnabled
                ? (float) ($taxSetting?->parts_vat_rate ?? $taxSetting?->vat_rate ?? CountryCurrencyRegistry::defaultVatRate($tenant->country))
                : 0.0;
            $currencyCode = $taxSetting?->currency_code ?: CountryCurrencyRegistry::tenantCurrency($tenant);
            $pricingMode = $partsInclusive ? 'inclusive' : 'exclusive';

            $calculatedLines = [];
            $lineMeta = [];

            foreach ($validated['items'] as $index => $item) {
                $source = $item['source'] ?? 'warehouse';
                $partId = $item['part_id'] ?? null;
                $warehouseId = $item['warehouse_id'] ?? null;
                $qty = round((float) $item['qty'], 2);

                $part = null;
                $balance = null;

                if ($partId) {
                    $part = Part::withoutGlobalScopes()
                        ->where('tenant_id', $tenantId)
                        ->where('is_active', true)
                        ->findOrFail($partId);

                    if ($source === 'warehouse') {
                        if ($warehouseId) {
                            $warehouse = Warehouse::withoutGlobalScopes()
                                ->where('tenant_id', $tenantId)
                                ->where('center_id', $centerId)
                                ->where('is_active', true)
                                ->findOrFail($warehouseId);
                            $warehouseId = $warehouse->id;
                        } else {
                            $warehouseId = Warehouse::withoutGlobalScopes()
                                ->where('tenant_id', $tenantId)
                                ->where('center_id', $centerId)
                                ->where('is_active', true)
                                ->where('is_default', true)
                                ->value('id')
                                ?? Warehouse::withoutGlobalScopes()
                                    ->where('tenant_id', $tenantId)
                                    ->where('center_id', $centerId)
                                    ->where('is_active', true)
                                    ->value('id');
                        }

                        if ($warehouseId) {
                            $balance = InventoryBalance::query()
                                ->withoutGlobalScopes()
                                ->where('warehouse_id', $warehouseId)
                                ->where('part_id', $part->id)
                                ->lockForUpdate()
                                ->first();

                            if (! $allowNegative) {
                                $onHand = (float) ($balance?->qty_on_hand ?? 0);

                                if ($onHand < $qty) {
                                    throw ValidationException::withMessages([
                                        'items' => [
                                            __('inventory.stock.insufficient_warning_with_qty', [
                                                'available' => $onHand,
                                                'requested' => $qty,
                                            ]).' ('.($item['name'] ?? $part->name_ar).')',
                                        ],
                                    ]);
                                }
                            }
                        }
                    }
                }

                // Authoritative price & discount enforcement
                $catalogPrice = $part
                    ? (($balance && (float) $balance->sale_price > 0) ? (float) $balance->sale_price : (float) ($part->default_sale_price ?? 0))
                    : max(0.0, (float) ($item['unit_price'] ?? 0));

                $minSalePrice = $part
                    ? max((float) ($balance?->min_sale_price ?? 0), (float) ($part->min_sale_price ?? 0))
                    : 0.0;

                $requestedUnitPrice = isset($item['unit_price']) ? (float) $item['unit_price'] : $catalogPrice;

                if (! $part && ($source === 'external' || $requestedUnitPrice > 0) && ! $allowCustomPricing) {
                    throw new AuthorizationException(
                        'ليس لديك صلاحية تسعير البنود الخارجية أو غير المرتبطة بكتالوج القطع.'
                    );
                }

                $effectiveFloor = $minSalePrice > 0 ? $minSalePrice : $catalogPrice;

                if ($part && $effectiveFloor > 0 && ($requestedUnitPrice + 0.001) < $effectiveFloor) {
                    throw ValidationException::withMessages([
                        "items.{$index}.unit_price" => [
                            'السعر المدخل أقل من الحد الأدنى المعتمد للقطعة ('.number_format($effectiveFloor, 2, '.', '').').',
                        ],
                    ]);
                }

                $unitPrice = $part ? $requestedUnitPrice : max(0.0, $requestedUnitPrice);
                $grossTotal = round($unitPrice * $qty, 2);

                $discountType = $item['discount_type'] ?? null;
                $discountValue = (float) ($item['discount_value'] ?? 0);

                if ($discountType === 'percentage') {
                    $pct = max(0.0, min(100.0, $discountValue));
                    $lineDiscount = round($grossTotal * ($pct / 100), 2);
                } elseif ($discountType === 'fixed') {
                    $lineDiscount = round(max(0.0, $discountValue) * $qty, 2);
                } else {
                    $lineDiscount = round(max(0.0, (float) ($item['discount'] ?? 0)), 2);
                }

                $lineDiscount = min($lineDiscount, $grossTotal);

                if ($part && $effectiveFloor > 0 && $qty > 0) {
                    $effectiveUnitAfterDiscount = ($grossTotal - $lineDiscount) / $qty;

                    if (($effectiveUnitAfterDiscount + 0.001) < $effectiveFloor) {
                        throw ValidationException::withMessages([
                            "items.{$index}.discount" => [
                                'الخصم يخفض صافي سعر الوحدة عن الحد الأدنى المعتمد ('.number_format($effectiveFloor, 2, '.', '').').',
                            ],
                        ]);
                    }
                }

                $lineCalc = $this->taxCalculator->calculateLine(
                    unitPrice: $unitPrice,
                    qty: $qty,
                    discount: $lineDiscount,
                    taxRate: $taxRate,
                    isInclusive: $partsInclusive,
                );
                $lineCalc['tax_category_code'] = $taxEnabled ? ($taxRate > 0 ? 'S' : 'Z') : 'O';

                $calculatedLines[] = $lineCalc;
                $lineMeta[] = [
                    'part' => $part,
                    'part_id' => $part?->id,
                    'warehouse_id' => $warehouseId,
                    'source' => $source,
                    'description' => $item['name'] ?? ($part?->name_ar ?: $part?->name_en ?: ''),
                    'qty' => $qty,
                    'unit_price' => $unitPrice,
                    'discount_type' => $discountType ?? 'fixed',
                    'discount_value' => $discountType ? $discountValue : $lineDiscount,
                    'discount_amount' => $lineDiscount,
                ];
            }

            $documentTax = $this->taxCalculator->calculateDocument($calculatedLines);

            $year = now()->year;
            $seq = CenterSequence::getNextValue($tenantId, $centerId, 'invoice', $year);
            $invoiceNumber = $tenant->formatDocumentCode('invoice', $centerId, $seq, $year);
            CenterSequence::getNextValue($tenantId, $centerId, 'icv');

            $addressParts = array_filter([
                $customer->building_number ? __('common.building').' '.$customer->building_number : null,
                $customer->address_line ?: null,
                $customer->district ? __('common.district').' '.$customer->district : null,
                $customer->city ?: null,
                $customer->postal_code ? __('common.postal_code').' '.$customer->postal_code : null,
            ]);
            $customerAddress = ! empty($addressParts) ? implode('، ', $addressParts) : null;

            $sellerNameAr = $tenant->legal_name ?: ($tenant->trade_name ?: $tenant->name);
            $sellerNameEn = $tenant->legal_name_en ?: $sellerNameAr;
            $sellerVat = $taxSetting?->tax_number ?? $tenant->vat_number;
            $sellerCr = $tenant->cr_number;

            $issueDate = Carbon::parse($validated['issue_date']);

            $invoice = Invoice::create([
                'tenant_id' => $tenantId,
                'center_id' => $centerId,
                'customer_id' => $customer->id,
                'work_order_id' => null,
                'invoice_number' => $invoiceNumber,
                'issue_date' => $issueDate,
                'supply_date' => $issueDate,
                'notes' => $validated['notes'] ?? null,
                'type' => 'invoice',
                'status' => 'valid',
                'payment_status' => 'unpaid',
                'customer_name_snapshot' => $customer->name,
                'customer_vat_snapshot' => $customer->tax_number,
                'customer_address_snapshot' => $customerAddress,
                'seller_name_snapshot' => $sellerNameAr,
                'seller_name_en_snapshot' => $sellerNameEn,
                'seller_vat_snapshot' => $sellerVat,
                'seller_cr_snapshot' => $sellerCr,
                'tax_enabled_snapshot' => $taxEnabled,
                'pricing_mode_snapshot' => $pricingMode,
                'tax_rate_snapshot' => $taxRate,
                'currency_code' => $currencyCode,
                'total_excl_tax' => $documentTax['total_excl_tax'],
                'total_tax' => $documentTax['total_tax'],
                'total_incl_tax' => $documentTax['total_incl_tax'],
                'total_taxable_amount' => $documentTax['total_taxable_amount'],
                'total_paid' => 0,
            ]);

            if ($taxEnabled && CountryCurrencyRegistry::isZatcaApplicable($tenant->country)) {
                $invoice->forceFill([
                    'zatca_qr_tlv' => $this->generateZatcaQr($invoice),
                ])->save();
            }

            $inventoryService = app(InventoryService::class);

            foreach ($documentTax['reconciled_lines'] as $idx => $reconciledLine) {
                $meta = $lineMeta[$idx];

                $invoice->lines()->create([
                    'tenant_id' => $tenantId,
                    'center_id' => $centerId,
                    'is_part' => true,
                    'part_id' => $meta['part_id'],
                    'description' => $meta['description'],
                    'qty' => $meta['qty'],
                    'unit_price' => $meta['unit_price'],
                    'discount_type' => $meta['discount_type'],
                    'discount_value' => $meta['discount_value'],
                    'discount_amount' => $meta['discount_amount'],
                    'is_taxable' => $taxEnabled,
                    'tax_category_code' => $reconciledLine['tax_category_code'],
                    'tax_rate_snapshot' => $taxRate,
                    'tax_amount' => round((float) $reconciledLine['tax_amount'], 2),
                    'line_total_excl_tax' => round((float) $reconciledLine['line_total_excl_tax'], 2),
                    'line_total_incl_tax' => round((float) $reconciledLine['line_total_incl_tax'], 2),
                ]);

                if ($meta['source'] === 'warehouse' && $meta['part_id'] && $meta['warehouse_id']) {
                    $inventoryService->issue(
                        warehouseId: (int) $meta['warehouse_id'],
                        partId: (int) $meta['part_id'],
                        qty: (float) $meta['qty'],
                        userId: $userId,
                        referenceType: Invoice::class,
                        referenceId: (int) $invoice->id,
                        notes: 'بيع مباشر - فاتورة مبيعات #'.$invoiceNumber,
                        allowNegative: $allowNegative,
                        moveType: InventoryMove::TYPE_SALE
                    );
                }
            }

            if (! empty($validated['payments'])) {
                $paymentService = app(PaymentService::class);

                foreach ($validated['payments'] as $paymentData) {
                    $type = $paymentData['type'] ?? 'payment';

                    if ($type === 'refund') {
                        throw ValidationException::withMessages([
                            'payments' => ['لا يمكن تسجيل مبلغ مسترد عند إنشاء فاتورة مبيعات جديدة قبل التحصيل.'],
                        ]);
                    }

                    $paymentService->recordPayment($invoice, [
                        'amount' => $paymentData['amount'],
                        'payment_method' => $paymentData['payment_method'],
                        'payment_date' => $paymentData['payment_date'],
                        'reference' => $paymentData['reference'] ?? null,
                        'notes' => $paymentData['notes'] ?? null,
                        'type' => 'payment',
                    ]);
                }
            }

            $invoice->refresh();
            $invoice->updatePaymentStatus();

            return $invoice;
        });
    }

    /**
     * TLV encoding helper
     */
    protected function tlvEncode(int $tag, string $value): string
    {
        return chr($tag).chr(strlen($value)).$value;
    }
}
