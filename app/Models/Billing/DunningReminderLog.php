<?php

declare(strict_types=1);

namespace App\Models\Billing;

use App\Models\Concerns\TenantScoped;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per reminder send attempt (or skip).
 *
 * Used for:
 *  - Audit trail ("we sent X to tenant Y on date Z")
 *  - Idempotency: the unique key on (invoice, tier, channel)
 *    guarantees the dispatcher can run multiple times per day
 *    without re-sending the same tier to the same invoice.
 *  - Reporting: admins see which tenants are receiving reminders
 *    and whether delivery is succeeding.
 *
 * "skipped" rows are intentional — they let the admin see that
 * the dispatcher considered this invoice and decided to skip it
 * (paid, opted out, etc) without the dispatcher silently
 * dropping the case.
 */
class DunningReminderLog extends Model
{
    use HasFactory, TenantScoped;

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    public const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'tenant_id',
        'subscription_invoice_id',
        'tier_key',
        'channel',
        'status',
        'error_message',
        'sent_at',
        'retry_count',
        'next_retry_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'next_retry_at' => 'datetime',
            'retry_count' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SubscriptionInvoice::class, 'subscription_invoice_id');
    }

    /**
     * Phase 7.4: alias for the invoice relationship using the
     * same name as the rest of the app (some callers use
     * "subscriptionInvoice" for clarity). Keeps both naming
     * conventions working.
     */
    public function subscriptionInvoice(): BelongsTo
    {
        return $this->invoice();
    }
}
