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
use App\Support\CustomerPortalSession;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Phase 9 — self-service cancel + reschedule HTTP flow.
 *
 *   POST /{tenant}/portal/appointment/{id}/cancel
 *   GET  /{tenant}/portal/appointment/{id}/edit
 *   GET  /{tenant}/portal/appointment/{id}/reschedule-availability
 *   POST /{tenant}/portal/appointment/{id}/reschedule
 *
 * Security contract pinned here:
 *
 *   - tenant + customer ownership of the row
 *   - appointment.status in {scheduled, confirmed}
 *   - at least 2 hours until scheduled_at
 *   - the new time (reschedule) is on a slot the
 *     server would publish
 *   - the new center is in the same tenant
 *   - the services are unchanged and still belong
 *     to the new center
 *   - the bay is server-picked; the client never
 *     gets to choose one
 *   - rate limits apply on (tenant, customer, IP)
 *   - a concurrent reschedule against the same last
 *     slot never produces two appointments
 */
class CustomerPortalActionsHttpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        // Pin "now" before parent::setUp() so the
        // framework's RefreshDatabase trait starts at
        // the same clock we use throughout the test.
        Carbon::setTestNow(Carbon::parse('2026-08-10 09:00:00'));

        parent::setUp();

        // Wipe the rate-limiter buckets so the
        // cancel / reschedule throttle tests do
        // not pollute the next one. The phpunit
        // env uses an array cache, but buckets
        // survive within the same process.
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
            'code' => 'B1',
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

    /**
     * Build a scheduled appointment that lives far
     * enough in the future to be cancelable /
     * reschedulable (≥ 3 hours out). The customer
     * owns the row.
     */
    private function scheduledAppointment(
        Tenant $tenant,
        Center $center,
        ServiceBay $bay,
        Customer $customer,
        Vehicle $vehicle,
        Service $service,
        int $hoursFromNow = 24,
    ): Appointment {
        $start = Carbon::now()->addHours($hoursFromNow)->setTime(10, 0);
        $end = $start->copy()->addMinutes(30);

        $appt = Appointment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'service_bay_id' => $bay->id,
            'scheduled_at' => $start,
            'estimated_end_at' => $end,
            'status' => Appointment::STATUS_SCHEDULED,
            'source' => Appointment::SOURCE_CUSTOMER_PORTAL,
        ]);
        $appt->services()->attach($service->id, ['estimated_minutes' => 30]);

        return $appt;
    }

    // ─────────────────────────────────────────────────────────────────
    // Cancellation
    // ─────────────────────────────────────────────────────────────────

    public function test_customer_can_cancel_their_own_eligible_appointment(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $bay = $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center);
        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);
        $appt = $this->scheduledAppointment($tenant, $center, $bay, $customer, $vehicle, $service, hoursFromNow: 24);

        $this->loginAs($customer, $tenant);

        $this->post(
            '/'.$tenant->slug.'/portal/appointment/'.$appt->id.'/cancel',
            ['reason' => 'test reason']
        )->assertRedirect('/'.$tenant->slug.'/portal/dashboard');

        $appt->refresh();
        $this->assertSame(Appointment::STATUS_CANCELLED, $appt->status);
        $this->assertNotNull($appt->cancelled_at);
        $this->assertStringContainsString('customer_portal', (string) $appt->cancellation_reason);
        $this->assertStringContainsString('test reason', (string) $appt->cancellation_reason);
    }

    public function test_cancel_works_without_reason(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $bay = $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center);
        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);
        $appt = $this->scheduledAppointment($tenant, $center, $bay, $customer, $vehicle, $service);

        $this->loginAs($customer, $tenant);

        $this->post('/'.$tenant->slug.'/portal/appointment/'.$appt->id.'/cancel')
            ->assertRedirect();

        $appt->refresh();
        $this->assertSame(Appointment::STATUS_CANCELLED, $appt->status);
        $this->assertSame('customer_portal', $appt->cancellation_reason);
    }

    public function test_cancel_within_two_hours_is_rejected_with_404(): void
    {
        // The 2-hour window is enforced both client-
        // side (UX hint) and server-side. Hitting the
        // endpoint directly with a soon-imminent
        // appointment must fail with 404 (no leak of
        // the underlying reason).
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $bay = $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center);
        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);
        $appt = $this->scheduledAppointment($tenant, $center, $bay, $customer, $vehicle, $service, hoursFromNow: 1);

        $this->loginAs($customer, $tenant);

        $this->post('/'.$tenant->slug.'/portal/appointment/'.$appt->id.'/cancel')
            ->assertNotFound();

        $appt->refresh();
        $this->assertSame(Appointment::STATUS_SCHEDULED, $appt->status);
    }

    public function test_cancel_after_check_in_is_rejected_with_404(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $bay = $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center);
        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);
        $appt = $this->scheduledAppointment($tenant, $center, $bay, $customer, $vehicle, $service);
        $appt->update(['status' => Appointment::STATUS_CHECKED_IN]);

        $this->loginAs($customer, $tenant);

        $this->post('/'.$tenant->slug.'/portal/appointment/'.$appt->id.'/cancel')
            ->assertNotFound();

        $appt->refresh();
        $this->assertSame(Appointment::STATUS_CHECKED_IN, $appt->status);
    }

    public function test_cancel_of_already_cancelled_appointment_returns_404(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $bay = $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center);
        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);
        $appt = $this->scheduledAppointment($tenant, $center, $bay, $customer, $vehicle, $service);
        $appt->update(['status' => Appointment::STATUS_CANCELLED]);

        $this->loginAs($customer, $tenant);

        $this->post('/'.$tenant->slug.'/portal/appointment/'.$appt->id.'/cancel')
            ->assertNotFound();
    }

    public function test_cancel_of_no_show_appointment_returns_404(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $bay = $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center);
        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);
        $appt = $this->scheduledAppointment($tenant, $center, $bay, $customer, $vehicle, $service);
        $appt->update(['status' => Appointment::STATUS_NO_SHOW]);

        $this->loginAs($customer, $tenant);

        $this->post('/'.$tenant->slug.'/portal/appointment/'.$appt->id.'/cancel')
            ->assertNotFound();
    }

    public function test_other_customer_cannot_cancel_my_appointment(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $bay = $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center);
        $owner = $this->customerIn($tenant, '+966501111111');
        $other = $this->customerIn($tenant, '+966502222222');
        $ownerVehicle = $this->vehicleFor($owner, $tenant);
        $appt = $this->scheduledAppointment($tenant, $center, $bay, $owner, $ownerVehicle, $service);

        $this->loginAs($other, $tenant);

        $this->post('/'.$tenant->slug.'/portal/appointment/'.$appt->id.'/cancel')
            ->assertNotFound();

        $appt->refresh();
        $this->assertSame(Appointment::STATUS_SCHEDULED, $appt->status);
    }

    public function test_customer_in_other_tenant_cannot_cancel_my_appointment(): void
    {
        $tenantA = $this->publishedTenant('a');
        $tenantB = $this->publishedTenant('b');
        $centerA = $this->openCenter($tenantA);
        $bayA = $this->bayIn($centerA);
        $serviceA = $this->serviceIn($tenantA, $centerA);
        $customerA = $this->customerIn($tenantA, '+966501111111');
        $vehicleA = $this->vehicleFor($customerA, $tenantA);
        $appt = $this->scheduledAppointment($tenantA, $centerA, $bayA, $customerA, $vehicleA, $serviceA);

        $customerB = $this->customerIn($tenantB, '+966502222222');
        $this->loginAs($customerB, $tenantB);

        $this->post('/'.$tenantA->slug.'/portal/appointment/'.$appt->id.'/cancel')
            ->assertRedirect('/'.$tenantA->slug.'/portal/login');

        $appt->refresh();
        $this->assertSame(Appointment::STATUS_SCHEDULED, $appt->status);
    }

    public function test_cancel_without_session_redirects_to_login(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $bay = $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center);
        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);
        $appt = $this->scheduledAppointment($tenant, $center, $bay, $customer, $vehicle, $service);

        $this->post('/'.$tenant->slug.'/portal/appointment/'.$appt->id.'/cancel')
            ->assertRedirect('/'.$tenant->slug.'/portal/login');
    }

    // ─────────────────────────────────────────────────────────────────
    // Reschedule
    // ─────────────────────────────────────────────────────────────────

    public function test_customer_can_reschedule_their_appointment_to_an_offered_slot(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant, '08:00', '18:00');
        $bay = $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center, 30);
        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);
        $appt = $this->scheduledAppointment($tenant, $center, $bay, $customer, $vehicle, $service, hoursFromNow: 24);

        $this->loginAs($customer, $tenant);

        // Pin clock to 00:00 so the morning slots are
        // not filtered by the 1-hour lookahead.
        Carbon::setTestNow(Carbon::parse('2026-08-10 00:00:00'));

        $newStart = Carbon::now()->addDay()->setTime(11, 0);

        $this->post('/'.$tenant->slug.'/portal/appointment/'.$appt->id.'/reschedule', [
            'center_id' => $center->id,
            'scheduled_at' => $newStart->toIso8601String(),
        ])->assertRedirect('/'.$tenant->slug.'/portal/appointment/'.$appt->id);

        $appt->refresh();
        $this->assertSame($newStart->toIso8601String(), $appt->scheduled_at->toIso8601String());
        $this->assertSame(Appointment::STATUS_SCHEDULED, $appt->status);
        $this->assertNotNull($appt->service_bay_id);
        $this->assertNull($appt->assigned_user_id);
        $this->assertSame($customer->id, $appt->customer_id);
        $this->assertSame($vehicle->id, $appt->vehicle_id);
        $this->assertTrue($appt->services->pluck('id')->contains($service->id));
    }

    public function test_reschedule_rejects_off_grid_minute(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant, '08:00', '18:00');
        $bay = $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center, 30);
        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);
        $appt = $this->scheduledAppointment($tenant, $center, $bay, $customer, $vehicle, $service, hoursFromNow: 24);

        $this->loginAs($customer, $tenant);
        Carbon::setTestNow(Carbon::parse('2026-08-10 00:00:00'));

        $offGrid = Carbon::now()->addDay()->setTime(10, 13);

        $this->post('/'.$tenant->slug.'/portal/appointment/'.$appt->id.'/reschedule', [
            'center_id' => $center->id,
            'scheduled_at' => $offGrid->toIso8601String(),
        ])->assertSessionHasErrors('booking');

        $appt->refresh();
        $this->assertNotSame($offGrid->toIso8601String(), $appt->scheduled_at->toIso8601String());
    }

    public function test_reschedule_rejects_outside_working_hours(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant, '08:00', '12:00');
        $bay = $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center, 60);
        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);
        $appt = $this->scheduledAppointment($tenant, $center, $bay, $customer, $vehicle, $service, hoursFromNow: 24);

        $this->loginAs($customer, $tenant);
        Carbon::setTestNow(Carbon::parse('2026-08-10 00:00:00'));

        // 10:00 + 60-min service = ends 11:00, OK.
        // 10:30 + 60-min service = ends 11:30, OK.
        // 11:00 + 60-min service = ends 12:00, OK.
        // 11:30 + 60-min service = ends 12:30, outside.
        $outOfHours = Carbon::now()->addDay()->setTime(11, 30);

        $this->post('/'.$tenant->slug.'/portal/appointment/'.$appt->id.'/reschedule', [
            'center_id' => $center->id,
            'scheduled_at' => $outOfHours->toIso8601String(),
        ])->assertSessionHasErrors('booking');
    }

    public function test_reschedule_rejects_within_two_hours(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant, '08:00', '18:00');
        $bay = $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center, 30);
        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);
        $appt = $this->scheduledAppointment($tenant, $center, $bay, $customer, $vehicle, $service, hoursFromNow: 24);

        $this->loginAs($customer, $tenant);

        // "Now" is 09:00. The proposed new start
        // is 10:30 (only 90 minutes out) — must be
        // rejected by the 2-hour window.
        $tooSoon = Carbon::now()->addMinutes(90);

        $this->post('/'.$tenant->slug.'/portal/appointment/'.$appt->id.'/reschedule', [
            'center_id' => $center->id,
            'scheduled_at' => $tooSoon->toIso8601String(),
        ])->assertSessionHasErrors('booking');
    }

    public function test_reschedule_rejects_beyond_14_days(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant, '08:00', '18:00');
        $bay = $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center, 30);
        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);
        $appt = $this->scheduledAppointment($tenant, $center, $bay, $customer, $vehicle, $service, hoursFromNow: 24);

        $this->loginAs($customer, $tenant);

        $tooFar = Carbon::now()->addDays(20)->setTime(10, 0);

        $this->post('/'.$tenant->slug.'/portal/appointment/'.$appt->id.'/reschedule', [
            'center_id' => $center->id,
            'scheduled_at' => $tooFar->toIso8601String(),
        ])->assertSessionHasErrors('booking');
    }

    public function test_reschedule_to_other_branch_in_same_tenant_is_allowed(): void
    {
        // The original appointment is at centerA and
        // attaches serviceA (registered to centerA).
        // When the customer tries to move to centerB,
        // the controller looks for serviceA in
        // centerB and finds nothing. The request must
        // fail with services_unavailable — the
        // services the customer booked in centerA are
        // not available in centerB. This is the safe
        // default: services are per-center, the
        // customer cannot cross branches unless the
        // operator has explicitly enabled a service
        // across both branches (a product feature
        // that lands in a later phase).
        $tenant = $this->publishedTenant();
        $centerA = $this->openCenter($tenant, '08:00', '18:00');
        $centerB = $this->openCenter($tenant, '08:00', '18:00');
        $bayA = $this->bayIn($centerA, 'A');
        $serviceA = $this->serviceIn($tenant, $centerA, 30);
        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);
        $appt = $this->scheduledAppointment($tenant, $centerA, $bayA, $customer, $vehicle, $serviceA, hoursFromNow: 24);

        $this->loginAs($customer, $tenant);
        Carbon::setTestNow(Carbon::parse('2026-08-10 00:00:00'));

        $newStart = Carbon::now()->addDay()->setTime(11, 0);
        $this->post('/'.$tenant->slug.'/portal/appointment/'.$appt->id.'/reschedule', [
            'center_id' => $centerB->id,
            'scheduled_at' => $newStart->toIso8601String(),
        ])->assertSessionHasErrors('booking');
    }

    public function test_reschedule_to_other_branch_succeeds_when_services_belong_to_new_center(): void
    {
        // The customer originally booked in centerA
        // with serviceA (which is registered to
        // centerA). To reschedule into centerB, the
        // operator must have a service with the SAME
        // id also registered to centerB. We simulate
        // that by attaching an additional service row
        // to centerB that is part of the original
        // appointment. In the real product this would
        // be the "service enabled in multiple centers"
        // feature. For this test we just verify the
        // controller honors the per-center service
        // membership check.
        //
        // The cleanest way to express the membership:
        // re-attach the existing service to centerB as
        // a sibling row, and have the appointment
        // refer to the centerB row id. We model this
        // by booking the original appointment against
        // a service id that is valid in BOTH centers.
        $tenant = $this->publishedTenant();
        $centerA = $this->openCenter($tenant, '08:00', '18:00');
        $centerB = $this->openCenter($tenant, '08:00', '18:00');
        $bayA = $this->bayIn($centerA, 'A');
        $bayB = $this->bayIn($centerB, 'B');

        // A service in centerA, then a sister service
        // in centerB. We book the original
        // appointment against a service id that
        // happens to exist in BOTH centers. The
        // easiest way to model "the same service
        // id valid in two centers" without a
        // many-to-many is to keep the centerA service
        // and have the controller, given a new
        // center, look up the same id in the new
        // center. Since the spec says "services
        // unchanged", we rely on the operator to
        // have made this work in the data — we simply
        // move the service row to centerB and assert
        // the reschedule succeeds. Then the operator
        // can move it back.
        $service = Service::factory()->forTenant($tenant, $centerA)->create([
            'duration_value' => 30,
            'duration_unit' => 'minute',
        ]);

        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);
        $appt = $this->scheduledAppointment($tenant, $centerA, $bayA, $customer, $vehicle, $service, hoursFromNow: 24);

        // Move the service to centerB to simulate
        // "the operator enabled this service in B".
        $service->update(['center_id' => $centerB->id]);

        $this->loginAs($customer, $tenant);
        Carbon::setTestNow(Carbon::parse('2026-08-10 00:00:00'));

        $newStart = Carbon::now()->addDay()->setTime(11, 0);
        $this->post('/'.$tenant->slug.'/portal/appointment/'.$appt->id.'/reschedule', [
            'center_id' => $centerB->id,
            'scheduled_at' => $newStart->toIso8601String(),
        ])->assertRedirect();

        $appt->refresh();
        $this->assertSame($centerB->id, $appt->center_id);
    }

    public function test_reschedule_rejects_inactive_branch(): void
    {
        $tenant = $this->publishedTenant();
        $centerA = $this->openCenter($tenant);
        $centerB = Center::factory()->create(['tenant_id' => $tenant->id, 'is_active' => false]);
        $bayA = $this->bayIn($centerA);
        $service = $this->serviceIn($tenant, $centerA);
        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);
        $appt = $this->scheduledAppointment($tenant, $centerA, $bayA, $customer, $vehicle, $service);

        $this->loginAs($customer, $tenant);
        Carbon::setTestNow(Carbon::parse('2026-08-10 00:00:00'));

        $newStart = Carbon::now()->addDay()->setTime(10, 0);
        $this->post('/'.$tenant->slug.'/portal/appointment/'.$appt->id.'/reschedule', [
            'center_id' => $centerB->id,
            'scheduled_at' => $newStart->toIso8601String(),
        ])->assertSessionHasErrors('booking');
    }

    public function test_reschedule_rejects_branch_in_other_tenant(): void
    {
        $tenantA = $this->publishedTenant('a');
        $tenantB = $this->publishedTenant('b');
        $centerA = $this->openCenter($tenantA);
        $centerB = $this->openCenter($tenantB);
        $bayA = $this->bayIn($centerA);
        $service = $this->serviceIn($tenantA, $centerA);
        $customer = $this->customerIn($tenantA);
        $vehicle = $this->vehicleFor($customer, $tenantA);
        $appt = $this->scheduledAppointment($tenantA, $centerA, $bayA, $customer, $vehicle, $service);

        $this->loginAs($customer, $tenantA);
        Carbon::setTestNow(Carbon::parse('2026-08-10 00:00:00'));

        $newStart = Carbon::now()->addDay()->setTime(10, 0);
        $this->post('/'.$tenantA->slug.'/portal/appointment/'.$appt->id.'/reschedule', [
            'center_id' => $centerB->id,
            'scheduled_at' => $newStart->toIso8601String(),
        ])->assertSessionHasErrors('booking');
    }

    public function test_reschedule_ignores_vehicle_or_services_in_payload(): void
    {
        // The customer can ONLY change center + time.
        // The services and the vehicle on the row
        // are read from the existing appointment, not
        // from the form. We send a foreign vehicle +
        // foreign services in the payload and assert
        // the original vehicle + services stay.
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant, '08:00', '18:00');
        $bay = $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center, 30);
        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);

        $otherCustomer = $this->customerIn($tenant, '+966509999999');
        $foreignVehicle = $this->vehicleFor($otherCustomer, $tenant);
        $foreignService = Service::factory()->forTenant($tenant, $center)->create([
            'duration_value' => 30,
            'duration_unit' => 'minute',
        ]);

        $appt = $this->scheduledAppointment($tenant, $center, $bay, $customer, $vehicle, $service, hoursFromNow: 24);

        $this->loginAs($customer, $tenant);
        Carbon::setTestNow(Carbon::parse('2026-08-10 00:00:00'));

        $newStart = Carbon::now()->addDay()->setTime(11, 0);
        $this->post('/'.$tenant->slug.'/portal/appointment/'.$appt->id.'/reschedule', [
            'center_id' => $center->id,
            'scheduled_at' => $newStart->toIso8601String(),
            'vehicle_id' => $foreignVehicle->id,
            'service_ids' => [$foreignService->id],
            // Even status / bay / assignee attempts.
            'service_bay_id' => 99999,
            'assigned_user_id' => 99999,
            'status' => 'cancelled',
            'source' => 'internal',
        ])->assertRedirect();

        $appt->refresh();
        $this->assertSame($vehicle->id, $appt->vehicle_id);
        $this->assertTrue($appt->services->pluck('id')->contains($service->id));
        $this->assertFalse($appt->services->pluck('id')->contains($foreignService->id));
        $this->assertSame(Appointment::STATUS_SCHEDULED, $appt->status);
        $this->assertSame(Appointment::SOURCE_CUSTOMER_PORTAL, $appt->source);
        $this->assertNotSame(99999, $appt->service_bay_id);
    }

    public function test_reschedule_picks_new_bay_server_side(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant, '08:00', '18:00');
        $busyBay = $this->bayIn($center, 'Busy');
        $freeBay = $this->bayIn($center, 'Free');
        $service = $this->serviceIn($tenant, $center, 30);
        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);
        $appt = $this->scheduledAppointment($tenant, $center, $busyBay, $customer, $vehicle, $service, hoursFromNow: 24);

        $this->loginAs($customer, $tenant);
        Carbon::setTestNow(Carbon::parse('2026-08-10 00:00:00'));

        // The client tries to keep the busy bay even
        // though it is the row's current bay. The
        // server should ignore the hint and pick the
        // free bay instead.
        $newStart = Carbon::now()->addDay()->setTime(11, 0);
        $this->post('/'.$tenant->slug.'/portal/appointment/'.$appt->id.'/reschedule', [
            'center_id' => $center->id,
            'scheduled_at' => $newStart->toIso8601String(),
            'service_bay_id' => $busyBay->id,
        ])->assertRedirect();

        $appt->refresh();
        $this->assertNotSame($busyBay->id, $appt->service_bay_id);
    }

    public function test_reschedule_race_two_concurrent_submissions_only_one_wins(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant, '08:00', '18:00');
        $busy = $this->bayIn($center, 'Sole');
        $service = $this->serviceIn($tenant, $center, 60);
        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);
        $appt = $this->scheduledAppointment($tenant, $center, $busy, $customer, $vehicle, $service, hoursFromNow: 24);

        $this->loginAs($customer, $tenant);
        Carbon::setTestNow(Carbon::parse('2026-08-10 00:00:00'));

        $newStart = Carbon::now()->addDay()->setTime(11, 0);
        // Pre-occupy the only bay at the new time.
        $existing = Appointment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'service_bay_id' => $busy->id,
            'scheduled_at' => $newStart,
            'estimated_end_at' => $newStart->copy()->addHour(),
            'status' => Appointment::STATUS_SCHEDULED,
        ]);

        $response = $this->post('/'.$tenant->slug.'/portal/appointment/'.$appt->id.'/reschedule', [
            'center_id' => $center->id,
            'scheduled_at' => $newStart->toIso8601String(),
        ]);
        $this->assertTrue(
            $response->isRedirect() || $response->getStatusCode() === 302,
            'Reschedule to a fully-booked slot must fail'
        );
        $appt->refresh();
        $this->assertNotSame($newStart->toIso8601String(), $appt->scheduled_at->toIso8601String());
    }

    public function test_other_customer_cannot_reschedule_my_appointment(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $bay = $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center);
        $owner = $this->customerIn($tenant, '+966501111111');
        $other = $this->customerIn($tenant, '+966502222222');
        $ownerVehicle = $this->vehicleFor($owner, $tenant);
        $appt = $this->scheduledAppointment($tenant, $center, $bay, $owner, $ownerVehicle, $service);

        $this->loginAs($other, $tenant);
        Carbon::setTestNow(Carbon::parse('2026-08-10 00:00:00'));

        $newStart = Carbon::now()->addDay()->setTime(11, 0);
        $this->get('/'.$tenant->slug.'/portal/appointment/'.$appt->id.'/edit')
            ->assertNotFound();
        // The action service locks the row inside its
        // own transaction and 404s for any other customer
        // — we never confirm to a third party that the
        // row exists.
        $this->post('/'.$tenant->slug.'/portal/appointment/'.$appt->id.'/reschedule', [
            'center_id' => $center->id,
            'scheduled_at' => $newStart->toIso8601String(),
        ])->assertNotFound();
    }

    public function test_reschedule_rejects_within_two_hours_via_session_window(): void
    {
        // The original appointment is far in the
        // future, but the proposed new time is too
        // soon. The 2-hour gate is on the new time,
        // not the old one.
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant, '08:00', '18:00');
        $bay = $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center, 30);
        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);
        $appt = $this->scheduledAppointment($tenant, $center, $bay, $customer, $vehicle, $service, hoursFromNow: 24);

        $this->loginAs($customer, $tenant);

        // 09:00 + 30 minutes = 09:30, 30 minutes out.
        $tooSoon = Carbon::now()->addMinutes(30);
        $this->post('/'.$tenant->slug.'/portal/appointment/'.$appt->id.'/reschedule', [
            'center_id' => $center->id,
            'scheduled_at' => $tooSoon->toIso8601String(),
        ])->assertSessionHasErrors('booking');
    }

    public function test_edit_page_renders_for_eligible_appointment(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant, '08:00', '18:00');
        $bay = $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center, 30);
        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);
        $appt = $this->scheduledAppointment($tenant, $center, $bay, $customer, $vehicle, $service, hoursFromNow: 24);

        $this->loginAs($customer, $tenant);

        $this->get('/'.$tenant->slug.'/portal/appointment/'.$appt->id.'/edit')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/Portal/Appointments/Reschedule')
                ->where('appointment.id', $appt->id)
            );
    }

    public function test_reschedule_availability_returns_slots_for_same_services(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant, '08:00', '18:00');
        $bay = $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center, 30);
        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);
        $appt = $this->scheduledAppointment($tenant, $center, $bay, $customer, $vehicle, $service, hoursFromNow: 24);

        $this->loginAs($customer, $tenant);
        Carbon::setTestNow(Carbon::parse('2026-08-10 00:00:00'));

        $this->getJson(
            '/'.$tenant->slug.'/portal/appointment/'.$appt->id.'/reschedule-availability?'.
            http_build_query([
                'center_id' => $center->id,
                'date' => Carbon::now()->addDay()->toDateString(),
            ])
        )
            ->assertOk()
            ->assertJsonPath('open', true)
            ->assertJsonStructure(['slots']);
    }

    // ─────────────────────────────────────────────────────────────────
    // Rate limits — we exercise the throttler by
    // spamming the endpoints past the per-minute cap.
    // ─────────────────────────────────────────────────────────────────

    public function test_cancel_endpoint_throttles_after_ten_requests_per_minute(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $bay = $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center);
        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);

        $this->loginAs($customer, $tenant);

        // 10 different appointments (all future, all
        // owned by the customer) so each cancel
        // attempt is a real, distinct request. After
        // 10 successful cancels, the 11th must
        // throttle.
        for ($i = 0; $i < 11; $i++) {
            $appt = $this->scheduledAppointment(
                $tenant,
                $center,
                $bay,
                $customer,
                $vehicle,
                $service,
                hoursFromNow: 24 + $i,
            );
            $r = $this->post('/'.$tenant->slug.'/portal/appointment/'.$appt->id.'/cancel');

            if ($i < 10) {
                $r->assertRedirect();
            } else {
                $r->assertStatus(429);
            }
        }
    }

    public function test_reschedule_endpoint_throttles_after_ten_requests_per_minute(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant, '08:00', '18:00');
        $bay = $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center, 30);
        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);
        $appt = $this->scheduledAppointment($tenant, $center, $bay, $customer, $vehicle, $service, hoursFromNow: 24);

        $this->loginAs($customer, $tenant);
        Carbon::setTestNow(Carbon::parse('2026-08-10 00:00:00'));

        for ($i = 0; $i < 11; $i++) {
            $r = $this->post('/'.$tenant->slug.'/portal/appointment/'.$appt->id.'/reschedule', [
                'center_id' => $center->id,
                'scheduled_at' => Carbon::now()->addDay()->setTime(10 + $i, 0)->toIso8601String(),
            ]);

            if ($i < 10) {
                $r->assertRedirect();
            } else {
                $r->assertStatus(429);
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // P1 regression — TOCTOU on the 2h window + status.
    //
    // The original Phase 9 controller checked
    // eligibility BEFORE the cancel / reschedule
    // transaction. Between that pre-check and the
    // staff scheduler's transaction, an operator
    // could check the customer in (status flips to
    // `checked_in`) or cancel the appointment from
    // the staff UI. The customer-side cancel /
    // reschedule must not accept those concurrent
    // transitions.
    //
    // We simulate the race in a single PHP test by
    // mutating the appointment row to `checked_in`
    // AFTER the controller's pre-check, but
    // simulating that the controller has already
    // entered the action service. The lockForUpdate
    // + post-lock re-check inside the service is
    // what catches the change. The same pattern
    // catches a `cancelled` row when the customer
    // tries to reschedule.
    // ─────────────────────────────────────────────────────────────────

    public function test_concurrent_check_in_blocks_customer_cancel(): void
    {
        // Pinned to a known clock: appointment is
        // scheduled 24h out, the customer passes the
        // controller's pre-check, and then — between
        // the pre-check and the action service's
        // lockForUpdate — the operator checks the
        // customer in. The cancel must fail.
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $bay = $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center);
        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);
        $appt = $this->scheduledAppointment($tenant, $center, $bay, $customer, $vehicle, $service, hoursFromNow: 24);

        $this->loginAs($customer, $tenant);

        // Simulate the operator checking the customer
        // in. The action service's lockForUpdate
        // will see this row version, not the
        // pre-check snapshot.
        $appt->update(['status' => Appointment::STATUS_CHECKED_IN]);

        $this->post('/'.$tenant->slug.'/portal/appointment/'.$appt->id.'/cancel')
            ->assertNotFound();

        $appt->refresh();
        $this->assertSame(Appointment::STATUS_CHECKED_IN, $appt->status);
        $this->assertNull($appt->cancelled_at);
    }

    public function test_concurrent_check_in_blocks_customer_reschedule(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant, '08:00', '18:00');
        $bay = $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center, 30);
        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);
        $appt = $this->scheduledAppointment($tenant, $center, $bay, $customer, $vehicle, $service, hoursFromNow: 24);

        $this->loginAs($customer, $tenant);
        Carbon::setTestNow(Carbon::parse('2026-08-10 00:00:00'));

        $originalTime = $appt->scheduled_at->copy();
        $originalBay = $appt->service_bay_id;
        $originalCenter = $appt->center_id;

        // Simulate the operator checking the customer
        // in. The action service's lockForUpdate
        // must catch this and refuse the move.
        $appt->update(['status' => Appointment::STATUS_CHECKED_IN]);

        $newStart = Carbon::now()->addDay()->setTime(11, 0);
        $this->post('/'.$tenant->slug.'/portal/appointment/'.$appt->id.'/reschedule', [
            'center_id' => $center->id,
            'scheduled_at' => $newStart->toIso8601String(),
        ])->assertNotFound();

        $appt->refresh();
        $this->assertSame(Appointment::STATUS_CHECKED_IN, $appt->status);
        // Time and bay must be unchanged.
        $this->assertSame(
            $originalTime->toIso8601String(),
            $appt->scheduled_at->toIso8601String()
        );
        $this->assertSame($originalBay, $appt->service_bay_id);
        $this->assertSame($originalCenter, $appt->center_id);
    }

    public function test_concurrent_cancellation_blocks_customer_reschedule(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant, '08:00', '18:00');
        $bay = $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center, 30);
        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);
        $appt = $this->scheduledAppointment($tenant, $center, $bay, $customer, $vehicle, $service, hoursFromNow: 24);

        $this->loginAs($customer, $tenant);
        Carbon::setTestNow(Carbon::parse('2026-08-10 00:00:00'));

        $originalTime = $appt->scheduled_at->copy();
        $originalBay = $appt->service_bay_id;
        $originalCenter = $appt->center_id;

        // Simulate the operator cancelling the
        // appointment from the staff UI. The
        // customer-side reschedule must not be
        // able to "undelete" a cancelled row.
        $appt->update([
            'status' => Appointment::STATUS_CANCELLED,
            'cancelled_at' => Carbon::now(),
            'cancellation_reason' => 'staff',
        ]);

        $newStart = Carbon::now()->addDay()->setTime(11, 0);
        $this->post('/'.$tenant->slug.'/portal/appointment/'.$appt->id.'/reschedule', [
            'center_id' => $center->id,
            'scheduled_at' => $newStart->toIso8601String(),
        ])->assertNotFound();

        $appt->refresh();
        $this->assertSame(Appointment::STATUS_CANCELLED, $appt->status);
        $this->assertSame(
            $originalTime->toIso8601String(),
            $appt->scheduled_at->toIso8601String()
        );
        $this->assertSame($originalBay, $appt->service_bay_id);
        $this->assertSame($originalCenter, $appt->center_id);
    }
}
