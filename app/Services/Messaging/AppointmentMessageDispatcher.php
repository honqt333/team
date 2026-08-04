<?php

declare(strict_types=1);

namespace App\Services\Messaging;

use App\Jobs\SendAppointmentMessage;
use App\Models\Appointment;
use App\Models\AppointmentMessageDelivery;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use ReflectionClass;
use Throwable;

/**
 * Phase 10 — the single entry point for enqueuing
 * customer-facing SMS messages about an appointment.
 *
 * What this class owns
 * --------------------
 *   1. The "is this message allowed for this
 *      appointment right now?" policy. Confirmation
 *      requires a freshly-created row in
 *      {scheduled, confirmed}. Reschedule fires
 *      regardless of source (we are reacting to a
 *      time change). Cancel fires for any state
 *      because the customer is the actor and they
 *      chose to cancel.
 *   2. The DB write to `appointment_message_deliveries`.
 *      The row is inserted with a stable
 *      `idempotency_key` derived from
 *      (message_type, appointment_id, scheduled_at),
 *      so a reschedule that changes the time gets a
 *      fresh row.
 *   3. The queue dispatch. The job carries the
 *      delivery id only — the worker re-reads the
 *      row and re-validates the appointment.
 *
 * Event identity (Phase 10 hot-fix)
 * ---------------------------------
 * The unique key for a delivery row is now
 * (appointment_id, message_type, scheduled_at_snapshot).
 * Two reschedules to two different times produce
 * TWO booking_rescheduled rows. A 24h reminder for
 * the first scheduled_at is a different row from
 * the 24h reminder for the second scheduled_at.
 * A booking_confirmation does NOT block a 24h or
 * 2h reminder (different message_type).
 *
 * Why the row is created synchronously (not by the
 * worker)
 * ---------------------------------------------
 * The job is the thing that talks to the provider.
 * The row is the "we promised to send this" stamp.
 * If we created the row in the worker, two
 * concurrent workers could both pass the "does a
 * row exist?" check and both send. The unique
 * index is the lock — only one insert can win. A
 * losing insert becomes a no-op (`firstOrCreate`
 * returns the existing row). The losing worker
 * then sees `status != queued` and bails.
 *
 * The row is created OUTSIDE the appointment's
 * transaction
 * -----------------------------------------------
 * A delivery row is not a property of the
 * appointment — it is a side-effect that becomes
 * durable only AFTER the appointment has been
 * committed. If the appointment transaction rolls
 * back, we don't want a dangling delivery row.
 * Conversely, a delivery row that fails to insert
 * MUST NOT abort the appointment transaction.
 *
 * The dispatch flow is:
 *   - The caller (controller or scheduler) commits
 *     the appointment transaction.
 *   - The caller invokes
 *     `dispatcher->confirmation($appt, $customer)`,
 *     which:
 *       a. Inserts the delivery row in a fresh
 *          short transaction. Failure here is
 *          logged but does NOT throw to the caller
 *          — the appointment is already committed
 *          and we don't want to 500 the customer
 *          because of a SMS-side problem.
 *       b. Dispatches the job.
 *
 * Tenant isolation
 * ----------------
 * The dispatcher is invoked from inside tenant-
 * scoped code (controllers, services). The
 * delivery row's tenant_id is taken from the
 * appointment, not from any request input. The
 * phone_hash is derived from the customer's
 * persisted phone; the dispatcher NEVER reads the
 * phone from the request body.
 */
class AppointmentMessageDispatcher
{
    public function __construct(
        private readonly AppointmentMessageTemplate $template,
    ) {}

    /**
     * Customer just booked a new appointment through
     * the portal.
     */
    public function confirmation(Appointment $appointment, Customer $customer): void
    {
        $this->enqueue(
            $appointment,
            $customer,
            AppointmentMessageDelivery::TYPE_BOOKING_CONFIRMATION
        );
    }

    /**
     * Customer successfully rescheduled an existing
     * appointment. The appointment's scheduled_at is
     * already updated by the time we land here.
     */
    public function rescheduled(Appointment $appointment, Customer $customer): void
    {
        $this->enqueue(
            $appointment,
            $customer,
            AppointmentMessageDelivery::TYPE_BOOKING_RESCHEDULED
        );
    }

