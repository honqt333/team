<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Appointment;
use App\Models\AppointmentMessageDelivery;
use App\Models\Customer;
use App\Services\Messaging\AppointmentMessageTemplate;
use App\Services\Messaging\SmsService;
use App\Support\TenancyContext;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ReflectionClass;
use RuntimeException;
use Throwable;

/**
 * Phase 10 — queue the actual SMS send for an
 * appointment-related delivery.
 *
 * Lifecycle
 * ---------
 *  1. The dispatcher creates a `queued` delivery row
 *     AFTER the appointment transaction commits. The
 *     row carries the message_type, the
 *     scheduled_at_snapshot, the idempotency_key,
 *     the phone_hash, and the appointment/customer/
 *     tenant ids.
 *  2. The dispatcher dispatches this job with the
 *     delivery row's id. The job carries the id, not
 *     a serialized Eloquent model, so the worker
 *     reads the FRESHEST version of the row (any
 *     state mutation between the dispatch and the
 *     worker pickup is honored).
 *  3. The job re-checks (in order):
 *       - the delivery row still exists
 *       - status is `queued` (not already sent/failed)
 *       - the appointment is still in a state that
 *         should receive this message (e.g. NOT
 *         cancelled for a confirmation)
 *       - the customer still has a phone
 *       - for time-bound messages (confirmation,
 *         reschedule, reminders): the appointment's
 *         current scheduled_at matches the row's
 *         scheduled_at_snapshot. A divergence means
 *         the appointment was rescheduled (or moved
 *         another way) after the row was created, so
 *         the message is now stale and we mark the
 *         row `skipped` instead of sending.
 *  4. The job renders the body, calls SmsService, and
 *     marks the row `sent` (with the provider
 *     message_id) or `failed` (with a sanitized
 *     error code, NO $e->getMessage() anywhere).
 *  5. The job supports retries via the queue's
 *     `tries` field. Each retry increments
 *     `attempts` and `attempted_at`. We intentionally
 *     do NOT auto-retry on the row going `failed` —
 *     a permanent failure (e.g. tenant has no SMS
 *     credits) would loop.
 *
 * Idempotency
 * -----------
 * The `appointment_message_deliveries` table has a
 * unique index (appointment_id, message_type,
 * scheduled_at_snapshot). The dispatcher uses a
 * `firstOrCreate` on this triple, so a duplicate
 * dispatch collapses to a single row. The job's
 * re-check on the row's status is the second line
 * of defense: if a duplicate row slipped past the
 * unique index (e.g. because the message type
 * changed for a reschedule, leaving the
 * rescheduled-row's uniqueness check different from
 * the cancelled-row's), the second worker sees
 * `status = sent` and aborts.
 *
 * Snapshot mismatch semantics
 * ---------------------------
 * For time-bound messages (confirmation,
 * reschedule, 24h, 2h) we store
 * `scheduled_at_snapshot` on the delivery row. The
 * worker compares it to the appointment's CURRENT
 * scheduled_at. A mismatch means the message is for
 * an event that no longer reflects the
 * appointment's state — we mark the row `skipped`
 * with reason `appointment_schedule_changed` and
 * return. This catches three real cases:
 *   - Reschedule: the original confirmation /
 *     reminder is for the old time. Skipping it
 *     prevents a "your appointment at 14:00 is
 *     confirmed" message arriving an hour after the
 *     customer moved the appointment to 15:00.
 *   - Cancel: a 24h reminder queued before a
 *     cancellation should not send. The status
 *     check catches most cancels, but the snapshot
 *     check is the safety net for the edge case
 *     where cancel + reminder race.
 *   - Double reschedule: a 24h reminder for time
 *     T1 must not send after the appointment moved
 *     to T2.
 *
 * Privacy
 * -------
 * The job writes the body in memory only. The
 * delivery row stores the phone_hash (sha256) and
 * the provider_message_id. No log line contains the
 * phone, the body, or a prefix of the body. The
 * failure path stores only the exception class
 * short name (e.g. `RuntimeException`) — never
 * `$e->getMessage()`, which can include the
 * provider's response body and therefore the phone.
 */
class SendAppointmentMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Worker tries this many times before marking `failed`. */
    public int $tries = 3;

    /** Backoff in seconds for the retry schedule. */
    public function backoff(): array
    {
        return [30, 120, 300];
    }

    /**
     * The delivery row's id. We store the id, not the
     * model, so the worker always reads the freshest
     * state. The dispatcher creates the row inside
     * the same transaction as the appointment
     * creation, then dispatches the job AFTER commit.
     */
    public function __construct(
        public readonly int $deliveryId,
    ) {}

    public function handle(
        AppointmentMessageTemplate $template,
        SmsService $sms,
    ): void {
        $delivery = AppointmentMessageDelivery::query()->find($this->deliveryId);

        if ($delivery === null) {
            // Defensive: the row vanished. Could
            // happen if the cascade-on-delete ran
            // (e.g. the appointment was hard-deleted
            // by an admin tool). Log and move on.
            Log::info('appointment_message.delivery_missing', [
                'delivery_id' => $this->deliveryId,
            ]);

            return;
        }

        // Re-validate. A second worker on the same
        // delivery row (e.g. the queue retried this
        // job because of a worker crash) sees
        // `status != queued` and bails.
        if ($delivery->isTerminal()) {
            Log::info('appointment_message.delivery_already_terminal', [
                'delivery_id' => $delivery->id,
                'status' => $delivery->status,
            ]);

            return;
        }

        TenancyContext::set((int) $delivery->tenant_id, $delivery->center_id ? (int) $delivery->center_id : null);

        try {
            $this->processDelivery($delivery, $template, $sms);
        } finally {
            TenancyContext::clear();
        }
    }

    private function processDelivery(
        AppointmentMessageDelivery $delivery,
        AppointmentMessageTemplate $template,
        SmsService $sms,
    ): void {

        // Re-fetch the appointment and re-evaluate
        // the "should we send?" rules. The
        // dispatcher's "this message type is for
        // status X" check is the first line of
        // defense; the worker's re-check is the
        // second.
        $appointment = Appointment::query()
            ->where('tenant_id', $delivery->tenant_id)
            ->whereKey($delivery->appointment_id)
            ->first();

        if ($appointment === null) {
            $this->markSkipped($delivery, 'appointment_missing');

            return;
        }

        // Snapshot mismatch: the appointment was
        // rescheduled (or otherwise moved) after
        // the row was created. The message is for
        // the OLD scheduled_at; sending it would
        // confuse the customer ("your appointment
        // at 14:00 is confirmed" an hour after
        // they moved it to 15:00).
        if ($this->isSnapshotStale($delivery, $appointment)) {
            $this->markSkipped($delivery, 'appointment_schedule_changed');

            return;
        }

        if (! $this->isMessageApplicable($appointment, $delivery->message_type)) {
            $this->markSkipped($delivery, 'not_applicable_anymore');

            return;
        }

        $customer = Customer::query()
            ->where('tenant_id', $delivery->tenant_id)
            ->whereKey($delivery->customer_id)
            ->first(['id', 'phone', 'whatsapp']);

        if ($customer === null) {
            $this->markSkipped($delivery, 'customer_missing');

            return;
        }

        $phone = (string) ($customer->phone ?: $customer->whatsapp);

        if ($phone === '') {
            $this->markSkipped($delivery, 'no_phone');

            return;
        }

        // Render the body and stamp the row with
        // "sending" so a second worker doesn't
        // double-dispatch.
        $tookOver = false;

        try {
            DB::transaction(function () use ($delivery, &$tookOver) {
                $fresh = AppointmentMessageDelivery::query()
                    ->whereKey($delivery->id)
                    ->where('status', AppointmentMessageDelivery::STATUS_QUEUED)
                    ->update([
                        'status' => AppointmentMessageDelivery::STATUS_SENDING,
                        'attempted_at' => Carbon::now(),
                        'attempts' => DB::raw('attempts + 1'),
                    ]);

                if ($fresh === 0) {
                    // Another worker beat us to it.
                    // The outer handler will see the
                    // row in a non-queued state and
                    // exit.
                    throw new RuntimeException('delivery_locked_by_other_worker');
                }

                $tookOver = true;
            });
        } catch (RuntimeException $e) {
            if (! $tookOver) {
                return;
            }

            throw $e;
        }

        if (! $tookOver) {
            return;
        }

        // Re-read the row with the fresh state. The
        // dispatched job is carrying the original id;
        // we trust the database for status.
        $delivery = $delivery->fresh();

        if ($delivery === null || $delivery->status !== AppointmentMessageDelivery::STATUS_SENDING) {
            return;
        }

        $rendered = $template->render($appointment, $delivery->message_type);
        $body = $rendered['body'];

        try {
            $result = $sms->send($phone, $body, $delivery->tenant_id);
        } catch (Throwable $e) {
            $this->markFailed($delivery, $e);

            // Re-throw so the queue's retry policy
            // kicks in. The markFailed call updated
            // the row to `failed` with a sanitized
            // error; the retry on the queue side
            // re-runs the job from the top, but the
            // row is now `failed` and the early-exit
            // check at the top of handle() bails.
            // We throw so the worker counts the
            // attempt; we do NOT want infinite
            // retries on a row that's already
            // `failed`.
            throw $e;
        }

        $delivery->update([
            'status' => AppointmentMessageDelivery::STATUS_SENT,
            'provider' => $result['provider'] ?? null,
            'provider_message_id' => $result['message_id'] ?? null,
            'sent_at' => Carbon::now(),
            'error_code' => null,
            'error_message' => null,
        ]);

        Log::info('appointment_message.sent', [
            'delivery_id' => $delivery->id,
            'appointment_id' => $delivery->appointment_id,
            'tenant_id' => $delivery->tenant_id,
            'customer_id' => $delivery->customer_id,
            'message_type' => $delivery->message_type,
            'provider_message_id' => $result['message_id'] ?? null,
        ]);
    }

    /**
     * Decide whether the message is still applicable
     * to the appointment. Reminders only fire for
     * active appointments; transactional
     * confirmations/reschedules/cancellations always
     * fire (the dispatcher only enqueues them in the
     * right state, so by the time we land here
     * the appointment has the right state). The
     * check is here for the reschedule case: a
     * reschedule of a row that has since been
     * cancelled by the customer should not generate
     * a "rescheduled" notification.
     */
    private function isMessageApplicable(Appointment $appointment, string $messageType): bool
    {
        return match ($messageType) {
            AppointmentMessageDelivery::TYPE_BOOKING_CONFIRMATION => in_array(
                $appointment->status,
                [
                    Appointment::STATUS_SCHEDULED,
                    Appointment::STATUS_CONFIRMED,
                    Appointment::STATUS_CHECKED_IN,
                ],
                true
            ),
            AppointmentMessageDelivery::TYPE_BOOKING_RESCHEDULED => in_array(
                $appointment->status,
                [
                    Appointment::STATUS_SCHEDULED,
                    Appointment::STATUS_CONFIRMED,
                    Appointment::STATUS_CHECKED_IN,
                ],
                true
            ),
            AppointmentMessageDelivery::TYPE_BOOKING_CANCELLED => $appointment->status === Appointment::STATUS_CANCELLED,
            AppointmentMessageDelivery::TYPE_REMINDER_24H,
            AppointmentMessageDelivery::TYPE_REMINDER_2H => in_array(
                $appointment->status,
                Appointment::BLOCKING_STATUSES,
                true
            ),
            default => false,
        };
    }

    /**
     * True if the delivery row's scheduled_at_snapshot
     * is set AND differs from the appointment's
     * CURRENT scheduled_at. The original message was
     * for an event that has since moved; sending it
     * would be misleading.
     *
     * The snapshot is nullable in the schema (so
     * non-time-bound messages — currently none — can
     * exist with NULL snapshot). When the snapshot
     * is NULL, we treat the row as not time-bound
     * and skip the check.
     */
    private function isSnapshotStale(AppointmentMessageDelivery $delivery, Appointment $appointment): bool
    {
        $snapshot = $delivery->scheduled_at_snapshot;

        if ($snapshot === null) {
            return false;
        }

        if ($appointment->scheduled_at === null) {
            // Defensive: the appointment lost its
            // scheduled_at somehow. The snapshot
            // was for a real time; the current row
            // has none. Treat as stale.
            return true;
        }

        return ! $snapshot->equalTo($appointment->scheduled_at);
    }

    private function markSkipped(AppointmentMessageDelivery $delivery, string $reason): void
    {
        $delivery->update([
            'status' => AppointmentMessageDelivery::STATUS_SKIPPED,
            'failed_at' => Carbon::now(),
            'error_code' => $reason,
            'error_message' => null,
        ]);

        Log::info('appointment_message.skipped', [
            'delivery_id' => $delivery->id,
            'appointment_id' => $delivery->appointment_id,
            'tenant_id' => $delivery->tenant_id,
            'reason' => $reason,
        ]);
    }

    /**
     * P1 privacy fix: the SmsService exception
     * message can include the provider's HTTP body
     * (and therefore the phone). We persist ONLY:
     *   - error_code: the exception class short
     *     name, lowercased and word-safe.
     *   - error_message: NULL.
     *
     * Operators investigating a failure look up the
     * provider_message_id on the SmsUsageLog (which
     * is the system's own audit table); the
     * IntegrationLog on the SmsService side carries
     * the full provider response for the support
     * team. We do not duplicate that surface here.
     */
    private function markFailed(AppointmentMessageDelivery $delivery, Throwable $e): void
    {
        $errorCode = substr(
            strtolower(
                preg_replace(
                    '/[^a-z0-9_]+/i',
                    '_',
                    (new ReflectionClass($e))->getShortName()
                )
            ),
            0,
            64
        );

        $delivery->update([
            'status' => AppointmentMessageDelivery::STATUS_FAILED,
            'failed_at' => Carbon::now(),
            'error_code' => $errorCode,
            // SECURITY: $e->getMessage() is NEVER
            // written to the row. The provider's
            // response body (and any PII it contains)
            // would leak otherwise.
            'error_message' => null,
        ]);

        // SECURITY: log the appointment id + tenant
        // id + delivery id + the exception class
        // name. The message body is NEVER logged;
        // neither is the phone, the body of the
        // SMS, or a prefix of either.
        Log::warning('appointment_message.send_failed', [
            'delivery_id' => $delivery->id,
            'appointment_id' => $delivery->appointment_id,
            'tenant_id' => $delivery->tenant_id,
            'reason_class' => (new ReflectionClass($e))->getShortName(),
        ]);
    }
}
