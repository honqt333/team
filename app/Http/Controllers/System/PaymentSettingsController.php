<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\PaymentSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Inertia\Inertia;
use Inertia\Response;

class PaymentSettingsController extends Controller
{
    /**
     * Show payment settings page.
     */
    public function index(): Response
    {
        $settings = PaymentSettings::first() ?? new PaymentSettings();
        
        // Mask secret keys for display
        $maskedSettings = [
            'moyasar' => $this->maskGatewayConfig($settings->moyasar),
            'tap' => $this->maskGatewayConfig($settings->tap),
            'paytabs' => $this->maskGatewayConfig($settings->paytabs),
            'tabby' => $this->maskGatewayConfig($settings->tabby),
            'tamara' => $this->maskGatewayConfig($settings->tamara),
            'bank_transfer_enabled' => $settings->bank_transfer_enabled,
            'bank_accounts' => $settings->bank_accounts ?? [],
            'bank_transfer_instructions' => $settings->bank_transfer_instructions,
            'default_gateway' => $settings->default_gateway,
            'mada_enabled' => $settings->mada_enabled,
            'visa_enabled' => $settings->visa_enabled,
            'mastercard_enabled' => $settings->mastercard_enabled,
            'applepay_enabled' => $settings->applepay_enabled,
            'stcpay_enabled' => $settings->stcpay_enabled,
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
            'bank_accounts.*.bank_name' => 'required_with:bank_accounts|string',
            'bank_accounts.*.account_name' => 'required_with:bank_accounts|string',
            'bank_accounts.*.iban' => 'nullable|string',
            'bank_accounts.*.account_number' => 'nullable|string',
            'bank_transfer_instructions' => 'nullable|string',
            
            // Payment methods
            'mada_enabled' => 'boolean',
            'visa_enabled' => 'boolean',
            'mastercard_enabled' => 'boolean',
            'applepay_enabled' => 'boolean',
            'stcpay_enabled' => 'boolean',
        ]);
        
        $settings = PaymentSettings::first() ?? new PaymentSettings();
        
        // Update gateway configs (preserve existing secrets if not changed)
        $settings->moyasar = $this->mergeGatewayConfig($settings->moyasar, $validated['moyasar'] ?? []);
        $settings->tap = $this->mergeGatewayConfig($settings->tap, $validated['tap'] ?? []);
        $settings->paytabs = $this->mergeGatewayConfig($settings->paytabs, $validated['paytabs'] ?? []);
        $settings->tabby = $this->mergeGatewayConfig($settings->tabby, $validated['tabby'] ?? []);
        $settings->tamara = $this->mergeGatewayConfig($settings->tamara, $validated['tamara'] ?? []);
        
        // Update other settings
        $settings->default_gateway = $validated['default_gateway'];
        $settings->bank_transfer_enabled = $validated['bank_transfer_enabled'] ?? false;
        $settings->bank_accounts = $validated['bank_accounts'] ?? [];
        $settings->bank_transfer_instructions = $validated['bank_transfer_instructions'] ?? null;
        $settings->mada_enabled = $validated['mada_enabled'] ?? true;
        $settings->visa_enabled = $validated['visa_enabled'] ?? true;
        $settings->mastercard_enabled = $validated['mastercard_enabled'] ?? true;
        $settings->applepay_enabled = $validated['applepay_enabled'] ?? true;
        $settings->stcpay_enabled = $validated['stcpay_enabled'] ?? false;
        
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
        if (!$config) {
            return ['enabled' => false];
        }
        
        $masked = $config;
        
        // Mask secret keys
        foreach (['secret_key', 'server_key', 'api_token', 'notification_token'] as $key) {
            if (!empty($masked[$key])) {
                $masked[$key . '_masked'] = '••••••••' . substr($masked[$key], -4);
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
            if (empty($merged[$key]) && !empty($existing[$key])) {
                $merged[$key] = $existing[$key];
            }
        }
        
        return $merged;
    }
}
