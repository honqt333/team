<?php

declare(strict_types=1);

namespace App\Services\Print;

use App\DTOs\Print\InvoicePrintDTO;
use App\Models\CenterAddress;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Payment;
use App\Models\TenantAddress;

class PrintDocumentDataBuilder
{
    public function __construct(
        private readonly ZatcaQrRenderer $qrRenderer,
    ) {}

    /**
     * Build a unified, snapshot-preserving print DTO for an Invoice
     * (work-order invoice, direct sales parts_invoice, or credit_note).
     * Never recalculates stored financial totals or overwrites frozen issuer snapshots.
     */
    public function buildFromInvoice(Invoice $invoice, ?string $locale = null): InvoicePrintDTO
    {
        $invoice->loadMissing([
            'customer',
            'originalInvoice:id,invoice_number,issue_date,total_incl_tax',
            'workOrder.vehicle.make',
            'workOrder.vehicle.model',
            'lines' => fn ($q) => $q->withoutGlobalScope('center_scoped'),
            'payments',
            'center.address',
            'tenant.address',
            'tenant.taxSettings',
            'companyTransaction',
        ]);

        $resolvedLocale = $locale ?: app()->getLocale();
        $isRtl = $resolvedLocale === 'ar';
        $direction = $isRtl ? 'rtl' : 'ltr';

        $documentType = $this->resolveInvoiceDocumentType($invoice);
        $isIssued = $invoice->status !== 'draft';
        $isCompany = ! $invoice->center_id;

        $tenant = $invoice->tenant;
        $center = $isCompany ? null : $invoice->center;

        $missingSnapshots = [];
        $seller = $this->resolveSellerIdentity($invoice, $tenant, $center, $isIssued, $isCompany, $isRtl, $missingSnapshots);

        $isVatEnabled = (bool) ($tenant?->taxSettings?->vat_enabled ?? true);
        $taxActive = (bool) ($invoice->tax_enabled_snapshot && $isVatEnabled);

        $qrMandatory = $isIssued
            && $taxActive
            && in_array($documentType, ['invoice', 'parts_invoice', 'credit_note'], true)
            && ! empty($invoice->zatca_qr_tlv);

        $qrDataUri = $taxActive && ! empty($invoice->zatca_qr_tlv)
            ? $this->qrRenderer->toDataUri($invoice->zatca_qr_tlv)
            : null;

        $items = $invoice->lines
            ->sortBy('id')
            ->values()
            ->map(static function (InvoiceLine $line): array {
                return [
                    'id' => $line->id,
                    'service_name' => $line->description ?? '',
                    'description' => $line->notes ?? '',
                    'qty' => (float) ($line->qty ?? 1),
                    'unit_price' => (float) ($line->unit_price ?? 0),
                    'discount' => (float) ($line->discount_amount ?? 0),
                    'is_part' => (bool) $line->is_part,
                    'is_warranty' => (bool) ($line->is_warranty ?? false),
                    'is_taxable' => $line->is_taxable !== false,
                    'tax_rate_snapshot' => $line->tax_rate_snapshot !== null ? (float) $line->tax_rate_snapshot : null,
                    'line_total_excl_tax' => (float) ($line->line_total_excl_tax ?? 0),
                    'line_total_incl_tax' => (float) ($line->line_total_incl_tax ?? 0),
                    'tax_amount' => (float) ($line->tax_amount ?? 0),
                ];
            })
            ->all();

        $badDebt = (float) $invoice->payments
            ->where('type', 'bad_debt')
            ->sum(static fn (Payment $p) => (float) ($p->amount ?? 0));

        $totalInclTax = (float) ($invoice->total_incl_tax ?? 0);
        $totalPaid = (float) ($invoice->total_paid ?? 0);
        $balance = $invoice->balance !== null
            ? (float) $invoice->balance
            : max(round($totalInclTax - $totalPaid - $badDebt, 2), 0.0);

        $totals = [
            'tax_enabled' => $taxActive,
            'pricing_mode' => $invoice->pricing_mode_snapshot ?? $invoice->workOrder?->pricing_mode_snapshot ?? 'exclusive',
            'tax_rate' => (float) ($invoice->tax_rate_snapshot ?? 0),
            'subtotal_excl_tax' => (float) ($invoice->total_excl_tax ?? 0),
            'total_tax' => (float) ($invoice->total_tax ?? 0),
            'grand_total_incl_tax' => $totalInclTax,
            'total_paid' => $totalPaid,
            'bad_debt' => $badDebt,
            'balance' => $balance,
        ];

        $payments = $invoice->payments
            ->values()
            ->map(static function (Payment $p): array {
                return [
                    'id' => $p->id,
                    'payment_date' => $p->payment_date?->toDateString(),
                    'payment_method' => $p->payment_method,
                    'reference' => $p->reference,
                    'amount' => (float) ($p->amount ?? 0),
                    'type' => $p->type !== null ? $p->type->value : 'payment',
                ];
            })
            ->all();

        $party = [
            'role' => 'customer',
            'name' => $invoice->customer_name_snapshot ?: ($invoice->customer?->name ?? ''),
            'phone' => $invoice->customer?->phone ?? '',
            'address' => $invoice->customer_address_snapshot ?: ($invoice->customer?->address_line ?? ''),
            'tax_number' => $invoice->customer_vat_snapshot ?: ($invoice->customer?->tax_number ?? ''),
        ];

        $vehicle = $this->resolveVehicle($invoice);

        $printSettings = is_array($tenant?->print_settings) ? $tenant->print_settings : [];

        return new InvoicePrintDTO(
            documentType: $documentType,
            locale: $resolvedLocale,
            direction: $direction,
            code: (string) ($invoice->invoice_number ?? ''),
            status: $invoice->status,
            subtype: $invoice->subtype,
            issueDate: $invoice->issue_date?->toDateString() ?? (is_string($invoice->issue_date) ? $invoice->issue_date : null),
            dueDate: $invoice->due_date?->toDateString() ?? (is_string($invoice->due_date) ? $invoice->due_date : null),
            supplyDate: $invoice->supply_date?->toDateString() ?? (is_string($invoice->supply_date) ? $invoice->supply_date : null),
            currencyCode: (string) ($invoice->currency_code ?: 'SAR'),
            seller: $seller,
            party: $party,
            vehicle: $vehicle,
            items: $items,
            totals: $totals,
            payments: $payments,
            qrDataUri: $qrDataUri,
            qrTlv: $taxActive ? $invoice->zatca_qr_tlv : null,
            qrMandatory: $qrMandatory,
            originalInvoiceNumber: $invoice->originalInvoice?->invoice_number,
            returnReason: $invoice->return_reason,
            notes: $invoice->notes,
            warrantyText: $tenant?->warranty_terms ?? null,
            termsAndConditions: $tenant?->invoice_terms ?? null,
            signatures: [
                'customer_signature' => $invoice->workOrder?->customer_signature ?? null,
                'delivery_signature' => $invoice->workOrder?->delivery_signature ?? null,
            ],
            settings: $printSettings,
            missingSnapshots: $missingSnapshots,
        );
    }