    /**
     * Customer successfully cancelled an existing
     * appointment. The appointment's status is
     * already `cancelled` by the time we land here.
     */
    public function cancelled(Appointment $appointment, Customer $customer): void
    {
        $this->enqueue(
            $appointment,
            $customer,
            AppointmentMessageDelivery::TYPE_BOOKING_CANCELLED
        );
    }

    /**
     * Reminder job entry. Called by
     * `AppointmentMessageReminderScanner` for each
     * (appointment, type) pair that the scanner
     * decides to dispatch. The row's idempotency_key
     * ensures a double-scan on the same window
     * collapses to one row.
     */
    public function reminder(Appointment $appointment, Customer $customer, string $messageType): void
    {
        if (! in_array($messageType, [
            AppointmentMessageDelivery::TYPE_REMINDER_24H,
            AppointmentMessageDelivery::TYPE_REMINDER_2H,
        ], true)) {
            throw new InvalidArgumentException("Not a reminder type: {$messageType}");
        }

        $this->enqueue($appointment, $customer, $messageType);
    }

    /**
     * Insert the delivery row + dispatch the job.
     * Idempotent on the (appointment_id,
     * message_type, scheduled_at_snapshot) triple.
     */
    private function enqueue(Appointment $appointment, Customer $customer, string $messageType): void
    {
        $rendered = $this->template->render($appointment, $messageType);
        $phone = (string) ($customer->phone ?: $customer->whatsapp);

        if ($phone === '') {
            Log::info('appointment_message.no_phone_skip', [
                'appointment_id' => $appointment->id,
                'tenant_id' => $appointment->tenant_id,
                'customer_id' => $customer->id,
                'message_type' => $messageType,
            ]);

            return;
        }

        $phoneHash = $this->template->hashPhone($phone);

        // The snapshot is the appointment's
        // current scheduled_at. Two reschedules to
        // two different times produce two
        // different snapshots, so the unique
        // index lets both inserts succeed. The
        // idempotency_key uses the same value to
        // make the row's identity self-describing.
        $snapshot = $appointment->scheduled_at;
        $snapshotForKey = $snapshot?->toIso8601String() ?? 'unscheduled';

        // We use DB::transaction to make the
        // existence-check + insert atomic. The unique
        // index is the real lock; the transaction
        // makes the two SQL statements a single
        // observable unit for any concurrent worker.
        $delivery = null;

        try {
            $delivery = DB::transaction(function () use (
                $appointment, $customer, $messageType, $phoneHash, $snapshot, $snapshotForKey
            ) {
                return AppointmentMessageDelivery::query()
                    ->firstOrCreate(
                        [
                            'appointment_id' => $appointment->id,
                            'message_type' => $messageType,
                            'scheduled_at_snapshot' => $snapshot,
                        ],
                        [
                            'tenant_id' => $appointment->tenant_id,
                            'customer_id' => $customer->id,
                            'idempotency_key' => substr(
                                hash('sha256', sprintf(
                                    '%s:%d:%s',
                                    $messageType,
                                    $appointment->id,
                                    $snapshotForKey
                                )),
                                0,
                                32
                            ),
                            'phone_hash' => $phoneHash,
                            'status' => AppointmentMessageDelivery::STATUS_QUEUED,
                            'attempts' => 0,
                            'queued_at' => Carbon::now(),
                        ]
                    );
            });
        } catch (Throwable $e) {
            // The appointment is already committed.
            // We log and move on — the customer has
            // their booking, the SMS is a "best
            // effort" side-effect, and a transient
            // DB error must not 500 the response.
            Log::warning('appointment_message.insert_failed', [
                'appointment_id' => $appointment->id,
                'tenant_id' => $appointment->tenant_id,
                'message_type' => $messageType,
                'reason_class' => (new ReflectionClass($e))->getShortName(),
            ]);

            return;
        }

        // If a previous row exists in a non-queued
        // state, we DON'T re-dispatch. This is the
        // second line of idempotency defense: even
        // if (appointment, type, snapshot) repeats
        // (e.g. a retry of the same dispatch), the
        // second dispatch sees the row is already
        // sent/failed and the worker bails.
        if ($delivery->isTerminal()) {
            return;
        }

        // If we just created a fresh row OR the row
        // is still `queued`, dispatch. The dispatch
        // is wrapped in try/catch because the
        // SyncQueue driver runs the job inline
        // (synchronously) — a throw from inside
        // the job's `handle()` propagates out of
        // `dispatch()` and, if we let it bubble,
        // out of `DB::afterCommit` and into the
        // controller that called the dispatcher.
        // The customer would see a 500 even though
        // the appointment itself is already
        // committed. The job's own
        // markFailed / markSkipped paths take care
        // of the row state; from the dispatcher's
        // perspective, dispatch is best-effort.
        $this->safeDispatch($delivery->id);
    }

