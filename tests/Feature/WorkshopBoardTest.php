<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class WorkshopBoardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::findOrCreate('crm.work_orders.view', 'web');
        Permission::findOrCreate('crm.work_orders.create', 'web');
        Permission::findOrCreate('crm.work_orders.update', 'web');
        Permission::findOrCreate('crm.customers.view', 'web');
        Permission::findOrCreate('crm.vehicles.view', 'web');
    }

    /**
     * Build a user with the given permissions, attached to a tenant + center.
     *
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
     * Build a customer + vehicle + department for the given user context.
     *
     * @return array{0: Customer, 1: Vehicle, 2: Department}
     */
    protected function createFixtures(User $user): array
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

        $department = Department::create([
            'tenant_id' => $user->tenant_id,
            'center_id' => $user->current_center_id,
            'name_ar' => 'قسم الورشة',
            'name_en' => 'Workshop',
            'name' => 'Workshop',
            'is_active' => true,
        ]);

        return [$customer, $vehicle, $department];
    }

    /**
     * Build a work order for the given user context.
     *
     * @param array<string, mixed> $overrides
     */
    protected function createWorkOrder(User $user, array $overrides = []): WorkOrder
    {
        $customer = Customer::create([
            'tenant_id' => $user->tenant_id,
            'center_id' => $user->current_center_id,
            'type' => 'individual',
            'name' => 'Test Customer '.uniqid(),
            'phone' => '05'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
        ]);

        $vehicle = Vehicle::create([
            'tenant_id' => $user->tenant_id,
            'center_id' => $user->current_center_id,
            'customer_id' => $customer->id,
            'plate_number' => strtoupper(Str::random(3)).' '.random_int(1000, 9999),
        ]);

        return WorkOrder::create(array_merge([
            'tenant_id' => $user->tenant_id,
            'center_id' => $user->current_center_id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'code' => 'WO-'.str_pad((string) random_int(1000, 999999), 6, '0', STR_PAD_LEFT),
            'status' => WorkOrder::STATUS_OPEN,
        ], $overrides));
    }

    /**
     * Add a service item to a work order with the given status.
     *
     * @param array<string, mixed> $overrides
     */
    protected function addItem(WorkOrder $workOrder, string $status, array $overrides = []): WorkOrderItem
    {
        return WorkOrderItem::create(array_merge([
            'work_order_id' => $workOrder->id,
            'tenant_id' => $workOrder->tenant_id,
            'center_id' => $workOrder->center_id,
            'title' => 'Test service',
            'qty' => 1,
            'unit_price' => 100,
            'status' => $status,
        ], $overrides));
    }

    public function test_authorized_user_can_view_workshop_board(): void
    {
        $user = $this->createUserWithPermissions(['crm.work_orders.view']);
        $workOrder = $this->createWorkOrder($user);
        $this->addItem($workOrder, WorkOrderItem::STATUS_PENDING);

        $response = $this->actingAs($user)->get('/app/workshop');

        $response->assertStatus(200);
        $response->assertInertia(
            fn ($page) => $page
                ->component('Workshop/Board')
                ->has('columns')
                ->has('statuses')
                ->has('counts')
        );
    }

    public function test_user_without_view_permission_cannot_access_board(): void
    {
        $user = $this->createUserWithPermissions([]);

        $response = $this->actingAs($user)->get('/app/workshop');

        $response->assertStatus(403);
    }

    public function test_active_item_appears_in_correct_column(): void
    {
        $user = $this->createUserWithPermissions(['crm.work_orders.view']);
        $workOrder = $this->createWorkOrder($user);
        $item = $this->addItem($workOrder, WorkOrderItem::STATUS_IN_PROGRESS, [
            'title' => 'Brake pads replacement',
        ]);

        $response = $this->actingAs($user)->get('/app/workshop');

        $response->assertStatus(200);
        $response->assertInertia(
            fn ($page) => $page
                ->where('columns.in_progress.0.id', $item->id)
                ->where('columns.in_progress.0.work_order_id', $workOrder->id)
                ->where('columns.in_progress.0.title', 'Brake pads replacement')
                ->where('counts.in_progress', 1)
                ->where('counts.total', 1)
        );
    }

    public function test_completed_and_cancelled_items_are_excluded(): void
    {
        $user = $this->createUserWithPermissions(['crm.work_orders.view']);
        $workOrder = $this->createWorkOrder($user);

        // Visible — pending
        $this->addItem($workOrder, WorkOrderItem::STATUS_PENDING);
        // Hidden — completed
        $this->addItem($workOrder, WorkOrderItem::STATUS_COMPLETED);
        // Hidden — cancelled
        $this->addItem($workOrder, WorkOrderItem::STATUS_CANCELLED);

        $response = $this->actingAs($user)->get('/app/workshop');

        $response->assertStatus(200);
        $response->assertInertia(
            fn ($page) => $page
                ->where('counts.pending', 1)
                ->where('counts.in_progress', 0)
                ->where('counts.on_hold', 0)
                ->where('counts.ready_for_qc', 0)
                ->where('counts.total', 1)
        );
    }

    public function test_items_from_other_tenants_are_not_visible(): void
    {
        $user = $this->createUserWithPermissions(['crm.work_orders.view']);
        $ownWorkOrder = $this->createWorkOrder($user);
        $this->addItem($ownWorkOrder, WorkOrderItem::STATUS_PENDING);

        // Other tenant
        $otherTenant = Tenant::factory()->create();
        $otherCenter = Center::factory()->create(['tenant_id' => $otherTenant->id]);
        $otherCustomer = Customer::create([
            'tenant_id' => $otherTenant->id,
            'center_id' => $otherCenter->id,
            'type' => 'individual',
            'name' => 'Foreign Customer',
            'phone' => '0500000000',
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
            'code' => 'WO-OTHER-1',
            'status' => WorkOrder::STATUS_OPEN,
        ]);
        $this->addItem($otherWorkOrder, WorkOrderItem::STATUS_PENDING);

        $response = $this->actingAs($user)->get('/app/workshop');

        $response->assertStatus(200);
        $response->assertInertia(
            fn ($page) => $page
                ->where('counts.total', 1)
                ->where('counts.pending', 1)
        );
    }

    public function test_items_from_other_centers_within_same_tenant_are_not_visible(): void
    {
        $user = $this->createUserWithPermissions(['crm.work_orders.view']);
        $ownWorkOrder = $this->createWorkOrder($user);
        $this->addItem($ownWorkOrder, WorkOrderItem::STATUS_PENDING);

        // Same tenant, different center
        $otherCenter = Center::factory()->create(['tenant_id' => $user->tenant_id]);
        $otherCustomer = Customer::create([
            'tenant_id' => $user->tenant_id,
            'center_id' => $otherCenter->id,
            'type' => 'individual',
            'name' => 'Other Center Customer',
            'phone' => '0501111111',
        ]);
        $otherVehicle = Vehicle::create([
            'tenant_id' => $user->tenant_id,
            'center_id' => $otherCenter->id,
            'customer_id' => $otherCustomer->id,
            'plate_number' => 'BRN 7777',
        ]);
        $otherWorkOrder = WorkOrder::create([
            'tenant_id' => $user->tenant_id,
            'center_id' => $otherCenter->id,
            'customer_id' => $otherCustomer->id,
            'vehicle_id' => $otherVehicle->id,
            'code' => 'WO-OTHER-CENTER',
            'status' => WorkOrder::STATUS_OPEN,
        ]);
        $this->addItem($otherWorkOrder, WorkOrderItem::STATUS_PENDING);

        $response = $this->actingAs($user)->get('/app/workshop');

        $response->assertStatus(200);
        $response->assertInertia(
            fn ($page) => $page
                ->where('counts.total', 1)
                ->where('counts.pending', 1)
        );
    }

    public function test_items_belonging_to_done_or_cancelled_work_orders_are_excluded(): void
    {
        $user = $this->createUserWithPermissions(['crm.work_orders.view']);

        $doneWorkOrder = $this->createWorkOrder($user, ['status' => WorkOrder::STATUS_DONE]);
        $this->addItem($doneWorkOrder, WorkOrderItem::STATUS_PENDING);

        $cancelledWorkOrder = $this->createWorkOrder($user, ['status' => WorkOrder::STATUS_CANCELLED]);
        $this->addItem($cancelledWorkOrder, WorkOrderItem::STATUS_PENDING);

        // Sanity: an active work order's item should still appear.
        $openWorkOrder = $this->createWorkOrder($user, ['status' => WorkOrder::STATUS_OPEN]);
        $this->addItem($openWorkOrder, WorkOrderItem::STATUS_PENDING);

        $response = $this->actingAs($user)->get('/app/workshop');

        $response->assertStatus(200);
        $response->assertInertia(
            fn ($page) => $page
                ->where('counts.total', 1)
                ->where('counts.pending', 1)
        );
    }

    public function test_card_payload_includes_customer_vehicle_and_due_date(): void
    {
        $user = $this->createUserWithPermissions(['crm.work_orders.view']);
        $workOrder = $this->createWorkOrder($user, [
            'expected_end_date' => now()->addDays(2)->toDateString(),
        ]);
        $item = $this->addItem($workOrder, WorkOrderItem::STATUS_IN_PROGRESS, [
            'due_date' => now()->addDay()->toDateString(),
            'started_at' => now()->subHour(),
        ]);

        $expectedCustomerName = $workOrder->customer->name;
        $expectedPlate = $workOrder->vehicle->plate_number;

        $response = $this->actingAs($user)->get('/app/workshop');

        $response->assertStatus(200);
        $response->assertInertia(
            fn ($page) => $page
                ->where('columns.in_progress.0.id', $item->id)
                ->where('columns.in_progress.0.customer_name', $expectedCustomerName)
                ->where('columns.in_progress.0.vehicle_plate', $expectedPlate)
                ->where('columns.in_progress.0.work_order_code', $workOrder->code)
                ->where('columns.in_progress.0.is_overdue', false)
        );
    }

    public function test_card_marks_overdue_when_due_date_in_the_past(): void
    {
        $user = $this->createUserWithPermissions(['crm.work_orders.view']);
        $workOrder = $this->createWorkOrder($user);
        $item = $this->addItem($workOrder, WorkOrderItem::STATUS_IN_PROGRESS, [
            'due_date' => now()->subDays(1)->toDateString(),
        ]);

        $response = $this->actingAs($user)->get('/app/workshop');

        $response->assertStatus(200);
        $response->assertInertia(
            fn ($page) => $page
                ->where('columns.in_progress.0.id', $item->id)
                ->where('columns.in_progress.0.is_overdue', true)
        );
    }

    public function test_due_date_equal_to_today_is_not_overdue(): void
    {
        $user = $this->createUserWithPermissions(['crm.work_orders.view']);
        $workOrder = $this->createWorkOrder($user);
        $item = $this->addItem($workOrder, WorkOrderItem::STATUS_IN_PROGRESS, [
            'due_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($user)->get('/app/workshop');

        $response->assertStatus(200);
        $response->assertInertia(
            fn ($page) => $page
                ->where('columns.in_progress.0.id', $item->id)
                ->where('columns.in_progress.0.is_overdue', false)
        );
    }

    public function test_work_order_expected_end_date_used_as_fallback_when_item_has_no_due_date(): void
    {
        $user = $this->createUserWithPermissions(['crm.work_orders.view']);
        $expectedEnd = now()->addDays(3)->toDateString();
        $workOrder = $this->createWorkOrder($user, [
            'expected_end_date' => $expectedEnd,
        ]);
        // Item has NO due_date — controller should fall back to WO date.
        $item = $this->addItem($workOrder, WorkOrderItem::STATUS_IN_PROGRESS);

        $response = $this->actingAs($user)->get('/app/workshop');

        $response->assertStatus(200);
        $response->assertInertia(
            fn ($page) => $page
                ->where('columns.in_progress.0.id', $item->id)
                ->where('columns.in_progress.0.due_date', $expectedEnd)
                ->where('columns.in_progress.0.is_overdue', false)
        );
    }

    public function test_work_order_expected_end_date_can_trigger_overdue_via_fallback(): void
    {
        $user = $this->createUserWithPermissions(['crm.work_orders.view']);
        $workOrder = $this->createWorkOrder($user, [
            // Yesterday — must be picked up via the fallback path.
            'expected_end_date' => now()->subDays(1)->toDateString(),
        ]);
        $item = $this->addItem($workOrder, WorkOrderItem::STATUS_IN_PROGRESS);

        $response = $this->actingAs($user)->get('/app/workshop');

        $response->assertStatus(200);
        $response->assertInertia(
            fn ($page) => $page
                ->where('columns.in_progress.0.id', $item->id)
                ->where('columns.in_progress.0.is_overdue', true)
        );
    }

    public function test_undated_items_sort_after_dated_items_in_same_column(): void
    {
        $user = $this->createUserWithPermissions(['crm.work_orders.view']);
        $workOrder = $this->createWorkOrder($user);

        // Undated item, created first so insertion order is not the
        // tiebreaker.
        $undated = $this->addItem($workOrder, WorkOrderItem::STATUS_IN_PROGRESS, [
            'title' => 'Undated item',
        ]);
        // Dated items, far in the future, also undated at the WO level
        // (no expected_end_date) to force the fallback to null.
        $datedFar = $this->addItem($workOrder, WorkOrderItem::STATUS_IN_PROGRESS, [
            'title' => 'Dated far',
            'due_date' => now()->addDays(10)->toDateString(),
        ]);
        $datedNear = $this->addItem($workOrder, WorkOrderItem::STATUS_IN_PROGRESS, [
            'title' => 'Dated near',
            'due_date' => now()->addDays(2)->toDateString(),
        ]);

        $response = $this->actingAs($user)->get('/app/workshop');

        $response->assertStatus(200);
        $response->assertInertia(
            fn ($page) => $page
                ->where('columns.in_progress.0.id', $datedNear->id)
                ->where('columns.in_progress.1.id', $datedFar->id)
                ->where('columns.in_progress.2.id', $undated->id)
        );
    }
}
