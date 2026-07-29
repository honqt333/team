<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Appointment;
use App\Models\CenterWorkingHour;
use App\Models\Customer;
use App\Models\Service;
use App\Models\ServiceBay;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Owns the appointment scheduling rules. Single source of truth
 * for the create / update / check-in / cancel / no-show flow and
 * the conflict checks. The controller delegates here; the Vue UI
 * also calls index/availability but the backend is authoritative.
 *
 * Conflict policy (see Phase 4 spec):
 *   - scheduled_at must be in the future (on update we relax
 *     this for past appointments that already exist).
 *   - estimated_end_at must be strictly after scheduled_at.
 *   - the bay cannot be double-booked if it is set.
 *   - the assigned technician cannot be double-booked if set.
 *   - the vehicle cannot be double-booked inside the same center.
 *   - cancelled / no_show / converted do NOT block a new booking.
 *   - working hours are checked when defined; if a CenterWorkingHour
 *     row exists for the day and is_open=false, we reject.
 *
 * All checks run inside a DB transaction with SELECT ... FOR UPDATE
 * on the bay / user rows so two concurrent requests cannot both
 * pass the "no conflict" check.
 */
class AppointmentSchedulingService
{
    /**
     * @param array<int> $serviceIds
     * @param array<string, mixed> $overrides
     */
    public function create(
        Carbon $scheduledAt,
        Carbon $estimatedEndAt,
        Customer $customer,
        Vehicle $vehicle,
        ?ServiceBay $bay,
        ?User $assignedUser,
        array $serviceIds,
        array $overrides = [],
    ): Appointment {
        $this->assertSameContext($customer, $vehicle, $bay, $assignedUser);
        $this->assertTimeWindow($scheduledAt, $estimatedEndAt, true);
        $this->assertWorkingHours($customer->center_id, $scheduledAt, $estimatedEndAt);

        // Internal create ALWAYS lands in `scheduled`. Every other
        // status (confirmed / checked_in / converted / cancelled)
        // is reached through a state transition or by the
        // conversion service. We refuse to be tricked into
        // creating a "converted" appointment via the create path.
        if (isset($overrides['status']) && $overrides['status'] !== Appointment::STATUS_SCHEDULED) {
            throw new RuntimeException('invalid_initial_status');
        }

        return DB::transaction(function () use (
            $scheduledAt, $estimatedEndAt, $customer, $vehicle, $bay, $assignedUser, $serviceIds, $overrides
        ) {
            // Lock the bay, the user (if any), and the vehicle.
            // The vehicle lock is what serializes the
            // "is this vehicle already booked?" check against a
            // parallel insert. With the row pinned, the other
            // transaction waits for our commit / rollback.
            $this->lockConflictSources($bay, $assignedUser, $vehicle);

            $this->assertNoConflicts(
                $customer->center_id,
                $scheduledAt,
                $estimatedEndAt,
                $bay,
                $assignedUser,
                $vehicle,
                excludeAppointmentId: null
            );

            $appointment = Appointment::create(array_merge([
                'tenant_id' => $customer->tenant_id,
                'center_id' => $customer->center_id,
                'customer_id' => $customer->id,
                'vehicle_id' => $vehicle->id,
                'service_bay_id' => $bay?->id,
                'assigned_user_id' => $assignedUser?->id,
                'scheduled_at' => $scheduledAt,
                'estimated_end_at' => $estimatedEndAt,
                'status' => $overrides['status'] ?? Appointment::STATUS_SCHEDULED,
                'source' => $overrides['source'] ?? Appointment::SOURCE_INTERNAL,
                'notes' => $overrides['notes'] ?? null,
            ]));

            $this->attachServices($appointment, $serviceIds);

            return $appointment;
        });
    }

