<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\VehicleMake;
use App\Services\Storage\TenantAttachmentsService;
use App\Support\Permissions;
use App\Support\PrintDocumentRegistry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** @bypass-authorization-scanner - Protected at route middleware level (auth + center.context + EnsureTwoFactorEnabled) */
class SystemSettingsController extends Controller
{
    /**
     * Display the system settings page with side panel navigation.
     */
    public function index(Request $request, TenantAttachmentsService $attachmentsService): Response
    {
        $activeSection = (string) $request->query('section', 'makes');

        if ($activeSection === 'storage') {
            return $this->storage($request, $attachmentsService);
        }

        $makes = VehicleMake::ordered()->get();

        return Inertia::render('Settings/System/Index', [
            'makes' => $makes,
            'activeSection' => $activeSection,
            'storage_usage' => null,
        ]);
    }

    /**
     * Display the cloud storage allocation & usage section inside system settings.
     */
    public function storage(Request $request, TenantAttachmentsService $attachmentsService): Response
    {
        $user = $request->user();
        $canViewAttachments = (bool) $user?->can(Permissions::ATTACHMENTS_VIEW);
        $filters = $request->only(['source', 'search']);

        $attachmentsPayload = $canViewAttachments && $user !== null
            ? $attachmentsService->paginateForUser(
                user: $user,
                filters: $filters,
                perPage: 15,
                page: max(1, (int) $request->query('page', 1)),
                path: route('settings.storage')
            )
            : null;

        return Inertia::render('Settings/System/Index', [
            'makes' => [],
            'activeSection' => 'storage',
            'storage_usage' => $user?->tenant?->storage_usage,
            'attachments' => $attachmentsPayload ? $attachmentsPayload['items']->toArray() : null,
            'attachmentSummary' => $attachmentsPayload ? $attachmentsPayload['summary'] : null,
            'attachmentFilters' => [
                'source' => (string) ($filters['source'] ?? ''),
                'search' => (string) ($filters['search'] ?? ''),
            ],
            'canViewAttachments' => $canViewAttachments,
            'canDeleteAttachments' => (bool) $user?->can(Permissions::ATTACHMENTS_DELETE),
        ]);
    }

    /**
     * Display the print settings page.
     */
    public function printSettings(): Response
    {
        $tenant = auth()->user()->tenant;
        $printSettings = $tenant->print_settings ?? [];
        $storedDocs = is_array($printSettings['documents'] ?? null) ? $printSettings['documents'] : [];
        $legacyAliases = PrintDocumentRegistry::legacyAliases();

        $documents = [];

        foreach (PrintDocumentRegistry::all() as $key => $meta) {
            $existing = $storedDocs[$key] ?? [];

            if (empty($existing)) {
                foreach ($legacyAliases as $alias => $target) {
                    if ($target === $key && ! empty($storedDocs[$alias])) {
                        $existing = $storedDocs[$alias];
                        break;
                    }
                }
            }

            $documents[$key] = [
                'name' => $meta['name_ar'],
                'title_ar' => $existing['title_ar'] ?? ($existing['title'] ?? $meta['name_ar']),
                'title_en' => $existing['title_en'] ?? ($existing['title'] ?? $meta['name_en']),
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
            'document_meta' => PrintDocumentRegistry::all(),
            'print_settings' => [
                'documents' => $documents,
                'visual' => array_merge([
                    'active_template' => 'TemplateDefaultA4',
                    'show_logo' => true,
                    'primary_color' => '#fbbf24',
                    'footer_text' => '',
                    'stamp_url' => '',
                    'show_currency_symbol' => true,
                    'currency_display_style' => 'text',
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
                    'visual.show_currency_symbol' => 'nullable|boolean',
                    'visual.currency_display_style' => 'nullable|in:symbol,text',
                ]);

                // Add timestamps and user info to changed documents; strip non-operational / phantom keys
                $currentSettings = $tenant->print_settings ?? [];
                $newDocuments = [];

                foreach ($validated['documents'] as $rawKey => $doc) {
                    $canonicalKey = PrintDocumentRegistry::normalizeKey((string) $rawKey);

                    if ($canonicalKey === null || ! PrintDocumentRegistry::isSupported($canonicalKey) || ! is_array($doc)) {
                        continue;
                    }

                    $oldDoc = $currentSettings['documents'][$canonicalKey] ?? null;

                    if (json_encode($doc) !== json_encode($oldDoc)) {
                        $doc['updated_at'] = now()->format('Y-m-d H:i:s');
                        $doc['updated_by'] = auth()->user()->name;
                    }

                    $newDocuments[$canonicalKey] = $doc;
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

        return back()->with('success', __('messages.system.settings.saved_success'));
    }
}
