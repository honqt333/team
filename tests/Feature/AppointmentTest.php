<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Center;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Service;
use App\Models\ServiceBay;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Services\AppointmentConversionService;
use App\Services\AppointmentSchedulingService;
use Carbon\Carbon;
use DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AppointmentTest extends TestCase
{
    use RefreshDatabase;

    protected AppointmentSchedulingService $scheduler;

    protected AppointmentConversionService $converter;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::findOrCreate('crm.appointments.manage', 'web');
        Permission::findOrCreate('crm.work_orders.view', 'web');
        Permission::findOrCreate('crm.work_orders.update', 'web');
        Permission::findOrCreate('crm.customers.view', 'web');
        Permission::findOrCreate('crm.vehicles.view', 'web');

        $this->scheduler = app(AppointmentSchedulingService::class);
        $this->converter = app(AppointmentConversionService::class);
    }

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

    protected function createService(User $owner, ?int $duration = 30): Service
    {
        $department = Department::create([
            'tenant_id' => $owner->tenant_id,
            'center_id' => $owner->current_center_id,
            'name_ar' => 'القسم '.uniqid(),
            'name_en' => 'Dept '.uniqid(),
            'is_active' => true,
        ]);

        return Service::create([
            'tenant_id' => $owner->tenant_id,
            'center_id' => $owner->current_center_id,
            'department_id' => $department->id,
            'name_ar' => 'الخدمة '.uniqid(),
            'name_en' => 'Service '.uniqid(),
            'base_price' => 100,
            'duration_value' => $duration,
            'duration_unit' => 'minute',
            'is_active' => true,
            'type' => Service::TYPE_INTERNAL,
        ]);
    }

    // ==================== Creation ====================

    public function test_can_create_appointment_in_current_center(): void
    {
        $owner = $this->createUserWithPermissions(['crm.appointments.manage']);
        [$customer, $vehicle, $bay, $tech] = $this->buildFixture($owner);
        $service = $this->createService($owner);

        $start = Carbon::now()->addDay()->setTime(10, 0);
        $end = $start->copy()->addMinutes(45);

        $appt = $this->scheduler->create(
            $start, $end, $customer, $vehicle, $bay, $tech,
            [$service->id],
            ['notes' => 'first booking']
        );

        $this->assertSame(Appointment::STATUS_SCHEDULED, $appt->status);
        $this->assertSame($owner->tenant_id, $appt->tenant_id);
        $this->assertSame($owner->current_center_id, $appt->center_id);
        $this->assertTrue($appt->services()->where('services.id', $service->id)->exists());
    }

    public function test_cannot_create_in_the_past(): void
    {
        $owner = $this->createUserWithPermissions(['crm.appointments.manage']);
        [$customer, $vehicle, $bay, $tech] = $this->buildFixture($owner);

        $start = Carbon::now()->subHour();
        $end = $start->copy()->addMinutes(30);

        $threw = false;

        try {
            $this->scheduler->create($start, $end, $customer, $vehicle, $bay, $tech, []);
        } catch (RuntimeException $e) {
            $threw = $e->getMessage() === 'start_in_past';
        }
        $this->assertTrue($threw);
    }

    public function test_end_must_be_after_start(): void
    {
        $owner = $this->createUserWithPermissions(['crm.appointments.manage']);
        [$customer, $vehicle, $bay, $tech] = $this->buildFixture($owner);

        $start = Carbon::now()->addDay();
        $end = $start->copy()->subMinutes(10);

        $threw = false;

        try {
            $this->scheduler->create($start, $end, $customer, $vehicle, $bay, $tech, []);
        } catch (RuntimeException $e) {
            $threw = $e->getMessage() === 'end_before_start';
        }
        $this->assertTrue($threw);
    }

    // ==================== Conflict detection ====================

    public function test_bay_double_booking_is_rejected(): void
    {
        $owner = $this->createUserWithPermissions(['crm.appointments.manage']);
        [$customer, $vehicle, $bay, $tech] = $this->buildFixture($owner);

        $start = Carbon::now()->addDay()->setTime(10, 0);
        $this->scheduler->create(
            $start, $start->copy()->addMinutes(60),
            $customer, $vehicle, $bay, $tech, []
        );

        $threw = false;

        try {
            $this->scheduler->create(
                $start->copy()->addMinutes(30), $start->copy()->addMinutes(90),
                $customer, $vehicle, $bay, $tech, []
            );
        } catch (RuntimeException $e) {
            $threw = $e->getMessage() === 'bay_conflict';
        }
        $this->assertTrue($threw);
    }

    public function test_technician_double_booking_is_rejected(): void
    {
        $owner = $this->createUserWithPermissions(['crm.appointments.manage']);
        [$customer, $vehicle, $bay, $tech] = $this->buildFixture($owner);

        $start = Carbon::now()->addDay()->setTime(10, 0);
        $this->scheduler->create(
            $start, $start->copy()->addMinutes(60),
            $customer, $vehicle, $bay, $tech, []
        );

        // Build a second bay so the bay itself doesn't conflict —
        // we want to see the technician-level conflict.
        $bay2 = ServiceBay::create([
            'tenant_id' => $owner->tenant_id,
            'center_id' => $owner->current_center_id,
            'name' => 'Bay 2 '.uniqid(),
            'is_active' => true,
        ]);

        $threw = false;

        try {
            $this->scheduler->create(
                $start->copy()->addMinutes(15), $start->copy()->addMinutes(45),
                $customer, $vehicle, $bay2, $tech, []
            );
        } catch (RuntimeException $e) {
            $threw = $e->getMessage() === 'technician_conflict';
        }
        $this->assertTrue($threw);
    }

    public function test_vehicle_double_booking_is_rejected(): void
    {
        $owner = $this->createUserWithPermissions(['crm.appointments.manage']);
        [$customer, $vehicle, $bay, $tech] = $this->buildFixture($owner);

        $start = Carbon::now()->addDay()->setTime(10, 0);
        $this->scheduler->create(
            $start, $start->copy()->addMinutes(60),
            $customer, $vehicle, $bay, $tech, []
        );

        // Build a second bay + a different technician so that the
        // bay/tech checks pass and we isolate the vehicle check.
        $otherBay = ServiceBay::create([
            'tenant_id' => $owner->tenant_id,
            'center_id' => $owner->current_center_id,
            'name' => 'Bay 2 '.uniqid(),
            'is_active' => true,
        ]);
        $otherTech = User::factory()->create([
            'tenant_id' => $owner->tenant_id,
            'current_center_id' => $owner->current_center_id,
        ]);
        $otherTech->centers()->attach($owner->current_center_id, ['tenant_id' => $owner->tenant_id]);

        $threw = false;

        try {
            $this->scheduler->create(
                $start->copy()->addMinutes(20), $start->copy()->addMinutes(40),
                $customer, $vehicle, $otherBay, $otherTech, []
            );
        } catch (RuntimeException $e) {
            $threw = $e->getMessage() === 'vehicle_conflict';
        }
        $this->assertTrue($threw);
    }

    public function test_booking_after_cancelled_appointment_is_allowed(): void
    {
        $owner = $this->createUserWithPermissions(['crm.appointments.manage']);
        [$customer, $vehicle, $bay, $tech] = $this->buildFixture($owner);

        $start = Carbon::now()->addDay()->setTime(10, 0);
        $first = $this->scheduler->create(
            $start, $start->copy()->addMinutes(60),
            $customer, $vehicle, $bay, $tech, []
        );
        $this->scheduler->cancel($first, 'customer changed mind');

        // The same slot should now be free.
        $second = $this->scheduler->create(
            $start->copy()->addMinutes(15), $start->copy()->addMinutes(45),
            $customer, $vehicle, $bay, $tech, []
        );

        $this->assertSame(Appointment::STATUS_SCHEDULED, $second->status);
        $this->assertNotSame($first->id, $second->id);
    }

    public function test_booking_after_no_show_is_allowed(): void
    {
        $owner = $this->createUserWithPermissions(['crm.appointments.manage']);
        [$customer, $vehicle, $bay, $tech] = $this->buildFixture($owner);

        $start = Carbon::now()->addDay()->setTime(10, 0);
        $first = $this->scheduler->create(
            $start, $start->copy()->addMinutes(60),
            $customer, $vehicle, $bay, $tech, []
        );
        $this->scheduler->markNoShow($first);

        $second = $this->scheduler->create(
            $start, $start->copy()->addMinutes(60),
            $customer, $vehicle, $bay, $tech, []
        );

        $this->assertNotSame($first->id, $second->id);
    }

    // ==================== Status transitions ====================

    public function test_check_in_no_show_and_cancel_set_correct_status(): void
    {
        $owner = $this->createUserWithPermissions(['crm.appointments.manage']);
        [$customer, $vehicle, $bay, $tech] = $this->buildFixture($owner);

        $start = Carbon::now()->addDay();
        $appt = $this->scheduler->create(
            $start, $start->copy()->addMinutes(30),
            $customer, $vehicle, $bay, $tech, []
        );

        $this->scheduler->checkIn($appt);
        $appt->refresh();
        $this->assertSame(Appointment::STATUS_CHECKED_IN, $appt->status);
        $this->assertNotNull($appt->checked_in_at);

        $appt2 = $this->scheduler->create(
            $start->copy()->addHours(2), $start->copy()->addHours(3),
            $customer, $vehicle, $bay, $tech, []
        );
        $this->scheduler->markNoShow($appt2);
        $appt2->refresh();
        $this->assertSame(Appointment::STATUS_NO_SHOW, $appt2->status);

        $appt3 = $this->scheduler->create(
            $start->copy()->addHours(4), $start->copy()->addHours(5),
            $customer, $vehicle, $bay, $tech, []
        );
        $this->scheduler->cancel($appt3, 'duplicate');
        $appt3->refresh();
        $this->assertSame(Appointment::STATUS_CANCELLED, $appt3->status);
        $this->assertSame('duplicate', $appt3->cancellation_reason);
    }

    // ==================== Conversion to work order ====================

    public function test_convert_appointment_creates_a_work_order_with_services(): void
    {
        $owner = $this->createUserWithPermissions([
            'crm.appointments.manage', 'crm.work_orders.view', 'crm.work_orders.update',
        ]);
        [$customer, $vehicle, $bay, $tech] = $this->buildFixture($owner);
        $service = $this->createService($owner, 45);

        $start = Carbon::now()->addDay()->setTime(10, 0);
        $appt = $this->scheduler->create(
            $start, $start->copy()->addMinutes(60),
            $customer, $vehicle, $bay, $tech, [$service->id]
        );

        $workOrder = $this->converter->convert($appt, $owner);

        $this->assertSame($customer->id, $workOrder->customer_id);
        $this->assertSame($vehicle->id, $workOrder->vehicle_id);
        $this->assertSame($owner->current_center_id, $workOrder->center_id);
        $this->assertSame(WorkOrder::STATUS_OPEN, $workOrder->status);

        $this->assertSame(1, $workOrder->items()->count());
        $item = $workOrder->items()->first();
        $this->assertSame($service->id, $item->service_id);
        $this->assertSame(1, (int) $item->qty);
    }

    public function test_double_convert_returns_same_work_order(): void
    {
        $owner = $this->createUserWithPermissions([
            'crm.appointments.manage', 'crm.work_orders.view', 'crm.work_orders.update',
        ]);
        [$customer, $vehicle, $bay, $tech] = $this->buildFixture($owner);
        $service = $this->createService($owner);

        $start = Carbon::now()->addDay();
        $appt = $this->scheduler->create(
            $start, $start->copy()->addMinutes(45),
            $customer, $vehicle, $bay, $tech, [$service->id]
        );

        $first = $this->converter->convert($appt, $owner);
        $second = $this->converter->convert($appt->refresh(), $owner);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, WorkOrder::where('customer_id', $customer->id)->count());
    }

    public function test_cannot_convert_cancelled_appointment(): void
    {
        $owner = $this->createUserWithPermissions([
            'crm.appointments.manage', 'crm.work_orders.view', 'crm.work_orders.update',
        ]);
        [$customer, $vehicle, $bay, $tech] = $this->buildFixture($owner);

        $start = Carbon::now()->addDay();
        $appt = $this->scheduler->create(
            $start, $start->copy()->addMinutes(30),
            $customer, $vehicle, $bay, $tech, []
        );
        $this->scheduler->cancel($appt, 'no');

        $threw = false;

        try {
            $this->converter->convert($appt->refresh(), $owner);
        } catch (RuntimeException $e) {
            $threw = $e->getMessage() === 'appointment_not_convertible';
        }
        $this->assertTrue($threw);
    }

    // ==================== Isolation ====================

    public function test_appointment_route_is_invisible_across_tenants(): void
    {
        $owner = $this->createUserWithPermissions(['crm.appointments.manage']);

        $attacker = User::factory()->create([
            'tenant_id' => Tenant::factory()->create()->id,
            'current_center_id' => null,
        ]);

        $this->actingAs($attacker)
            ->getJson('/app/appointments')
            ->assertStatus(403);
    }

    public function test_authorized_user_can_view_appointments_index_with_bilingual_services(): void
    {
        $owner = $this->createUserWithPermissions(['crm.appointments.manage']);
        [$customer, $vehicle, $bay, $tech] = $this->buildFixture($owner);

        $service = Service::create([
            'tenant_id' => $owner->tenant_id,
            'center_id' => $owner->current_center_id,
            'name_ar' => 'تغيير زيت وفلتر',
            'name_en' => 'Oil & Filter Change',
            'base_price' => 200,
            'duration_value' => 45,
            'duration_unit' => 'minute',
            'type' => 'internal',
            'is_active' => true,
        ]);

        $response = $this->actingAs($owner)->get('/app/appointments');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Appointments/Index')
            ->has('services', 1)
            ->where('services.0.id', $service->id)
            ->where('services.0.duration_value', 45)
        );
    }

    public function test_bay_from_other_center_is_rejected(): void
    {
        $owner = $this->createUserWithPermissions(['crm.appointments.manage']);
        [$customer, $vehicle, $bay, $tech] = $this->buildFixture($owner);

        $otherCenter = Center::factory()->create(['tenant_id' => $owner->tenant_id]);
        $foreignBay = ServiceBay::create([
            'tenant_id' => $owner->tenant_id,
            'center_id' => $otherCenter->id,
            'name' => 'Foreign Bay',
            'is_active' => true,
        ]);

        $start = Carbon::now()->addDay();

        $threw = false;

        try {
            $this->scheduler->create(
                $start, $start->copy()->addMinutes(30),
                $customer, $vehicle, $foreignBay, $tech, []
            );
        } catch (RuntimeException $e) {
            $threw = $e->getMessage() === 'cross_tenant_or_center';
        }
        $this->assertTrue($threw);
    }

    public function test_inactive_bay_is_rejected(): void
    {
        $owner = $this->createUserWithPermissions(['crm.appointments.manage']);
        [$customer, $vehicle, $bay, $tech] = $this->buildFixture($owner);
        $bay->update(['is_active' => false]);

        $start = Carbon::now()->addDay();

        $threw = false;

        try {
            $this->scheduler->create(
                $start, $start->copy()->addMinutes(30),
                $customer, $vehicle, $bay, $tech, []
            );
        } catch (RuntimeException $e) {
            $threw = $e->getMessage() === 'bay_inactive';
        }
        $this->assertTrue($threw);
    }

    public function test_vehicle_from_other_customer_is_rejected(): void
    {
        $owner = $this->createUserWithPermissions(['crm.appointments.manage']);
        [$customer, $vehicle, $bay, $tech] = $this->buildFixture($owner);

        // Build a vehicle under a different customer in the same center.
        $otherCustomer = Customer::create([
            'tenant_id' => $owner->tenant_id,
            'center_id' => $owner->current_center_id,
            'type' => 'individual',
            'name' => 'Other '.uniqid(),
            'phone' => '05'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
        ]);
        $foreignVehicle = Vehicle::create([
            'tenant_id' => $owner->tenant_id,
            'center_id' => $owner->current_center_id,
            'customer_id' => $otherCustomer->id,
            'plate_number' => 'OTH 0001',
        ]);

        $start = Carbon::now()->addDay();

        $threw = false;

        try {
            $this->scheduler->create(
                $start, $start->copy()->addMinutes(30),
                $customer, $vehicle, $bay, $tech, []
            );
            $this->scheduler->create(
                $start->copy()->addHours(2), $start->copy()->addHours(3),
                $customer, $foreignVehicle, $bay, $tech, []
            );
        } catch (RuntimeException $e) {
            $threw = $e->getMessage() === 'vehicle_not_owned_by_customer';
        }
        $this->assertTrue($threw);
    }

    // ==================== ServiceBay CRUD ====================

    public function test_bay_with_active_appointments_cannot_be_deleted(): void
    {
        $owner = $this->createUserWithPermissions(['crm.appointments.manage']);
        [$customer, $vehicle, $bay, $tech] = $this->buildFixture($owner);

        $start = Carbon::now()->addDay();
        $this->scheduler->create(
            $start, $start->copy()->addMinutes(30),
            $customer, $vehicle, $bay, $tech, []
        );

        $this->assertTrue($bay->hasActiveAppointments());

        $response = $this->actingAs($owner)->deleteJson("/app/service-bays/{$bay->id}");

        $response->assertStatus(422);
        $response->assertJsonPath('code', 'bay_has_active_appointments');
    }

    public function test_bay_without_active_appointments_can_be_deleted(): void
    {
        $owner = $this->createUserWithPermissions(['crm.appointments.manage']);
        [, , $bay] = $this->buildFixture($owner);

        $this->assertFalse($bay->hasActiveAppointments());

        $response = $this->actingAs($owner)->deleteJson("/app/service-bays/{$bay->id}");

        $response->assertStatus(200);
        $this->assertNull(ServiceBay::find($bay->id));
    }

    // ==================== P1 hardening tests ====================

    /**
     * P1: two parallel bookings for the same vehicle. The first
     * wins, the second sees the first's row inside the same
     * connection (because we wrap both calls in the same
     * transaction in this test) and the row stays visible — the
     * vehicle lock + the re-read inside the lock window is what
     * catches this in MySQL/PostgreSQL with a real concurrent
     * process. We assert both outcomes here:
     *   1. the second call throws vehicle_conflict when called
     *      AFTER the first is committed (the common case);
     *   2. the second call also throws when called BEFORE the
     *      first commits, because the first transaction's row is
     *      visible to the second one inside the same connection
     *      (the lockForUpdate on the vehicle row would block it
     *      in a real concurrent scenario).
     */
    public function test_concurrent_vehicle_booking_only_one_wins(): void
    {
        $owner = $this->createUserWithPermissions(['crm.appointments.manage']);
        [$customer, $vehicle, $bay, $tech] = $this->buildFixture($owner);

        // Build a second bay so the bay check is satisfied and
        // only the vehicle check can fail.
        $otherBay = ServiceBay::create([
            'tenant_id' => $owner->tenant_id,
            'center_id' => $owner->current_center_id,
            'name' => 'Bay 2 '.uniqid(),
            'is_active' => true,
        ]);
        $otherTech = User::factory()->create([
            'tenant_id' => $owner->tenant_id,
            'current_center_id' => $owner->current_center_id,
        ]);
        $otherTech->centers()->attach($owner->current_center_id, ['tenant_id' => $owner->tenant_id]);

        $start = Carbon::now()->addDay()->setTime(10, 0);

        $first = $this->scheduler->create(
            $start, $start->copy()->addMinutes(60),
            $customer, $vehicle, $otherBay, $otherTech, []
        );

        // Common case: second call AFTER the first is committed,
        // using a DIFFERENT bay + DIFFERENT tech so only the
        // vehicle check can fail.
        $threw = false;

        try {
            $this->scheduler->create(
                $start->copy()->addMinutes(15), $start->copy()->addMinutes(45),
                $customer, $vehicle, $bay, $tech, []
            );
        } catch (RuntimeException $e) {
            $threw = $e->getMessage() === 'vehicle_conflict';
        }
        $this->assertTrue($threw);

        // Now wrap the second call in a transaction that the
        // first one is still inside, with the SAME window. The
        // first row is visible to the second transaction in the
        // same connection — same protection as a real concurrent
        // process would see after lockForUpdate.
        DB::beginTransaction();

        try {
            $threw2 = false;

            try {
                $this->scheduler->create(
                    $start->copy()->addMinutes(15), $start->copy()->addMinutes(45),
                    $customer, $vehicle, $bay, $tech, []
                );
            } catch (RuntimeException $e) {
                $threw2 = $e->getMessage() === 'vehicle_conflict';
            }
            $this->assertTrue($threw2, 'second call inside open transaction must still see the first appointment.');
        } finally {
            DB::rollBack();
        }

        $this->assertNotNull($first->id);
    }

    /**
     * P1: reschedule must accept "keep current bay" — passing
     * changeBay=false with no bay arg must keep the original
     * bay and pass the conflict check against itself (we
     * exclude the appointment id from the conflict scan).
     */
    public function test_reschedule_with_current_bay_succeeds(): void
    {
        $owner = $this->createUserWithPermissions(['crm.appointments.manage']);
        [$customer, $vehicle, $bay, $tech] = $this->buildFixture($owner);

        $start = Carbon::now()->addDay()->setTime(10, 0);
        $appt = $this->scheduler->create(
            $start, $start->copy()->addMinutes(60),
            $customer, $vehicle, $bay, $tech, []
        );

        $newStart = $start->copy()->addHours(3);
        $newEnd = $newStart->copy()->addMinutes(45);

        $rescheduled = $this->scheduler->reschedule(
            $appt,
            $newStart,
            $newEnd,
            // changeBay omitted — keep the existing bay
            changeBay: false,
            changeAssignee: false,
        );

        $this->assertSame($bay->id, $rescheduled->service_bay_id);
        $this->assertSame($tech->id, $rescheduled->assigned_user_id);
        $this->assertSame($newStart->toIso8601String(), $rescheduled->scheduled_at->toIso8601String());
    }

    /**
     * P1: reschedule with changeBay=true and a null bay must
     * CLEAR the bay. Distinguishes "not passed" from
     * "explicitly null".
     */
    public function test_reschedule_can_explicitly_clear_the_bay(): void
    {
        $owner = $this->createUserWithPermissions(['crm.appointments.manage']);
        [$customer, $vehicle, $bay, $tech] = $this->buildFixture($owner);

        $start = Carbon::now()->addDay()->setTime(10, 0);
        $appt = $this->scheduler->create(
            $start, $start->copy()->addMinutes(60),
            $customer, $vehicle, $bay, $tech, []
        );

        $newStart = $start->copy()->addHours(3);
        $rescheduled = $this->scheduler->reschedule(
            $appt,
            $newStart,
            $newStart->copy()->addMinutes(45),
            changeBay: true,
            bay: null,
        );

        $this->assertNull($rescheduled->service_bay_id, 'changeBay=true + null bay must clear the bay.');
    }

    /**
     * P1: reschedule must re-check conflicts with the FINAL bay
     * (after the change). We swap the appointment to a new bay
     * that is already taken in the new window — the swap must
     * be rejected even though the OLD bay was free.
     */
    public function test_reschedule_swap_to_busy_bay_is_rejected(): void
    {
        $owner = $this->createUserWithPermissions(['crm.appointments.manage']);
        [$customer, $vehicle, $bay, $tech] = $this->buildFixture($owner);

        $otherBay = ServiceBay::create([
            'tenant_id' => $owner->tenant_id,
            'center_id' => $owner->current_center_id,
            'name' => 'Other Bay '.uniqid(),
            'is_active' => true,
        ]);

        $start = Carbon::now()->addDay()->setTime(10, 0);

        // A second customer/vehicle takes the otherBay in the
        // window we want to reschedule into.
        $otherCustomer = Customer::create([
            'tenant_id' => $owner->tenant_id,
            'center_id' => $owner->current_center_id,
            'type' => 'individual',
            'name' => 'Other '.uniqid(),
            'phone' => '05'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
        ]);
        $otherVehicle = Vehicle::create([
            'tenant_id' => $owner->tenant_id,
            'center_id' => $owner->current_center_id,
            'customer_id' => $otherCustomer->id,
            'plate_number' => 'OTH 0001',
        ]);

        $newStart = $start->copy()->addHours(4);
        $this->scheduler->create(
            $newStart, $newStart->copy()->addMinutes(60),
            $otherCustomer, $otherVehicle, $otherBay, $tech, []
        );

        // The original appointment starts in the original bay.
        $appt = $this->scheduler->create(
            $start, $start->copy()->addMinutes(60),
            $customer, $vehicle, $bay, $tech, []
        );

        // Reschedule the original appointment into the other bay's
        // window. Must fail with bay_conflict, not silently
        // succeed because the OLD bay was free.
        $threw = false;

        try {
            $this->scheduler->reschedule(
                $appt,
                $newStart,
                $newStart->copy()->addMinutes(45),
                changeBay: true,
                bay: $otherBay,
            );
        } catch (RuntimeException $e) {
            $threw = $e->getMessage() === 'bay_conflict';
        }
        $this->assertTrue($threw);

        // The appointment must NOT have been moved to the other bay.
        $appt->refresh();
        $this->assertSame($bay->id, $appt->service_bay_id);
    }

    /**
     * P1: passing a resource from a different center must be
     * rejected with cross_tenant_or_center, not silently allowed.
     */
    public function test_reschedule_with_other_center_bay_is_rejected(): void
    {
        $owner = $this->createUserWithPermissions(['crm.appointments.manage']);
        [$customer, $vehicle, $bay, $tech] = $this->buildFixture($owner);

        $otherCenter = Center::factory()->create(['tenant_id' => $owner->tenant_id]);
        $foreignBay = ServiceBay::create([
            'tenant_id' => $owner->tenant_id,
            'center_id' => $otherCenter->id,
            'name' => 'Foreign '.uniqid(),
            'is_active' => true,
        ]);

        $start = Carbon::now()->addDay()->setTime(10, 0);
        $appt = $this->scheduler->create(
            $start, $start->copy()->addMinutes(60),
            $customer, $vehicle, $bay, $tech, []
        );

        $threw = false;

        try {
            $this->scheduler->reschedule(
                $appt,
                $start->copy()->addHours(2),
                $start->copy()->addHours(3),
                changeBay: true,
                bay: $foreignBay,
            );
        } catch (RuntimeException $e) {
            $threw = $e->getMessage() === 'cross_tenant_or_center';
        }
        $this->assertTrue($threw);
    }

    // ==================== Strict state transitions ====================

    public function test_create_with_non_scheduled_status_is_rejected(): void
    {
        $owner = $this->createUserWithPermissions(['crm.appointments.manage']);
        [$customer, $vehicle, $bay, $tech] = $this->buildFixture($owner);

        $start = Carbon::now()->addDay()->setTime(10, 0);

        $threw = false;

        try {
            $this->scheduler->create(
                $start, $start->copy()->addMinutes(30),
                $customer, $vehicle, $bay, $tech, [],
                ['status' => 'converted']
            );
        } catch (RuntimeException $e) {
            $threw = $e->getMessage() === 'invalid_initial_status';
        }
        $this->assertTrue($threw);
    }

    public function test_cannot_check_in_cancelled_appointment(): void
    {
        $owner = $this->createUserWithPermissions(['crm.appointments.manage']);
        [$customer, $vehicle, $bay, $tech] = $this->buildFixture($owner);

        $start = Carbon::now()->addDay();
        $appt = $this->scheduler->create(
            $start, $start->copy()->addMinutes(30),
            $customer, $vehicle, $bay, $tech, []
        );
        $this->scheduler->cancel($appt, 'no');

        $threw = false;

        try {
            $this->scheduler->checkIn($appt->refresh());
        } catch (RuntimeException $e) {
            $threw = $e->getMessage() === 'invalid_transition_check_in';
        }
        $this->assertTrue($threw);
    }

    public function test_cannot_check_in_converted_appointment(): void
    {
        $owner = $this->createUserWithPermissions([
            'crm.appointments.manage', 'crm.work_orders.view', 'crm.work_orders.update',
        ]);
        [$customer, $vehicle, $bay, $tech] = $this->buildFixture($owner);
        $service = $this->createService($owner);

        $start = Carbon::now()->addDay();
        $appt = $this->scheduler->create(
            $start, $start->copy()->addMinutes(45),
            $customer, $vehicle, $bay, $tech, [$service->id]
        );

        $this->converter->convert($appt, $owner);

        $threw = false;

        try {
            $this->scheduler->checkIn($appt->refresh());
        } catch (RuntimeException $e) {
            $threw = $e->getMessage() === 'invalid_transition_check_in';
        }
        $this->assertTrue($threw);
    }

    public function test_cannot_reschedule_cancelled_or_no_show(): void
    {
        $owner = $this->createUserWithPermissions(['crm.appointments.manage']);
        [$customer, $vehicle, $bay, $tech] = $this->buildFixture($owner);

        $start = Carbon::now()->addDay();

        $cancelled = $this->scheduler->create(
            $start, $start->copy()->addMinutes(30),
            $customer, $vehicle, $bay, $tech, []
        );
        $this->scheduler->cancel($cancelled, 'no');

        $threwCancel = false;

        try {
            $this->scheduler->reschedule(
                $cancelled->refresh(),
                $start->copy()->addHours(3),
                $start->copy()->addHours(4),
            );
        } catch (RuntimeException $e) {
            $threwCancel = $e->getMessage() === 'invalid_transition_reschedule';
        }
        $this->assertTrue($threwCancel);

        $noShow = $this->scheduler->create(
            $start->copy()->addHours(5), $start->copy()->addHours(6),
            $customer, $vehicle, $bay, $tech, []
        );
        $this->scheduler->markNoShow($noShow);

        $threwNoShow = false;

        try {
            $this->scheduler->reschedule(
                $noShow->refresh(),
                $start->copy()->addHours(7),
                $start->copy()->addHours(8),
            );
        } catch (RuntimeException $e) {
            $threwNoShow = $e->getMessage() === 'invalid_transition_reschedule';
        }
        $this->assertTrue($threwNoShow);
    }

    public function test_cannot_convert_cancelled_or_no_show(): void
    {
        $owner = $this->createUserWithPermissions([
            'crm.appointments.manage', 'crm.work_orders.view', 'crm.work_orders.update',
        ]);
        [$customer, $vehicle, $bay, $tech] = $this->buildFixture($owner);

        $start = Carbon::now()->addDay();

        $cancelled = $this->scheduler->create(
            $start, $start->copy()->addMinutes(30),
            $customer, $vehicle, $bay, $tech, []
        );
        $this->scheduler->cancel($cancelled, 'no');

        $threwCancel = false;

        try {
            $this->converter->convert($cancelled->refresh(), $owner);
        } catch (RuntimeException $e) {
            $threwCancel = $e->getMessage() === 'appointment_not_convertible';
        }
        $this->assertTrue($threwCancel);

        $noShow = $this->scheduler->create(
            $start->copy()->addHours(3), $start->copy()->addHours(4),
            $customer, $vehicle, $bay, $tech, []
        );
        $this->scheduler->markNoShow($noShow);

        $threwNoShow = false;

        try {
            $this->converter->convert($noShow->refresh(), $owner);
        } catch (RuntimeException $e) {
            $threwNoShow = $e->getMessage() === 'appointment_not_convertible';
        }
        $this->assertTrue($threwNoShow);
    }

    public function test_cannot_cancel_no_show(): void
    {
        $owner = $this->createUserWithPermissions(['crm.appointments.manage']);
        [$customer, $vehicle, $bay, $tech] = $this->buildFixture($owner);

        $start = Carbon::now()->addDay();
        $appt = $this->scheduler->create(
            $start, $start->copy()->addMinutes(30),
            $customer, $vehicle, $bay, $tech, []
        );
        $this->scheduler->markNoShow($appt);

        $threw = false;

        try {
            $this->scheduler->cancel($appt->refresh(), 'no');
        } catch (RuntimeException $e) {
            $threw = $e->getMessage() === 'invalid_transition_cancel';
        }
        $this->assertTrue($threw);
    }

    // ==================== HTTP-level tests for the update route ====================

    /**
     * P1: PUT /appointments/{id} with `service_bay_id = <other>`
     * must swap the bay. The Vue UI relies on this to drag a
     * booking to a different bay.
     */
    public function test_http_update_can_change_bay(): void
    {
        $owner = $this->createUserWithPermissions(['crm.appointments.manage']);
        [$customer, $vehicle, $bay, $tech] = $this->buildFixture($owner);

        $otherBay = ServiceBay::create([
            'tenant_id' => $owner->tenant_id,
            'center_id' => $owner->current_center_id,
            'name' => 'Other Bay '.uniqid(),
            'is_active' => true,
        ]);

        $start = Carbon::now()->addDay()->setTime(10, 0);
        $appt = $this->scheduler->create(
            $start, $start->copy()->addMinutes(45),
            $customer, $vehicle, $bay, $tech, []
        );

        $newStart = $start->copy()->addHours(2);
        $response = $this->actingAs($owner)->putJson(
            "/app/appointments/{$appt->id}",
            [
                'scheduled_at' => $newStart->toIso8601String(),
                'estimated_end_at' => $newStart->copy()->addMinutes(45)->toIso8601String(),
                'service_bay_id' => $otherBay->id,
            ]
        );

        $response->assertStatus(200);
        $appt->refresh();
        $this->assertSame($otherBay->id, $appt->service_bay_id);
    }

    /**
     * P1: PUT /appointments/{id} with `service_bay_id = null`
     * must CLEAR the bay. This is the third state in
     * three-state resolution: keep, change, clear.
     */
    public function test_http_update_can_explicitly_clear_bay(): void
    {
        $owner = $this->createUserWithPermissions(['crm.appointments.manage']);
        [$customer, $vehicle, $bay, $tech] = $this->buildFixture($owner);

        $start = Carbon::now()->addDay()->setTime(10, 0);
        $appt = $this->scheduler->create(
            $start, $start->copy()->addMinutes(45),
            $customer, $vehicle, $bay, $tech, []
        );

        $this->assertNotNull($appt->service_bay_id);

        $newStart = $start->copy()->addHours(2);
        $response = $this->actingAs($owner)->putJson(
            "/app/appointments/{$appt->id}",
            [
                'scheduled_at' => $newStart->toIso8601String(),
                'estimated_end_at' => $newStart->copy()->addMinutes(45)->toIso8601String(),
                'service_bay_id' => null,
            ]
        );

        $response->assertStatus(200);
        $appt->refresh();
        $this->assertNull($appt->service_bay_id, 'service_bay_id: null must clear the bay, not keep it.');
    }

    /**
     * P1: PUT /appointments/{id} without `service_bay_id` at all
     * must KEEP the current bay. This is the first state in
     * three-state resolution.
     */
    public function test_http_update_without_bay_field_keeps_current_bay(): void
    {
        $owner = $this->createUserWithPermissions(['crm.appointments.manage']);
        [$customer, $vehicle, $bay, $tech] = $this->buildFixture($owner);

        $start = Carbon::now()->addDay()->setTime(10, 0);
        $appt = $this->scheduler->create(
            $start, $start->copy()->addMinutes(45),
            $customer, $vehicle, $bay, $tech, []
        );

        // No `service_bay_id` in the payload.
        $newStart = $start->copy()->addHours(2);
        $response = $this->actingAs($owner)->putJson(
            "/app/appointments/{$appt->id}",
            [
                'scheduled_at' => $newStart->toIso8601String(),
                'estimated_end_at' => $newStart->copy()->addMinutes(45)->toIso8601String(),
            ]
        );

        $response->assertStatus(200);
        $appt->refresh();
        $this->assertSame($bay->id, $appt->service_bay_id, 'omitted service_bay_id must keep the current bay.');
    }

    /**
     * P1: same three-state resolution for assigned_user_id.
     * Omit → keep, send a different id → swap, send null → clear.
     */
    public function test_http_update_tech_resolution(): void
    {
        $owner = $this->createUserWithPermissions(['crm.appointments.manage']);
        [$customer, $vehicle, $bay, $tech] = $this->buildFixture($owner);

        $otherTech = User::factory()->create([
            'tenant_id' => $owner->tenant_id,
            'current_center_id' => $owner->current_center_id,
        ]);
        $otherTech->centers()->attach($owner->current_center_id, ['tenant_id' => $owner->tenant_id]);

        $start = Carbon::now()->addDay()->setTime(10, 0);
        $appt = $this->scheduler->create(
            $start, $start->copy()->addMinutes(45),
            $customer, $vehicle, $bay, $tech, []
        );

        $newStart = $start->copy()->addHours(2);

        // Swap to a different tech.
        $swap = $this->actingAs($owner)->putJson(
            "/app/appointments/{$appt->id}",
            [
                'scheduled_at' => $newStart->toIso8601String(),
                'estimated_end_at' => $newStart->copy()->addMinutes(45)->toIso8601String(),
                'assigned_user_id' => $otherTech->id,
            ]
        );
        $swap->assertStatus(200);
        $appt->refresh();
        $this->assertSame($otherTech->id, $appt->assigned_user_id);

        // Clear the tech.
        $newerStart = $newStart->copy()->addHours(2);
        $clear = $this->actingAs($owner)->putJson(
            "/app/appointments/{$appt->id}",
            [
                'scheduled_at' => $newerStart->toIso8601String(),
                'estimated_end_at' => $newerStart->copy()->addMinutes(45)->toIso8601String(),
                'assigned_user_id' => null,
            ]
        );
        $clear->assertStatus(200);
        $appt->refresh();
        $this->assertNull($appt->assigned_user_id, 'assigned_user_id: null must clear the assignee.');

        // Omit the field, keep the cleared state.
        $latestStart = $newerStart->copy()->addHours(2);
        $keep = $this->actingAs($owner)->putJson(
            "/app/appointments/{$appt->id}",
            [
                'scheduled_at' => $latestStart->toIso8601String(),
                'estimated_end_at' => $latestStart->copy()->addMinutes(45)->toIso8601String(),
            ]
        );
        $keep->assertStatus(200);
        $appt->refresh();
        $this->assertNull($appt->assigned_user_id, 'omitted assigned_user_id must keep the current (null) value.');
    }
}
