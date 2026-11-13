<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\PaymentSettings;
use App\Services\System\SystemAuditService;
use Illuminate\Http\Request;

class PaymentSettingsController extends Controller
{
    public function __construct(
        protected SystemAuditService $audit
    ) {}

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

            // Company info + Subscription VAT — moved to CompanySettingsController
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

        // Company info + Subscription VAT — moved to CompanySettingsController

        $settings->save();

        // Clear cache
        PaymentSettings::clearCache();

        // Payment gateway configuration decides where real money goes.
        // The audit entry records WHICH gateways were enabled — never
        // the secret keys themselves.
        $this->audit->log('payment_settings_update', PaymentSettings::class, $settings->id, [
            'default_gateway' => $settings->default_gateway,
            'moyasar_enabled' => (bool) data_get($settings->moyasar, 'enabled'),
            'tap_enabled' => (bool) data_get($settings->tap, 'enabled'),
            'paytabs_enabled' => (bool) data_get($settings->paytabs, 'enabled'),
            'tabby_enabled' => (bool) data_get($settings->tabby, 'enabled'),
            'tamara_enabled' => (bool) data_get($settings->tamara, 'enabled'),
            'bank_transfer_enabled' => $settings->bank_transfer_enabled,
        ], $request);

        return back()->with('success', __('messages.system.payment_settings.saved'));
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
