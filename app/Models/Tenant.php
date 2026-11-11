<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Billing\Subscription;
use App\Services\Billing\SubscriptionLifecycleService;
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
        'country',
        'currency',
        'timezone',
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
        // Falls back to the platform brandmark. The mark carries no baked-in
        // wordmark, so the platform name stays dynamic (config('app.name')).
        return $this->logo_path ? Storage::url($this->logo_path) : asset('images/brand/logo-256.png');
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
     * Resolve the full subscription lifecycle state array via SubscriptionLifecycleService.
     */
    public function getSubscriptionLifecycle(): array
    {
        return app(SubscriptionLifecycleService::class)
            ->resolveTenantState($this);
    }

    public function getSubscriptionState(): string
    {
        return $this->getSubscriptionLifecycle()['state'];
    }

    public function isOnTrial(): bool
    {
        return $this->getSubscriptionLifecycle()['is_on_trial'];
    }

    public function isInSubscriptionGracePeriod(): bool
    {
        return $this->getSubscriptionLifecycle()['is_in_grace_period'];
    }

    public function canOperate(): bool
    {
        return $this->getSubscriptionLifecycle()['can_operate'];
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

    /**
     * Map a notification TYPE to the EVENT KEY used by the settings matrix.
     *
     * The notification system writes dotted types onto the row
     * (`work_order.created`, `invoice.created`, `quote.approved`), while
     * the tenant settings matrix — and the settings page that renders it —
     * use snake_case event keys (`work_order_created`, `invoice_issued`,
     * `quote_approved`).
     *
     * Before this mapping existed, the two namespaces never met, so every
     * per-event toggle on the settings page was a no-op: the tenant turned
     * `work_order_created` off and kept receiving `work_order.created`
     * notifications. Only the channel-level kill switch worked.
     *
     * The mapping is deliberately explicit rather than a generic
     * dots-to-underscores transform, because the two sides do NOT always
     * agree on the words:
     *
     *   work_order.created        → work_order_created
     *   work_order.status_changed → work_order_status_changed
     *   work_order.completed      → work_order_completed
     *   invoice.created           → invoice_issued
     *   invoice.issued            → invoice_issued
     *   quote.created             → quote_shared
     *   quote.shared              → quote_shared
     *   quote.approved            → quote_approved
     *   quote.rejected            → quote_approved
     *   payment.received          → payment_received
     *   payment.recorded          → payment_received
     *   stock.low                 → low_stock_alert
     *   low_stock_alert           → low_stock_alert
     *   appointment.booked        → appointment_booked
     *   appointment.reminder      → appointment_reminder
     *   appointment.cancelled     → appointment_cancelled
     *
     * @return array<int, string> The event keys to consult, in priority order.
     */
    public static function notificationEventKeysFor(string $type): array
    {
        $normalized = str_replace(['.', '-'], '_', strtolower($type));

        $map = [
            'work_order_created' => 'work_order_created',
            'work_order_status_changed' => 'work_order_status_changed',
            'work_order_completed' => 'work_order_completed',
            'invoice_created' => 'invoice_issued',
            'invoice_issued' => 'invoice_issued',
            'quote_created' => 'quote_shared',
            'quote_shared' => 'quote_shared',
            'quote_approved' => 'quote_approved',
            'quote_rejected' => 'quote_approved',
            'payment_received' => 'payment_received',
            'payment_recorded' => 'payment_received',
            'stock_low' => 'low_stock_alert',
            'low_stock_alert' => 'low_stock_alert',
            'appointment_booked' => 'appointment_booked',
            'appointment_reminder' => 'appointment_reminder',
            'appointment_cancelled' => 'appointment_cancelled',
        ];

        $keys = [];

        // The exact key first, so a tenant that saved a dotted key (from an
        // older payload) still wins over the normalized one.
        if (array_key_exists($type, $map)) {
            $keys[] = $type;
        }

        if (array_key_exists($normalized, $map)) {
            $keys[] = $map[$normalized];
        }

        // Unknown type — fall back to the normalized key so a tenant can
        // still toggle an event we have not mapped yet.
        if ($keys === []) {
            $keys[] = $normalized;
        }

        return array_values(array_unique($keys));
    }

    public function isNotificationEventEnabled(string $event, string $channel = 'in_app'): bool
    {
        if (! $this->isNotificationChannelEnabled($channel)) {
            return false;
        }

        $settings = $this->getEffectiveNotificationSettings();

        foreach (self::notificationEventKeysFor($event) as $key) {
            if (array_key_exists($key, $settings['events'] ?? [])) {
                return (bool) ($settings['events'][$key][$channel] ?? false);
            }
        }

        // An event nobody configured stays enabled — a tenant must not
        // silently lose notifications because the matrix has no row for it.
        return true;
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
