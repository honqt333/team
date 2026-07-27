<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Models\Billing\DunningReminderLog;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Phase 7.4 — exponential-backoff retry for failed dunning
 * sends.
 *
 * When a send fails, we don't just give up — we schedule a
 * retry. The cadence is 24h, then 72h, then we mark the log
 * as permanently failed and stop retrying. That gives the
 * tenant a 4-day total window to react before we go silent.
 *
 * Why 24h then 72h? Because 24h is enough for the most
 * common transient failure (SMTP blip, mail queue lag),
 * and 72h covers the "tenant is on vacation" case without
 * burning through all our retry budget on day 1.
 *
 * The dispatcher in DunningService is responsible for
 * incrementing retry_count and setting next_retry_at when a
 * send fails. This service is the read-side helper that the
 * retry cron uses to find rows that are due.
 */
class DunningRetryService
{
    public const MAX_RETRIES = 2; // 24h + 72h = 2 retries (3 total attempts)

    /**
     * Find FAILED log rows that are due for another attempt
     * and have retry budget remaining.
     *
     * @return Collection<int, DunningReminderLog>
     */
    public function findDueForRetry(): Collection
    {
        return DunningReminderLog::query()
            ->where('status', DunningReminderLog::STATUS_FAILED)
            ->where('retry_count', '<', self::MAX_RETRIES)
            ->whereNotNull('next_retry_at')
            ->where('next_retry_at', '<=', now())
            ->with(['tenant', 'subscriptionInvoice'])
            ->get();
    }

    /**
     * Mark a log row as retried successfully. We UPDATE the
     * existing row rather than insert a new one — the unique
     * constraint on (invoice, tier, channel) would block a
     * new row anyway, and the audit trail of "tried, failed,
     * retried, succeeded" is more useful as a single row.
     */
    public function markRetried(DunningReminderLog $log): void
    {
        $log->update([
            'status' => DunningReminderLog::STATUS_SENT,
            'error_message' => null,
            'sent_at' => now(),
        ]);
    }

    /**
     * Compute the next retry timestamp for a failed send
     * based on the current retry count. Used by the dispatcher
     * when a send fails.
     */
    public function nextRetryAt(int $currentRetryCount): ?Carbon
    {
        if ($currentRetryCount >= self::MAX_RETRIES) {
            return null;
        }

        // 0 -> 24h, 1 -> 72h
        return match ($currentRetryCount) {
            0 => now()->addHours(24),
            1 => now()->addHours(72),
            default => null,
        };
    }
}
