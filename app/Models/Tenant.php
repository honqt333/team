<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'status',
        'trial_ends_at',
        'suspended_at',
        'suspension_reason',
        // Company Profile
        'legal_name',
        'legal_name_en',
        'trade_name',
        'owner_name',
        'vat_number',
        'cr_number',
        'iban',
        'phone',
        'email',
        'logo_path',
        'invoice_number_format',
        'two_factor_enabled',
        'two_factor_enforcement',
        'sms_2fa_enabled',
    ];
    
    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'suspended_at' => 'datetime',
            'two_factor_enabled' => 'boolean',
            'sms_2fa_enabled' => 'boolean',
        ];
    }

    public function centers(): HasMany
    {
        return $this->hasMany(Center::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function taxSettings(): HasOne
    {
        return $this->hasOne(TenantTaxSetting::class);
    }

    public function address(): HasOne
    {
        return $this->hasOne(TenantAddress::class);
    }

    public function zatcaSettings(): HasOne
    {
        return $this->hasOne(TenantZatcaSetting::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(\App\Models\Billing\Subscription::class);
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::created(function (Tenant $tenant) {
            // Seed default roles for the new tenant
            (new \App\Services\TenantSetupService())->seedRolesForTenant($tenant->id);
            
            // Create default settings if needed (future)
        });
    }
}
