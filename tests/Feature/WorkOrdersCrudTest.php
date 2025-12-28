<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class WorkOrdersCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset permission cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create all required permissions for guard 'web'
        Permission::findOrCreate('crm.work_orders.view', 'web');
        Permission::findOrCreate('crm.work_orders.create', 'web');
        Permission::findOrCreate('crm.work_orders.update', 'web');
        Permission::findOrCreate('crm.work_orders.delete', 'web');
        Permission::findOrCreate('crm.customers.view', 'web');
        Permission::findOrCreate('crm.customers.create', 'web');
        Permission::findOrCreate('crm.vehicles.view', 'web');
        Permission::findOrCreate('crm.vehicles.create', 'web');
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

        // Set the team id for permissions based on the user's tenant
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

        foreach ($permissions as $permissionName) {
            $user->givePermissionTo($permissionName);
        }

        $user->refresh();

        return $user;
    }

    protected function createCustomerAndVehicle(User $user): array
    {
        $customer = Customer::create([
            'tenant_id' => $user->tenant_id,
            'center_id' => $user->current_center_id,
            'type' => 'individual',
            'name' => 'Test Customer',
            'phone' => '0501234567',
        ]);

        $vehicle = Vehicle::create([
            'tenant_id' => $user->tenant_id,
            'center_id' => $user->current_center_id,
            'customer_id' => $customer->id,
            'plate_number' => 'ABC 1234',
        ]);

        return [$customer, $vehicle];
    }

    public function test_authorized_user_can_create_and_list_work_orders(): void
    {
        $user = $this->createUserWithPermissions([
            'crm.work_orders.view',
            'crm.work_orders.create',
        ]);

        [$customer, $vehicle] = $this->createCustomerAndVehicle($user);

        // Create work order
        $response = $this->actingAs($user)->postJson('/app/work-orders', [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'items' => [
                ['title' => 'Oil Change', 'qty' => 1, 'unit_price' => 100],
                ['title' => 'Filter Replacement', 'qty' => 2, 'unit_price' => 50],
            ],
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('customer_id', $customer->id);
        $this->assertStringStartsWith('WO-', $response->json('code'));

        // List work orders
        $listResponse = $this->actingAs($user)->getJson('/app/api/work-orders');
        $listResponse->assertStatus(200);
        $listResponse->assertJsonFragment(['code' => $response->json('code')]);
    }

    public function test_user_without_permission_cannot_create(): void
    {
        $user = $this->createUserWithPermissions([]);

        [$customer, $vehicle] = $this->createCustomerAndVehicle($user);

        $response = $this->actingAs($user)->postJson('/app/work-orders', [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'items' => [
                ['title' => 'Oil Change', 'qty' => 1, 'unit_price' => 100],
            ],
        ]);

        $response->assertStatus(403);
    }

    public function test_user_cannot_access_work_order_from_another_center(): void
    {
        $user = $this->createUserWithPermissions(['crm.work_orders.view']);

        // Create another tenant/center with a work order
        $otherTenant = Tenant::factory()->create();
        $otherCenter = Center::factory()->create(['tenant_id' => $otherTenant->id]);
        
        $otherCustomer = Customer::create([
            'tenant_id' => $otherTenant->id,
            'center_id' => $otherCenter->id,
            'type' => 'individual',
            'name' => 'Other Customer',
            'phone' => '0509876543',
        ]);
        
        $otherVehicle = Vehicle::create([
            'tenant_id' => $otherTenant->id,
            'center_id' => $otherCenter->id,
            'customer_id' => $otherCustomer->id,
            'plate_number' => 'XYZ 9999',
        ]);

        $otherWorkOrder = WorkOrder::create([
            'tenant_id' => $otherTenant->id,
            'center_id' => $otherCenter->id,
            'customer_id' => $otherCustomer->id,
            'vehicle_id' => $otherVehicle->id,
            'code' => 'WO-000001',
            'status' => 'open',
        ]);

        // Try to access work order from another center
        $response = $this->actingAs($user)->getJson("/app/work-orders/{$otherWorkOrder->id}");

        $response->assertStatus(404);
    }

    public function test_can_create_work_order_without_items(): void
    {
        $user = $this->createUserWithPermissions(['crm.work_orders.create']);

        [$customer, $vehicle] = $this->createCustomerAndVehicle($user);

        $response = $this->actingAs($user)->postJson('/app/work-orders', [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'items' => [],
        ]);

        // Items are optional, so this should succeed
        $response->assertStatus(201);
    }

    public function test_validation_fails_when_item_title_missing(): void
    {
        $user = $this->createUserWithPermissions(['crm.work_orders.create']);

        [$customer, $vehicle] = $this->createCustomerAndVehicle($user);

        $response = $this->actingAs($user)->postJson('/app/work-orders', [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'items' => [
                ['qty' => 1, 'unit_price' => 100], // Missing title
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items.0.title']);
    }

    public function test_work_order_code_is_sequential(): void
    {
        $user = $this->createUserWithPermissions([
            'crm.work_orders.view',
            'crm.work_orders.create',
        ]);

        [$customer, $vehicle1] = $this->createCustomerAndVehicle($user);
        
        // Create a second vehicle for the same customer
        $vehicle2 = \App\Models\Vehicle::create([
            'tenant_id' => $user->tenant_id,
            'center_id' => $user->current_center_id,
            'customer_id' => $customer->id,
            'plate_number' => 'XYZ 5678',
        ]);

        // Create first work order
        $response1 = $this->actingAs($user)->postJson('/app/work-orders', [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle1->id,
            'items' => [['title' => 'Service 1', 'qty' => 1, 'unit_price' => 100]],
        ]);

        $response1->assertStatus(201);
        $this->assertEquals('WO-000001', $response1->json('code'));

        // Create second work order with different vehicle
        $response2 = $this->actingAs($user)->postJson('/app/work-orders', [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle2->id,
            'items' => [['title' => 'Service 2', 'qty' => 1, 'unit_price' => 200]],
        ]);

        $response2->assertStatus(201);
        $this->assertEquals('WO-000002', $response2->json('code'));
    }

    public function test_item_total_is_calculated_correctly(): void
    {
        $user = $this->createUserWithPermissions([
            'crm.work_orders.view',
            'crm.work_orders.create',
        ]);

        [$customer, $vehicle] = $this->createCustomerAndVehicle($user);

        $response = $this->actingAs($user)->postJson('/app/work-orders', [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'items' => [
                ['title' => 'Oil Change', 'qty' => 2, 'unit_price' => 50],
            ],
        ]);

        $response->assertStatus(201);
        
        // Get work order ID and check items directly from DB
        $workOrderId = $response->json('id');
        $items = \App\Models\WorkOrderItem::withoutGlobalScopes()
            ->where('work_order_id', $workOrderId)
            ->get();
        
        $this->assertCount(1, $items, 'Should have 1 item');
        $this->assertEquals(100.00, (float) $items[0]->total);
    }
}
