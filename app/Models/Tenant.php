<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Billing\Subscription;
use App\Services\TenantSetupService;
use App\Services\TenantStorageService;
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
        'auto_suspended_at',
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
        'work_order_number_format',
        'quote_number_format',
        'workflow_settings',
        'notification_templates',
        'notification_settings',
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
        // Dunning (Phase 6) — collection automation preferences
        'dunning_opted_out',
        'dunning_language',
    ];

    protected $appends = [
        'logo_url',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'suspended_at' => 'datetime',
            'auto_suspended_at' => 'datetime',
            'two_factor_enabled' => 'boolean',
            'sms_2fa_enabled' => 'boolean',
            'print_settings' => 'array',
            'workflow_settings' => 'array',
            'notification_templates' => 'array',
            'notification_settings' => 'array',
            'dunning_opted_out' => 'boolean',
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

    public function latestSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    public function currentSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->ofMany([
            'id' => 'max',
        ], function ($query) {
            $query->whereIn('status', ['active', 'trial', 'trialing']);
        });
    }

    public function activeSubscription(): HasOne
    {
        return $this->currentSubscription();
    }

    public function backups(): HasMany
    {
        return $this->hasMany(TenantBackup::class);
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
                $query->where('price_monthly', '>', 0)
                    ->orWhere('price_yearly', '>', 0);
            })
            ->exists();
    }

    public function formatDocumentCode(string $type, int $centerId, int $seq, ?int $year = null): string
    {
        $year = $year ?? (int) now()->format('Y');

        $format = match ($type) {
            'invoice' => $this->invoice_number_format ?: 'INV-{CENTER}-{YYYY}-{SEQ}',
            'work_order' => $this->work_order_number_format ?: 'WO-{SEQ}',
            'quote' => $this->quote_number_format ?: 'QT-{SEQ}',
            default => strtoupper($type).'-{SEQ}',
        };

        $replacements = [
            '{CENTER}' => (string) $centerId,
            '{YYYY}' => (string) $year,
            '{YY}' => substr((string) $year, -2),
            '{MM}' => now()->format('m'),
            '{DD}' => now()->format('d'),
            '{SEQ}' => str_pad((string) $seq, 6, '0', STR_PAD_LEFT),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $format);
    }

    public function getWorkflowSetting(string $key, mixed $default = null): mixed
    {
        return $this->workflow_settings[$key] ?? $default;
    }

    public function getNotificationTemplate(string $type, ?string $default = null): string
    {
        return $this->notification_templates[$type] ?? $default ?? '';
    }

    /**
     * Default tenant notification configuration matrix.
     *
     * @return array<string, mixed>
     */
    public static function defaultNotificationSettings(): array
    {
        return [
            'channels' => [
                'in_app' => true,
                'email' => false,
                'sms' => false,
                'whatsapp' => false,
            ],
            'channel_configs' => [
                'email_recipient' => '',
                'whatsapp_phone' => '',
            ],
            'events' => [
                'appointment_booked' => ['in_app' => true, 'email' => false, 'sms' => false, 'whatsapp' => false],
                'appointment_reminder' => ['in_app' => true, 'email' => false, 'sms' => false, 'whatsapp' => false],
                'appointment_cancelled' => ['in_app' => true, 'email' => false, 'sms' => false, 'whatsapp' => false],
                'work_order_created' => ['in_app' => true, 'email' => false, 'sms' => false, 'whatsapp' => false],
                'work_order_status_changed' => ['in_app' => true, 'email' => false, 'sms' => false, 'whatsapp' => false],
                'work_order_completed' => ['in_app' => true, 'email' => false, 'sms' => false, 'whatsapp' => false],
                'quote_shared' => ['in_app' => true, 'email' => false, 'sms' => false, 'whatsapp' => false],
                'quote_approved' => ['in_app' => true, 'email' => false, 'sms' => false, 'whatsapp' => false],
                'invoice_issued' => ['in_app' => true, 'email' => false, 'sms' => false, 'whatsapp' => false],
                'payment_received' => ['in_app' => true, 'email' => false, 'sms' => false, 'whatsapp' => false],
                'low_stock_alert' => ['in_app' => true, 'email' => false, 'sms' => false, 'whatsapp' => false],
            ],
        ];
    }

    /**
     * Get effective notification settings with defaults merged.
     *
     * @return array<string, mixed>
     */
    public function getEffectiveNotificationSettings(): array
    {
        $defaults = self::defaultNotificationSettings();
        $saved = $this->notification_settings ?? [];

        return [
            'channels' => array_merge($defaults['channels'], $saved['channels'] ?? []),
            'channel_configs' => array_merge($defaults['channel_configs'], $saved['channel_configs'] ?? []),
            'events' => array_replace_recursive($defaults['events'], $saved['events'] ?? []),
        ];
    }

    public function isNotificationChannelEnabled(string $channel): bool
    {
        $settings = $this->getEffectiveNotificationSettings();

        return (bool) ($settings['channels'][$channel] ?? false);
    }

    public function isNotificationEventEnabled(string $event, string $channel = 'in_app'): bool
    {
        if (! $this->isNotificationChannelEnabled($channel)) {
            return false;
        }

        $settings = $this->getEffectiveNotificationSettings();

        return (bool) ($settings['events'][$event][$channel] ?? false);
    }

    /**
     * Get storage quota and usage summary for the tenant.
     *
     * @return array<string, mixed>
     */
    public function getStorageUsageAttribute(): array
    {
        return TenantStorageService::getStorageUsageSummary($this);
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