    /**
     * Move a previously-booked appointment to a new time window.
     *
     * Resource resolution:
     *   - `changeBay = true` and `?ServiceBay $bay` = a new bay:
     *     swap to that bay.
     *   - `changeBay = true` and `$bay = null`: clear the bay
     *     entirely (the appointment becomes "no specific bay").
     *   - `changeBay = false`: keep the existing bay.
     *
     * The same three-state resolution applies to
     * `changeAssignee` / `?User $assignedUser`. We need the
     * explicit "change vs keep vs clear" distinction because
     * `?ServiceBay $bay = null` is ambiguous in PHP — null can
     * mean "not passed" or "explicitly null".
     */
    public function reschedule(
        Appointment $appointment,
        Carbon $newStart,
        Carbon $newEnd,
        bool $changeBay = false,
        ?ServiceBay $bay = null,
        bool $changeAssignee = false,
        ?User $assignedUser = null,
    ): Appointment {
        // Reschedule is "editing a live appointment". Converted,
        // cancelled, and no_show appointments are immutable here.
        $allowedFrom = [Appointment::STATUS_SCHEDULED, Appointment::STATUS_CONFIRMED];
        $this->assertTransitionAllowed($appointment, $allowedFrom, 'reschedule');

        $this->assertTimeWindow($newStart, $newEnd, false);
        $this->assertWorkingHours($appointment->center_id, $newStart, $newEnd);

        // Resolve the FINAL bay and user we'll check + persist.
        // $changeBay=true  → use $bay (which may itself be null = cleared)
        // $changeBay=false → keep $appointment->service_bay
        $finalBay = $changeBay ? $bay : $appointment->serviceBay;
        $finalUser = $changeAssignee ? $assignedUser : $appointment->assignedUser;

        // Cross-context check on the final resources — defense in
        // depth in case the controller skipped validation.
        if ($finalBay && $finalBay->center_id !== $appointment->center_id) {
            throw new RuntimeException('cross_tenant_or_center');
        }

        if ($finalUser && $finalUser->tenant_id !== $appointment->tenant_id) {
            throw new RuntimeException('cross_tenant_or_center');
        }

        return DB::transaction(function () use ($appointment, $newStart, $newEnd, $finalBay, $finalUser) {
            // Lock the FINAL bay, the FINAL user, and the vehicle
            // — the row we lock must match the row we'll check.
            $this->lockConflictSources($finalBay, $finalUser, $appointment->vehicle);

            // Re-check conflicts with the FINAL resources inside
            // the locked transaction. This is what makes
            // "swap to a different bay" safe: the check uses the
            // same ids the update will persist.
            $this->assertNoConflicts(
                $appointment->center_id,
                $newStart,
                $newEnd,
                $finalBay,
                $finalUser,
                $appointment->vehicle,
                excludeAppointmentId: $appointment->id
            );

            $appointment->update([
                'scheduled_at' => $newStart,
                'estimated_end_at' => $newEnd,
                'service_bay_id' => $finalBay?->id,
                'assigned_user_id' => $finalUser?->id,
            ]);

            return $appointment->refresh();
        });
    }

    public function checkIn(Appointment $appointment): Appointment
    {
        return DB::transaction(function () use ($appointment) {
            // Lock the row, re-read the latest status, then
            // enforce the transition. Doing all three inside
            // the same transaction is what stops two parallel
            // requests from both passing the pre-check on a
            // shared snapshot.
            $fresh = Appointment::query()
                ->whereKey($appointment->id)
                ->lockForUpdate()
                ->first();

            if (! $fresh) {
                throw new RuntimeException('appointment_not_found');
            }

            // Terminal states (cancelled, no_show, converted) are
            // one-way. Re-checking-in a converted appointment would
            // make the work order's life cycle ambiguous, and
            // re-checking-in a cancelled one contradicts the cancel.
            $allowedFrom = [Appointment::STATUS_SCHEDULED, Appointment::STATUS_CONFIRMED];
            $this->assertTransitionAllowed($fresh, $allowedFrom, 'check_in');

            $fresh->update([
                'status' => Appointment::STATUS_CHECKED_IN,
                'checked_in_at' => Carbon::now(),
            ]);

            return $fresh->refresh();
        });
    }

    public function markNoShow(Appointment $appointment): Appointment
    {
        return DB::transaction(function () use ($appointment) {
            $fresh = Appointment::query()
                ->whereKey($appointment->id)
                ->lockForUpdate()
                ->first();

            if (! $fresh) {
                throw new RuntimeException('appointment_not_found');
            }

            // Only meaningful from a not-yet-terminal state. We
            // refuse to mark a converted or cancelled appointment as
            // no-show — that would silently rewrite history.
            $allowedFrom = [Appointment::STATUS_SCHEDULED, Appointment::STATUS_CONFIRMED];
            $this->assertTransitionAllowed($fresh, $allowedFrom, 'no_show');

            $fresh->update(['status' => Appointment::STATUS_NO_SHOW]);

            return $fresh->refresh();
        });
    }

