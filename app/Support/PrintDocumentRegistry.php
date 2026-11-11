<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Single Source of Truth for tenant-customizable operational print documents.
 *
 * Only operational/transactional documents that are printed through PrintLayout + PrintEngine
 * (and therefore reflect per-document print_settings: template, custom title, terms,
 * signatures, stamp, IBAN, customer address) belong here.
 *
 * Internal pages and tabular reports (customers, vehicles, payroll, attendance, employees,
 * financial reports) intentionally follow global visual identity settings only
 * (show_logo, primary_color, footer_text, currency display) via StandardReportHeader/Footer.
 * Platform-to-tenant billing documents (e.g. subscription_invoice) belong to System billing,
 * not tenant print settings.
 */
final class PrintDocumentRegistry
{
    /**
     * Canonical operational document definitions keyed by document_type.
     *
     * @var array<string, array{
     *     name_ar: string,
     *     name_en: string,
     *     group: string,
     *     options: array<int, string>
     * }>
     */
    private const DOCUMENTS = [
        'invoice' => [
            'name_ar' => 'فاتورة',
            'name_en' => 'Invoice',
            'group' => 'sales',
            'options' => ['print_terms', 'terms_first_page', 'show_stamp', 'show_iban', 'show_customer_address'],
        ],
        'parts_invoice' => [
            'name_ar' => 'فاتورة قطع غيار',
            'name_en' => 'Spare Parts Invoice',
            'group' => 'sales',
            'options' => ['print_terms', 'terms_first_page', 'show_stamp', 'show_iban', 'show_customer_address'],
        ],
        'credit_note' => [
            'name_ar' => 'إشعار دائن',
            'name_en' => 'Credit Note',
            'group' => 'sales',
            'options' => ['print_terms', 'terms_first_page', 'show_stamp', 'show_iban', 'show_customer_address'],
        ],
        'proforma_invoice' => [
            'name_ar' => 'فاتورة أولية',
            'name_en' => 'Proforma Invoice',
            'group' => 'workshop',
            'options' => ['print_terms', 'terms_first_page', 'show_stamp', 'show_iban', 'show_customer_address'],
        ],
        'quotation' => [
            'name_ar' => 'عرض سعر',
            'name_en' => 'Quotation',
            'group' => 'workshop',
            'options' => ['print_terms', 'terms_first_page', 'show_stamp', 'show_iban', 'show_customer_address'],
        ],
        'work_order' => [
            'name_ar' => 'كرت صيانة',
            'name_en' => 'Work Order',
            'group' => 'workshop',
            'options' => ['print_terms', 'terms_first_page', 'show_stamp', 'show_iban', 'show_customer_address'],
        ],
        'condition_report' => [
            'name_ar' => 'تقرير حالة المركبة',
            'name_en' => 'Vehicle Condition Report',
            'group' => 'workshop',
            'options' => ['print_terms', 'terms_first_page', 'show_stamp', 'show_customer_address'],
        ],
        'payments' => [
            'name_ar' => 'سندات الدفع والمدفوعات',
            'name_en' => 'Payment Receipts',
            'group' => 'workshop',
            'options' => ['print_terms', 'terms_first_page', 'show_stamp', 'show_iban', 'show_customer_address'],
        ],
        'purchase_invoice' => [
            'name_ar' => 'فاتورة مشتريات',
            'name_en' => 'Purchase Invoice',
            'group' => 'purchasing',
            'options' => ['print_terms', 'terms_first_page', 'show_stamp', 'show_iban', 'show_customer_address'],
        ],
        'purchase_return' => [
            'name_ar' => 'فاتورة مرتجع مشتريات',
            'name_en' => 'Purchase Return Invoice',
            'group' => 'purchasing',
            'options' => ['print_terms', 'terms_first_page', 'show_stamp', 'show_iban', 'show_customer_address'],
        ],
    ];

    /**
     * Legacy keys mapped to their canonical document_type for backward compatibility.
     *
     * @var array<string, string>
     */
    private const LEGACY_ALIASES = [
        'quote' => 'quotation',
        'return_invoice' => 'credit_note',
        'receipt_voucher' => 'payments',
        'receipt' => 'payments',
    ];

    /**
     * Full metadata map keyed by canonical document_type.
     *
     * @return array<string, array{
     *     name_ar: string,
     *     name_en: string,
     *     group: string,
     *     options: array<int, string>
     * }>
     */
    public static function all(): array
    {
        return self::DOCUMENTS;
    }

    /**
     * Canonical operational document keys.
     *
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return array_keys(self::DOCUMENTS);
    }

    /**
     * Map of canonical key => Arabic default title.
     *
     * @return array<string, string>
     */
    public static function labels(): array
    {
        $labels = [];

        foreach (self::DOCUMENTS as $key => $meta) {
            $labels[$key] = $meta['name_ar'];
        }

        return $labels;
    }

    /**
     * Resolve legacy alias to canonical document_type when recognized.
     */
    public static function normalizeKey(?string $documentType): ?string
    {
        if ($documentType === null || $documentType === '') {
            return null;
        }

        return self::LEGACY_ALIASES[$documentType] ?? $documentType;
    }

    /**
     * Determine whether the key (or its alias) represents a supported operational document.
     */
    public static function isSupported(?string $documentType): bool
    {
        $normalized = self::normalizeKey($documentType);

        return $normalized !== null && array_key_exists($normalized, self::DOCUMENTS);
    }

    /**
     * Legacy keys that may still be sent by older clients and normalized on ingestion.
     *
     * @return array<string, string>
     */
    public static function legacyAliases(): array
    {
        return self::LEGACY_ALIASES;
    }

    /**
     * Allowed `document_type` values accepted by signature management endpoints.
     *
     * @return array<int, string>
     */
    public static function allowedSignatureDocumentTypes(): array
    {
        return array_values(array_unique(array_merge(
            self::keys(),
            array_keys(self::LEGACY_ALIASES),
        )));
    }
}
