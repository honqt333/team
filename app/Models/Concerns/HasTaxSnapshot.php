<?php

namespace App\Models\Concerns;

use App\Models\TenantTaxSetting;
use App\Support\TenancyContext;
use Illuminate\Database\Eloquent\Model;

trait HasTaxSnapshot
{
    public static function bootHasTaxSnapshot(): void
    {
        static::creating(function (Model $model) {
            // If snapshots are already set (e.g. during conversion), skip
            if ($model->tax_enabled_snapshot !== null) {
                return;
            }

            $tenantId = $model->tenant_id ?? TenancyContext::tenantId();

            if ($tenantId) {
                $settings = TenantTaxSetting::where('tenant_id', $tenantId)->first();

                if ($settings) {
                    $model->tax_enabled_snapshot = $settings->vat_enabled;
                    $model->pricing_mode_snapshot = $settings->pricing_mode;
                    $model->tax_rate_snapshot = $settings->vat_rate;
                    $model->currency_code = $settings->currency_code;
                } else {
                    // Fallback / Defaults
                    $model->tax_enabled_snapshot = false;
                    $model->pricing_mode_snapshot = 'exclusive';
                    $model->tax_rate_snapshot = 15.00;
                    $model->currency_code = 'SAR';
                }
            }
        });
    }
}