    /**
     * Best-effort dispatch. The job is the source
     * of truth for the row's state, so any failure
     * here is logged but does not propagate to the
     * caller. The customer-facing controller has
     * already committed the appointment by the
     * time we are called; an SMS-side failure
     * must NOT undo that.
     *
     * Three failure modes are handled:
     *
     *   1. The job was already in a terminal state
     *      when the worker picked it up
     *      (double-dispatch, retry of a previous
     *      tick). The job's `handle()` bails with
     *      no exception. We observe the row is
     *      `sent` and treat the dispatch as a
     *      success.
     *
     *   2. The job ran and the SmsService threw.
     *      The job's `markFailed()` updates the
     *      row to `failed` and re-throws so the
     *      queue's retry policy counts the attempt.
     *      The throw bubbles to here, we catch it,
     *      log only the class name (no phone, no
     *      body, no PII), and continue.
     *
     *   3. The queue itself could not accept the
     *      job (driver down, serialization error).
     *      The throw happens BEFORE the job's
     *      handle() runs. We mark the row `failed`
     *      with `error_code = 'dispatch_failed'`
     *      via a conditional UPDATE that only
     *      matches rows still in `queued`. The
     *      recovery command (`appointments:recover-stuck`)
     *      only unsticks `sending` rows, so a
     *      `queued` row with a broken dispatch is
     *      stranded otherwise. Marking it `failed`
     *      with a stable code gives operators a
     *      signal (the dashboard can filter on
     *      `error_code = 'dispatch_failed'`) and
     *      ends the recovery ambiguity. The
     *      conditional predicate
     *      `status = 'queued'` ensures we do NOT
     *      overwrite a row that the job, in some
     *      interleaving, has already taken to
     *      `sending` / `failed` / `sent` /
     *      `skipped`.
     *
     * In none of the three cases does the
     * caller's response change. The customer sees
     * the same redirect and the same generic
     * "your appointment is confirmed" flash.
     */
    private function safeDispatch(int $deliveryId): void
    {
        try {
            SendAppointmentMessage::dispatch($deliveryId);
        } catch (Throwable $e) {
            $reasonClass = (new ReflectionClass($e))->getShortName();

            // SECURITY: log the delivery id and the
            // exception class only. The SmsService's
            // exception message can include the
            // provider's HTTP body (which can carry
            // the phone). We do NOT echo it.
            Log::warning('appointment_message.dispatch_failed', [
                'delivery_id' => $deliveryId,
                'reason_class' => $reasonClass,
            ]);

            // Conditional UPDATE: only flip rows
            // that are STILL `queued`. If the
            // job (in another interleaving) has
            // already taken the row to `sending`
            // or terminal, we leave the row
            // alone — its own state machine is
            // the source of truth.
            //
            // The error code is a fixed sentinel,
            // not derived from the exception class
            // (the operator wants one bucket for
            // "dispatch failed", not a long list
            // of class names). The exception class
            // is in the application log only.
            //
            // error_message is NULL on purpose:
            // even the truncated exception
            // message can leak the phone in some
            // queue-driver failure envelopes. The
            // row never carries the message body
            // or the phone.
            AppointmentMessageDelivery::query()
                ->where('id', $deliveryId)
                ->where('status', AppointmentMessageDelivery::STATUS_QUEUED)
                ->update([
                    'status' => AppointmentMessageDelivery::STATUS_FAILED,
                    'failed_at' => Carbon::now(),
                    'error_code' => 'dispatch_failed',
                    'error_message' => null,
                    'attempts' => DB::raw('attempts + 1'),
                ]);
        }
    }
}
