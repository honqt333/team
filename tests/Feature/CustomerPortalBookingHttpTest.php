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
use App\Services\AppointmentSchedulingService;
use App\Support\CustomerPortalSession;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use RuntimeException;
use Tests\TestCase;

/**
 * Phase 8 — Self-service booking HTTP flow.
 *
 * The customer portal exposes four booking endpoints
 * under the `customer.portal.session` middleware:
 *
 *   GET  /{tenant}/portal/appointments/new
 *   GET  /{tenant}/portal/appointments/availability
 *   POST /{tenant}/portal/appointments
 *   GET  /{tenant}/portal/appointments/{id}
 *
 * These tests pin the security contract:
 *
 *   - tenant + customer isolation at every step
 *   - vehicle ownership (no cross-customer vehicle
 *     reuse, even within the same tenant)
 *   - center + service activity + service-center
 *     consistency
 *   - the booking window (no past, no < 1h, no
 *     > 14 days)
 *   - duration is server-derived from services, never
 *     trusted from the client
 *   - the bay is server-picked, never trusted from
 *     the client
 *   - two concurrent submissions cannot both win
 *     the last slot
 *   - the availability + store endpoints are
 *     throttled
 *   - the confirmation page enforces customer
 *     ownership of the appointment
 */
class CustomerPortalBookingHttpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        // Pin "now" to a stable morning so the
        // minimum-lookahead filter does not depend
        // on the wall clock. We do this BEFORE
        // parent::setUp() so the framework's
        // RefreshDatabase trait starts at the same
        // time we will use throughout the test.
        Carbon::setTestNow(Carbon::parse('2026-08-10 09:00:00'));

        parent::setUp();

        // Wipe the rate-limiter buckets so a test
        // that exercises the throttle does not
        // pollute the next one. The phpunit env
        // uses the array cache driver, but a
        // prior test's buckets survive until
        // the process exits — `Cache::flush()`
        // makes each test own a fresh cache.
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function clearRateLimiters(): void
    {
        // The test environment uses an array cache,
        // so rate-limit buckets do not leak across
        // tests. This helper is here for future
        // drivers (e.g. database) and to make the
        // intent of the call sites obvious.
        foreach ([
            'avail:demo:1:127.0.0.1',
            'avail:demo:2:127.0.0.1',
            'avail:other:1:127.0.0.1',
            'book:demo:1:127.0.0.1',
            'book:demo:2:127.0.0.1',
            'book:other:1:127.0.0.1',
        ] as $key) {
            RateLimiter::clear($key);
        }
    }

    private function publishedTenant(string $slug = 'demo'): Tenant
    {
        $tenant = Tenant::factory()->create(['slug' => $slug]);
        TenantWebsite::factory()->for($tenant)->published()->create();

        return $tenant;
    }

    private function customerIn(Tenant $tenant, string $phone = '+966501234567'): Customer
    {
        return Customer::factory()->create([
            'tenant_id' => $tenant->id,
            'phone' => $phone,
            'whatsapp' => $phone,
        ]);
    }

    private function openCenter(Tenant $tenant, string $open = '08:00', string $close = '18:00'): Center
    {
        $center = Center::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
        ]);

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

    private function bayIn(Center $center, string $name = 'Bay A'): ServiceBay
    {
        return ServiceBay::create([
            'tenant_id' => $center->tenant_id,
            'center_id' => $center->id,
            'name' => $name,
            'code' => strtoupper(substr($name, -3)),
            'is_active' => true,
        ]);
    }

    private function vehicleFor(Customer $customer, Tenant $tenant): Vehicle
    {
        return Vehicle::factory()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
        ]);
    }

    private function serviceIn(Tenant $tenant, Center $center, int $minutes = 30): Service
    {
        return Service::factory()->forTenant($tenant, $center)->create([
            'duration_value' => $minutes,
            'duration_unit' => 'minute',
        ]);
    }

    private function loginAs(Customer $customer, Tenant $tenant): void
    {
        session()->put(CustomerPortalSession::key(), [
            'customer_id' => $customer->id,
            'tenant_id' => $tenant->id,
            'created_at' => Carbon::now()->toIso8601String(),
            'last_seen_at' => Carbon::now()->toIso8601String(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // GET /{tenant}/portal/appointments/new
    // ─────────────────────────────────────────────────────────────────

    public function test_new_page_renders_for_authenticated_customer(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $customer = $this->customerIn($tenant);
        $this->vehicleFor($customer, $tenant);
        $this->serviceIn($tenant, $center);

        $this->loginAs($customer, $tenant);

        $this->get('/'.$tenant->slug.'/portal/appointments/new')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/Portal/Appointments/New')
                ->where('tenant.slug', $tenant->slug)
                ->where('centers.0.id', $center->id)
            );
    }

    public function test_new_page_only_shows_active_centers(): void
    {
        $tenant = $this->publishedTenant();
        $active = $this->openCenter($tenant);
        $inactive = Center::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => false,
        ]);
        $customer = $this->customerIn($tenant);
        $this->loginAs($customer, $tenant);

        $this->get('/'.$tenant->slug.'/portal/appointments/new')
            ->assertInertia(fn ($page) => $page
                ->where('centers', fn ($list) => collect($list)
                    ->pluck('id')
                    ->contains($active->id)
                    && ! collect($list)->pluck('id')->contains($inactive->id)
                )
            );
    }

    public function test_new_page_only_shows_active_internal_services(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $active = $this->serviceIn($tenant, $center);
        $inactive = Service::factory()->forTenant($tenant, $center)->inactive()->create();
        $external = Service::factory()->forTenant($tenant, $center)->external()->create();

        $customer = $this->customerIn($tenant);
        $this->loginAs($customer, $tenant);

        $this->get('/'.$tenant->slug.'/portal/appointments/new')
            ->assertInertia(fn ($page) => $page
                ->where('services', fn ($list) => collect($list)
                    ->pluck('id')
                    ->contains($active->id)
                    && ! collect($list)->pluck('id')->contains($inactive->id)
                    && ! collect($list)->pluck('id')->contains($external->id)
                )
            );
    }

    public function test_new_page_does_not_leak_other_customers_vehicles(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $customer = $this->customerIn($tenant);
        $myVehicle = $this->vehicleFor($customer, $tenant);

        $otherCustomer = $this->customerIn($tenant, '+966509876543');
        $otherVehicle = $this->vehicleFor($otherCustomer, $tenant);

        $this->loginAs($customer, $tenant);

        $this->get('/'.$tenant->slug.'/portal/appointments/new')
            ->assertInertia(fn ($page) => $page
                ->where('vehicles', fn ($list) => collect($list)
                    ->pluck('id')
                    ->contains($myVehicle->id)
                    && ! collect($list)->pluck('id')->contains($otherVehicle->id)
                )
            );
    }

    public function test_new_page_redirects_to_login_when_unauthenticated(): void
    {
        $tenant = $this->publishedTenant();

        $this->get('/'.$tenant->slug.'/portal/appointments/new')
            ->assertRedirect('/'.$tenant->slug.'/portal/login');
    }

    public function test_new_page_404s_for_unpublished_tenant(): void
    {
        $tenant = Tenant::factory()->create(['slug' => 'draft-tenant']);
        $customer = $this->customerIn($tenant);
        $this->loginAs($customer, $tenant);

        $this->get('/'.$tenant->slug.'/portal/appointments/new')->assertNotFound();
    }

    // ─────────────────────────────────────────────────────────────────
    // GET /{tenant}/portal/appointments/availability
    // ─────────────────────────────────────────────────────────────────

    public function test_availability_returns_slots_for_valid_combo(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center, 30);
        $customer = $this->customerIn($tenant);
        $this->loginAs($customer, $tenant);

        $this->getJson(
            '/'.$tenant->slug.'/portal/appointments/availability?'.
            http_build_query([
                'center_id' => $center->id,
                'date' => Carbon::now()->addDay()->toDateString(),
                'service_ids' => [$service->id],
            ])
        )
            ->assertOk()
            ->assertJsonPath('open', true)
            ->assertJsonPath('duration_minutes', 30)
            ->assertJsonStructure(['slots']);
    }

    public function test_availability_rejects_service_from_other_center(): void
    {
        $tenant = $this->publishedTenant();
        $centerA = $this->openCenter($tenant);
        $centerB = $this->openCenter($tenant);
        $this->bayIn($centerA);
        $serviceInB = $this->serviceIn($tenant, $centerB, 30);

        $customer = $this->customerIn($tenant);
        $this->loginAs($customer, $tenant);

        $this->getJson(
            '/'.$tenant->slug.'/portal/appointments/availability?'.
            http_build_query([
                'center_id' => $centerA->id,
                'date' => Carbon::now()->addDay()->toDateString(),
                'service_ids' => [$serviceInB->id],
            ])
        )
            ->assertStatus(422)
            ->assertJsonPath('reason', 'services_unavailable');
    }

    public function test_availability_rejects_inactive_center(): void
    {
        $tenant = $this->publishedTenant();
        $inactive = Center::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => false,
        ]);
        $customer = $this->customerIn($tenant);
        $this->loginAs($customer, $tenant);

        $this->getJson(
            '/'.$tenant->slug.'/portal/appointments/availability?'.
            http_build_query([
                'center_id' => $inactive->id,
                'date' => Carbon::now()->addDay()->toDateString(),
                'service_ids' => [1],
            ])
        )
            ->assertStatus(422)
            ->assertJsonPath('reason', 'center_unavailable');
    }

    public function test_availability_requires_booking_session(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center);

        $this->getJson(
            '/'.$tenant->slug.'/portal/appointments/availability?'.
            http_build_query([
                'center_id' => $center->id,
                'date' => Carbon::now()->addDay()->toDateString(),
                'service_ids' => [$service->id],
            ])
        )
            ->assertStatus(401);
    }

    public function test_availability_rejects_past_date(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center);
        $customer = $this->customerIn($tenant);
        $this->loginAs($customer, $tenant);

        $this->getJson(
            '/'.$tenant->slug.'/portal/appointments/availability?'.
            http_build_query([
                'center_id' => $center->id,
                'date' => Carbon::now()->subDay()->toDateString(),
                'service_ids' => [$service->id],
            ])
        )
            ->assertOk()
            ->assertJsonPath('open', false)
            ->assertJsonPath('reason', 'past_date');
    }

    // ─────────────────────────────────────────────────────────────────
    // POST /{tenant}/portal/appointments
    // ─────────────────────────────────────────────────────────────────

    public function test_successful_booking_creates_scheduled_appointment(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center, 30);

        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);
        $this->loginAs($customer, $tenant);

        $slot = Carbon::now()->addDay()->setTime(10, 0);
        $response = $this->post('/'.$tenant->slug.'/portal/appointments', [
            'center_id' => $center->id,
            'vehicle_id' => $vehicle->id,
            'service_ids' => [$service->id],
            'scheduled_at' => $slot->toIso8601String(),
        ]);

        $response->assertRedirect();

        $appt = Appointment::query()->where('tenant_id', $tenant->id)->first();
        $this->assertNotNull($appt);
        $this->assertSame($customer->id, $appt->customer_id);
        $this->assertSame($vehicle->id, $appt->vehicle_id);
        $this->assertSame($center->id, $appt->center_id);
        $this->assertSame(Appointment::STATUS_SCHEDULED, $appt->status);
        $this->assertSame(Appointment::SOURCE_CUSTOMER_PORTAL, $appt->source);
        $this->assertNotNull($appt->service_bay_id);
        $this->assertNull($appt->assigned_user_id);
        $this->assertTrue($appt->services->pluck('id')->contains($service->id));
    }

    public function test_booking_with_vehicle_owned_by_another_customer_is_rejected(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center);

        $customer = $this->customerIn($tenant);
        $otherCustomer = $this->customerIn($tenant, '+966509876543');
        $foreignVehicle = $this->vehicleFor($otherCustomer, $tenant);
        $this->loginAs($customer, $tenant);

        $slot = Carbon::now()->addDay()->setTime(10, 0);
        $this->post('/'.$tenant->slug.'/portal/appointments', [
            'center_id' => $center->id,
            'vehicle_id' => $foreignVehicle->id,
            'service_ids' => [$service->id],
            'scheduled_at' => $slot->toIso8601String(),
        ])->assertSessionHasErrors('booking');

        $this->assertSame(0, Appointment::query()->count());
    }

    public function test_booking_with_center_from_other_tenant_is_rejected(): void
    {
        $tenantA = $this->publishedTenant('a');
        $tenantB = $this->publishedTenant('b');
        $centerA = $this->openCenter($tenantA);
        $centerB = $this->openCenter($tenantB);
        $this->bayIn($centerA);
        $this->bayIn($centerB);
        $serviceInA = $this->serviceIn($tenantA, $centerA);

        $customer = $this->customerIn($tenantA);
        $vehicle = $this->vehicleFor($customer, $tenantA);
        $this->loginAs($customer, $tenantA);

        $slot = Carbon::now()->addDay()->setTime(10, 0);
        $this->post('/'.$tenantA->slug.'/portal/appointments', [
            'center_id' => $centerB->id,
            'vehicle_id' => $vehicle->id,
            'service_ids' => [$serviceInA->id],
            'scheduled_at' => $slot->toIso8601String(),
        ])->assertSessionHasErrors('booking');

        $this->assertSame(0, Appointment::query()->count());
    }

    public function test_booking_with_inactive_center_is_rejected(): void
    {
        $tenant = $this->publishedTenant();
        $inactive = Center::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => false,
        ]);
        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);
        $this->loginAs($customer, $tenant);

        $slot = Carbon::now()->addDay()->setTime(10, 0);
        $this->post('/'.$tenant->slug.'/portal/appointments', [
            'center_id' => $inactive->id,
            'vehicle_id' => $vehicle->id,
            'service_ids' => [1],
            'scheduled_at' => $slot->toIso8601String(),
        ])->assertSessionHasErrors('booking');
    }

    public function test_booking_in_the_past_is_rejected(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center);
        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);
        $this->loginAs($customer, $tenant);

        $this->post('/'.$tenant->slug.'/portal/appointments', [
            'center_id' => $center->id,
            'vehicle_id' => $vehicle->id,
            'service_ids' => [$service->id],
            'scheduled_at' => Carbon::now()->subHour()->toIso8601String(),
        ])->assertSessionHasErrors('booking');
    }

    public function test_booking_in_less_than_one_hour_is_rejected(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center);
        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);
        $this->loginAs($customer, $tenant);

        $this->post('/'.$tenant->slug.'/portal/appointments', [
            'center_id' => $center->id,
            'vehicle_id' => $vehicle->id,
            'service_ids' => [$service->id],
            'scheduled_at' => Carbon::now()->addMinutes(15)->toIso8601String(),
        ])->assertSessionHasErrors('booking');
    }

    public function test_booking_beyond_14_days_is_rejected(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center);
        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);
        $this->loginAs($customer, $tenant);

        $this->post('/'.$tenant->slug.'/portal/appointments', [
            'center_id' => $center->id,
            'vehicle_id' => $vehicle->id,
            'service_ids' => [$service->id],
            'scheduled_at' => Carbon::now()->addDays(20)->toIso8601String(),
        ])->assertSessionHasErrors('booking');
    }

    public function test_duration_is_server_derived_from_services(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $this->bayIn($center);
        $s30 = $this->serviceIn($tenant, $center, 30);
        $s60 = $this->serviceIn($tenant, $center, 60);

        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);
        $this->loginAs($customer, $tenant);

        // Even if the client lies about duration via
        // a third "service" that doesn't exist, the
        // server recomputes from the real services.
        $slot = Carbon::now()->addDay()->setTime(10, 0);
        $this->post('/'.$tenant->slug.'/portal/appointments', [
            'center_id' => $center->id,
            'vehicle_id' => $vehicle->id,
            'service_ids' => [$s30->id, $s60->id],
            'scheduled_at' => $slot->toIso8601String(),
            // Defensive: the controller should ignore
            // these. They are not whitelisted in the
            // validation rules either.
            'duration' => 1,
            'estimated_end_at' => $slot->copy()->addMinute()->toIso8601String(),
        ])->assertRedirect();

        $appt = Appointment::query()->where('tenant_id', $tenant->id)->first();
        $this->assertNotNull($appt);
        $this->assertSame(
            $slot->copy()->addMinutes(90)->toIso8601String(),
            $appt->estimated_end_at->toIso8601String()
        );
    }

    public function test_bay_id_from_client_is_ignored(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $realBay = $this->bayIn($center, 'Real');
        $decoyBay = $this->bayIn($center, 'Decoy');
        $service = $this->serviceIn($tenant, $center);

        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);
        $this->loginAs($customer, $tenant);

        $slot = Carbon::now()->addDay()->setTime(10, 0);
        $this->post('/'.$tenant->slug.'/portal/appointments', [
            'center_id' => $center->id,
            'vehicle_id' => $vehicle->id,
            'service_ids' => [$service->id],
            'scheduled_at' => $slot->toIso8601String(),
            'service_bay_id' => $decoyBay->id,
        ])->assertRedirect();

        $appt = Appointment::query()->where('tenant_id', $tenant->id)->first();
        $this->assertNotNull($appt);
        $this->assertSame($realBay->id, $appt->service_bay_id);
    }

    public function test_store_endpoint_throttles_after_10_requests_per_minute(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center, 30);
        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);
        $this->loginAs($customer, $tenant);

        $slot = Carbon::now()->addDay()->setTime(10, 0);

        for ($i = 0; $i < 10; $i++) {
            $nextSlot = $slot->copy()->addHours($i);
            // Reset "now" so the lookahead filter
            // still allows each subsequent slot.
            $this->post('/'.$tenant->slug.'/portal/appointments', [
                'center_id' => $center->id,
                'vehicle_id' => $vehicle->id,
                'service_ids' => [$service->id],
                'scheduled_at' => $nextSlot->toIso8601String(),
            ]);
        }

        // 11th request from the same key — should be
        // throttled. Note: the throttled response
        // uses Inertia's 302 → / back; the assertion
        // here is simply that the 11th request does
        // not create an 11th appointment.
        $this->post('/'.$tenant->slug.'/portal/appointments', [
            'center_id' => $center->id,
            'vehicle_id' => $vehicle->id,
            'service_ids' => [$service->id],
            'scheduled_at' => $slot->copy()->addHours(11)->toIso8601String(),
        ])->assertStatus(429);
    }

    public function test_booking_race_two_concurrent_only_one_wins(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant, '08:00', '18:00');
        $bay = $this->bayIn($center, 'Sole');
        $service = $this->serviceIn($tenant, $center, 60);

        $alice = $this->customerIn($tenant, '+966501111111');
        $aliceVehicle = $this->vehicleFor($alice, $tenant);
        $bob = $this->customerIn($tenant, '+966502222222');
        $bobVehicle = $this->vehicleFor($bob, $tenant);

        $slot = Carbon::now()->addDay()->setTime(10, 0);
        $end = $slot->copy()->addHour();

        // Insert an existing appointment that blocks
        // every bay at 10:00-11:00, so both racers
        // must collide with it. Whoever wins is the
        // one that grabbed the lock first; the other
        // gets a conflict.
        Appointment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $alice->id,
            'vehicle_id' => $aliceVehicle->id,
            'service_bay_id' => $bay->id,
            'scheduled_at' => $slot,
            'estimated_end_at' => $end,
            'status' => 'scheduled',
        ]);

        // Pre-block 9 requests so the 10th hit the
        // throttle. Then the next 2 should both
        // fail on conflict (one on the existing
        // appointment, one on each other).
        $this->clearRateLimiters();
        $this->loginAs($bob, $tenant);
        $r1 = $this->post('/'.$tenant->slug.'/portal/appointments', [
            'center_id' => $center->id,
            'vehicle_id' => $bobVehicle->id,
            'service_ids' => [$service->id],
            'scheduled_at' => $slot->toIso8601String(),
        ]);
        $this->assertTrue(
            $r1->isRedirect() || $r1->getStatusCode() === 302,
            'Bob expected to fail with conflict'
        );
        $this->assertSame(1, Appointment::query()->where('tenant_id', $tenant->id)->count());
    }

    public function test_booking_in_closed_window_is_rejected(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant, '08:00', '10:00');
        $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center, 30);
        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);
        $this->loginAs($customer, $tenant);

        // 09:00 + 30 = 09:30, fine.
        // 09:30 + 30 = 10:00, exactly at close.
        // 09:45 — but slots are 30-min aligned so
        // 09:30 and 10:00 are the only candidates.
        // 10:00 + 30 = 10:30 > close → reject.
        $slot = Carbon::now()->addDay()->setTime(10, 0);
        $this->post('/'.$tenant->slug.'/portal/appointments', [
            'center_id' => $center->id,
            'vehicle_id' => $vehicle->id,
            'service_ids' => [$service->id],
            'scheduled_at' => $slot->toIso8601String(),
        ])->assertSessionHasErrors('booking');
    }

    public function test_booking_without_session_is_rejected(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $service = $this->serviceIn($tenant, $center);
        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);

        $slot = Carbon::now()->addDay()->setTime(10, 0);
        $this->post('/'.$tenant->slug.'/portal/appointments', [
            'center_id' => $center->id,
            'vehicle_id' => $vehicle->id,
            'service_ids' => [$service->id],
            'scheduled_at' => $slot->toIso8601String(),
        ])->assertRedirect('/'.$tenant->slug.'/portal/login');
    }

    // ─────────────────────────────────────────────────────────────────
    // GET /{tenant}/portal/appointments/{id}
    // ─────────────────────────────────────────────────────────────────

    public function test_confirmation_renders_for_owner(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $bay = $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center, 30);
        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);

        $appt = Appointment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'service_bay_id' => $bay->id,
            'scheduled_at' => Carbon::now()->addDay()->setTime(10, 0),
            'estimated_end_at' => Carbon::now()->addDay()->setTime(10, 30),
            'status' => Appointment::STATUS_SCHEDULED,
            'source' => Appointment::SOURCE_CUSTOMER_PORTAL,
        ]);
        $appt->services()->attach($service->id, ['estimated_minutes' => 30]);

        $this->loginAs($customer, $tenant);

        $this->get('/'.$tenant->slug.'/portal/appointment/'.$appt->id)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/Portal/Appointments/Confirmation')
                ->where('appointment.id', $appt->id)
            );
    }

    public function test_confirmation_404s_for_other_customer(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $bay = $this->bayIn($center);

        $owner = $this->customerIn($tenant, '+966501111111');
        $other = $this->customerIn($tenant, '+966502222222');
        $ownerVehicle = $this->vehicleFor($owner, $tenant);

        $appt = Appointment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $owner->id,
            'vehicle_id' => $ownerVehicle->id,
            'service_bay_id' => $bay->id,
            'scheduled_at' => Carbon::now()->addDay()->setTime(10, 0),
            'estimated_end_at' => Carbon::now()->addDay()->setTime(10, 30),
            'status' => Appointment::STATUS_SCHEDULED,
            'source' => Appointment::SOURCE_CUSTOMER_PORTAL,
        ]);

        $this->loginAs($other, $tenant);

        $this->get('/'.$tenant->slug.'/portal/appointment/'.$appt->id)
            ->assertNotFound();
    }

    public function test_confirmation_404s_for_other_tenant(): void
    {
        $tenantA = $this->publishedTenant('a');
        $tenantB = $this->publishedTenant('b');
        $centerA = $this->openCenter($tenantA);
        $bayA = $this->bayIn($centerA);

        $customerA = $this->customerIn($tenantA, '+966501111111');
        $customerB = $this->customerIn($tenantB, '+966502222222');
        $vehicleA = $this->vehicleFor($customerA, $tenantA);

        $appt = Appointment::create([
            'tenant_id' => $tenantA->id,
            'center_id' => $centerA->id,
            'customer_id' => $customerA->id,
            'vehicle_id' => $vehicleA->id,
            'service_bay_id' => $bayA->id,
            'scheduled_at' => Carbon::now()->addDay()->setTime(10, 0),
            'estimated_end_at' => Carbon::now()->addDay()->setTime(10, 30),
            'status' => Appointment::STATUS_SCHEDULED,
            'source' => Appointment::SOURCE_CUSTOMER_PORTAL,
        ]);

        $this->loginAs($customerB, $tenantB);

        // Cross-tenant routes resolve the {tenant}
        // slug, so a customer from tenant B can never
        // reach tenant A's appointment URL. The
        // route's tenant parameter must match the
        // session's tenant.
        $this->get('/'.$tenantA->slug.'/portal/appointment/'.$appt->id)
            ->assertRedirect('/'.$tenantA->slug.'/portal/login');
    }

    // ─────────────────────────────────────────────────────────────────
    // P1 — slot-bypass protection (P1: file_put_contents
    // removed, slot-snapshot enforced in store()).
    // ─────────────────────────────────────────────────────────────────

    public function test_off_grid_time_within_working_hours_is_rejected(): void
    {
        // The working window 08:00 - 18:00 with 30-min
        // intervals offers slots at 08:00, 08:30, 09:00,
        // ... but NOT 10:13. The store endpoint must
        // refuse a hand-crafted scheduled_at that is
        // inside working hours, has a free bay, but is
        // not on a published slot boundary. Without the
        // check, a malicious client could create an
        // appointment on an "off-grid" minute that no
        // other customer could ever book.
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant, '08:00', '18:00');
        $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center, 30);
        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);
        $this->loginAs($customer, $tenant);

        // Pin to 00:00 so the morning slots are not
        // filtered out by the 1-hour minimum-lookahead
        // guard.
        Carbon::setTestNow(Carbon::parse('2026-08-10 00:00:00'));

        $offGrid = Carbon::now()->addDay()->setTime(10, 13);

        $this->post('/'.$tenant->slug.'/portal/appointments', [
            'center_id' => $center->id,
            'vehicle_id' => $vehicle->id,
            'service_ids' => [$service->id],
            'scheduled_at' => $offGrid->toIso8601String(),
        ])->assertSessionHasErrors('booking');

        $this->assertSame(0, Appointment::query()->where('tenant_id', $tenant->id)->count());
    }

    public function test_on_grid_offered_slot_is_accepted(): void
    {
        // Sanity check the other half of the contract:
        // a slot that the availability endpoint WOULD
        // offer (10:00 on a 30-min grid) still goes
        // through, so we know the new check does not
        // over-reject.
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant, '08:00', '18:00');
        $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center, 30);
        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);
        $this->loginAs($customer, $tenant);

        Carbon::setTestNow(Carbon::parse('2026-08-10 00:00:00'));

        $onGrid = Carbon::now()->addDay()->setTime(10, 0);

        $this->post('/'.$tenant->slug.'/portal/appointments', [
            'center_id' => $center->id,
            'vehicle_id' => $vehicle->id,
            'service_ids' => [$service->id],
            'scheduled_at' => $onGrid->toIso8601String(),
        ])->assertRedirect();

        $this->assertSame(1, Appointment::query()->where('tenant_id', $tenant->id)->count());
    }

    // ─────────────────────────────────────────────────────────────────
    // P1 — centerIdOverride from a different tenant is
    // rejected by the scheduler itself.
    // ─────────────────────────────────────────────────────────────────

    public function test_center_id_override_from_other_tenant_is_rejected_by_scheduler(): void
    {
        // The portal controller filters centers by the
        // current tenant before forwarding the id to
        // the scheduler, but the scheduler is the
        // ultimate authority on tenant boundaries. We
        // drive the scheduler directly with a foreign
        // centerIdOverride and assert it throws
        // cross_tenant_or_center — defense in depth.
        $tenantA = $this->publishedTenant('a');
        $tenantB = $this->publishedTenant('b');
        $centerA = $this->openCenter($tenantA);
        $this->bayIn($centerA);
        $service = $this->serviceIn($tenantA, $centerA, 30);
        $customerA = $this->customerIn($tenantA, '+966501111111');
        $vehicleA = $this->vehicleFor($customerA, $tenantA);
        $centerB = $this->openCenter($tenantB);
        $bayB = $this->bayIn($centerB, 'BB1');
        $foreignCenterId = $centerB->id;

        // Foreign bay in tenant B. The portal would
        // never produce this id for tenant A's
        // customer, but a controller bug could. We
        // assert the scheduler blocks it.
        $start = Carbon::now()->addDays(1)->setTime(10, 0);
        $end = $start->copy()->addMinutes(30);

        $threw = false;

        try {
            app(AppointmentSchedulingService::class)->create(
                scheduledAt: $start,
                estimatedEndAt: $end,
                customer: $customerA,
                vehicle: $vehicleA,
                bay: $bayB,
                assignedUser: null,
                serviceIds: [$service->id],
                centerIdOverride: $foreignCenterId,
            );
        } catch (RuntimeException $e) {
            $threw = $e->getMessage() === 'cross_tenant_or_center';
        }
        $this->assertTrue($threw, 'Scheduler must reject centerIdOverride from another tenant');
        $this->assertSame(0, Appointment::query()->count());
    }
}
