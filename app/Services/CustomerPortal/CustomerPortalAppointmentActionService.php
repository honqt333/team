<?php

declare(strict_types=1);

namespace App\Services\CustomerPortal;

use App\Models\Appointment;
use App\Models\Center;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Tenant;
use App\Services\AppointmentSchedulingService;
use App\Services\Messaging\AppointmentMessageDispatcher;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Customer-portal cancel + reschedule action service.
 *
 * Why this exists
 * --------------
 * The Phase 9 controller (CustomerPortalAppointmentActionsController)
 * had a TOCTOU race on the 2-hour window and the status
 * allowlist. The controller checked `scheduled` / `confirmed`
 * and `>= 2h` once on the row, then called
 * `AppointmentSchedulingService::cancel()` /
 * `reschedule()`. Between those two calls an operator
 * could:
 *
 *   - check the customer in (status becomes `checked_in`)
 *   - cancel the appointment from the staff UI
 *   - convert the appointment to a work order
 *   - move the appointment to a different time
 *
 * The staff-side `cancel()` accepts `checked_in` as a
 * valid source state. The staff-side `reschedule()` was
 * already locking the bay + user + vehicle but NOT the
 * appointment row itself, so a status transition that
 * happened between the controller's pre-check and the
 * scheduler's transaction would slip through.
 *
 * This service closes the gap. The public API is the
 * same shape the controller was using (cancel, reschedule)
 * but EVERY state read + state write happens inside a
 * `DB::transaction` with the appointment row locked
 * via `lockForUpdate()`. The status, the 2-hour
 * window, and the tenant + customer ownership are
 * re-validated AFTER the lock is acquired. If the
 * staff side raced us to a terminal state, the
 * second check fails and we throw a `port_al_race`
 * RuntimeException that the controller maps to a
 * customer-friendly 404.
 *
 * The bay / user / vehicle locks from the staff
 * service still apply (the scheduler takes them
 * inside its own transaction). The two-phase
 * commit we end up with is:
 *
 *   1. THIS service: lock appointment row, re-check
 *      ownership + status + 2h, do the read of
 *      `appointments` the controller would have
 *      done, hand off a fresh model instance to the
 *      scheduler.
 *   2. AppointmentSchedulingService: take the bay
 *      + user + vehicle locks, do the conflict
 *      re-check, persist the change.
 *
 * The staff scheduler's status check still runs as
 * a defense-in-depth layer (it now runs AFTER the
 * appointment is locked, so it cannot lose a race
 * to a state change that happened between the two
 * transactions). See the reschedule() change that
 * locks the row.
 */
class CustomerPortalAppointmentActionService
{
    /** Hours before scheduled_at where the customer can still act. */
    public const MIN_HOURS_BEFORE = 2;

    /** Max booking window. */
    public const MAX_LOOKAHEAD_DAYS = 14;

    /** Max characters for the optional cancellation reason. */
    public const CANCEL_REASON_MAX = 255;

    public function __construct(
        private readonly CustomerPortalAvailabilityService $availability,
        private readonly AppointmentSchedulingService $scheduler,
        private readonly AppointmentMessageDispatcher $messageDispatcher,
    ) {}

