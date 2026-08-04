<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Center;
use App\Models\CenterWorkingHour;
use App\Models\Customer;
use App\Models\Service;
use App\Models\ServiceBay;
use App\Models\Tenant;
use App\Models\TenantWebsite;
use App\Models\Vehicle;
use App\Services\CustomerPortal\CustomerPortalAvailabilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Phase 8 — Availability calculator (read path).
 *
 * The CustomerPortalAvailabilityService is the SOLE
 * authority for "what times are bookable?" on the public
 * portal. The tests here pin its contract:
 *
 *   - closed days and outside-hours ranges return no slots
 *   - the minimum-lookahead filter strips slots earlier
 *     than now + 1 hour
 *   - the maximum-window filter strips dates beyond
 *     now + 14 days
 *   - past dates and malformed dates return empty
 *   - the slot grid stops at close - duration so a 60-min
 *     service can't end after closing
 *   - the service-center filter is honored (a service
 *     from center B does not appear as bookable at
 *     center A even within the same tenant)
 *   - the tenant filter is honored (a service in tenant
 *     A is invisible to tenant B)
 *   - anyBayFree correctly drops slots that have every
 *     bay already booked
 */
class CustomerPortalAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Pin "now" to a stable mid-morning slot so the
        // lookahead / window math is deterministic across
        // CI runs and across clock skew.
        Carbon::setTestNow(Carbon::parse('2026-08-10 09:00:00'));

        // Wipe the rate-limiter buckets so a test
        // that exercises the throttle does not
        // pollute the next one. The phpunit env
        // uses the array cache driver, but the
        // buckets survive within the same
        // process — `Cache::flush()` makes each
        // test own a fresh cache.
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function publishedTenant(string $slug = 'demo'): Tenant
    {
        $tenant = Tenant::factory()->create(['slug' => $slug]);
        TenantWebsite::factory()->for($tenant)->published()->create();

        return $tenant;
    }

    private function customerIn(Tenant $tenant): Customer
    {
        return Customer::factory()->create([
            'tenant_id' => $tenant->id,
            'phone' => '+966501234567',
            'whatsapp' => '+966501234567',
        ]);
    }

    private function openCenter(Tenant $tenant, string $open = '08:00', string $close = '18:00'): Center
    {
        $center = Center::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
        ]);

        // Open every day of the week.
        for ($dow = 0; $dow <= 6; $dow++) {
            CenterWorkingHour::create([
                'tenant_id' => $tenant->id,
                'center_id' => $center->id,
                'day_of_week' => $dow,
                'is_open' => true,
                'open_time' => $open,
                'close_time' => $close,
            ]);
        }

        return $center;
    }

    private function closedDayCenter(Tenant $tenant, int $dayOfWeek): Center
    {
        $center = $this->openCenter($tenant);
        CenterWorkingHour::where('center_id', $center->id)
            ->where('day_of_week', $dayOfWeek)
            ->update(['is_open' => false]);

        return $center;
    }

    private function bayIn(Center $center, string $name = 'Bay A', bool $isActive = true): ServiceBay
    {
        return ServiceBay::create([
            'tenant_id' => $center->tenant_id,
            'center_id' => $center->id,
            'name' => $name,
            'code' => strtoupper(substr($name, -3)),
            'is_active' => $isActive,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // Closed day
    // ─────────────────────────────────────────────────────────────────

    public function test_closed_day_returns_no_slots(): void
    {
        $tenant = $this->publishedTenant();
        $tomorrow = Carbon::now()->addDay();
        $center = $this->closedDayCenter($tenant, $tomorrow->dayOfWeek);
        $this->bayIn($center);
        $service = Service::factory()->forTenant($tenant, $center)->create([
            'duration_value' => 30,
            'duration_unit' => 'minute',
        ]);

        $result = app(CustomerPortalAvailabilityService::class)->slots(
            tenantId: $tenant->id,
            centerId: $center->id,
            serviceIds: [$service->id],
            dateYmd: $tomorrow->toDateString(),
        );

        $this->assertFalse($result['open']);
        $this->assertSame('closed', $result['reason']);
        $this->assertSame([], $result['slots']);
    }

    public function test_no_working_hours_returns_no_hours_reason(): void
    {
        $tenant = $this->publishedTenant();
        $center = Center::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
        ]);
        // No CenterWorkingHour rows for this center.
        $this->bayIn($center);
        $service = Service::factory()->forTenant($tenant, $center)->create();

        $result = app(CustomerPortalAvailabilityService::class)->slots(
            tenantId: $tenant->id,
            centerId: $center->id,
            serviceIds: [$service->id],
            dateYmd: Carbon::now()->addDay()->toDateString(),
        );

        $this->assertFalse($result['open']);
        $this->assertSame('no_hours', $result['reason']);
    }

    // ─────────────────────────────────────────────────────────────────
    // Past / too-soon / too-far
    // ─────────────────────────────────────────────────────────────────

    public function test_past_date_returns_no_slots(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $this->bayIn($center);
        $service = Service::factory()->forTenant($tenant, $center)->create();

        $result = app(CustomerPortalAvailabilityService::class)->slots(
            tenantId: $tenant->id,
            centerId: $center->id,
            serviceIds: [$service->id],
            dateYmd: Carbon::now()->subDay()->toDateString(),
        );

        $this->assertFalse($result['open']);
        $this->assertSame('past_date', $result['reason']);
    }

    public function test_beyond_14_days_returns_no_slots(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $this->bayIn($center);
        $service = Service::factory()->forTenant($tenant, $center)->create();

        $result = app(CustomerPortalAvailabilityService::class)->slots(
            tenantId: $tenant->id,
            centerId: $center->id,
            serviceIds: [$service->id],
            dateYmd: Carbon::now()->addDays(15)->toDateString(),
        );

        $this->assertFalse($result['open']);
        $this->assertSame('beyond_window', $result['reason']);
    }

    public function test_invalid_date_string_returns_no_slots(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $this->bayIn($center);
        $service = Service::factory()->forTenant($tenant, $center)->create();

        $result = app(CustomerPortalAvailabilityService::class)->slots(
            tenantId: $tenant->id,
            centerId: $center->id,
            serviceIds: [$service->id],
            dateYmd: 'not-a-date',
        );

        $this->assertFalse($result['open']);
        $this->assertSame('invalid_date', $result['reason']);
    }

    public function test_min_lookahead_filters_earlier_than_one_hour(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant, '08:00', '18:00');
        $this->bayIn($center);
        $service = Service::factory()->forTenant($tenant, $center)->create([
            'duration_value' => 30,
            'duration_unit' => 'minute',
        ]);

        // Carbon is pinned to 09:00 — slots earlier than
        // 10:00 should be dropped, slots 10:00 onward
        // should remain.
        $result = app(CustomerPortalAvailabilityService::class)->slots(
            tenantId: $tenant->id,
            centerId: $center->id,
            serviceIds: [$service->id],
            dateYmd: Carbon::now()->toDateString(),
        );

        $this->assertTrue($result['open']);
        $earliest = collect($result['slots'])
            ->map(fn ($s) => Carbon::parse($s))
            ->sortBy(fn (Carbon $c) => $c->getTimestamp())
            ->first();
        $this->assertNotNull($earliest);
        $this->assertTrue(
            $earliest->gte(Carbon::now()->addHour()),
            "Earliest slot {$earliest->toIso8601String()} should be >= 10:00"
        );
    }

    // ─────────────────────────────────────────────────────────────────
    // Bay + center shape
    // ─────────────────────────────────────────────────────────────────

    public function test_no_active_bays_returns_no_slots(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        // No bays in the center.
        $service = Service::factory()->forTenant($tenant, $center)->create();

        $result = app(CustomerPortalAvailabilityService::class)->slots(
            tenantId: $tenant->id,
            centerId: $center->id,
            serviceIds: [$service->id],
            dateYmd: Carbon::now()->addDay()->toDateString(),
        );

        $this->assertFalse($result['open']);
        $this->assertSame('no_bays', $result['reason']);
    }

    public function test_inactive_bays_do_not_count(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $this->bayIn($center, 'Inactive', false);
        $service = Service::factory()->forTenant($tenant, $center)->create([
            'duration_value' => 30,
            'duration_unit' => 'minute',
        ]);

        $result = app(CustomerPortalAvailabilityService::class)->slots(
            tenantId: $tenant->id,
            centerId: $center->id,
            serviceIds: [$service->id],
            dateYmd: Carbon::now()->addDay()->toDateString(),
        );

        $this->assertFalse($result['open']);
        $this->assertSame('no_bays', $result['reason']);
    }

    public function test_slot_stops_at_close_minus_duration(): void
    {
        $tenant = $this->publishedTenant();
        // 8-10 working window, 90-min service. The
        // last valid start is 8:30 (8:30 + 90 = 10:00).
        $center = $this->openCenter($tenant, '08:00', '10:00');
        $this->bayIn($center);
        $service = Service::factory()->forTenant($tenant, $center)->create([
            'duration_value' => 90,
            'duration_unit' => 'minute',
        ]);

        $result = app(CustomerPortalAvailabilityService::class)->slots(
            tenantId: $tenant->id,
            centerId: $center->id,
            serviceIds: [$service->id],
            dateYmd: Carbon::now()->addDay()->toDateString(),
        );

        $this->assertTrue($result['open']);
        $this->assertSame(90, $result['duration_minutes']);
        // The grid runs 08:00, 08:30 in 30-min steps.
        // 08:30 + 90 min = 10:00 (== close), 09:00 +
        // 90 = 10:30 > close so excluded. Earliest
        // slot is filtered by MIN_LOOKAHEAD, so on
        // tomorrow the earliest is 10:00 (09:00 + 1h)
        // — outside the 8:00-10:00 window. So the
        // result is empty. We use today+0 (pinned to
        // 09:00) and assert no slot starts at or after
        // 09:30.
        // Re-pin to 00:00 to make 08:00 the earliest
        // and keep the rest of the test meaningful.
        Carbon::setTestNow(Carbon::parse('2026-08-10 00:00:00'));
        $result = app(CustomerPortalAvailabilityService::class)->slots(
            tenantId: $tenant->id,
            centerId: $center->id,
            serviceIds: [$service->id],
            dateYmd: Carbon::now()->toDateString(),
        );
        Carbon::setTestNow(Carbon::parse('2026-08-10 09:00:00'));

        $this->assertTrue($result['open']);
        $starts = collect($result['slots'])
            ->map(fn ($s) => Carbon::parse($s))
            ->map(fn (Carbon $c) => $c->format('H:i'))
            ->all();
        $this->assertContains('08:00', $starts);
        $this->assertContains('08:30', $starts);
        $this->assertNotContains('09:00', $starts);
    }

    // ─────────────────────────────────────────────────────────────────
    // Tenant + center isolation
    // ─────────────────────────────────────────────────────────────────

    public function test_service_from_other_tenant_is_ignored(): void
    {
        $tenantA = $this->publishedTenant('a');
        $centerA = $this->openCenter($tenantA);
        $this->bayIn($centerA);

        $tenantB = $this->publishedTenant('b');
        $centerB = $this->openCenter($tenantB);
        $serviceInB = Service::factory()->forTenant($tenantB, $centerB)->create();

        $result = app(CustomerPortalAvailabilityService::class)->slots(
            tenantId: $tenantA->id,
            centerId: $centerA->id,
            serviceIds: [$serviceInB->id],
            dateYmd: Carbon::now()->addDay()->toDateString(),
        );

        // The service is not visible to tenant A. The
        // calculator should reject the request (count
        // mismatch between requested and visible
        // services → empty result with no_services).
        $this->assertFalse($result['open']);
        $this->assertSame('no_services', $result['reason']);
    }

    public function test_service_from_other_center_in_same_tenant_is_ignored(): void
    {
        $tenant = $this->publishedTenant();
        $centerA = $this->openCenter($tenant);
        $centerB = $this->openCenter($tenant);
        $this->bayIn($centerA);
        $this->bayIn($centerB);

        $serviceInB = Service::factory()->forTenant($tenant, $centerB)->create();

        $result = app(CustomerPortalAvailabilityService::class)->slots(
            tenantId: $tenant->id,
            centerId: $centerA->id,
            serviceIds: [$serviceInB->id],
            dateYmd: Carbon::now()->addDay()->toDateString(),
        );

        $this->assertFalse($result['open']);
        $this->assertSame('no_services', $result['reason']);
    }

    public function test_inactive_service_is_ignored(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $this->bayIn($center);
        $service = Service::factory()->forTenant($tenant, $center)->inactive()->create();

        $result = app(CustomerPortalAvailabilityService::class)->slots(
            tenantId: $tenant->id,
            centerId: $center->id,
            serviceIds: [$service->id],
            dateYmd: Carbon::now()->addDay()->toDateString(),
        );

        $this->assertFalse($result['open']);
        $this->assertSame('no_services', $result['reason']);
    }

    public function test_external_service_is_ignored(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $this->bayIn($center);
        $service = Service::factory()->forTenant($tenant, $center)->external()->create();

        $result = app(CustomerPortalAvailabilityService::class)->slots(
            tenantId: $tenant->id,
            centerId: $center->id,
            serviceIds: [$service->id],
            dateYmd: Carbon::now()->addDay()->toDateString(),
        );

        $this->assertFalse($result['open']);
        $this->assertSame('no_services', $result['reason']);
    }

    public function test_external_center_is_ignored(): void
    {
        $tenant = $this->publishedTenant();
        $inactiveCenter = Center::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => false,
        ]);
        $service = Service::factory()->forTenant($tenant, $inactiveCenter)->create();

        $result = app(CustomerPortalAvailabilityService::class)->slots(
            tenantId: $tenant->id,
            centerId: $inactiveCenter->id,
            serviceIds: [$service->id],
            dateYmd: Carbon::now()->addDay()->toDateString(),
        );

        $this->assertFalse($result['open']);
        $this->assertSame('center_unavailable', $result['reason']);
    }

    // ─────────────────────────────────────────────────────────────────
    // Duration from services
    // ─────────────────────────────────────────────────────────────────

    public function test_duration_is_sum_of_service_durations(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $this->bayIn($center);
        $s1 = Service::factory()->forTenant($tenant, $center)->create([
            'duration_value' => 30,
            'duration_unit' => 'minute',
        ]);
        $s2 = Service::factory()->forTenant($tenant, $center)->create([
            'duration_value' => 1,
            'duration_unit' => 'hour',
        ]);

        $result = app(CustomerPortalAvailabilityService::class)->slots(
            tenantId: $tenant->id,
            centerId: $center->id,
            serviceIds: [$s1->id, $s2->id],
            dateYmd: Carbon::now()->addDay()->toDateString(),
        );

        $this->assertTrue($result['open']);
        $this->assertSame(90, $result['duration_minutes']);
    }

    public function test_duration_falls_back_when_no_service_has_duration(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $this->bayIn($center);
        $s1 = Service::factory()->forTenant($tenant, $center)->create(['duration_value' => null]);
        $s2 = Service::factory()->forTenant($tenant, $center)->create(['duration_value' => null]);

        $result = app(CustomerPortalAvailabilityService::class)->slots(
            tenantId: $tenant->id,
            centerId: $center->id,
            serviceIds: [$s1->id, $s2->id],
            dateYmd: Carbon::now()->addDay()->toDateString(),
        );

        $this->assertTrue($result['open']);
        $this->assertSame(
            CustomerPortalAvailabilityService::FALLBACK_DURATION_MINUTES,
            $result['duration_minutes']
        );
    }

    // ─────────────────────────────────────────────────────────────────
    // Bay conflicts
    // ─────────────────────────────────────────────────────────────────

    public function test_slot_with_all_bays_busy_is_excluded(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant, '08:00', '18:00');
        $bay = $this->bayIn($center);

        // Re-pin now to 00:00 so the morning slots
        // are not filtered out by the lookahead.
        Carbon::setTestNow(Carbon::parse('2026-08-10 00:00:00'));

        $service = Service::factory()->forTenant($tenant, $center)->create([
            'duration_value' => 60,
            'duration_unit' => 'minute',
        ]);

        // Block the bay for the entire morning by
        // placing a 60-min booking at 08:00. After
        // that the bay is free from 09:00 onward, so
        // 08:00 itself should be the only excluded
        // slot.
        Appointment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $this->customerIn($tenant)->id,
            'vehicle_id' => Vehicle::factory()->create([
                'tenant_id' => $tenant->id,
                'customer_id' => $this->customerIn($tenant)->id,
            ])->id,
            'service_bay_id' => $bay->id,
            'scheduled_at' => Carbon::now()->setTime(8, 0),
            'estimated_end_at' => Carbon::now()->setTime(9, 0),
            'status' => 'scheduled',
        ]);

        $result = app(CustomerPortalAvailabilityService::class)->slots(
            tenantId: $tenant->id,
            centerId: $center->id,
            serviceIds: [$service->id],
            dateYmd: Carbon::now()->toDateString(),
        );

        Carbon::setTestNow(Carbon::parse('2026-08-10 09:00:00'));

        $this->assertTrue($result['open']);
        $starts = collect($result['slots'])
            ->map(fn ($s) => Carbon::parse($s)->format('H:i'))
            ->all();
        $this->assertNotContains('08:00', $starts);
        $this->assertContains('09:00', $starts);
    }

    public function test_one_free_bay_is_enough_to_offer_slot(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant, '08:00', '18:00');
        $busy = $this->bayIn($center, 'Busy');
        $free = $this->bayIn($center, 'Free');

        Carbon::setTestNow(Carbon::parse('2026-08-10 00:00:00'));

        $service = Service::factory()->forTenant($tenant, $center)->create([
            'duration_value' => 30,
            'duration_unit' => 'minute',
        ]);

        Appointment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $this->customerIn($tenant)->id,
            'vehicle_id' => Vehicle::factory()->create([
                'tenant_id' => $tenant->id,
                'customer_id' => $this->customerIn($tenant)->id,
            ])->id,
            'service_bay_id' => $busy->id,
            'scheduled_at' => Carbon::now()->setTime(8, 0),
            'estimated_end_at' => Carbon::now()->setTime(12, 0),
            'status' => 'scheduled',
        ]);

        $result = app(CustomerPortalAvailabilityService::class)->slots(
            tenantId: $tenant->id,
            centerId: $center->id,
            serviceIds: [$service->id],
            dateYmd: Carbon::now()->toDateString(),
        );

        Carbon::setTestNow(Carbon::parse('2026-08-10 09:00:00'));

        $this->assertTrue($result['open']);
        $this->assertNotEmpty($result['slots']);
    }

    // ─────────────────────────────────────────────────────────────────
    // pickBay fairness
    // ─────────────────────────────────────────────────────────────────

    public function test_pick_bay_prefers_less_loaded_bay_today(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $busy = $this->bayIn($center, 'Loaded');
        $free = $this->bayIn($center, 'Quiet');

        $customer = $this->customerIn($tenant);
        $vehicle = Vehicle::factory()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
        ]);

        // Load up `busy` with three existing
        // appointments on the same calendar day as
        // the requested slot. The "load" is computed
        // by day, so we put the appointments on
        // tomorrow (the same day we'll ask to book).
        $slot = Carbon::now()->addDay()->setTime(10, 0);

        for ($i = 0; $i < 3; $i++) {
            Appointment::create([
                'tenant_id' => $tenant->id,
                'center_id' => $center->id,
                'customer_id' => $customer->id,
                'vehicle_id' => $vehicle->id,
                'service_bay_id' => $busy->id,
                'scheduled_at' => $slot->copy()->addHours(4 + $i),
                'estimated_end_at' => $slot->copy()->addHours(5 + $i),
                'status' => 'scheduled',
            ]);
        }

        $end = $slot->copy()->addMinutes(30);

        $picked = app(CustomerPortalAvailabilityService::class)->pickBay(
            tenantId: $tenant->id,
            centerId: $center->id,
            scheduledAt: $slot,
            estimatedEndAt: $end,
        );

        $this->assertNotNull($picked);
        $this->assertSame($free->id, $picked->id);
    }

    public function test_pick_bay_returns_null_when_every_bay_busy(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $bay = $this->bayIn($center);

        $customer = $this->customerIn($tenant);
        $vehicle = Vehicle::factory()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
        ]);

        $start = Carbon::now()->addDays(1)->setTime(10, 0);
        $end = $start->copy()->addMinutes(30);

        Appointment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'service_bay_id' => $bay->id,
            'scheduled_at' => $start,
            'estimated_end_at' => $end,
            'status' => 'scheduled',
        ]);

        $picked = app(CustomerPortalAvailabilityService::class)->pickBay(
            tenantId: $tenant->id,
            centerId: $center->id,
            scheduledAt: $start,
            estimatedEndAt: $end,
        );

        $this->assertNull($picked);
    }
}