    public function cancel(Appointment $appointment, ?string $reason = null): Appointment
    {
        return DB::transaction(function () use ($appointment, $reason) {
            $fresh = Appointment::query()
                ->whereKey($appointment->id)
                ->lockForUpdate()
                ->first();

            if (! $fresh) {
                throw new RuntimeException('appointment_not_found');
            }

            // You can cancel something that is still alive. You
            // cannot un-convert or un-cancel by re-cancelling.
            $allowedFrom = [
                Appointment::STATUS_SCHEDULED,
                Appointment::STATUS_CONFIRMED,
                Appointment::STATUS_CHECKED_IN,
            ];
            $this->assertTransitionAllowed($fresh, $allowedFrom, 'cancel');

            $fresh->update([
                'status' => Appointment::STATUS_CANCELLED,
                'cancelled_at' => Carbon::now(),
                'cancellation_reason' => $reason,
            ]);

            return $fresh->refresh();
        });
    }

    // ==================== Conflict detection ====================

    /**
     * Detect any conflicting appointment. A "conflict" is an
     * appointment that:
     *   1. is in BLOCKING_STATUSES (scheduled / confirmed / checked_in)
     *   2. shares the bay, the user, or the vehicle
     *   3. time-range overlaps (half-open intervals)
     *
     * @throws RuntimeException with a specific code the controller
     *                          maps to an HTTP status.
     */
    private function assertNoConflicts(
        int $centerId,
        Carbon $start,
        Carbon $end,
        ?ServiceBay $bay,
        ?User $assignedUser,
        Vehicle $vehicle,
        ?int $excludeAppointmentId,
    ): void {
        $base = Appointment::query()
            ->where('center_id', $centerId)
            ->whereIn('status', Appointment::BLOCKING_STATUSES)
            ->where('scheduled_at', '<', $end)
            ->where('estimated_end_at', '>', $start);

        if ($excludeAppointmentId !== null) {
            $base->where('id', '!=', $excludeAppointmentId);
        }

        if ($bay) {
            $bayHit = (clone $base)->where('service_bay_id', $bay->id)->first();

            if ($bayHit) {
                throw new RuntimeException('bay_conflict');
            }
        }

        if ($assignedUser) {
            $userHit = (clone $base)->where('assigned_user_id', $assignedUser->id)->first();

            if ($userHit) {
                throw new RuntimeException('technician_conflict');
            }
        }

        $vehicleHit = (clone $base)->where('vehicle_id', $vehicle->id)->first();

        if ($vehicleHit) {
            throw new RuntimeException('vehicle_conflict');
        }
    }

    /**
     * Acquire row-level locks on the bay, the assigned user, and
     * the vehicle. This is the serializer that prevents two
     * parallel transactions from both passing the conflict check.
     *
     * The vehicle lock is critical: without it, a check on
     * `appointments where vehicle_id = X` could see a snapshot
     * taken just before the other transaction committed. The
     * vehicle row itself exists, so lockForUpdate on it pins the
     * other transaction until our commit (or rollback) is done.
     *
     * Order is intentional: bay first, then user, then vehicle.
     * No semantic meaning, just keeps the same ordering across
     * requests so the lock acquisition never deadlocks against
     * another transaction that grabbed them in the same order.
     */
    private function lockConflictSources(
        ?ServiceBay $bay,
        ?User $assignedUser,
        Vehicle $vehicle,
    ): void {
        if ($bay) {
            ServiceBay::query()->whereKey($bay->id)->lockForUpdate()->first();
        }

        if ($assignedUser) {
            User::query()->whereKey($assignedUser->id)->lockForUpdate()->first();
        }
        Vehicle::query()->whereKey($vehicle->id)->lockForUpdate()->first();
    }

    private function assertTimeWindow(Carbon $start, Carbon $end, bool $rejectPast): void
    {
        if (! $end->gt($start)) {
            throw new RuntimeException('end_before_start');
        }

        if ($rejectPast && $start->lt(Carbon::now())) {
            throw new RuntimeException('start_in_past');
        }
    }

