<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\PaymentSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CompanySettingsController extends Controller
{
    /**
     * Show company settings page.
     *
     * Company info + subscription VAT are stored on the PaymentSettings row
     * (shared table) because the original schema has only one settings row,
     * and these fields are rendered on tenant invoices. We keep writing to
     * the same row so we don't break any existing data or invoice rendering.
     */
    public function index(): Response
    {
        $settings = PaymentSettings::first() ?? new PaymentSettings;

        $companySettings = [
            'company_legal_name' => $settings->company_legal_name,
            'company_legal_name_en' => $settings->company_legal_name_en,
            'company_vat_number' => $settings->company_vat_number,
            'company_cr_number' => $settings->company_cr_number,
            'company_phone' => $settings->company_phone,
            'company_email' => $settings->company_email,
            'company_website' => $settings->company_website,
            'company_logo_url' => $settings->company_logo_path
                ? Storage::url($settings->company_logo_path)
                : null,
            'company_stamp_url' => $settings->company_stamp_path
                ? Storage::url($settings->company_stamp_path)
                : null,

            // Subscription VAT — applied to tenant subscription invoices
            'subscription_vat_enabled' => (bool) ($settings->subscription_vat_enabled ?? false),
            'subscription_vat_rate' => (float) ($settings->subscription_vat_rate ?? 15),
            'subscription_vat_inclusive' => (bool) ($settings->subscription_vat_inclusive ?? false),
        ];

        return Inertia::render('System/Settings/CompanySettings', [
            'settings' => $companySettings,
        ]);
    }

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

        return back()->with('success', 'تم حفظ معلومات الشركة بنجاح');
    }

    /**
     * Upload company logo. Stores in public/company and persists the path.
     */
    public function uploadLogo(Request $request): JsonResponse
    {
        $request->validate([
            'logo' => 'required|image|mimes:png,jpg,jpeg,webp,svg|max:4096',
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
            'stamp' => 'required|image|mimes:png,jpg,jpeg,webp,svg|max:2048',
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
