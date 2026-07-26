<?php

declare(strict_types=1);

namespace App\Models\Billing;

use App\Models\Concerns\TenantScoped;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Dunning configuration — one row per tenant, with a global
 * default (tenant_id = NULL) used when a tenant has no override.
 *
 * Admin-tunable: the Settings page lets the super_admin edit the
 * global row, and per-tenant overrides for any specific tenant.
 * See DunningService::configFor() for the resolution order.
 */
class DunningConfig extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'enabled',
        'active_tiers',
        'channel_matrix',
        'message_templates',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            // channel_matrix and message_templates are stored as JSON
            // on MySQL/Postgres and as TEXT on SQLite. Casting to
            // array normalizes both into a PHP array.
            'channel_matrix' => 'array',
            'message_templates' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Default channel matrix used when no override is configured.
     * Tier 1 = email only (gentle). Tier 2 = email + in-app
     * (firm). Tier 3 = email + in-app + SMS (urgent).
     * Tier 4 = email + in-app + WhatsApp (final notice before
     * suspension). These match the templates we render in
     * DunningService::buildMessageBody().
     */
    public static function defaultChannelMatrix(): array
    {
        return [
            'tier_1' => ['email'],
            'tier_2' => ['email', 'in_app'],
            'tier_3' => ['email', 'in_app', 'sms'],
            'tier_4' => ['email', 'in_app', 'whatsapp'],
        ];
    }

    /**
     * The default tier definitions — used by DunningService to
     * decide WHEN each tier fires. Stored here (not in the DB) so
     * the days-overdue thresholds stay in code: changing them
     * should be a deliberate code change with a deploy, not a
     * live config edit.
     *
     * Shape per tier:
     *   key:        the tier identifier
     *   days:       invoice must be this many days past due_at
     *   tone:       ar/en label used in the message subject
     *   description: shown on the settings page
     */
    public static function tierDefinitions(): array
    {
        return [
            'tier_1' => [
                'days' => 3,
                'tone' => ['ar' => 'ودود', 'en' => 'Friendly'],
                'description' => [
                    'ar' => 'تذكير ودي عبر الإيميل فقط',
                    'en' => 'Friendly reminder via email only',
                ],
            ],
            'tier_2' => [
                'days' => 7,
                'tone' => ['ar' => 'حازم', 'en' => 'Firm'],
                'description' => [
                    'ar' => 'إيميل + إشعار داخل التطبيق',
                    'en' => 'Email + in-app notification',
                ],
            ],
            'tier_3' => [
                'days' => 14,
                'tone' => ['ar' => 'عاجل', 'en' => 'Urgent'],
                'description' => [
                    'ar' => 'إيميل + إشعار + SMS — تحذير قبل التعليق',
                    'en' => 'Email + in-app + SMS — suspension warning',
                ],
            ],
            'tier_4' => [
                'days' => 30,
                'tone' => ['ar' => 'نهائي', 'en' => 'Final'],
                'description' => [
                    'ar' => 'إشعار نهائي قبل تعليق الخدمة',
                    'en' => 'Final notice before service suspension',
                ],
            ],
        ];
    }
}
