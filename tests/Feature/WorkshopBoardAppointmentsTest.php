<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Center;
use App\Models\Customer;
use App\Models\ServiceBay;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\AppointmentSchedulingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class WorkshopBoardAppointmentsTest extends TestCase
{
    use RefreshDatabase;

    protected AppointmentSchedulingService $scheduler;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::findOrCreate('crm.work_orders.view', 'web');
        Permission::findOrCreate('crm.work_orders.update', 'web');
        Permission::findOrCreate('crm.appointments.manage', 'web');
        Permission::findOrCreate('crm.customers.view', 'web');
        Permission::findOrCreate('crm.vehicles.view', 'web');

        $this->scheduler = app(AppointmentSchedulingService::class);
    }

    /**
     * @param array<int, string> $permissions
     */
    protected function createUserWithPermissions(array $permissions): User
    {
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
        ]);

        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

        foreach ($permissions as $permissionName) {
            $user->givePermissionTo($permissionName);
        }

        $user->refresh();

        return $user;
    }

    /**
     * @return array{0: Customer, 1: Vehicle, 2: ServiceBay, 3: User}
     */
    protected function buildFixture(User $owner): array
    {
        $customer = Customer::create([
            'tenant_id' => $owner->tenant_id,
            'center_id' => $owner->current_center_id,
            'type' => 'individual',
            'name' => 'Customer '.uniqid(),
            'phone' => '05'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
        ]);

        $vehicle = Vehicle::create([
            'tenant_id' => $owner->tenant_id,
            'center_id' => $owner->current_center_id,
            'customer_id' => $customer->id,
            'plate_number' => strtoupper(Str::random(3)).' '.random_int(1000, 9999),
        ]);

        $bay = ServiceBay::create([
            'tenant_id' => $owner->tenant_id,
            'center_id' => $owner->current_center_id,
            'name' => 'Bay '.uniqid(),
            'is_active' => true,
        ]);

        $tech = User::factory()->create([
            'tenant_id' => $owner->tenant_id,
            'current_center_id' => $owner->current_center_id,
        ]);
        $tech->centers()->attach($owner->current_center_id, ['tenant_id' => $owner->tenant_id]);

        return [$customer, $vehicle, $bay, $tech];
    }

    // ==================== todayAppointments shape ====================

    public function test_today_appointment_appears_in_board_payload(): void
    {
        $owner = $this->createUserWithPermissions(['crm.work_orders.view']);
        [$customer, $vehicle, $bay, $tech] = $this->buildFixture($owner);

        $start = Carbon::now()->addHours(2)->setMinute(0)->setSecond(0);
        $this->scheduler->create(
            $start, $start->copy()->addMinutes(45),
            $customer, $vehicle, $bay, $tech, []
        );

        $response = $this->actingAs($owner)->get('/app/workshop');

        $response->assertStatus(200);
        $response->assertInertia(
            fn ($page) => $page
                ->component('Workshop/Board')
                ->has('todayAppointments', 1)
                ->where('todayAppointments.0.customer.name', $customer->name)
                ->where('todayAppointments.0.vehicle.plate_number', $vehicle->plate_number)
                ->where('todayAppointments.0.service_bay.name', $bay->name)
                ->where('todayAppointments.0.assigned_user.name', $tech->name)
                ->where('todayAppointments.0.status', 'scheduled')
                ->where('canManageAppointments', false)
        );
    }

    public function test_other_tenant_appointment_does_not_appear(): void
    {
        $owner = $this->createUserWithPermissions(['crm.work_orders.view']);
        [$customer, $vehicle, $bay, $tech] = $this->buildFixture($owner);

        $start = Carbon::now()->addHours(2)->setMinute(0)->setSecond(0);
        $this->scheduler->create(
            $start, $start->copy()->addMinutes(45),
            $customer, $vehicle, $bay, $tech, []
        );

        // Build an "attacker" from a different tenant + center.
        $otherTenant = Tenant::factory()->create();
        $otherCenter = Center::factory()->create(['tenant_id' => $otherTenant->id]);
        $attacker = User::factory()->create([
            'tenant_id' => $otherTenant->id,
            'current_center_id' => $otherCenter->id,
        ]);
        $attacker->centers()->attach($otherCenter->id, ['tenant_id' => $otherTenant->id]);
        app(PermissionRegistrar::class)->setPermissionsTeamId($otherTenant->id);
        $attacker->givePermissionTo('crm.work_orders.view');

        $response = $this->actingAs($attacker)->get('/app/workshop');

        $response->assertStatus(200);
        $response->assertInertia(
            fn ($page) => $page->where('todayAppointments', [])
        );
    }

    public function test_other_center_appointment_does_not_appear(): void
    {
        $owner = $this->createUserWithPermissions(['crm.work_orders.view']);
        [$customer, $vehicle, $bay, $tech] = $this->buildFixture($owner);

        $start = Carbon::now()->addHours(2)->setMinute(0)->setSecond(0);
        $this->scheduler->create(
            $start, $start->copy()->addMinutes(45),
            $customer, $vehicle, $bay, $tech, []
        );

        // Build a "neighbor" center in the same tenant.
        $otherCenter = Center::factory()->create(['tenant_id' => $owner->tenant_id]);
        $otherCustomer = Customer::create([
            'tenant_id' => $owner->tenant_id,
            'center_id' => $otherCenter->id,
            'type' => 'individual',
            'name' => 'Other '.uniqid(),
            'phone' => '05'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
        ]);
        $otherVehicle = Vehicle::create([
            'tenant_id' => $owner->tenant_id,
            'center_id' => $otherCenter->id,
            'customer_id' => $otherCustomer->id,
            'plate_number' => 'BRN 7777',
        ]);
        $otherBay = ServiceBay::create([
            'tenant_id' => $owner->tenant_id,
            'center_id' => $otherCenter->id,
            'name' => 'Foreign Bay',
            'is_active' => true,
        ]);
        $otherTech = User::factory()->create([
            'tenant_id' => $owner->tenant_id,
            'current_center_id' => $otherCenter->id,
        ]);
        $otherTech->centers()->attach($otherCenter->id, ['tenant_id' => $owner->tenant_id]);

        $this->scheduler->create(
            $start, $start->copy()->addMinutes(45),
            $otherCustomer, $otherVehicle, $otherBay, $otherTech, []
        );

        $response = $this->actingAs($owner)->get('/app/workshop');

        $response->assertInertia(
            fn ($page) => $page->has('todayAppointments', 1)
                ->where('todayAppointments.0.customer.name', $customer->name)
        );
    }

    public function test_cancelled_no_show_and_converted_appointments_do_not_appear(): void
    {
        $owner = $this->createUserWithPermissions(['crm.work_orders.view']);
        [$customer, $vehicle, $bay, $tech] = $this->buildFixture($owner);

        $start = Carbon::now()->addHours(2)->setMinute(0)->setSecond(0);
        $base = $this->scheduler->create(
            $start, $start->copy()->addMinutes(45),
            $customer, $vehicle, $bay, $tech, []
        );

        $cancelled = $this->scheduler->create(
            $start->copy()->addHours(2), $start->copy()->addHours(3),
            $customer, $vehicle, $bay, $tech, []
        );
        $this->scheduler->cancel($cancelled, 'no');

        $noShow = $this->scheduler->create(
            $start->copy()->addHours(3), $start->copy()->addHours(4),
            $customer, $vehicle, $bay, $tech, []
        );
        $this->scheduler->markNoShow($noShow);

        // Converted appointments would need a service + a work
        // order; we manually flip the status here so we don't
        // pull in unrelated Phase-4 helpers.
        $base->update(['status' => Appointment::STATUS_CONVERTED]);

        $response = $this->actingAs($owner)->get('/app/workshop');

        $response->assertInertia(
            fn ($page) => $page->has('todayAppointments', 0)
        );
    }

    public function test_tomorrow_appointment_does_not_appear_in_today_section(): void
    {
        $owner = $this->createUserWithPermissions(['crm.work_orders.view']);
        [$customer, $vehicle, $bay, $tech] = $this->buildFixture($owner);

        $tomorrow = Carbon::now()->addDay()->setTime(10, 0);
        $this->scheduler->create(
            $tomorrow, $tomorrow->copy()->addMinutes(30),
            $customer, $vehicle, $bay, $tech, []
        );

        $response = $this->actingAs($owner)->get('/app/workshop');

        $response->assertInertia(
            fn ($page) => $page->has('todayAppointments', 0)
        );
    }

    public function test_appointments_ordered_by_scheduled_at_ascending(): void
    {
        $owner = $this->createUserWithPermissions(['crm.work_orders.view']);
        [$customer, $vehicle, $bay, $tech] = $this->buildFixture($owner);

        // Anchor the three slots in the same calendar day no
        // matter when the suite runs. The original
        // implementation used now()->addHours(2) as the base +
        // addHours(5) for the last slot, which crossed the day
        // boundary around 19:00 and dropped one row out of the
        // today section.
        //
        // Strategy: pick a base 30 minutes in the future, then
        // place the three slots 15 minutes apart (so the full
        // 30-minute spread + the 30-minute duration of the last
        // slot fit in 60 minutes). If now+30min has crossed
        // into tomorrow, fall back to 15 minutes before today's
        // endOfDay. The clamp keeps every slot within the
        // current calendar day, even at 23:55.
        $now = Carbon::now();
        $endOfDay = $now->copy()->endOfDay();

        if ($now->copy()->addMinutes(30)->lt($endOfDay)) {
            // Normal case: plenty of room today. Use now+30min
            // as the base.
            $base = $now->copy()->addMinutes(30)->setSecond(0);
        } else {
            // Late night: not enough room for a 30-minute
            // lookahead. Place the base 45 minutes before
            // endOfDay so the last slot's 30-minute duration
            // still ends before midnight.
            $base = $endOfDay->copy()->subMinutes(45);
        }

        $early = $base->copy();
        $mid = $base->copy()->addMinutes(15);
        $late = $base->copy()->addMinutes(30);

        // 15-minute duration per slot (shorter than the typical
        // 30 used elsewhere in the suite) so back-to-back
        // appointments do not conflict on the same bay. Three
        // back-to-back 15-min slots fit in 45 minutes from the
        // base, well within today's remaining hours.
        $this->scheduler->create($mid, $mid->copy()->addMinutes(15), $customer, $vehicle, $bay, $tech, []);
        $this->scheduler->create($late, $late->copy()->addMinutes(15), $customer, $vehicle, $bay, $tech, []);
        $this->scheduler->create($early, $early->copy()->addMinutes(15), $customer, $vehicle, $bay, $tech, []);

        $response = $this->actingAs($owner)->get('/app/workshop');

        $response->assertInertia(
            fn ($page) => $page
                ->has('todayAppointments', 3)
                ->where('todayAppointments.0.scheduled_at', $early->toIso8601String())
                ->where('todayAppointments.1.scheduled_at', $mid->toIso8601String())
                ->where('todayAppointments.2.scheduled_at', $late->toIso8601String())
        );
    }

    public function test_user_without_work_orders_view_gets_403(): void
    {
        $user = $this->createUserWithPermissions([]);

        $this->actingAs($user)->get('/app/workshop')->assertStatus(403);
    }

    public function test_can_manage_appointments_flag_is_set_for_authorized_user(): void
    {
        $owner = $this->createUserWithPermissions([
            'crm.work_orders.view', 'crm.appointments.manage',
        ]);

        $this->actingAs($owner)->get('/app/workshop')
            ->assertInertia(
                fn ($page) => $page->where('canManageAppointments', true)
            );
    }

    public function test_can_manage_appointments_flag_is_false_for_observer(): void
    {
        $owner = $this->createUserWithPermissions(['crm.work_orders.view']);

        $this->actingAs($owner)->get('/app/workshop')
            ->assertInertia(
                fn ($page) => $page->where('canManageAppointments', false)
            );
    }

    public function test_payload_is_capped_at_today_limit(): void
    {
        $owner = $this->createUserWithPermissions(['crm.work_orders.view']);
        [$customer, $vehicle, $bay, $tech] = $this->buildFixture($owner);

        // The controller caps at TODAY_APPOINTMENTS_LIMIT (8). We
        // book 10 today to make sure only 8 come through.
        $start = Carbon::now()->addHours(1)->setMinute(0)->setSecond(0);

        for ($i = 0; $i < 10; $i++) {
            $s = $start->copy()->addMinutes($i * 10);
            $this->scheduler->create(
                $s, $s->copy()->addMinutes(5),
                $customer, $vehicle, $bay, $tech, []
            );
        }

        $this->actingAs($owner)->get('/app/workshop')
            ->assertInertia(
                fn ($page) => $page->has('todayAppointments', 8)
            );
    }

    public function test_check_in_from_board_endpoint_changes_status(): void
    {
        $owner = $this->createUserWithPermissions([
            'crm.work_orders.view', 'crm.appointments.manage',
        ]);
        [$customer, $vehicle, $bay, $tech] = $this->buildFixture($owner);

        $start = Carbon::now()->addHours(2)->setMinute(0)->setSecond(0);
        $appt = $this->scheduler->create(
            $start, $start->copy()->addMinutes(45),
            $customer, $vehicle, $bay, $tech, []
        );

        $response = $this->actingAs($owner)->postJson(
            "/app/appointments/{$appt->id}/check-in"
        );

        $response->assertStatus(200);
        $appt->refresh();
        $this->assertSame(Appointment::STATUS_CHECKED_IN, $appt->status);
        $this->assertNotNull($appt->checked_in_at);
    }

    public function test_check_in_rejected_for_cancelled_appointment(): void
    {
        $owner = $this->createUserWithPermissions([
            'crm.work_orders.view', 'crm.appointments.manage',
        ]);
        [$customer, $vehicle, $bay, $tech] = $this->buildFixture($owner);

        $start = Carbon::now()->addHours(2)->setMinute(0)->setSecond(0);
        $appt = $this->scheduler->create(
            $start, $start->copy()->addMinutes(45),
            $customer, $vehicle, $bay, $tech, []
        );
        $this->scheduler->cancel($appt, 'no');

        $response = $this->actingAs($owner)->postJson(
            "/app/appointments/{$appt->id}/check-in"
        );

        $response->assertStatus(422);
    }

    public function test_check_in_rejected_for_converted_appointment(): void
    {
        $owner = $this->createUserWithPermissions([
            'crm.work_orders.view', 'crm.appointments.manage',
        ]);
        [$customer, $vehicle, $bay, $tech] = $this->buildFixture($owner);

        $start = Carbon::now()->addHours(2)->setMinute(0)->setSecond(0);
        $appt = $this->scheduler->create(
            $start, $start->copy()->addMinutes(45),
            $customer, $vehicle, $bay, $tech, []
        );
        $appt->update(['status' => Appointment::STATUS_CONVERTED]);

        $response = $this->actingAs($owner)->postJson(
            "/app/appointments/{$appt->id}/check-in"
        );

        $response->assertStatus(422);
    }
}
