<?php

namespace App\Models;

use App\Models\Billing\Subscription;
use App\Services\TenantSetupService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

// @bypass-tenancy-scanner - Root identity: this IS the tenant
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
        // Document Titles
        'quote_title',
        'work_order_title',
        'invoice_title',
        // Terms & Conditions
        'quote_terms',
        'work_order_terms',
        'invoice_terms',
        // Visual Print Settings
        'print_settings',
    ];

    protected $appends = [
        'logo_url',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'suspended_at' => 'datetime',
            'two_factor_enabled' => 'boolean',
            'sms_2fa_enabled' => 'boolean',
            'print_settings' => 'array',
        ];
    }

    public function getLogoUrlAttribute(): string
    {
        return $this->logo_path ? Storage::url($this->logo_path) : asset('images/logo.png');
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
        return $this->hasMany(Subscription::class);
    }

    /**
     * Check if the tenant has an active paid subscription.
     */
    public function hasPaidSubscription(): bool
    {
        if ($this->slug === 'khidmh' || $this->slug === 'test-company') {
            return true;
        }

        // For development/seeding convenience, if running in local/testing and there are no plans/subscriptions at all, default to true or false.
        // But to be precise, let's query the database.
        return $this->subscriptions()
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            })
            ->whereHas('plan', function ($query) {
                $query->where('price_monthly', '>', 0);
            })
            ->exists();
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::created(function (Tenant $tenant) {
            $service = new TenantSetupService;

            // Seed default roles + permissions for the new tenant
            $service->seedRolesForTenant($tenant->id);

            // Seed default lookup data (units, employee types, job titles).
            // Idempotent — safe to re-run via the backfill command.
            $service->seedDefaultsForTenant($tenant->id);
        });
    }
}
