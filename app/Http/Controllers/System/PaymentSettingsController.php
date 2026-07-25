<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\PaymentSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PaymentSettingsController extends Controller
{
    /**
     * Show payment settings page.
     */
    public function index(): Response
    {
        $settings = PaymentSettings::first() ?? new PaymentSettings;

        // Pass through every field the Settings page binds to. Missing
        // fields here meant the form re-mounted with `undefined` values
        // and the user would see them as blank, even when the DB had
        // data. We mask secret keys server-side so they're never sent
        // to the browser in cleartext.
        $maskedSettings = [
            // Gateways
            'moyasar' => $this->maskGatewayConfig($settings->moyasar),
            'tap' => $this->maskGatewayConfig($settings->tap),
            'paytabs' => $this->maskGatewayConfig($settings->paytabs),
            'tabby' => $this->maskGatewayConfig($settings->tabby),
            'tamara' => $this->maskGatewayConfig($settings->tamara),

            // Default + bank transfer
            'default_gateway' => $settings->default_gateway,
            'bank_transfer_enabled' => (bool) $settings->bank_transfer_enabled,
            'bank_accounts' => $settings->bank_accounts ?? [],
            'bank_transfer_instructions' => $settings->bank_transfer_instructions,
            'bank_transfer_require_receipt' => (bool) ($settings->bank_transfer_require_receipt ?? true),
            'bank_transfer_max_days' => $settings->bank_transfer_max_days ?? 7,
            'bank_transfer_min_amount' => (float) ($settings->bank_transfer_min_amount ?? 0),
            'bank_transfer_receipt_instructions' => $settings->bank_transfer_receipt_instructions,

            // Payment methods
            'mada_enabled' => (bool) ($settings->mada_enabled ?? true),
            'visa_enabled' => (bool) ($settings->visa_enabled ?? true),
            'mastercard_enabled' => (bool) ($settings->mastercard_enabled ?? true),
            'applepay_enabled' => (bool) ($settings->applepay_enabled ?? true),
            'stcpay_enabled' => (bool) ($settings->stcpay_enabled ?? false),

            // Company info (displayed on tenant invoices/PDFs)
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

            // Subscription VAT
            'subscription_vat_enabled' => (bool) ($settings->subscription_vat_enabled ?? false),
            'subscription_vat_rate' => (float) ($settings->subscription_vat_rate ?? 15),
            'subscription_vat_inclusive' => (bool) ($settings->subscription_vat_inclusive ?? false),
        ];

        return Inertia::render('System/Settings/PaymentSettings', [
            'settings' => $maskedSettings,
        ]);
    }

    /**
     * Update payment settings.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'default_gateway' => 'required|string|in:moyasar,tap,paytabs,tabby,tamara,bank_transfer',

            // Moyasar
            'moyasar.enabled' => 'boolean',
            'moyasar.publishable_key' => 'nullable|string',
            'moyasar.secret_key' => 'nullable|string',

            // Tap
            'tap.enabled' => 'boolean',
            'tap.public_key' => 'nullable|string',
            'tap.secret_key' => 'nullable|string',

            // PayTabs
            'paytabs.enabled' => 'boolean',
            'paytabs.profile_id' => 'nullable|string',
            'paytabs.server_key' => 'nullable|string',
            'paytabs.client_key' => 'nullable|string',

            // Tabby (BNPL)
            'tabby.enabled' => 'boolean',
            'tabby.public_key' => 'nullable|string',
            'tabby.secret_key' => 'nullable|string',
            'tabby.merchant_code' => 'nullable|string',

            // Tamara (BNPL)
            'tamara.enabled' => 'boolean',
            'tamara.api_token' => 'nullable|string',
            'tamara.notification_token' => 'nullable|string',

            // Bank Transfer
            'bank_transfer_enabled' => 'boolean',
            'bank_accounts' => 'array',
            'bank_accounts.*.bank_name' => 'required_with:bank_accounts|string|max:255',
            'bank_accounts.*.account_name' => 'required_with:bank_accounts|string|max:255',
            'bank_accounts.*.iban' => 'nullable|string|max:34',
            'bank_accounts.*.account_number' => 'nullable|string|max:50',
            'bank_transfer_instructions' => 'nullable|string|max:2000',
            'bank_transfer_require_receipt' => 'boolean',
            'bank_transfer_max_days' => 'nullable|integer|min:1|max:90',
            'bank_transfer_min_amount' => 'nullable|numeric|min:0',
            'bank_transfer_receipt_instructions' => 'nullable|string|max:2000',

            // Payment methods
            'mada_enabled' => 'boolean',
            'visa_enabled' => 'boolean',
            'mastercard_enabled' => 'boolean',
            'applepay_enabled' => 'boolean',
            'stcpay_enabled' => 'boolean',

            // Company info
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

        // Update gateway configs (preserve existing secrets if not changed)
        $settings->moyasar = $this->mergeGatewayConfig($settings->moyasar, $validated['moyasar'] ?? []);
        $settings->tap = $this->mergeGatewayConfig($settings->tap, $validated['tap'] ?? []);
        $settings->paytabs = $this->mergeGatewayConfig($settings->paytabs, $validated['paytabs'] ?? []);
        $settings->tabby = $this->mergeGatewayConfig($settings->tabby, $validated['tabby'] ?? []);
        $settings->tamara = $this->mergeGatewayConfig($settings->tamara, $validated['tamara'] ?? []);

        // Top-level scalars
        $settings->default_gateway = $validated['default_gateway'];
        $settings->bank_transfer_enabled = (bool) ($validated['bank_transfer_enabled'] ?? false);
        $settings->bank_accounts = $validated['bank_accounts'] ?? [];
        $settings->bank_transfer_instructions = $validated['bank_transfer_instructions'] ?? null;
        $settings->bank_transfer_require_receipt = (bool) ($validated['bank_transfer_require_receipt'] ?? true);
        $settings->bank_transfer_max_days = (int) ($validated['bank_transfer_max_days'] ?? 7);
        $settings->bank_transfer_min_amount = (float) ($validated['bank_transfer_min_amount'] ?? 0);
        $settings->bank_transfer_receipt_instructions = $validated['bank_transfer_receipt_instructions'] ?? null;

        $settings->mada_enabled = (bool) ($validated['mada_enabled'] ?? true);
        $settings->visa_enabled = (bool) ($validated['visa_enabled'] ?? true);
        $settings->mastercard_enabled = (bool) ($validated['mastercard_enabled'] ?? true);
        $settings->applepay_enabled = (bool) ($validated['applepay_enabled'] ?? true);
        $settings->stcpay_enabled = (bool) ($validated['stcpay_enabled'] ?? false);

        // Company info
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

        // Clear cache
        PaymentSettings::clearCache();

        return back()->with('success', 'تم حفظ إعدادات الدفع بنجاح');
    }

    /**
     * Mask secret keys for display.
     */
    private function maskGatewayConfig(?array $config): array
    {
        if (! $config) {
            return ['enabled' => false];
        }

        $masked = $config;

        // Mask secret keys
        foreach (['secret_key', 'server_key', 'api_token', 'notification_token'] as $key) {
            if (! empty($masked[$key])) {
                $masked[$key.'_masked'] = '••••••••'.substr($masked[$key], -4);
                $masked[$key] = ''; // Don't send actual secret to frontend
            }
        }

        return $masked;
    }

    /**
     * Merge gateway config preserving existing secrets.
     */
    private function mergeGatewayConfig(?array $existing, array $new): array
    {
        $merged = $new;

        // Preserve existing secrets if not provided
        foreach (['secret_key', 'server_key', 'api_token', 'notification_token'] as $key) {
            if (empty($merged[$key]) && ! empty($existing[$key])) {
                $merged[$key] = $existing[$key];
            }
        }

        return $merged;
    }
}
