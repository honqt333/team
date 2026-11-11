<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Tenant;
use App\Models\TenantTaxSetting;
use App\Support\CountryCurrencyRegistry;
use App\Support\TenancyContext;
use Illuminate\Database\Eloquent\Model;

// @bypass-tenancy-scanner - This is a Trait (Concern), not an Eloquent Model. Scoping is handled by the parent model.
trait HasTaxSnapshot
{
    public static function bootHasTaxSnapshot(): void
    {
        static::creating(function (Model $model) {
            $tenantId = $model->tenant_id ?? TenancyContext::tenantId();

            // Always ensure currency_code is populated from tenant/tax settings when omitted,
            // even if tax_enabled_snapshot was already provided manually.
            if ($tenantId && empty($model->currency_code)) {
                $settings = TenantTaxSetting::where('tenant_id', $tenantId)->first();
                $tenant = $model->relationLoaded('tenant') ? $model->tenant : Tenant::find($tenantId);
                $model->currency_code = $settings?->currency_code ?: CountryCurrencyRegistry::tenantCurrency($tenant);
            }

            // If tax snapshots are already set (e.g. during conversion), skip overwriting them
            if ($model->tax_enabled_snapshot !== null) {
                return;
            }

            if ($tenantId) {
                $settings ??= TenantTaxSetting::where('tenant_id', $tenantId)->first();
                $tenant ??= $model->relationLoaded('tenant') ? $model->tenant : Tenant::find($tenantId);
                $fallbackCountry = CountryCurrencyRegistry::tenantCountry($tenant);
                $fallbackCurrency = CountryCurrencyRegistry::tenantCurrency($tenant);
                $fallbackVatRate = CountryCurrencyRegistry::defaultVatRate($fallbackCountry);

                if ($settings) {
                    $model->tax_enabled_snapshot = $settings->vat_enabled;
                    $model->pricing_mode_snapshot = $settings->services_inclusive ? 'inclusive' : 'exclusive';
                    $model->tax_rate_snapshot = $settings->vat_rate ?? $fallbackVatRate;
                    $model->currency_code = $model->currency_code ?: ($settings->currency_code ?: $fallbackCurrency);
                } else {
                    // Fallback / Defaults
                    $model->tax_enabled_snapshot = false;
                    $model->pricing_mode_snapshot = 'exclusive';
                    $model->tax_rate_snapshot = $fallbackVatRate;
                    $model->currency_code = $model->currency_code ?: $fallbackCurrency;
                }
            }
        });
    }

    public function refreshTaxSnapshot(): void
    {
        $tenantId = $this->tenant_id ?? TenancyContext::tenantId();

        if ($tenantId) {
            $settings = TenantTaxSetting::where('tenant_id', $tenantId)->first();
            $tenant = $this->relationLoaded('tenant') ? $this->tenant : Tenant::find($tenantId);
            $fallbackCurrency = CountryCurrencyRegistry::tenantCurrency($tenant);

            if ($settings) {
                $this->tax_enabled_snapshot = $settings->vat_enabled;
                $this->pricing_mode_snapshot = $settings->services_inclusive ? 'inclusive' : 'exclusive';
                $this->tax_rate_snapshot = $settings->vat_rate ?? CountryCurrencyRegistry::defaultVatRate(CountryCurrencyRegistry::tenantCountry($tenant));
                $this->currency_code = $settings->currency_code ?: $fallbackCurrency;
            }
        }
    }
}
