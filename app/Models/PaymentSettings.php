<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

// @bypass-tenancy-scanner - Single-row global payment gateway config (cached, not tenant-owned)
class PaymentSettings extends Model
{
    protected $table = 'payment_settings';

    protected $fillable = [
        'moyasar',
        'tap',
        'paytabs',
        'tabby',
        'tamara',
        'bank_transfer_enabled',
        'bank_accounts',
        'bank_transfer_instructions',
        'default_gateway',
        'mada_enabled',
        'visa_enabled',
        'mastercard_enabled',
        'applepay_enabled',
        'stcpay_enabled',
        'company_legal_name',
        'company_legal_name_en',
        'company_vat_number',
        'company_cr_number',
        'company_phone',
        'company_email',
        'company_website',
        'company_logo_path',
        'subscription_vat_enabled',
        'subscription_vat_rate',
        'subscription_vat_inclusive',
        'bank_transfer_min_amount',
        'bank_transfer_max_days',
        'bank_transfer_require_receipt',
        'bank_transfer_receipt_instructions',
    ];

    protected function casts(): array
    {
        return [
            'moyasar' => 'array',
            'tap' => 'array',
            'paytabs' => 'array',
            'tabby' => 'array',
            'tamara' => 'array',
            'bank_accounts' => 'array',
            'bank_transfer_enabled' => 'boolean',
            'mada_enabled' => 'boolean',
            'visa_enabled' => 'boolean',
            'mastercard_enabled' => 'boolean',
            'stcpay_enabled' => 'boolean',
            'subscription_vat_enabled' => 'boolean',
            'subscription_vat_inclusive' => 'boolean',
            'subscription_vat_rate' => 'decimal:2',
            'bank_transfer_require_receipt' => 'boolean',
        ];
    }

    /**
     * Get settings (cached).
     */
    public static function getSettings(): self
    {
        return Cache::remember('payment_settings', 3600, function () {
            return self::first() ?? new self;
        });
    }

    /**
     * Clear settings cache.
     */
    public static function clearCache(): void
    {
        Cache::forget('payment_settings');
    }

    /**
     * Get gateway config.
     */
    public function getGatewayConfig(string $gateway): ?array
    {
        return $this->{$gateway} ?? null;
    }

    /**
     * Check if gateway is enabled.
     */
    public function isGatewayEnabled(string $gateway): bool
    {
        $config = $this->getGatewayConfig($gateway);

        if (empty($config['enabled'])) {
            return false;
        }

        // Check for required keys based on gateway
        return match ($gateway) {
            'tamara' => ! empty($config['api_token']),
            'tabby' => ! empty($config['secret_key']),
            default => ! empty($config['secret_key'] ?? $config['server_key']),
        };
    }

    /**
     * Get enabled payment methods.
     */
    public function getEnabledMethods(): array
    {
        $methods = [];

        if ($this->mada_enabled) {
            $methods[] = 'mada';
        }

        if ($this->visa_enabled) {
            $methods[] = 'visa';
        }

        if ($this->mastercard_enabled) {
            $methods[] = 'mastercard';
        }

        if ($this->applepay_enabled) {
            $methods[] = 'applepay';
        }

        if ($this->stcpay_enabled) {
            $methods[] = 'stcpay';
        }

        if ($this->bank_transfer_enabled) {
            $methods[] = 'bank_transfer';
        }

        return $methods;
    }

    /**
     * Get bank accounts.
     */
    public function getBankAccounts(): array
    {
        return $this->bank_accounts ?? [];
    }

    /**
     * Get company display name based on locale.
     */
    public function getCompanyDisplayName(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();

        if ($locale === 'en' && $this->company_legal_name_en) {
            return $this->company_legal_name_en;
        }

        return $this->company_legal_name ?? config('app.name', 'Carag');
    }

    /**
     * Get VAT rate for subscriptions.
     */
    public function getSubscriptionVatRate(): float
    {
        if (! $this->subscription_vat_enabled) {
            return 0.0;
        }

        return (float) ($this->subscription_vat_rate ?? 15.00);
    }

    /**
     * Calculate VAT amount for a base price.
     * If inclusive: extract VAT from total. If exclusive: add VAT on top.
     */
    public function calculateVat(float $basePrice, bool $returnExclusive = false): array
    {
        $rate = $this->getSubscriptionVatRate() / 100;
        $inclusive = $this->subscription_vat_inclusive ?? false;

        if ($inclusive) {
            $exclusive = $basePrice / (1 + $rate);
            $vat = $basePrice - $exclusive;

            return [
                'exclusive' => round($exclusive, 2),
                'vat' => round($vat, 2),
                'total' => round($basePrice, 2),
            ];
        }

        $vat = $basePrice * $rate;
        $total = $basePrice + $vat;

        return [
            'exclusive' => round($basePrice, 2),
            'vat' => round($vat, 2),
            'total' => round($total, 2),
        ];
    }

    /**
     * Get company info for invoices/receipts.
     */
    public function getCompanyInfo(): array
    {
        return [
            'legal_name' => $this->company_legal_name,
            'legal_name_en' => $this->company_legal_name_en,
            'vat_number' => $this->company_vat_number,
            'cr_number' => $this->company_cr_number,
            'phone' => $this->company_phone,
            'email' => $this->company_email,
            'website' => $this->company_website,
            'logo_url' => $this->company_logo_path ? Storage::url($this->company_logo_path) : null,
        ];
    }
}