    /**
     * Cancel an appointment through the customer portal.
     *
     * Locks the appointment row for the duration of the
     * transaction, re-checks ownership and the
     * scheduled/confirmed allowlist + 2-hour window
     * AFTER the lock, then defers to the staff
     * scheduler for the actual status transition.
     *
     * The staff-side `cancel()` accepts `checked_in`
     * as a legal source state — we intentionally
     * REJECT that here. The customer is leaving
     * the building; they cannot cancel from the
     * portal anymore. We surface this as a 404 to
     * the controller (the same way we surface
     * every other ineligible state) so the
     * response never reveals the underlying reason.
     */
    public function cancel(
        Tenant $tenant,
        Customer $customer,
        int $appointmentId,
        ?string $reason = null,
    ): Appointment {
        return DB::transaction(function () use ($tenant, $customer, $appointmentId, $reason) {
            $appointment = $this->lockOwnAppointment($tenant, $customer, $appointmentId);

            // The customer portal is intentionally
            // stricter than the staff-side cancel:
            // we never accept `checked_in` here. If
            // the operator raced us to a check-in
            // between the controller's pre-check and
            // the lock, this branch catches it. The
            // caller's controller maps any rejection
            // here to the same generic 404, so the
            // customer never sees the underlying
            // reason.
            $this->assertActionable($appointment);
            $this->assertNotCheckedIn($appointment);

            // We re-derive the reason here so the audit
            // log is consistent regardless of which
            // code path called the service. An empty
            // string is fine: the staff-side cancel
            // accepts a null reason, so we propagate
            // it as-is.
            $prefixed = $reason === null || trim($reason) === ''
                ? 'customer_portal'
                : 'customer_portal: '.trim($reason);

            // Hand off to the staff scheduler. We
            // pass `isCustomerAction = true` so the
            // scheduler applies the stricter allowlist
            // (scheduled/confirmed only) — a defense
            // in depth in case our assertNotCheckedIn
            // was bypassed somehow.
            $cancelled = $this->scheduler->cancel($appointment, $prefixed, isCustomerAction: true);

            // Phase 10 — dispatch the cancel
            // confirmation SMS AFTER the transaction
            // commits. The dispatcher's enqueue is
            // wrapped in its own short transaction;
            // failure here is logged but does not
            // surface to the controller (the customer
            // already has their cancellation).
            DB::afterCommit(function () use ($cancelled, $customer) {
                $this->messageDispatcher->cancelled($cancelled, $customer);
            });

            return $cancelled;
        });
    }

