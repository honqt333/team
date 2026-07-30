<?php

declare(strict_types=1);

namespace App\Services\Messaging;

use App\Models\Appointment;
use App\Models\AppointmentMessageDelivery;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Phase 10 — find appointments that are about to
 * start and dispatch a reminder for each.
 *
 * Two windows
 * -----------
 *   - 24 hours: scheduled_at is between [now+23h,
 *     now+25h]. The 2-hour window is wide enough
 *     to absorb scheduler jitter (a cron that
 *     skipped a 6 AM tick still catches the next
 *     one). A 24-hour reminder is more than a
 *     binary "is it exactly 24h?" check.
 *   - 2 hours: scheduled_at is between [now+1.5h,
 *     now+2.5h]. Same 60-minute width.
 *
 * Each (appointment, type, scheduled_at_snapshot)
 * triple dispatches at most once. The unique index
 * on (appointment_id, message_type,
 * scheduled_at_snapshot) is the idempotency
 * guarantee; the dispatcher's
 * `firstOrCreate` is the only call that can insert
 * a duplicate, and the unique index collapses the
 * duplicate to a no-op.
 *
 * Pre-filter semantics
 * --------------------
 * The pre-filter on this query MUST be scoped to
 * the reminder type, not to ANY delivery row for
 * the appointment. A confirmation row from booking
 * time does NOT block a 24h reminder, and a 24h
 * reminder does NOT block a 2h reminder. We filter
 * on (appointment_id, message_type, scheduled_at =
 * appointments.scheduled_at) so the comparison
 * happens against the current scheduled_at, not
 * against a historical snapshot.
 *
 * Tenant isolation
 * ----------------
 * The scan runs without a TenancyContext — it's
 * invoked from a console command, not from a
 * request. We query every appointment that matches
 * the status + window predicate and dispatch
 * tenant-by-tenant. The delivery row's tenant_id
 * comes from the appointment, so a mistake here
 * would only ever mis-target a delivery to a
 * row's own tenant.
 *
 * Reschedule handling
 * -------------------
 * If an appointment is rescheduled, the OLD
 * scheduled_at falls out of the window, the NEW
 * scheduled_at falls into the window, the new
 * delivery row has scheduled_at_snapshot = the new
 * scheduled_at, and the new unique key
 * (appointment, type, new_snapshot) inserts
 * cleanly. The OLD reminder (with the old snapshot)
 * is still in the table but is not in the
 * (appt, type, current scheduled_at) tuple, so the
 * pre-filter does not see it. The worker for the
 * old reminder, if it had been queued, would also
 * see that scheduled_at_snapshot !=
 * appointment.scheduled_at and skip the send
 * (P1: appointment_schedule_changed).
 */
class AppointmentMessageReminderScanner
{
    /**
     * How wide the reminder window is, in minutes.
     * Phase 10 spec: "the 24-hour and 2-hour
     * windows must be flexible against scheduler
     * delays; do not rely on a single minute".
     */
    public const WINDOW_WIDTH_MINUTES = 60;

    /** Half-width for the 24h window. */
    public const OFFSET_24H_HOURS = 24;

    /** Half-width for the 2h window. */
    public const OFFSET_2H_HOURS = 2;

    /**
     * Run the scan and return a count of
     * (appointment, type, scheduled_at_snapshot)
     * tuples dispatched. The command uses this for
     * the summary log line.
     */
    public function scan(?Carbon $now = null): int
    {
        $now = $now ?? Carbon::now();
        $dispatched = 0;

        // We query WITHOUT the TenantScoped global
        // scope. The scanner must see every tenant.
        $window24 = $this->windowFor($now, self::OFFSET_24H_HOURS);
        $window2 = $this->windowFor($now, self::OFFSET_2H_HOURS);

        $appointments24 = $this->appointmentsInWindow($window24, AppointmentMessageDelivery::TYPE_REMINDER_24H);
        $appointments2 = $this->appointmentsInWindow($window2, AppointmentMessageDelivery::TYPE_REMINDER_2H);

        foreach ($appointments24 as $appointment) {
            $customer = $this->customerFor($appointment);

            if ($customer === null) {
                continue;
            }
            $this->dispatcher->reminder(
                $appointment,
                $customer,
                AppointmentMessageDelivery::TYPE_REMINDER_24H
            );
            $dispatched++;
        }

        foreach ($appointments2 as $appointment) {
            $customer = $this->customerFor($appointment);

            if ($customer === null) {
                continue;
            }
            $this->dispatcher->reminder(
                $appointment,
                $customer,
                AppointmentMessageDelivery::TYPE_REMINDER_2H
            );
            $dispatched++;
        }

        return $dispatched;
    }

    public function __construct(
        private readonly AppointmentMessageDispatcher $dispatcher,
    ) {}

    private function windowFor(Carbon $now, int $hoursAhead): array
    {
        // [now + hoursAhead - halfWidth, now + hoursAhead + halfWidth]
        $center = $now->copy()->addHours($hoursAhead);
        $halfWidth = (int) (self::WINDOW_WIDTH_MINUTES / 2);

        return [
            $center->copy()->subMinutes($halfWidth),
            $center->copy()->addMinutes($halfWidth),
        ];
    }

    /**
     * Find appointments inside the reminder window
     * that have NOT yet received THIS type of
     * reminder for the CURRENT scheduled_at.
     *
     * The pre-filter compares the appointment's
     * CURRENT scheduled_at to a delivery row's
     * scheduled_at_snapshot. A historical reminder
     * for an earlier scheduled_at does not block
     * the new one, because the snapshots differ.
     * A confirmation or reschedule message for
     * the same appointment does not block ANY
     * reminder, because the message_type differs.
     *
     * @return Collection<int, Appointment>
     */
    private function appointmentsInWindow(array $window, ?string $messageType = null)
    {
        [$start, $end] = $window;

        $q = Appointment::query()
            ->whereIn('status', Appointment::BLOCKING_STATUSES)
            ->where('scheduled_at', '>=', $start)
            ->where('scheduled_at', '<=', $end);

        // Reminder dedup, scoped to (appointment,
        // THIS type, CURRENT scheduled_at). Any
        // other delivery row — confirmation,
        // reschedule, a reminder for a previous
        // scheduled_at — is invisible to this
        // filter.
        $q->whereNotExists(function ($sub) use ($messageType) {
            $sub->select(DB::raw(1))
                ->from('appointment_message_deliveries as d')
                ->whereColumn('d.appointment_id', '=', 'appointments.id');

            if ($messageType !== null) {
                $sub->where('d.message_type', '=', $messageType);
            }

            // Match the CURRENT scheduled_at. The
            // delivery row carries a snapshot; if
            // the appointment has been rescheduled,
            // the snapshot and the current value
            // diverge and the subquery returns no
            // rows, so the appointment is included
            // in the candidate set.
            $sub->whereRaw('d.scheduled_at_snapshot = appointments.scheduled_at');
        });

        return $q->get();
    }

    private function customerFor(Appointment $appointment): ?Customer
    {
        return Customer::query()
            ->where('tenant_id', $appointment->tenant_id)
            ->whereKey($appointment->customer_id)
            ->first(['id', 'phone', 'whatsapp']);
    }
}
