<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\VehicleMake;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** @bypass-authorization-scanner - Protected at route middleware level (auth + center.context + EnsureTwoFactorEnabled) */
class SystemSettingsController extends Controller
{
    /**
     * Display the system settings page with side panel navigation.
     */
    public function index(Request $request): Response
    {
        $makes = VehicleMake::ordered()->get();

        return Inertia::render('Settings/System/Index', [
            'makes' => $makes,
            'activeSection' => $request->query('section', 'makes'),
        ]);
    }

    /**
     * Display the print settings page.
     */
    public function printSettings(): Response
    {
        $tenant = auth()->user()->tenant;
        $printSettings = $tenant->print_settings ?? [];

        // Comprehensive document types aligned with PrintService
        $documentTypes = [
            // App invoices
            'invoice' => 'فاتورة',
            'proforma_invoice' => 'فاتورة أولية',
            'credit_note' => 'إشعار دائن',
            'parts_invoice' => 'فاتورة قطع غيار',

            // Quotes
            'quotation' => 'عرض سعر',

            // Work orders
            'work_order' => 'كرت صيانة',
            'condition_report' => 'تقرير حالة المركبة',
            'payments' => 'سندات الدفع والمدفوعات',

            // Receipts
            'receipt' => 'سند قبض مالي',
            'delivery_note' => 'سند تسليم',

            // Purchasing
            'purchase_invoice' => 'فاتورة مشتريات',
            'purchase_return' => 'فاتورة مرتجع مشتريات',

            // Billing
            'subscription_invoice' => 'فاتورة اشتراك',

            // HR
            'payslip' => 'سند راتب',
            'payroll' => 'كشف رواتب',
            'attendance' => 'تقرير حضور',
            'employee' => 'ملف موظف',

            // Reports
            'customers' => 'قائمة عملاء',
            'vehicles' => 'قائمة مركبات',
        ];

        $documents = [];

        foreach ($documentTypes as $key => $name) {
            $existing = $printSettings['documents'][$key] ?? [];
            $documents[$key] = [
                'name' => $name,
                'title_ar' => $existing['title_ar'] ?? ($existing['title'] ?? $name),
                'title_en' => $existing['title_en'] ?? ($existing['title'] ?? $name),
                'template' => $existing['template'] ?? null,
                'terms' => is_array($existing['terms'] ?? null) ? array_values(array_filter($existing['terms'], fn ($t) => ! empty($t['text_ar']) || ! empty($t['text_en']))) : [],
                'print_terms' => $existing['print_terms'] ?? true,
                'terms_first_page' => $existing['terms_first_page'] ?? false,
                'show_stamp' => $existing['show_stamp'] ?? true,
                'show_qr_code' => $existing['show_qr_code'] ?? true,
                'show_iban' => $existing['show_iban'] ?? false,
                'show_customer_address' => $existing['show_customer_address'] ?? true,
                'signatures' => $existing['signatures'] ?? [],
                'updated_at' => $existing['updated_at'] ?? null,
                'updated_by' => $existing['updated_by'] ?? null,
            ];
        }

        return Inertia::render('Settings/Print/Index', [
            'print_settings' => [
                'documents' => $documents,
                'visual' => array_merge([
                    'active_template' => 'TemplateDefaultA4',
                    'show_logo' => true,
                    'primary_color' => '#fbbf24',
                    'footer_text' => '',
                    'stamp_url' => '',
                ], $printSettings['visual'] ?? []),
            ],
        ]);
    }

    /**
     * Update system settings (Tenant level).
     */
    public function update(Request $request)
    {
        $section = $request->input('section', 'general');
        $tenant = auth()->user()->tenant;

        switch ($section) {
            case 'general':
                $request->validate([
                    'settings' => 'required|array',
                    'settings.sms_2fa_enabled' => 'boolean',
                ]);

                if (isset($request->settings['sms_2fa_enabled'])) {
                    $tenant->update(['sms_2fa_enabled' => $request->settings['sms_2fa_enabled']]);
                }
                break;

            case 'print':
                $validated = $request->validate([
                    'documents' => 'required|array',
                    'visual' => 'required|array',
                    'visual.active_template' => 'nullable|string',
                    'visual.stamp_url' => 'nullable|string',
                    'visual.show_logo' => 'nullable|boolean',
                    'visual.primary_color' => 'nullable|string|max:7',
                    'visual.footer_text' => 'nullable|string',
                ]);

                // Add timestamps and user info to changed documents
                $currentSettings = $tenant->print_settings ?? [];
                $newDocuments = $validated['documents'];

                foreach ($newDocuments as $key => $doc) {
                    $oldDoc = $currentSettings['documents'][$key] ?? null;

                    // Simplified check: if any boolean or string field changed
                    if (json_encode($doc) !== json_encode($oldDoc)) {
                        $newDocuments[$key]['updated_at'] = now()->format('Y-m-d H:i:s');
                        $newDocuments[$key]['updated_by'] = auth()->user()->name;
                    }
                }

                $tenant->update([
                    'print_settings' => [
                        'documents' => $newDocuments,
                        'visual' => $validated['visual'],
                    ],
                    // Keep legacy columns in sync for now if they exist
                    'quote_title' => $newDocuments['quotation']['title_ar'] ?? ($newDocuments['quotation']['title'] ?? $tenant->quote_title),
                    'work_order_title' => $newDocuments['work_order']['title_ar'] ?? ($newDocuments['work_order']['title'] ?? $tenant->work_order_title),
                    'invoice_title' => $newDocuments['invoice']['title_ar'] ?? ($newDocuments['invoice']['title'] ?? $tenant->invoice_title),
                    'quote_terms' => is_array($newDocuments['quotation']['terms'] ?? null) ? ($newDocuments['quotation']['terms'][0]['text_ar'] ?? '') : ($newDocuments['quotation']['terms'] ?? $tenant->quote_terms),
                    'work_order_terms' => is_array($newDocuments['work_order']['terms'] ?? null) ? ($newDocuments['work_order']['terms'][0]['text_ar'] ?? '') : ($newDocuments['work_order']['terms'] ?? $tenant->work_order_terms),
                    'invoice_terms' => is_array($newDocuments['invoice']['terms'] ?? null) ? ($newDocuments['invoice']['terms'][0]['text_ar'] ?? '') : ($newDocuments['invoice']['terms'] ?? $tenant->invoice_terms),
                ]);
                break;
        }

        return back()->with('success', 'تم حفظ الإعدادات بنجاح');
    }
}