    private function resolveInvoiceDocumentType(Invoice $invoice): string
    {
        $type = $invoice->type instanceof \BackedEnum
            ? (string) $invoice->type->value
            : (string) ($invoice->type ?? '');

        if ($type === 'credit_note') {
            return 'credit_note';
        }

        if ($invoice->work_order_id || $invoice->companyTransaction) {
            return 'invoice';
        }

        return 'parts_invoice';
    }

    /**
     * Resolve seller identity preserving frozen snapshots on issued invoices.
     * Never falls back to platform identity ("Khidmah Pro").
     *
     * @param  array<int, string>  $missingSnapshots
     * @return array<string, mixed>
     */
    private function resolveSellerIdentity(
        Invoice $invoice,
        mixed $tenant,
        mixed $center,
        bool $isIssued,
        bool $isCompany,
        bool $isRtl,
        array &$missingSnapshots,
    ): array {
        $sellerNameSnap = $invoice->seller_name_snapshot;
        $sellerNameEnSnap = $invoice->seller_name_en_snapshot;
        $hasIssuerSnapshot = $sellerNameSnap !== null || $sellerNameEnSnap !== null;

        if ($isIssued && ! $hasIssuerSnapshot) {
            $missingSnapshots[] = 'seller_name_snapshot';
        }

        $fallbackAr = $isCompany
            ? ($tenant?->trade_name ?: ($tenant?->legal_name ?: ($tenant?->name ?? '')))
            : ($center?->name_ar ?: ($center?->name ?: ($tenant?->trade_name ?: ($tenant?->legal_name ?: ($tenant?->name ?? '')))));

        $fallbackEn = $isCompany
            ? ($tenant?->trade_name ?: ($tenant?->legal_name ?: ($tenant?->name ?? '')))
            : ($center?->name_en ?: ($center?->name ?: ($tenant?->trade_name ?: ($tenant?->legal_name ?: ($tenant?->name ?? '')))));

        if ($isIssued) {
            $nameAr = $sellerNameSnap ?? $fallbackAr;
            $nameEn = $sellerNameEnSnap ?? $sellerNameSnap ?? $fallbackEn;
            $name = $isRtl ? $nameAr : $nameEn;

            // Preserve explicit empty string on issued snapshots; only fall back if no issuer snapshot exists at all.
            $vatNumber = $hasIssuerSnapshot
                ? $invoice->seller_vat_snapshot
                : ($invoice->seller_vat_snapshot ?? $tenant?->taxSettings?->tax_number ?? $center?->vat_number ?? $tenant?->vat_number);

            if ($invoice->tax_enabled_snapshot && empty($vatNumber)) {
                $missingSnapshots[] = 'seller_vat_snapshot';
            }

            $crNumber = $hasIssuerSnapshot
                ? $invoice->seller_cr_snapshot
                : ($invoice->seller_cr_snapshot ?? $center?->cr_number ?? $tenant?->cr_number);
        } else {
            // Draft / unissued documents use current Center -> Tenant -> TaxSetting settings.
            $nameAr = $fallbackAr;
            $nameEn = $fallbackEn;
            $name = $isRtl ? $nameAr : $nameEn;
            $vatNumber = $tenant?->taxSettings?->tax_number ?? $center?->vat_number ?? $tenant?->vat_number;
            $crNumber = $center?->cr_number ?? $tenant?->cr_number;
        }

        $logoUrl = $isCompany
            ? ($tenant?->logo_url ?? '')
            : ($center?->logo_invoice_url ?: ($center?->logo_light_url ?: ($tenant?->logo_url ?? '')));

        $address = $isCompany
            ? ($this->formatAddress($tenant?->address) ?: $this->formatAddress($center?->address) ?: '')
            : ($this->formatAddress($center?->address) ?: $this->formatAddress($tenant?->address) ?: '');

        return [
            'name' => $name,
            'name_ar' => $nameAr,
            'name_en' => $nameEn,
            'vat_number' => $vatNumber,
            'cr_number' => $crNumber,
            'phone' => $center?->phone ?: ($tenant?->phone ?? ''),
            'iban' => $tenant?->iban ?? '',
            'address' => $address,
            'logo_url' => $logoUrl,
            'stamp_url' => $isCompany ? '' : ($center?->stamp_url ?? ''),
            'is_company' => $isCompany,
            'branch_name' => $isCompany ? null : ($isRtl ? ($center?->name_ar ?: $center?->name) : ($center?->name_en ?: $center?->name)),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveVehicle(Invoice $invoice): ?array
    {
        $wo = $invoice->workOrder;
        if (! $wo) {
            return null;
        }

        $vehicle = $wo->vehicle;
        $make = $vehicle?->make?->name_ar ?: ($vehicle?->make?->name_en ?: '');
        $model = $vehicle?->model?->name_ar ?: ($vehicle?->model?->name_en ?: '');
        $makeStr = trim($make.' '.$model) ?: '-';

        return [
            'make' => $makeStr,
            'plate' => $vehicle?->plate_number,
            'color' => $vehicle?->color,
            'mileage' => $wo->mileage,
            'fuel_level' => $wo->fuel_level,
            'customer_complaint' => $wo->customer_complaint,
        ];
    }

    private function formatAddress(CenterAddress|TenantAddress|string|null $address): ?string
    {
        if ($address === null || $address === '') {
            return null;
        }

        if (is_string($address)) {
            $trimmed = trim($address);
            if (str_starts_with($trimmed, '{')) {
                $decoded = json_decode($trimmed, true);
                if (is_array($decoded)) {
                    if (! empty($decoded['address_line'])) {
                        return (string) $decoded['address_line'];
                    }

                    $parts = array_filter([
                        ! empty($decoded['building_number']) ? (string) $decoded['building_number'] : null,
                        ! empty($decoded['street']) ? (string) $decoded['street'] : null,
                        ! empty($decoded['district']) ? (string) $decoded['district'] : null,
                        ! empty($decoded['city']) ? (string) $decoded['city'] : null,
                        ! empty($decoded['postal_code']) ? (string) $decoded['postal_code'] : null,
                    ]);

                    return $parts !== [] ? implode('، ', $parts) : null;
                }
            }

            return $trimmed;
        }

        if (! empty($address->address_line)) {
            return (string) $address->address_line;
        }

        $parts = array_filter([
            $address->building_number ? (string) $address->building_number : null,
            $address->street ? (string) $address->street : null,
            $address->district ? (string) $address->district : null,
            $address->city ? (string) $address->city : null,
            $address->postal_code ? (string) $address->postal_code : null,
        ]);

        return $parts !== [] ? implode('، ', $parts) : null;
    }
}
