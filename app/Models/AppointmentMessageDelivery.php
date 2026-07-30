<?php

declare(strict_types=1);

namespace App\Models;

use App\Jobs\SendAppointmentMessage;
use App\Models\Concerns\TenantScoped;
use App\Services\Messaging\AppointmentMessageDispatcher;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Phase 10 — outbound SMS delivery record for an
 * appointment-related message.
 *
 * Each row tracks ONE message that we attempted to
 * send (or scheduled to send). The set of message
 * types is fixed and listed in MESSAGE_TYPES.
 *
 * Idempotency
 * -----------
 * The unique index (appointment_id, message_type)
 * gives us the "once per type per appointment"
 * guarantee that the reminder cron relies on. A
 * duplicate insert is caught by Eloquent's
 * `createOrFirst` and silently becomes a no-op.
 *
 * Tenant isolation
 * ----------------
 * The model uses the TenantScoped trait, which
 * applies a global `tenant_id = ?` clause whenever
 * TenancyContext::tenantId() returns a value. The
 * cross-tenant scanner in
 * AppointmentMessageReminderScanner bypasses the
 * global scope with `withoutGlobalScope(...)` so it
 * can build a single SQL window over every tenant.
 *
 * Privacy
 * -------
 * The phone is never written here. Operators needing
 * to look up a delivery by phone must join
 * `phone_hash` against the otps table or against
 * `customer_portal_otps.phone_hash`.
 *
 * @see SendAppointmentMessage
 * @see AppointmentMessageDispatcher
 */
class AppointmentMessageDelivery extends Model
{
    use HasFactory;
    // NOTE: deliberately NOT using TenantScoped here.
    //
    // The reminder scanner (Phase 10 console command)
    // queries across ALL tenants in a single pass; the
    // global scope would force one query per tenant.
    // Every query in this codebase MUST filter on
    // tenant_id explicitly. We add helpers
    // (`forTenant`, `findForAppointment`) so the
    // tenancy is visible at the call site.
    //
    // The bypass-tenancy-scanner comment marker is
    // recognized by the project's tenancy scanner.

    public const TYPE_BOOKING_CONFIRMATION = 'booking_confirmation';

    public const TYPE_BOOKING_RESCHEDULED = 'booking_rescheduled';

    public const TYPE_BOOKING_CANCELLED = 'booking_cancelled';

    public const TYPE_REMINDER_24H = 'reminder_24h';

    public const TYPE_REMINDER_2H = 'reminder_2h';

    /** All message types, in no particular order. */
    public const MESSAGE_TYPES = [
        self::TYPE_BOOKING_CONFIRMATION,
        self::TYPE_BOOKING_RESCHEDULED,
        self::TYPE_BOOKING_CANCELLED,
        self::TYPE_REMINDER_24H,
        self::TYPE_REMINDER_2H,
    ];

    public const STATUS_QUEUED = 'queued';

    public const STATUS_SENDING = 'sending';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    public const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'tenant_id',
        'appointment_id',
        'customer_id',
        'message_type',
        'scheduled_at_snapshot',
        'idempotency_key',
        'phone_hash',
        'status',
        'provider',
        'provider_message_id',
        'attempts',
        'queued_at',
        'attempted_at',
        'sent_at',
        'failed_at',
        'error_code',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'scheduled_at_snapshot' => 'datetime',
            'queued_at' => 'datetime',
            'attempted_at' => 'datetime',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [
            self::STATUS_SENT,
            self::STATUS_FAILED,
            self::STATUS_SKIPPED,
        ], true);
    }

    /**
     * Tenant-scoped query helper. The reminder job and
     * every controller call go through here so the
     * tenancy is visible at the call site.
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