    /**
     * Reschedule an appointment through the customer
     * portal. The new time must be on a slot the
     * availability endpoint would publish (defends
     * against off-grid minutes), the new center
     * must belong to the same tenant, the services
     * stay the same as the original appointment, and
     * the bay is server-picked.
     *
     * The status + 2h window + tenant + customer
     * ownership are re-checked AFTER the appointment
     * row is locked, so a concurrent check-in,
     * cancellation, or conversion cannot slip
     * through.
     */
    public function reschedule(
        Tenant $tenant,
        Customer $customer,
        int $appointmentId,
        int $newCenterId,
        Carbon $newStart,
    ): Appointment {
        return DB::transaction(function () use (
            $tenant,
            $customer,
            $appointmentId,
            $newCenterId,
            $newStart,
        ) {
            $appointment = $this->lockOwnAppointment($tenant, $customer, $appointmentId);
            $this->assertActionable($appointment);

            // The customer cannot change the vehicle
            // or the services in this phase. Both
            // are read from the existing appointment.
            $vehicle = $appointment->vehicle;

            if ($vehicle === null) {
                // A scheduling-time invariant says
                // every appointment has a vehicle.
                // If we hit this branch, the row is
                // corrupt and we want to fail loudly
                // rather than book something with
                // no vehicle.
                throw new RuntimeException('vehicle_missing');
            }
            $originalServiceIds = $appointment->services->pluck('id')->all();

            // Center: must exist, be in this tenant,
            // and be active. The controller pre-checks
            // this, but we re-validate under the lock.
            $newCenter = Center::query()
                ->where('tenant_id', $tenant->id)
                ->where('is_active', true)
                ->whereKey($newCenterId)
                ->first();

            if ($newCenter === null) {
                throw new RuntimeException('center_unavailable');
            }

            // Services: must still be active, internal,
            // and registered to the new center. A service
            // could have been deactivated, re-categorized,
            // or moved to a different center between the
            // time the customer opened the reschedule
            // form and the time they submitted.
            $services = Service::query()
                ->where('tenant_id', $tenant->id)
                ->where('is_active', true)
                ->where('type', Service::TYPE_INTERNAL)
                ->where('center_id', $newCenter->id)
                ->whereIn('id', $originalServiceIds)
                ->get();

            if ($services->count() !== count($originalServiceIds)) {
                throw new RuntimeException('services_unavailable');
            }

            // Booking window: at least 2h out, at most
            // 14 days out. The 2h check is on the new
            // time, not the old one — moving the
            // appointment 30 minutes into the future
            // is not allowed.
            $now = Carbon::now();
            $earliest = $now->copy()->addHours(self::MIN_HOURS_BEFORE);
            $maxDate = $now->copy()->addDays(self::MAX_LOOKAHEAD_DAYS);

            if ($newStart->lt($earliest) || $newStart->gt($maxDate)) {
                throw new RuntimeException('slot_out_of_window');
            }

            // Slot snapshot: the new scheduled_at must
            // be one of the slots the availability
            // endpoint would publish. This rejects
            // off-grid minutes (e.g. 10:13).
            $slotResult = $this->availability->slots(
                tenantId: $tenant->id,
                centerId: $newCenter->id,
                serviceIds: $originalServiceIds,
                dateYmd: $newStart->copy()->startOfDay()->toDateString(),
            );
            $offered = collect($slotResult['slots'] ?? [])
                ->map(fn ($s) => Carbon::parse($s)->setTimezone($newStart->getTimezone())->timestamp)
                ->all();
            $startTs = $newStart->copy()->setTimezone($newStart->getTimezone())->timestamp;

            if (! in_array($startTs, $offered, true)) {
                throw new RuntimeException('slot_no_longer_available');
            }

            // Duration from the re-loaded service rows.
            $duration = 0;

            foreach ($services as $s) {
                $minutes = $this->serviceMinutes($s);

                if ($minutes !== null) {
                    $duration += $minutes;
                }
            }

            if ($duration <= 0) {
                $duration = CustomerPortalAvailabilityService::FALLBACK_DURATION_MINUTES;
            }
            $newEnd = $newStart->copy()->addMinutes($duration);

            // Server-picked bay. The excludeAppointmentId
            // argument is what lets us move the row
            // out of its own current slot without the
            // bay picker seeing the row as "self
            // conflicting" if the new slot overlaps
            // the old one.
            $newBay = $this->availability->pickBay(
                tenantId: $tenant->id,
                centerId: $newCenter->id,
                scheduledAt: $newStart,
                estimatedEndAt: $newEnd,
                excludeAppointmentId: $appointment->id,
            );

            if ($newBay === null) {
                throw new RuntimeException('slot_no_longer_available');
            }

            // Hand off to the staff scheduler. The
            // staff scheduler takes its own row-level
            // locks on bay + user + vehicle, and (as
            // of the Phase 9 P1 fix) the appointment
            // row again, and re-checks status. By the
            // time it gets here, we have already
            // confirmed the row is in {scheduled,
            // confirmed} and the customer owns it.
            // If a concurrent staff action managed
            // to change the status between our
            // lockForUpdate and the scheduler's own
            // re-read, the staff scheduler will throw
            // and we surface the same generic error
            // to the customer.
            $rescheduled = $this->scheduler->reschedule(
                appointment: $appointment,
                newStart: $newStart,
                newEnd: $newEnd,
                changeBay: true,
                bay: $newBay,
                changeAssignee: false,
                newCenterId: $newCenter->id !== $appointment->center_id ? $newCenter->id : null,
            );

            // Phase 10 — dispatch the rescheduled
            // confirmation SMS AFTER the transaction
            // commits. The dispatcher's enqueue is
            // wrapped in its own short transaction;
            // failure here is logged but does not
            // surface to the controller.
            DB::afterCommit(function () use ($rescheduled, $customer) {
                $this->messageDispatcher->rescheduled($rescheduled, $customer);
            });

            return $rescheduled;
        });
    }

