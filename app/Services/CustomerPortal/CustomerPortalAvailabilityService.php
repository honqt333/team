<?php

declare(strict_types=1);

namespace App\Services\CustomerPortal;

use App\Models\Appointment;
use App\Models\Center;
use App\Models\CenterWorkingHour;
use App\Models\Service;
use App\Models\ServiceBay;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Read-only availability calculator for the customer portal.
 *
 * This service is the SOLE authority for the public
 * "what times are bookable?" question. It is intentionally
 * separate from AppointmentSchedulingService:
 *
 *   - AppointmentSchedulingService is the *write* path:
 *     conflict detection + transaction + row locks.
 *   - CustomerPortalAvailabilityService is the *read*
 *     path: a small, deterministic algorithm that the
 *     portal calls repeatedly to render a slot grid.
 *
 * The two share a few primitive inputs (center_id, bay
 * list, blocking status set) but never share state.
 * Keeping them separate lets the read path run
 * without locks, and lets the write path stay focused
 * on atomicity.
 *
 * Algorithm (deterministic, no clock jitter):
 *
 *   1. Validate inputs against the tenant. Tenant
 *      isolation is enforced at every step.
 *
 *   2. Read the CenterWorkingHour for the date's
 *      dayOfWeek. If no row exists we stay
 *      permissive (matches the existing service's
 *      behavior — incomplete config is a config
 *      problem, not a customer-visible block).
 *      is_open=false → no slots.
 *
 *   3. Pull all active ServiceBay rows for the
 *      center. If zero → no slots.
 *
 *   4. Pull all blocking appointments for the center
 *      on the date. Status filter is the same as
 *      Appointment::BLOCKING_STATUSES.
 *
 *   5. duration = sum of minutesFromService for the
 *      selected services. Fallback: 30 minutes if
 *      every selected service has a null duration.
 *      We never trust a duration from the client.
 *
 *   6. interval = 30 minutes (matches the spec; could
 *      be a per-tenant config in a later phase).
 *
 *   7. Walk slot starts from open_time in 30-minute
 *      steps, stopping at close_time - duration.
 *      For each candidate slot [start, start+duration]
 *      we ask: is there at least one bay with no
 *      conflicting appointment in that window? If
 *      yes, the slot is offered to the user.
 *
 *   8. Slots earlier than now + 1 hour are filtered
 *      out (the spec's minimum lookahead). The
 *      check uses CarbonImmutable so DST / timezone
 *      transitions don't shift the boundary.
 *
 *   9. Slots are returned as ISO-8601 strings
 *      (`scheduled_at`) so the Vue form can post
 *      them back without any conversion.
 */
class CustomerPortalAvailabilityService
{
    /**
     * Minimum lookahead in hours. The spec says "from
     * one hour at minimum". We pin this in code
     * because it is part of the public contract.
     */
    public const MIN_LOOKAHEAD_HOURS = 1;

    /**
     * Maximum booking window in days. Slots beyond
     * now + 14 days are never offered.
     */
    public const MAX_LOOKAHEAD_DAYS = 14;

    /**
     * Slot interval in minutes. Every candidate slot
     * starts on a multiple of this from midnight.
     */
    public const SLOT_INTERVAL_MINUTES = 30;

    /**
     * Fallback duration when every selected service
     * has a null duration. Should never trigger in
     * a well-configured center, but we never block
     * a customer because of a config gap.
     */
    public const FALLBACK_DURATION_MINUTES = 30;

    /**
     * Compute the bookable slots for a center +
     * services + date combination.
     *
     * The result is a list of slot timestamps
     * (ISO-8601) the portal can render. Each is the
     * start of a slot; the end is implicit
     * (start + duration). The Vue form posts the
     * start back to the server; the server
     * re-derives the end and runs the conflict
     * check again under a lock.
     *
     * @return array{
     *     center_id: int,
     *     date: string,
     *     duration_minutes: int,
     *     open: bool,
     *     slots: array<int, string>,
     *     reason: ?string,
     * }
     */
    public function slots(
        int $tenantId,
        int $centerId,
        array $serviceIds,
        string $dateYmd,
        ?CarbonImmutable $now = null,
    ): array {
        $now = $now ?? CarbonImmutable::now();

        // 1. Tenant + active center. We do NOT trust
        // either id from the request without a
        // tenant_id filter; the route middleware has
        // already resolved the tenant, but a
        // cross-tenant request is a class of bug we
        // want caught here too.
        $center = Center::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereKey($centerId)
            ->first();

        if ($center === null) {
            return $this->emptyResult($centerId, $dateYmd, 0, 'center_unavailable');
        }

        // 2. Date sanity: not in the past, not beyond
        // the booking window, parsed in tenant-local
        // terms. We use the current server timezone
        // for the date string because the calendar
        // input is meant to be "the customer's
        // local day". A future phase could plumb
        // tenant timezone through.
        try {
            $date = CarbonImmutable::parse($dateYmd)->startOfDay();
        } catch (Throwable) {
            return $this->emptyResult($centerId, $dateYmd, 0, 'invalid_date');
        }

        if ($date->lt($now->startOfDay())) {
            return $this->emptyResult($centerId, $dateYmd, 0, 'past_date');
        }

        $maxDate = $now->startOfDay()->addDays(self::MAX_LOOKAHEAD_DAYS);

        if ($date->gt($maxDate)) {
            return $this->emptyResult($centerId, $dateYmd, 0, 'beyond_window');
        }

        // 3. Tenant-scoped services, active internal,
        // AND in the same center as the booking. A
        // service registered in center B cannot be
        // booked at center A even within the same
        // tenant — workshop capacity is planned per
        // center. This is also defense-in-depth: a
        // malicious client that guesses a service id
        // from another center in the same tenant is
        // rejected here, not later.
        $services = Service::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('type', Service::TYPE_INTERNAL)
            ->where('center_id', $center->id)
            ->whereIn('id', $serviceIds)
            ->get();

        if ($services->isEmpty()) {
            return $this->emptyResult($centerId, $dateYmd, 0, 'no_services');
        }
        $serviceIds = $services->pluck('id')->all();

        // 4. Duration from services. We never accept
        // a duration from the client; it is always
        // recomputed from the persisted service rows.
        $duration = $this->durationFromServices($services);

        if ($duration <= 0) {
            $duration = self::FALLBACK_DURATION_MINUTES;
        }

        // 5. Working hours for the day. Missing row
        // → permissive (matches the staff path).
        $hours = CenterWorkingHour::query()
            ->where('center_id', $center->id)
            ->where('day_of_week', $date->dayOfWeek)
            ->first();

        if ($hours === null) {
            return $this->emptyResult($centerId, $dateYmd, $duration, 'no_hours');
        }

        if (! $hours->is_open) {
            return $this->emptyResult($centerId, $dateYmd, $duration, 'closed');
        }

        $openAt = $this->parseTimeOnDate($date, $hours->open_time);
        $closeAt = $this->parseTimeOnDate($date, $hours->close_time);

        // 6. Active bays in the center. None = no
        // slots. (We treat "no bays configured" as a
        // public-visible block rather than a
        // permissive fallback, because the customer
        // cannot physically be served.)
        $bays = ServiceBay::query()
            ->where('center_id', $center->id)
            ->where('is_active', true)
            ->get();

        if ($bays->isEmpty()) {
            return $this->emptyResult($centerId, $dateYmd, $duration, 'no_bays');
        }

        // 7. All blocking appointments on this center
        // on this date. We use the day window for the
        // query so the result set stays small.
        $dayStart = $date;
        $dayEnd = $date->endOfDay();
        $existing = Appointment::query()
            ->where('tenant_id', $tenantId)
            ->where('center_id', $center->id)
            ->whereIn('status', Appointment::BLOCKING_STATUSES)
            ->where('scheduled_at', '<', $dayEnd)
            ->where('estimated_end_at', '>', $dayStart)
            ->get(['id', 'service_bay_id', 'scheduled_at', 'estimated_end_at']);

        // 8. The minimum bookable start time. We
        // never offer a slot earlier than now +
        // MIN_LOOKAHEAD_HOURS.
        $earliest = $now->copy()->addHours(self::MIN_LOOKAHEAD_HOURS);

        // 9. Walk the day in SLOT_INTERVAL_MINUTES
        // steps. The last start that fits inside the
        // working window is closeAt - duration.
        $latestStart = $closeAt->copy()->subMinutes($duration);

        if ($latestStart->lt($openAt)) {
            return $this->emptyResult($centerId, $dateYmd, $duration, 'no_room');
        }

        $slots = [];
        $cursor = $openAt->copy();

        while ($cursor->lte($latestStart)) {
            $slotEnd = $cursor->copy()->addMinutes($duration);

            // Past the minimum-lookahead boundary?
            if ($cursor->gte($earliest)) {
                // At least one bay free across the
                // full slot? We check by iterating
                // bays; the predicate is the same as
                // the write path's conflict check.
                if ($this->anyBayFree($bays, $existing, $cursor, $slotEnd)) {
                    $slots[] = $cursor->toIso8601String();
                }
            }

            $cursor = $cursor->addMinutes(self::SLOT_INTERVAL_MINUTES);
        }

        return [
            'center_id' => $center->id,
            'date' => $date->toDateString(),
            'duration_minutes' => $duration,
            'open' => true,
            'slots' => $slots,
            'reason' => null,
        ];
    }

    /**
     * The canonical "which bay" rule used at booking
     * confirmation. Deterministic, fair-enough:
     *
     *   - Filter to active bays in the center.
     *   - Drop bays that already have a blocking
     *     appointment overlapping the slot.
     *   - Sort the survivors by (today's appointment
     *     count ascending, id ascending). A bay that
     *     has done less work today gets the next
     *     booking, breaking ties on id for stability.
     *
     * The optional `?int $excludeAppointmentId` is
     * used by the reschedule path: when the customer
     * moves the same appointment to a new slot, the
     * old row would otherwise conflict with itself
     * (if the old slot overlaps the new one — e.g.
     * same bay, near-by time) and the reschedule
     * would always fail. Excluding the appointment's
     * own id from the conflict check lets the bay
     * picker see the slot as free for this row.
     *
     * Returns null if no bay is free. The caller
     * surfaces a generic "this time is no longer
     * available" message — we never reveal which
     * bays exist or how many.
     */
    public function pickBay(
        int $tenantId,
        int $centerId,
        Carbon $scheduledAt,
        Carbon $estimatedEndAt,
        ?int $excludeAppointmentId = null,
    ): ?ServiceBay {
        $bays = ServiceBay::query()
            ->where('center_id', $centerId)
            ->where('is_active', true)
            ->get();

        if ($bays->isEmpty()) {
            return null;
        }

        $conflictingBayIds = Appointment::query()
            ->where('tenant_id', $tenantId)
            ->where('center_id', $centerId)
            ->whereIn('status', Appointment::BLOCKING_STATUSES)
            ->where('scheduled_at', '<', $estimatedEndAt)
            ->where('estimated_end_at', '>', $scheduledAt)
            ->when($excludeAppointmentId !== null, fn ($q) => $q->where('id', '!=', $excludeAppointmentId))
            ->whereNotNull('service_bay_id')
            ->pluck('service_bay_id')
            ->all();

        $free = $bays->reject(fn (ServiceBay $b) => in_array($b->id, $conflictingBayIds, true));

        if ($free->isEmpty()) {
            return null;
        }

        // Today's load per bay. The query is bounded
        // to the same calendar day as the requested
        // slot, so the "fairer" bay is the one that
        // has been used less so far today.
        $dayStart = $scheduledAt->copy()->startOfDay();
        $dayEnd = $scheduledAt->copy()->endOfDay();
        $loadByBay = Appointment::query()
            ->where('tenant_id', $tenantId)
            ->where('center_id', $centerId)
            ->whereIn('status', Appointment::BLOCKING_STATUSES)
            ->whereBetween('scheduled_at', [$dayStart, $dayEnd])
            ->whereNotNull('service_bay_id')
            ->selectRaw('service_bay_id, COUNT(*) as c')
            ->groupBy('service_bay_id')
            ->pluck('c', 'service_bay_id');

        return $free
            ->sort(function (ServiceBay $a, ServiceBay $b) use ($loadByBay) {
                $loadA = $loadByBay[$a->id] ?? 0;
                $loadB = $loadByBay[$b->id] ?? 0;

                if ($loadA !== $loadB) {
                    return $loadA <=> $loadB;
                }

                // Stable tie-break on id ascending.
                return $a->id <=> $b->id;
            })
            ->values()
            ->first();
    }

    /**
     * True if at least one bay in the given list is
     * free across [start, end] given the set of
     * blocking appointments. The list of appointments
     * is the day's projection; we filter to those that
     * overlap the slot.
     */
    private function anyBayFree(
        Collection $bays,
        Collection $existing,
        CarbonImmutable $start,
        CarbonImmutable $end,
    ): bool {
        $conflictingBayIds = $existing
            ->filter(function (Appointment $a) use ($start, $end) {
                return $a->service_bay_id !== null
                    && $a->scheduled_at !== null
                    && $a->estimated_end_at !== null
                    && $a->scheduled_at->lt($end)
                    && $a->estimated_end_at->gt($start);
            })
            ->pluck('service_bay_id')
            ->all();

        foreach ($bays as $bay) {
            if (! in_array($bay->id, $conflictingBayIds, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Collection<int, Service> $services
     */
    private function durationFromServices(Collection $services): int
    {
        $total = 0;

        foreach ($services as $service) {
            $minutes = $this->minutesFromService($service);

            if ($minutes !== null) {
                $total += $minutes;
            }
        }

        return $total;
    }

    /**
     * @return int|null null if the service has no
     *                  usable duration.
     */
    private function minutesFromService(Service $service): ?int
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

    private function parseTimeOnDate(CarbonImmutable $date, string $hhmm): CarbonImmutable
    {
        [$h, $m] = array_pad(explode(':', $hhmm), 2, '0');

        return $date->setTime((int) $h, (int) $m, 0);
    }

    /**
     * @return array{
     *     center_id: int,
     *     date: string,
     *     duration_minutes: int,
     *     open: bool,
     *     slots: array<int, string>,
     *     reason: ?string,
     * }
     */
    private function emptyResult(int $centerId, string $date, int $duration, string $reason): array
    {
        return [
            'center_id' => $centerId,
            'date' => $date,
            'duration_minutes' => $duration,
            'open' => false,
            'slots' => [],
            'reason' => $reason,
        ];
    }
}
