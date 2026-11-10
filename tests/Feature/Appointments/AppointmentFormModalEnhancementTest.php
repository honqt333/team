<?php

declare(strict_types=1);

namespace Tests\Feature\Appointments;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AppointmentFormModalEnhancementTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Center $center;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::findOrCreate(Permissions::APPOINTMENTS_MANAGE, 'web');
        Permission::findOrCreate(Permissions::WORK_ORDERS_CREATE, 'web');

        $this->tenant = Tenant::factory()->create();
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);

        $this->user->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
        $this->user->givePermissionTo(Permissions::APPOINTMENTS_MANAGE);
        $this->user->givePermissionTo(Permissions::WORK_ORDERS_CREATE);
    }

    public function test_appointments_index_provides_customers_with_their_vehicles(): void
    {
        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'محمد عبدالله',
            'phone' => '0551234567',
        ]);

        $vehicle = Vehicle::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customer->id,
            'plate_number' => 'أ ب ج 1234',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('appointments.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Appointments/Index')
            ->has('customers')
            ->where('customers.0.name', 'محمد عبدالله')
            ->where('customers.0.vehicles.0.plate_number', 'أ ب ج 1234')
        );
    }

    public function test_customer_api_search_includes_vehicles(): void
    {
        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'سعيد الغامدي',
            'phone' => '0599988776',
        ]);

        $vehicle = Vehicle::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customer->id,
            'plate_number' => 'د ر س 5678',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/app/api/customers/search?q=سعيد');

        $response->assertOk();
        $response->assertJsonFragment([
            'name' => 'سعيد الغامدي',
            'plate_number' => 'د ر س 5678',
        ]);
    }
}