    /**
     * Read-only variant of the action service used
     * by the reschedule form's availability endpoint.
     * We don't need a lock here because the
     * availability view is purely informational —
     * the actual slot is re-validated under the
     * lock at submit time.
     */
    public function availabilityForReschedule(
        Tenant $tenant,
        Customer $customer,
        int $appointmentId,
    ): Appointment {
        $appointment = Appointment::query()
            ->where('tenant_id', $tenant->id)
            ->where('customer_id', $customer->id)
            ->whereKey($appointmentId)
            ->with('services:id')
            ->first();

        if ($appointment === null) {
            abort(404);
        }
        $this->assertActionable($appointment);

        return $appointment;
    }

    /**
     * Read-only load for the reschedule edit page.
     * No lock because the form is just a view; the
     * server-derived duration + slot are recomputed
     * at submit time.
     */
    public function loadForEdit(
        Tenant $tenant,
        Customer $customer,
        int $appointmentId,
    ): Appointment {
        $appointment = Appointment::query()
            ->where('tenant_id', $tenant->id)
            ->where('customer_id', $customer->id)
            ->whereKey($appointmentId)
            ->with([
                'center:id,name,name_ar,name_en,is_active,is_main',
                'services:id,center_id,name_ar,name_en,duration_value,duration_unit',
            ])
            ->first();

        if ($appointment === null) {
            abort(404);
        }
        $this->assertActionable($appointment);

        return $appointment;
    }

    /**
     * Lock the appointment row inside the current
     * transaction. Returns the locked Appointment
     * instance. Throws if the row does not belong
     * to this customer in this tenant — we 404
     * from the controller so the message is
     * generic.
     */
    private function lockOwnAppointment(Tenant $tenant, Customer $customer, int $appointmentId): Appointment
    {
        $appointment = Appointment::query()
            ->where('tenant_id', $tenant->id)
            ->where('customer_id', $customer->id)
            ->whereKey($appointmentId)
            ->lockForUpdate()
            ->with('services:id')
            ->first();

        if ($appointment === null) {
            // Same generic 404 the controller was
            // using. We deliberately do not
            // distinguish "row does not exist"
            // from "row exists but in another
            // tenant" — the existence itself is
            // private.
            abort(404);
        }

        return $appointment;
    }

    /**
     * Re-check the eligibility rules AFTER the
     * appointment row is locked. We never use
     * this to expose a specific failure reason
     * to the customer; the controller maps every
     * rejection to the same 404.
     */
    private function assertActionable(Appointment $appointment): void
    {
        $allowedFrom = [
            Appointment::STATUS_SCHEDULED,
            Appointment::STATUS_CONFIRMED,
        ];

        if (! in_array($appointment->status, $allowedFrom, true)) {
            abort(404);
        }

        if ($appointment->scheduled_at === null) {
            abort(404);
        }

        $earliest = Carbon::now()->addHours(self::MIN_HOURS_BEFORE);

        if ($appointment->scheduled_at->lt($earliest)) {
            abort(404);
        }
    }

    /**
     * Belt-and-suspenders check that the row is NOT
     * `checked_in`. The customer portal is stricter
     * than the staff-side cancel which (rightly)
     * accepts `checked_in` so an operator can walk
     * a customer out after they arrived. The portal
     * never does this: the operator owns the
     * decision once the customer is on premises.
     */
    private function assertNotCheckedIn(Appointment $appointment): void
    {
        if ($appointment->status === Appointment::STATUS_CHECKED_IN) {
            abort(404);
        }
    }

    private function serviceMinutes(Service $service): ?int
    {
        if (! $service->duration_value) {
            return null;
        }

        return match ($service->duration_unit ?? 'minute') {
            'hour' => (int) $service->duration_value * 60,
            'day' => (int) $service->duration_value * 60 * 24,
            default => (int) $service->duration_value,
        };
    }
}
