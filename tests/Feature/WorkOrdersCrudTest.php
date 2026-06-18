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

    public function test_authorized_user_can_update_item_status_and_activity_is_logged(): void
    {
        $user = $this->createUserWithPermissions([
            'crm.work_orders.view',
            'crm.work_orders.create',
            'crm.work_orders.update',
        ]);

        [$customer, $vehicle] = $this->createCustomerAndVehicle($user);

        // Create work order
        $response = $this->actingAs($user)->postJson('/app/work-orders', [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'items' => [
                ['title' => 'Oil Change', 'qty' => 1, 'unit_price' => 100],
            ],
        ]);

        $response->assertStatus(201);
        $workOrderId = $response->json('id');
        $workOrder = WorkOrder::withoutGlobalScopes()->find($workOrderId);
        $item = $workOrder->items()->first();

        // Update item status
        $statusResponse = $this->actingAs($user)->patchJson("/app/work-orders/{$workOrder->id}/items/{$item->id}/status", [
            'status' => 'in_progress',
        ]);

        $statusResponse->assertStatus(200);
        $this->assertEquals('in_progress', $item->fresh()->status);

        // Assert activity logged
        $this->assertDatabaseHas('work_order_activities', [
            'work_order_id' => $workOrder->id,
            'action' => 'item_status_updated',
        ]);
    }

    public function test_authorized_user_cannot_delete_item_if_it_has_parts_or_technicians(): void
    {
        $user = $this->createUserWithPermissions([
            'crm.work_orders.view',
            'crm.work_orders.create',
            'crm.work_orders.update',
        ]);

        [$customer, $vehicle] = $this->createCustomerAndVehicle($user);

        // Create work order
        $response = $this->actingAs($user)->postJson('/app/work-orders', [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'items' => [
                ['title' => 'Oil Change', 'qty' => 1, 'unit_price' => 100],
            ],
        ]);

        $workOrderId = $response->json('id');
        $workOrder = WorkOrder::withoutGlobalScopes()->find($workOrderId);
        $item = $workOrder->items()->first();

        // 1. Attaching a technician
        $techUser = User::factory()->create(['tenant_id' => $user->tenant_id]);
        $item->technicians()->attach($techUser->id);

        // Attempt delete
        $deleteResponse = $this->actingAs($user)->delete("/app/work-orders/{$workOrder->id}/items/{$item->id}");
        $deleteResponse->assertRedirect();
        
        // Assert item was NOT deleted
        $this->assertDatabaseHas('work_order_items', ['id' => $item->id]);

        // Detach technician
        $item->technicians()->detach($techUser->id);

        // 2. Attach a part
        $warehouse = \App\Models\Warehouse::create([
            'tenant_id' => $user->tenant_id,
            'center_id' => $user->current_center_id,
            'name' => 'Main Warehouse',
        ]);
        $part = \App\Models\Part::create([
            'tenant_id' => $user->tenant_id,
            'sku' => 'PART123',
            'name_ar' => 'قطعة غيار',
            'name_en' => 'Spare Part',
            'is_active' => true,
        ]);
        $itemPart = \App\Models\WorkOrderItemPart::create([
            'tenant_id' => $user->tenant_id,
            'center_id' => $user->current_center_id,
            'work_order_item_id' => $item->id,
            'part_id' => $part->id,
            'warehouse_id' => $warehouse->id,
            'name' => 'Spare Part',
            'qty' => 1,
            'unit_price' => 50,
            'total' => 50,
        ]);

        // Attempt delete
        $deleteResponse = $this->actingAs($user)->delete("/app/work-orders/{$workOrder->id}/items/{$item->id}");
        $deleteResponse->assertRedirect();

        // Assert item was NOT deleted
        $this->assertDatabaseHas('work_order_items', ['id' => $item->id]);

        // Delete the part
        $itemPart->delete();

        // 3. Delete should succeed now that technicians and parts are gone
        $deleteResponse = $this->actingAs($user)->delete("/app/work-orders/{$workOrder->id}/items/{$item->id}");
        $deleteResponse->assertRedirect();

        // Assert item was deleted
        $this->assertDatabaseMissing('work_order_items', ['id' => $item->id]);
    }

    public function test_authorized_user_cannot_delete_item_if_work_order_has_payments(): void
    {
        $user = $this->createUserWithPermissions([
            'crm.work_orders.view',
            'crm.work_orders.create',
            'crm.work_orders.update',
        ]);

        [$customer, $vehicle] = $this->createCustomerAndVehicle($user);

        // Create work order
        $response = $this->actingAs($user)->postJson('/app/work-orders', [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'items' => [
                ['title' => 'Oil Change', 'qty' => 1, 'unit_price' => 100],
            ],
        ]);

        $workOrderId = $response->json('id');
        $workOrder = WorkOrder::withoutGlobalScopes()->find($workOrderId);
        $item = $workOrder->items()->first();

        // 1. Create a payment on the work order
        $payment = \App\Models\Payment::create([
            'tenant_id' => $user->tenant_id,
            'center_id' => $user->current_center_id,
            'work_order_id' => $workOrder->id,
            'amount' => 50,
            'payment_method' => 'cash',
            'payment_date' => now(),
            'type' => 'payment',
        ]);

        // Attempt delete
        $deleteResponse = $this->actingAs($user)->delete("/app/work-orders/{$workOrder->id}/items/{$item->id}");
        $deleteResponse->assertRedirect();

        // Assert item was NOT deleted
        $this->assertDatabaseHas('work_order_items', ['id' => $item->id]);

        // Delete the payment
        $payment->forceDelete(); // forceDelete to completely remove it from the DB query check

        // 2. Delete should succeed now that payment is deleted
        $deleteResponse = $this->actingAs($user)->delete("/app/work-orders/{$workOrder->id}/items/{$item->id}");
        $deleteResponse->assertRedirect();

        // Assert item was deleted
        $this->assertDatabaseMissing('work_order_items', ['id' => $item->id]);
    }

    public function test_authorized_user_can_reactivate_cancelled_item(): void
    {
        $user = $this->createUserWithPermissions([
            'crm.work_orders.view',
            'crm.work_orders.create',
            'crm.work_orders.update',
        ]);

        [$customer, $vehicle] = $this->createCustomerAndVehicle($user);

        // Create work order
        $response = $this->actingAs($user)->postJson('/app/work-orders', [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'items' => [
                ['title' => 'Oil Change', 'qty' => 1, 'unit_price' => 100],
            ],
        ]);

        $workOrderId = $response->json('id');
        $workOrder = WorkOrder::withoutGlobalScopes()->find($workOrderId);
        $item = $workOrder->items()->first();

        // 1. Set status to cancelled
        $item->update(['status' => 'cancelled']);

        // 2. Change status back to pending
        $statusResponse = $this->actingAs($user)->patchJson("/app/work-orders/{$workOrder->id}/items/{$item->id}/status", [
            'status' => 'pending',
        ]);

        $statusResponse->assertStatus(200);
        $this->assertEquals('pending', $item->fresh()->status);
    }

    public function test_authorized_user_can_add_item_under_packages_department(): void
    {
        $user = $this->createUserWithPermissions([
            'crm.work_orders.view',
            'crm.work_orders.create',
            'crm.work_orders.update',
        ]);

        [$customer, $vehicle] = $this->createCustomerAndVehicle($user);

        // Create work order
        $response = $this->actingAs($user)->postJson('/app/work-orders', [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'items' => [],
        ]);

        $workOrderId = $response->json('id');
        $workOrder = WorkOrder::withoutGlobalScopes()->find($workOrderId);

        // Create a service of type package
        $service = \App\Models\Service::create([
            'tenant_id' => $user->tenant_id,
            'center_id' => $user->current_center_id,
            'name_ar' => 'باقة خدمات',
            'name_en' => 'Service Package',
            'type' => 'package',
            'base_price' => 150,
            'min_price' => 100,
            'is_active' => true,
        ]);

        // Attempt add item under 'packages' department
        $addResponse = $this->actingAs($user)->postJson("/app/work-orders/{$workOrder->id}/items", [
            'service_id' => $service->id,
            'department_id' => 'packages',
            'title' => 'Package Item',
            'qty' => 1,
            'unit_price' => 150,
        ]);

        $addResponse->assertRedirect();
        
        // Assert item was created with department_id null
        $item = $workOrder->items()->first();
        $this->assertNotNull($item);
        $this->assertNull($item->department_id);
        $this->assertEquals($service->id, $item->service_id);
    }

    public function test_suspending_and_resuming_work_order_retains_item_statuses(): void
    {
        $user = $this->createUserWithPermissions([
            'crm.work_orders.view',
            'crm.work_orders.create',
            'crm.work_orders.update',
        ]);

        [$customer, $vehicle] = $this->createCustomerAndVehicle($user);

        // 1. Create work order
        $response = $this->actingAs($user)->postJson('/app/work-orders', [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'items' => [
                ['title' => 'Service A', 'qty' => 1, 'unit_price' => 100],
                ['title' => 'Service B', 'qty' => 1, 'unit_price' => 200],
            ],
        ]);

        $workOrderId = $response->json('id');
        $workOrder = WorkOrder::withoutGlobalScopes()->find($workOrderId);
        
        // Set statuses to Open / In Progress
        $workOrder->update(['status' => WorkOrder::STATUS_IN_PROGRESS]);

        $itemA = $workOrder->items[0];
        $itemB = $workOrder->items[1];

        $itemA->update(['status' => \App\Models\WorkOrderItem::STATUS_PENDING]);
        $itemB->update(['status' => \App\Models\WorkOrderItem::STATUS_IN_PROGRESS]);

        // 2. Put on Hold
        $holdResponse = $this->actingAs($user)->post("/app/work-orders/{$workOrder->id}/hold", [
            'reason' => 'Waiting for parts',
        ]);
        $holdResponse->assertRedirect();

        $workOrder->refresh();
        $itemA->refresh();
        $itemB->refresh();

        $this->assertEquals(WorkOrder::STATUS_ON_HOLD, $workOrder->status);
        $this->assertEquals('Waiting for parts', $workOrder->hold_reason);

        // Both items should be on hold
        $this->assertEquals(\App\Models\WorkOrderItem::STATUS_ON_HOLD, $itemA->status);
        $this->assertEquals(\App\Models\WorkOrderItem::STATUS_ON_HOLD, $itemB->status);
        
        // Original statuses must be stored in suspended_status
        $this->assertEquals(\App\Models\WorkOrderItem::STATUS_PENDING, $itemA->suspended_status);
        $this->assertEquals(\App\Models\WorkOrderItem::STATUS_IN_PROGRESS, $itemB->suspended_status);

        // 3. Resume
        $resumeResponse = $this->actingAs($user)->post("/app/work-orders/{$workOrder->id}/resume");
        $resumeResponse->assertRedirect();

        $workOrder->refresh();
        $itemA->refresh();
        $itemB->refresh();

        $this->assertEquals(WorkOrder::STATUS_IN_PROGRESS, $workOrder->status);
        $this->assertNull($workOrder->hold_reason);

        // Items should be restored to their original statuses
        $this->assertEquals(\App\Models\WorkOrderItem::STATUS_PENDING, $itemA->status);
        $this->assertEquals(\App\Models\WorkOrderItem::STATUS_IN_PROGRESS, $itemB->status);

        // suspended_status column should be cleared
        $this->assertNull($itemA->suspended_status);
        $this->assertNull($itemB->suspended_status);
    }

    public function test_cannot_transition_service_back_to_pending_after_work_started(): void
    {
        $user = $this->createUserWithPermissions([
            'crm.work_orders.view',
            'crm.work_orders.create',
            'crm.work_orders.update',
        ]);

        [$customer, $vehicle] = $this->createCustomerAndVehicle($user);

        $response = $this->actingAs($user)->postJson('/app/work-orders', [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'items' => [
                ['title' => 'Service A', 'qty' => 1, 'unit_price' => 100],
            ],
        ]);

        $workOrderId = $response->json('id');
        $workOrder = WorkOrder::withoutGlobalScopes()->find($workOrderId);
        $item = $workOrder->items[0];

        // 1. When card is Open, transitioning to pending and back is allowed
        $workOrder->update(['status' => WorkOrder::STATUS_OPEN]);
        $item->update(['status' => \App\Models\WorkOrderItem::STATUS_IN_PROGRESS]);
        
        $updateResponse = $this->actingAs($user)->patchJson("/app/work-orders/{$workOrder->id}/items/{$item->id}/status", [
            'status' => \App\Models\WorkOrderItem::STATUS_PENDING,
        ]);
        // Should succeed when card is open
        $updateResponse->assertOk();
        $this->assertEquals(\App\Models\WorkOrderItem::STATUS_PENDING, $item->fresh()->status);

        // 2. When card is In Progress, changing from in_progress back to pending is BLOCKED
        $workOrder->update(['status' => WorkOrder::STATUS_IN_PROGRESS]);
        $item->refresh();
        $item->update(['status' => \App\Models\WorkOrderItem::STATUS_IN_PROGRESS]);

        $updateResponseBlocked = $this->actingAs($user)->patchJson("/app/work-orders/{$workOrder->id}/items/{$item->id}/status", [
            'status' => \App\Models\WorkOrderItem::STATUS_PENDING,
        ]);
        // Should fail validation (returns 422)
        $updateResponseBlocked->assertStatus(422);
        $this->assertEquals(\App\Models\WorkOrderItem::STATUS_IN_PROGRESS, $item->fresh()->status);
    }

    public function test_completing_work_order_with_or_without_balance_generates_invoice(): void
    {
        $user = $this->createUserWithPermissions([
            'crm.work_orders.view',
            'crm.work_orders.create',
            'crm.work_orders.update',
        ]);

        [$customer, $vehicle] = $this->createCustomerAndVehicle($user);

        // 1. Create work order
        $response = $this->actingAs($user)->postJson('/app/work-orders', [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'items' => [
                ['title' => 'Service A', 'qty' => 1, 'unit_price' => 100],
            ],
        ]);

        $workOrderId = $response->json('id');
        $workOrder = WorkOrder::withoutGlobalScopes()->find($workOrderId);
        $item = $workOrder->items[0];

        // Transition work order and item to proper states
        $workOrder->update(['status' => WorkOrder::STATUS_IN_PROGRESS]);
        $item->update(['status' => \App\Models\WorkOrderItem::STATUS_COMPLETED]);

        // There's a remaining balance of 100 since no payment was made, but vehicle exit should succeed!
        $completeResponse = $this->actingAs($user)->post("/app/work-orders/{$workOrder->id}/complete", [
            'exit_date' => '2026-06-18',
            'notes' => 'Customer exited successfully.',
            'is_deferred' => true,
            'due_date' => '2026-06-30',
        ]);

        $workOrder->refresh();
        $invoice = $workOrder->invoice;
        $this->assertNotNull($invoice);
        $completeResponse->assertRedirect(route('app.invoices.show', $invoice->id));

        // Work order details updated
        $this->assertEquals(WorkOrder::STATUS_DONE, $workOrder->status);
        $this->assertEquals('2026-06-18 00:00:00', $workOrder->closed_at->toDateTimeString());
        $this->assertStringContainsString('ملاحظات الخروج: Customer exited successfully.', $workOrder->notes);

        // Invoice status
        $this->assertEquals('valid', $invoice->status);
        $this->assertEquals($workOrder->total, $invoice->total_incl_tax);
        $this->assertEquals('2026-06-30', $invoice->due_date->toDateString());
    }

    public function test_completing_work_order_with_balance_without_deferred_invoice_succeeds(): void
    {
        $user = $this->createUserWithPermissions([
            'crm.work_orders.view',
            'crm.work_orders.create',
            'crm.work_orders.update',
        ]);

        [$customer, $vehicle] = $this->createCustomerAndVehicle($user);

        $response = $this->actingAs($user)->postJson('/app/work-orders', [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'items' => [
                ['title' => 'Service A', 'qty' => 1, 'unit_price' => 100],
            ],
        ]);

        $workOrderId = $response->json('id');
        $workOrder = WorkOrder::withoutGlobalScopes()->find($workOrderId);
        $item = $workOrder->items[0];

        $workOrder->update(['status' => WorkOrder::STATUS_IN_PROGRESS]);
        $item->update(['status' => \App\Models\WorkOrderItem::STATUS_COMPLETED]);

        // Attempt complete without is_deferred
        $completeResponse = $this->actingAs($user)->post("/app/work-orders/{$workOrder->id}/complete", [
            'exit_date' => '2026-06-18',
            'notes' => 'Customer exited successfully.',
        ]);

        $completeResponse->assertRedirect();
        $completeResponse->assertSessionHas('success');
        $this->assertEquals(WorkOrder::STATUS_DONE, $workOrder->fresh()->status);
        $this->assertNull($workOrder->fresh()->invoice);
    }

    public function test_completed_sub_filter_returns_only_orders_with_completed_items_and_no_exit(): void
    {
        $user = $this->createUserWithPermissions([
            'crm.work_orders.view',
            'crm.work_orders.create',
            'crm.work_orders.update',
        ]);

        [$customer, $vehicleA] = $this->createCustomerAndVehicle($user);

        // Create a second vehicle for the same customer
        $vehicleB = \App\Models\Vehicle::create([
            'tenant_id'    => $customer->tenant_id,
            'center_id'    => $customer->center_id,
            'customer_id'  => $customer->id,
            'plate_number' => 'XYZ 9999',
        ]);

        // 1. Work Order A: Completed services, exit pending
        $responseA = $this->actingAs($user)->postJson('/app/work-orders', [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicleA->id,
            'items' => [
                ['title' => 'Service A', 'qty' => 1, 'unit_price' => 100],
            ],
        ]);
        $responseA->assertSuccessful();
        $workOrderA = WorkOrder::withoutGlobalScopes()->find($responseA->json('id'));
        $workOrderA->update(['status' => WorkOrder::STATUS_IN_PROGRESS]);
        \App\Models\WorkOrderItem::withoutGlobalScopes()
            ->where('work_order_id', $workOrderA->id)
            ->first()
            ->update(['status' => \App\Models\WorkOrderItem::STATUS_COMPLETED]);

        // 2. Work Order B: In progress service, not completed (different vehicle)
        $responseB = $this->actingAs($user)->postJson('/app/work-orders', [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicleB->id,
            'items' => [
                ['title' => 'Service B', 'qty' => 1, 'unit_price' => 150],
            ],
        ]);
        $responseB->assertSuccessful();
        $workOrderB = WorkOrder::withoutGlobalScopes()->find($responseB->json('id'));
        $workOrderB->update(['status' => WorkOrder::STATUS_IN_PROGRESS]);

        // Request work orders with status=open and sub_filter=completed via JSON API
        $indexResponse = $this->actingAs($user)->getJson("/app/api/work-orders-index?status=open&sub_filter=completed");
        $indexResponse->assertStatus(200);

        $ids = collect($indexResponse->json('data'))->pluck('id')->all();
        $this->assertContains($workOrderA->id, $ids);
        $this->assertNotContains($workOrderB->id, $ids);
    }


    public function test_cannot_start_work_on_work_order_without_services(): void
    {
        $user = $this->createUserWithPermissions([
            'crm.work_orders.view',
            'crm.work_orders.create',
            'crm.work_orders.update',
        ]);

        [$customer, $vehicle] = $this->createCustomerAndVehicle($user);

        // Create a work order without items
        $response = $this->actingAs($user)->postJson('/app/work-orders', [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'items' => [],
        ]);

        $workOrderId = $response->json('id');
        $workOrder = WorkOrder::withoutGlobalScopes()->find($workOrderId);

        // Attempt starting work
        $startResponse = $this->actingAs($user)->post("/app/work-orders/{$workOrder->id}/start");
        $startResponse->assertRedirect();
        $startResponse->assertSessionHas('error');
        $this->assertEquals(WorkOrder::STATUS_OPEN, $workOrder->fresh()->status);
    }

    public function test_cancelling_service_reduces_work_order_totals_immediately(): void
    {
        $user = $this->createUserWithPermissions([
            'crm.work_orders.view',
            'crm.work_orders.create',
            'crm.work_orders.update',
        ]);

        [$customer, $vehicle] = $this->createCustomerAndVehicle($user);

        // Create work order with tax enabled and pricing mode exclusive
        $workOrder = WorkOrder::create([
            'tenant_id' => $user->tenant_id,
            'center_id' => $user->current_center_id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'code' => WorkOrder::generateCode($user->tenant_id, $user->current_center_id),
            'status' => WorkOrder::STATUS_OPEN,
            'tax_enabled_snapshot' => true,
            'pricing_mode_snapshot' => 'exclusive',
            'tax_rate_snapshot' => 15.00,
        ]);

        // Add items manually
        $itemA = $workOrder->items()->create([
            'tenant_id' => $user->tenant_id,
            'center_id' => $user->current_center_id,
            'title' => 'Service A',
            'qty' => 1,
            'unit_price' => 100,
            'status' => 'pending',
        ]);

        $itemB = $workOrder->items()->create([
            'tenant_id' => $user->tenant_id,
            'center_id' => $user->current_center_id,
            'title' => 'Service B',
            'qty' => 1,
            'unit_price' => 50,
            'status' => 'pending',
        ]);

        // Force a save to calculate initial totals
        $workOrder->save();
        $workOrder->refresh();

        $this->assertEquals(150.00, (float)$workOrder->total_excl_tax);
        $this->assertEquals(22.50, (float)$workOrder->total_tax);
        $this->assertEquals(172.50, (float)$workOrder->total_incl_tax);

        // Now cancel service B via patch request
        $response = $this->actingAs($user)->patchJson("/app/work-orders/{$workOrder->id}/items/{$itemB->id}/status", [
            'status' => 'cancelled',
        ]);

        $response->assertStatus(200);

        $workOrder->refresh();

        // Totals must be recalculated and saved in DB immediately
        $this->assertEquals(100.00, (float)$workOrder->total_excl_tax);
        $this->assertEquals(15.00, (float)$workOrder->total_tax);
        $this->assertEquals(115.00, (float)$workOrder->total_incl_tax);
    }

    public function test_cannot_complete_work_order_if_no_completed_services(): void
    {
        $user = $this->createUserWithPermissions([
            'crm.work_orders.view',
            'crm.work_orders.create',
            'crm.work_orders.update',
        ]);

        [$customer, $vehicle] = $this->createCustomerAndVehicle($user);

        // Create work order
        $workOrder = WorkOrder::create([
            'tenant_id' => $user->tenant_id,
            'center_id' => $user->current_center_id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'code' => WorkOrder::generateCode($user->tenant_id, $user->current_center_id),
            'status' => WorkOrder::STATUS_IN_PROGRESS,
            'tax_enabled_snapshot' => false,
        ]);

        $item = $workOrder->items()->create([
            'tenant_id' => $user->tenant_id,
            'center_id' => $user->current_center_id,
            'title' => 'Service A',
            'qty' => 1,
            'unit_price' => 100,
            'status' => 'cancelled', // Only one service, and it's cancelled
        ]);

        $workOrder->save();

        // Attempt to complete/exit vehicle
        $completeResponse = $this->actingAs($user)->post("/app/work-orders/{$workOrder->id}/complete", [
            'exit_date' => '2026-06-18',
            'notes' => 'Customer exited.',
        ]);

        // It should reject completing, redirect with error, and keep the work order in progress
        $completeResponse->assertRedirect();
        $completeResponse->assertSessionHas('error');
        
        $workOrder->refresh();
        $this->assertEquals(WorkOrder::STATUS_IN_PROGRESS, $workOrder->status);
    }

    public function test_payments_inherited_in_invoices(): void
    {
        $user = $this->createUserWithPermissions([
            'crm.work_orders.view',
            'crm.work_orders.create',
            'crm.work_orders.update',
        ]);

        [$customer, $vehicle] = $this->createCustomerAndVehicle($user);

        // Create work order
        $workOrder = WorkOrder::create([
            'tenant_id' => $user->tenant_id,
            'center_id' => $user->current_center_id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'code' => WorkOrder::generateCode($user->tenant_id, $user->current_center_id),
            'status' => WorkOrder::STATUS_IN_PROGRESS,
            'tax_enabled_snapshot' => false,
        ]);

        $item = $workOrder->items()->create([
            'tenant_id' => $user->tenant_id,
            'center_id' => $user->current_center_id,
            'title' => 'Service A',
            'qty' => 1,
            'unit_price' => 100,
            'status' => 'completed',
        ]);

        $workOrder->save();

        // 1. Record a partial payment of 40 on the work order
        $payment = $workOrder->payments()->create([
            'tenant_id' => $workOrder->tenant_id,
            'center_id' => $workOrder->center_id,
            'amount' => 40.00,
            'payment_date' => now(),
            'payment_method' => 'cash',
            'type' => 'payment',
            'received_by' => $user->id,
        ]);

        $this->assertEquals(40.00, $workOrder->total_paid);
        $this->assertEquals(60.00, $workOrder->balance);

        // 2. Complete the work order (which creates the invoice)
        $completeResponse = $this->actingAs($user)->post("/app/work-orders/{$workOrder->id}/complete", [
            'exit_date' => '2026-06-18',
            'notes' => 'Customer exited.',
            'is_deferred' => true,
            'due_date' => '2026-06-30',
        ]);

        $completeResponse->assertRedirect();
        
        $workOrder->refresh();
        $invoice = $workOrder->invoice;
        $this->assertNotNull($invoice);

        // 3. Verify that the payment was inherited by the invoice
        $payment->refresh();
        $this->assertEquals($invoice->id, $payment->invoice_id);

        $invoice->refresh();
        $this->assertEquals(40.00, (float)$invoice->total_paid);
        $this->assertEquals('partial', $invoice->payment_status);

        // 4. Record a new payment of 60 on the work order after completion
        $newPayment = $workOrder->payments()->create([
            'tenant_id' => $workOrder->tenant_id,
            'center_id' => $workOrder->center_id,
            'amount' => 60.00,
            'payment_date' => now(),
            'payment_method' => 'mada',
            'type' => 'payment',
            'received_by' => $user->id,
        ]);

        // It should automatically link to the invoice and update the invoice status to paid
        $newPayment->refresh();
        $this->assertEquals($invoice->id, $newPayment->invoice_id);

        $invoice->refresh();
        $this->assertEquals(100.00, (float)$invoice->total_paid);
        $this->assertEquals('paid', $invoice->payment_status);
    }
}
