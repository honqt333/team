<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

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
            'applepay_enabled' => 'boolean',
            'stcpay_enabled' => 'boolean',
        ];
    }

    /**
     * Get settings (cached).
     */
    public static function getSettings(): self
    {
        return Cache::remember('payment_settings', 3600, function () {
            return self::first() ?? new self();
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
        if (empty($config['enabled'])) return false;
        
        // Check for required keys based on gateway
        return match ($gateway) {
            'tamara' => !empty($config['api_token']),
            'tabby' => !empty($config['secret_key']),
            default => !empty($config['secret_key'] ?? $config['server_key']),
        };
    }

    /**
     * Get enabled payment methods.
     */
    public function getEnabledMethods(): array
    {
        $methods = [];
        if ($this->mada_enabled) $methods[] = 'mada';
        if ($this->visa_enabled) $methods[] = 'visa';
        if ($this->mastercard_enabled) $methods[] = 'mastercard';
        if ($this->applepay_enabled) $methods[] = 'applepay';
        if ($this->stcpay_enabled) $methods[] = 'stcpay';
        if ($this->bank_transfer_enabled) $methods[] = 'bank_transfer';
        return $methods;
    }

    /**
     * Get bank accounts.
     */
    public function getBankAccounts(): array
    {
        return $this->bank_accounts ?? [];
    }
}
