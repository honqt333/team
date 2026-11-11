<?php

declare(strict_types=1);

namespace Tests\Feature\Appointments;

use App\Models\Appointment;
use App\Models\Center;
use App\Models\Customer;
use App\Models\Service;
use App\Models\ServiceBay;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\Permissions;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AppointmentsIndexCrudTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Center $center;

    protected Center $otherCenter;

    protected User $user;

    protected Customer $customer;

    protected Vehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->tenant = Tenant::factory()->create(['status' => 'active']);
        $this->center = Center::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);
        $this->otherCenter = Center::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);

        Permission::firstOrCreate(['name' => Permissions::APPOINTMENTS_MANAGE, 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => Permissions::WORK_ORDERS_CREATE, 'guard_name' => 'web']);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);
        $this->user->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);
        $this->user->givePermissionTo([Permissions::APPOINTMENTS_MANAGE, Permissions::WORK_ORDERS_CREATE]);

        $this->customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'خالد الشمري',
            'phone' => '0555555555',
        ]);

        $this->vehicle = Vehicle::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'plate_number' => 'ح ط ي 9988',
        ]);
    }

    public function test_unauthorized_user_cannot_view_appointments(): void
    {
        $unauthorized = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);
        $unauthorized->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($unauthorized)->get(route('appointments.index'));

        $response->assertForbidden();
    }

    public function test_authorized_user_can_view_appointments_index_and_center_isolation(): void
    {
        $bay = ServiceBay::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name' => 'Bay 1',
            'type' => 'maintenance',
            'is_active' => true,
        ]);

        Appointment::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'service_bay_id' => $bay->id,
            'status' => Appointment::STATUS_SCHEDULED,
            'scheduled_at' => Carbon::now()->addHour(),
            'estimated_end_at' => Carbon::now()->addHours(2),
            'notes' => 'فحص دوري',
        ]);

        // Appointment in another center
        Appointment::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->otherCenter->id,
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'status' => Appointment::STATUS_SCHEDULED,
            'scheduled_at' => Carbon::now()->addHours(3),
            'estimated_end_at' => Carbon::now()->addHours(4),
            'notes' => 'موعد فرع آخر',
        ]);

        $response = $this->actingAs($this->user)->get(route('appointments.index'));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Appointments/Index')
                ->has('appointments', 1)
                ->where('appointments.0.notes', 'فحص دوري')
                ->where('appointments.0.customer.name', 'خالد الشمري')
                ->has('bays', 1)
                ->has('statuses')
            );
    }

    public function test_authorized_user_can_create_appointment(): void
    {
        $service = Service::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name_ar' => 'صيانة عامة',
            'name_en' => 'General Maintenance',
            'base_price' => 250,
            'type' => Service::TYPE_INTERNAL,
            'is_active' => true,
        ]);

        $start = Carbon::now()->addDay()->setTime(10, 0);
        $end = $start->copy()->addHour();

        $response = $this->actingAs($this->user)
            ->postJson(route('appointments.store'), [
                'customer_id' => $this->customer->id,
                'vehicle_id' => $this->vehicle->id,
                'scheduled_at' => $start->toIso8601String(),
                'estimated_end_at' => $end->toIso8601String(),
                'service_ids' => [$service->id],
                'notes' => 'تغيير زيت وفلاتر',
            ]);

        $response->assertOk()
            ->assertJsonPath('payload.notes', 'تغيير زيت وفلاتر');

        $this->assertDatabaseHas('appointments', [
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'notes' => 'تغيير زيت وفلاتر',
            'status' => Appointment::STATUS_SCHEDULED,
        ]);
    }

    public function test_authorized_user_can_check_in_and_cancel_appointment(): void
    {
        $appointment = Appointment::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'status' => Appointment::STATUS_SCHEDULED,
            'scheduled_at' => Carbon::now()->addHour(),
            'estimated_end_at' => Carbon::now()->addHours(2),
        ]);

        $checkInResponse = $this->actingAs($this->user)
            ->postJson(route('appointments.check-in', ['appointment' => $appointment->id]));

        $checkInResponse->assertOk();
        $this->assertSame(Appointment::STATUS_CHECKED_IN, $appointment->fresh()->status);

        $cancelResponse = $this->actingAs($this->user)
            ->postJson(route('appointments.cancel', ['appointment' => $appointment->id]), [
                'reason' => 'طلب العميل التأجيل',
            ]);

        $cancelResponse->assertOk();
        $fresh = $appointment->fresh();
        $this->assertSame(Appointment::STATUS_CANCELLED, $fresh->status);
        $this->assertSame('طلب العميل التأجيل', $fresh->cancellation_reason);
    }
}
