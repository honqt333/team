<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\TenantTaxSetting;
use App\Support\TenancyContext;
use Illuminate\Database\Eloquent\Model;

// @bypass-tenancy-scanner - This is a Trait (Concern), not an Eloquent Model. Scoping is handled by the parent model.
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
                    $model->pricing_mode_snapshot = $settings->services_inclusive ? 'inclusive' : 'exclusive';
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

    public function refreshTaxSnapshot(): void
    {
        $tenantId = $this->tenant_id ?? TenancyContext::tenantId();

        if ($tenantId) {
            $settings = TenantTaxSetting::where('tenant_id', $tenantId)->first();

            if ($settings) {
                $this->tax_enabled_snapshot = $settings->vat_enabled;
                $this->pricing_mode_snapshot = $settings->services_inclusive ? 'inclusive' : 'exclusive';
                $this->tax_rate_snapshot = $settings->vat_rate;
                $this->currency_code = $settings->currency_code;
            }
        }
    }
}
