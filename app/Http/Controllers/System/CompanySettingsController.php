<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\PaymentSettings;
use App\Services\System\SystemAuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CompanySettingsController extends Controller
{
    public function __construct(
        protected SystemAuditService $audit
    ) {}

    /**
     * Update company settings (including subscription VAT).
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'company_legal_name' => 'nullable|string|max:255',
            'company_legal_name_en' => 'nullable|string|max:255',
            'company_vat_number' => 'nullable|string|max:15',
            'company_cr_number' => 'nullable|string|max:50',
            'company_phone' => 'nullable|string|max:50',
            'company_email' => 'nullable|email|max:255',
            'company_website' => 'nullable|url|max:255',

            // Subscription VAT
            'subscription_vat_enabled' => 'boolean',
            'subscription_vat_rate' => 'nullable|numeric|min:0|max:100',
            'subscription_vat_inclusive' => 'boolean',
        ]);

        $settings = PaymentSettings::first() ?? new PaymentSettings;

        $settings->company_legal_name = $validated['company_legal_name'] ?? null;
        $settings->company_legal_name_en = $validated['company_legal_name_en'] ?? null;
        $settings->company_vat_number = $validated['company_vat_number'] ?? null;
        $settings->company_cr_number = $validated['company_cr_number'] ?? null;
        $settings->company_phone = $validated['company_phone'] ?? null;
        $settings->company_email = $validated['company_email'] ?? null;
        $settings->company_website = $validated['company_website'] ?? null;

        // Subscription VAT
        $settings->subscription_vat_enabled = (bool) ($validated['subscription_vat_enabled'] ?? false);
        $settings->subscription_vat_rate = (float) ($validated['subscription_vat_rate'] ?? 15.00);
        $settings->subscription_vat_inclusive = (bool) ($validated['subscription_vat_inclusive'] ?? false);

        $settings->save();

        // Clear the shared settings cache so tenant invoices pick up the change
        PaymentSettings::clearCache();

        // These values are rendered on every tenant invoice, so a change
        // here is a financial-config change and must be traceable.
        $this->audit->log('company_settings_update', PaymentSettings::class, $settings->id, [
            'company_legal_name' => $settings->company_legal_name,
            'company_vat_number' => $settings->company_vat_number,
            'subscription_vat_enabled' => $settings->subscription_vat_enabled,
            'subscription_vat_rate' => $settings->subscription_vat_rate,
            'subscription_vat_inclusive' => $settings->subscription_vat_inclusive,
        ], $request);

        return back()->with('success', __('messages.system.company.saved'));
    }

    /**
     * Upload company logo. Stores in public/company and persists the path.
     */
    public function uploadLogo(Request $request): JsonResponse
    {
        $request->validate([
            'logo' => 'required|image|mimes:png,jpg,jpeg,webp|max:4096',
        ]);

        $settings = PaymentSettings::first() ?? new PaymentSettings;

        // Remove old logo file (best-effort)
        if ($settings->company_logo_path && Storage::disk('public')->exists($settings->company_logo_path)) {
            Storage::disk('public')->delete($settings->company_logo_path);
        }

        $path = $request->file('logo')->store('company', 'public');
        $settings->company_logo_path = $path;
        $settings->save();
        PaymentSettings::clearCache();

        return response()->json([
            'url' => Storage::url($path),
            'path' => $path,
        ]);
    }

    /**
     * Remove company logo.
     */
    public function removeLogo(): JsonResponse
    {
        $settings = PaymentSettings::first() ?? new PaymentSettings;

        if ($settings->company_logo_path && Storage::disk('public')->exists($settings->company_logo_path)) {
            Storage::disk('public')->delete($settings->company_logo_path);
        }

        $settings->company_logo_path = null;
        $settings->save();
        PaymentSettings::clearCache();

        return response()->json(['ok' => true]);
    }

    /**
     * Upload company stamp (signature image, usually PNG with
     * transparent background). Stored separately from the logo
     * so admins can replace either without affecting the other.
     */
    public function uploadStamp(Request $request): JsonResponse
    {
        $request->validate([
            'stamp' => 'required|image|mimes:png,jpg,jpeg,webp|max:2048',
        ]);

        $settings = PaymentSettings::first() ?? new PaymentSettings;

        // Remove old stamp file (best-effort)
        if ($settings->company_stamp_path && Storage::disk('public')->exists($settings->company_stamp_path)) {
            Storage::disk('public')->delete($settings->company_stamp_path);
        }

        $path = $request->file('stamp')->store('company/stamps', 'public');
        $settings->company_stamp_path = $path;
        $settings->save();
        PaymentSettings::clearCache();

        return response()->json([
            'url' => Storage::url($path),
            'path' => $path,
        ]);
    }

    /**
     * Remove company stamp.
     */
    public function removeStamp(): JsonResponse
    {
        $settings = PaymentSettings::first() ?? new PaymentSettings;

        if ($settings->company_stamp_path && Storage::disk('public')->exists($settings->company_stamp_path)) {
            Storage::disk('public')->delete($settings->company_stamp_path);
        }

        $settings->company_stamp_path = null;
        $settings->save();
        PaymentSettings::clearCache();

        return response()->json(['ok' => true]);
    }
}