    /**
     * Single source of truth for "this transition is allowed".
     * Each caller passes the list of source states from which
     * the action is legal; everything else is forbidden.
     */
    private function assertTransitionAllowed(
        Appointment $appointment,
        array $allowedFrom,
        string $actionCode,
    ): void {
        if (! in_array($appointment->status, $allowedFrom, true)) {
            throw new RuntimeException("invalid_transition_{$actionCode}");
        }
    }

    private function assertWorkingHours(int $centerId, Carbon $start, Carbon $end): void
    {
        $hours = CenterWorkingHour::query()
            ->where('center_id', $centerId)
            ->where('day_of_week', $start->dayOfWeek)
            ->first();

        // If the center has no working-hour rows for the day we
        // stay permissive — better to let the operator decide than
        // to hard-block on incomplete configuration.
        if (! $hours) {
            return;
        }

        if (! $hours->is_open) {
            throw new RuntimeException('center_closed');
        }

        $open = $this->parseTimeOnDate($start, $hours->open_time);
        $close = $this->parseTimeOnDate($start, $hours->close_time);

        if ($start->lt($open) || $end->gt($close)) {
            throw new RuntimeException('outside_working_hours');
        }
    }

    private function parseTimeOnDate(Carbon $date, string $hhmm): Carbon
    {
        [$h, $m] = array_pad(explode(':', $hhmm), 2, '0');

        return $date->copy()->setTime((int) $h, (int) $m, 0);
    }

    // ==================== Cross-context ====================

    private function assertSameContext(
        Customer $customer,
        Vehicle $vehicle,
        ?ServiceBay $bay,
        ?User $assignedUser,
    ): void {
        if ($vehicle->customer_id !== $customer->id) {
            throw new RuntimeException('vehicle_not_owned_by_customer');
        }

        if ($vehicle->tenant_id !== $customer->tenant_id
            || $vehicle->center_id !== $customer->center_id) {
            throw new RuntimeException('cross_tenant_or_center');
        }

        if ($bay) {
            if ($bay->tenant_id !== $customer->tenant_id
                || $bay->center_id !== $customer->center_id) {
                throw new RuntimeException('cross_tenant_or_center');
            }

            if (! $bay->is_active) {
                throw new RuntimeException('bay_inactive');
            }
        }

        if ($assignedUser) {
            if ($assignedUser->tenant_id !== $customer->tenant_id) {
                throw new RuntimeException('cross_tenant_or_center');
            }
            // Confirm the user is actually assigned to this center
            // via the centers pivot. super_admin bypass is the
            // standard escape hatch.
            $isAssigned = $assignedUser->centers()
                ->where('centers.id', $customer->center_id)
                ->exists();

            if (! $isAssigned && ! $assignedUser->hasRole('super_admin')) {
                throw new RuntimeException('user_not_in_center');
            }
        }
    }

    private function attachServices(Appointment $appointment, array $serviceIds): void
    {
        if (empty($serviceIds)) {
            return;
        }

        $services = Service::query()
            ->whereIn('id', $serviceIds)
            ->where('center_id', $appointment->center_id)
            ->get();

        $sync = [];

        foreach ($services as $service) {
            $sync[$service->id] = [
                'estimated_minutes' => $this->minutesFromService($service),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $appointment->services()->sync($sync);
    }

    private function minutesFromService(Service $service): ?int
    {
        if (! $service->duration_value) {
            return null;
        }
        $unit = $service->duration_unit ?? 'minute';

        return match ($unit) {
            'hour' => (int) $service->duration_value * 60,
            'day' => (int) $service->duration_value * 60 * 24,
            default => (int) $service->duration_value,
        };
    }

    /**
     * Public helper for the controller / Vue UI: returns the
     * estimated minutes for a set of services (sum).
     *
     * @param array<int> $serviceIds
     */
    public function estimateMinutes(array $serviceIds): int
    {
        if (empty($serviceIds)) {
            return 30; // sane default for a no-service appointment
        }

        $services = Service::query()->whereIn('id', $serviceIds)->get();

        $total = 0;

        foreach ($services as $service) {
            $total += $this->minutesFromService($service) ?? 30;
        }

        return max(15, $total);
    }
}
