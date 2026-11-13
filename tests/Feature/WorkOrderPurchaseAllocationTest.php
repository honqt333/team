<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Customer;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Models\WorkOrderItemPart;
use App\Models\WorkOrderPurchaseAllocation;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class WorkOrderPurchaseAllocationTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Center $center;

    protected User $user;

    protected WorkOrder $workOrder;

    protected WorkOrderItem $workOrderItem;

    protected Supplier $supplier;

    protected PurchaseInvoice $purchaseInvoice;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ([
            Permissions::WORK_ORDERS_VIEW,
            Permissions::WORK_ORDERS_UPDATE,
            Permissions::PURCHASE_INVOICES_VIEW,
            Permissions::INVOICES_VIEW,
        ] as $perm) {
            Permission::findOrCreate($perm, 'web');
        }

        $this->tenant = Tenant::create([
            'name' => 'Allocation Test Workshop',
            'slug' => 'allocation-test-workshop',
            'country' => 'SA',
            'currency' => 'SAR',
            'timezone' => 'Asia/Riyadh',
            'is_active' => true,
        ]);

        $this->center = Center::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Main Branch',
            'slug' => 'main-branch',
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
            'is_active' => true,
        ]);

        $this->user->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
        $this->user->givePermissionTo([
            Permissions::WORK_ORDERS_VIEW,
            Permissions::WORK_ORDERS_UPDATE,
            Permissions::PURCHASE_INVOICES_VIEW,
            Permissions::INVOICES_VIEW,
        ]);

        $customer = Customer::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'type' => 'individual',
            'name' => 'Sami Customer',
            'phone' => '0555000011',
        ]);

        $vehicle = Vehicle::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $customer->id,
            'plate_number' => 'XYZ 9999',
        ]);

        $this->workOrder = WorkOrder::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'code' => 'WO-ALLOC-001',
            'status' => WorkOrder::STATUS_OPEN,
            'entry_date' => now()->toDateString(),
            'tax_enabled_snapshot' => false,
        ]);

        $this->workOrderItem = WorkOrderItem::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'work_order_id' => $this->workOrder->id,
            'title' => 'Suspension Maintenance',
            'qty' => 1,
            'unit_price' => 500.00,
            'status' => WorkOrderItem::STATUS_IN_PROGRESS,
        ]);
        $this->workOrder->save();

        $this->supplier = Supplier::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name' => 'Direct Parts Co',
            'phone' => '0555000022',
            'is_active' => true,
        ]);

        $this->purchaseInvoice = PurchaseInvoice::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'supplier_id' => $this->supplier->id,
            'invoice_number' => 'INV-SUP-555',
            'code' => 'PINV-ALLOC-001',
            'issue_date' => now()->toDateString(),
            'status' => PurchaseInvoice::STATUS_OPEN,
            'subtotal' => 400.00,
            'tax_amount' => 60.00,
            'total' => 460.00,
            'balance' => 460.00,
        ]);
    }

    public function test_can_allocate_external_part_and_supplier_service(): void
    {
        $this->actingAs($this->user);

        // 1. Allocate external part
        $partResponse = $this->postJson(route('app.work-orders.purchase-allocations.store', $this->workOrder), [
            'purchase_invoice_id' => $this->purchaseInvoice->id,
            'type' => WorkOrderPurchaseAllocation::TYPE_EXTERNAL_PART,
            'item_description' => 'Original Front Shock Absorber',
            'allocated_qty' => 2,
            'unit_cost' => 150.00,
            'tax_amount' => 45.00,
            'total_cost' => 345.00,
            'notes' => 'Urgent replacement from outer vendor',
        ]);

        $partResponse->assertStatus(201);
        $this->assertDatabaseHas('work_order_purchase_allocations', [
            'work_order_id' => $this->workOrder->id,
            'purchase_invoice_id' => $this->purchaseInvoice->id,
            'type' => WorkOrderPurchaseAllocation::TYPE_EXTERNAL_PART,
            'item_description' => 'Original Front Shock Absorber',
            'allocated_qty' => 2.00,
            'unit_cost' => 150.00,
            'tax_amount' => 45.00,
            'total_cost' => 345.00,
        ]);

        // 2. Allocate supplier service
        $serviceResponse = $this->postJson(route('app.work-orders.purchase-allocations.store', $this->workOrder), [
            'purchase_invoice_id' => $this->purchaseInvoice->id,
            'type' => WorkOrderPurchaseAllocation::TYPE_SUPPLIER_SERVICE,
            'item_description' => 'Lathe Machine Finishing',
            'allocated_qty' => 1,
            'unit_cost' => 100.00,
            'tax_amount' => 15.00,
            'total_cost' => 115.00,
        ]);

        $serviceResponse->assertStatus(201);
        $this->assertDatabaseHas('work_order_purchase_allocations', [
            'work_order_id' => $this->workOrder->id,
            'purchase_invoice_id' => $this->purchaseInvoice->id,
            'type' => WorkOrderPurchaseAllocation::TYPE_SUPPLIER_SERVICE,
            'item_description' => 'Lathe Machine Finishing',
        ]);

        // 3. Test WorkOrder relation scopes
        $this->workOrder->refresh();
        $this->assertCount(2, $this->workOrder->purchaseAllocations);
        $this->assertCount(1, $this->workOrder->externalPartAllocations);
        $this->assertCount(1, $this->workOrder->supplierServiceAllocations);
        $this->assertEquals('Original Front Shock Absorber', $this->workOrder->externalPartAllocations->first()->item_description);
        $this->assertEquals('Lathe Machine Finishing', $this->workOrder->supplierServiceAllocations->first()->item_description);
    }

    public function test_can_map_and_unmap_allocation_to_work_order_item_part(): void
    {
        $this->actingAs($this->user);

        $partItem = WorkOrderItemPart::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'work_order_id' => $this->workOrder->id,
            'work_order_item_id' => $this->workOrderItem->id,
            'name' => 'Front Shock Absorber',
            'source' => WorkOrderItemPart::SOURCE_EXTERNAL,
            'qty' => 2,
            'unit_price' => 200.00,
            'total' => 400.00,
            'status' => WorkOrderItemPart::STATUS_PENDING,
        ]);

        $allocation = WorkOrderPurchaseAllocation::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'work_order_id' => $this->workOrder->id,
            'purchase_invoice_id' => $this->purchaseInvoice->id,
            'type' => WorkOrderPurchaseAllocation::TYPE_EXTERNAL_PART,
            'item_description' => 'Purchased Shock Absorber',
            'allocated_qty' => 2,
            'unit_cost' => 150.00,
            'tax_amount' => 45.00,
            'total_cost' => 345.00,
        ]);

        // Map allocation to part
        $mapResponse = $this->postJson(route('app.work-orders.purchase-allocations.map', [
            'workOrder' => $this->workOrder,
            'allocation' => $allocation,
        ]), [
            'work_order_item_part_id' => $partItem->id,
        ]);

        $mapResponse->assertOk();
        $this->assertEquals($partItem->id, $allocation->fresh()->work_order_item_part_id);
        $this->assertEquals($partItem->id, $allocation->fresh()->targetPart->id);
        $this->assertEquals($allocation->id, $partItem->fresh()->purchaseAllocation->id);

        // Unmap allocation
        $unmapResponse = $this->postJson(route('app.work-orders.purchase-allocations.map', [
            'workOrder' => $this->workOrder,
            'allocation' => $allocation,
        ]), [
            'unmap' => true,
        ]);

        $unmapResponse->assertOk();
        $this->assertNull($allocation->fresh()->work_order_item_part_id);
        $this->assertNull($partItem->fresh()->purchaseAllocation);
    }

    public function test_can_map_and_unmap_allocation_to_work_order_item_service(): void
    {
        $this->actingAs($this->user);

        $allocation = WorkOrderPurchaseAllocation::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'work_order_id' => $this->workOrder->id,
            'purchase_invoice_id' => $this->purchaseInvoice->id,
            'type' => WorkOrderPurchaseAllocation::TYPE_SUPPLIER_SERVICE,
            'item_description' => 'Outside Calibration',
            'allocated_qty' => 1,
            'unit_cost' => 100.00,
            'tax_amount' => 15.00,
            'total_cost' => 115.00,
        ]);

        // Map to service item
        $mapResponse = $this->postJson(route('app.work-orders.purchase-allocations.map', [
            'workOrder' => $this->workOrder,
            'allocation' => $allocation,
        ]), [
            'work_order_item_id' => $this->workOrderItem->id,
        ]);

        $mapResponse->assertOk();
        $this->assertEquals($this->workOrderItem->id, $allocation->fresh()->work_order_item_id);
        $this->assertEquals($this->workOrderItem->id, $allocation->fresh()->targetService->id);
        $this->assertEquals($allocation->id, $this->workOrderItem->fresh()->supplierServiceAllocation->id);

        // Unmap using target_type = none
        $unmapResponse = $this->postJson(route('app.work-orders.purchase-allocations.map', [
            'workOrder' => $this->workOrder,
            'allocation' => $allocation,
        ]), [
            'target_type' => 'none',
        ]);

        $unmapResponse->assertOk();
        $this->assertNull($allocation->fresh()->work_order_item_id);
        $this->assertNull($this->workOrderItem->fresh()->supplierServiceAllocation);
    }

    public function test_can_deallocate_purchase_line(): void
    {
        $this->actingAs($this->user);

        $allocation = WorkOrderPurchaseAllocation::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'work_order_id' => $this->workOrder->id,
            'purchase_invoice_id' => $this->purchaseInvoice->id,
            'type' => WorkOrderPurchaseAllocation::TYPE_EXTERNAL_PART,
            'item_description' => 'To be deleted',
            'allocated_qty' => 1,
            'unit_cost' => 50.00,
            'total_cost' => 50.00,
        ]);

        $deleteResponse = $this->deleteJson(route('app.work-orders.purchase-allocations.destroy', [
            'workOrder' => $this->workOrder,
            'allocation' => $allocation,
        ]));

        $deleteResponse->assertOk();
        $this->assertDatabaseMissing('work_order_purchase_allocations', [
            'id' => $allocation->id,
        ]);
    }

    public function test_can_create_quick_purchase_invoice_and_auto_allocate(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson(route('app.work-orders.quick-purchase-invoice.store', $this->workOrder), [
            'supplier_id' => $this->supplier->id,
            'invoice_number' => 'QUICK-INV-777',
            'issue_date' => now()->toDateString(),
            'type' => 'supplier_service',
            'lines' => [
                [
                    'item_description' => 'Outside Transmission Repair',
                    'allocated_qty' => 1,
                    'unit_cost' => 300.00,
                    'tax_amount' => 45.00,
                    'total_cost' => 345.00,
                    'work_order_item_id' => $this->workOrderItem->id,
                ],
            ],
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('purchase_invoices', [
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'work_order_id' => $this->workOrder->id,
            'supplier_id' => $this->supplier->id,
            'invoice_number' => 'QUICK-INV-777',
            'subtotal' => 300.00,
            'tax_amount' => 45.00,
            'total' => 345.00,
        ]);

        $this->assertDatabaseHas('work_order_purchase_allocations', [
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'work_order_id' => $this->workOrder->id,
            'work_order_item_id' => $this->workOrderItem->id,
            'type' => WorkOrderPurchaseAllocation::TYPE_SUPPLIER_SERVICE,
            'item_description' => 'Outside Transmission Repair',
            'total_cost' => 345.00,
        ]);
    }

    public function test_tenant_and_center_isolation_enforced(): void
    {
        $otherTenant = Tenant::create([
            'name' => 'Foreign Workshop',
            'slug' => 'foreign-workshop',
            'country' => 'SA',
            'currency' => 'SAR',
            'timezone' => 'Asia/Riyadh',
            'is_active' => true,
        ]);

        $otherCenter = Center::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Foreign Center',
            'slug' => 'foreign-center',
            'is_active' => true,
        ]);

        $otherSupplier = Supplier::create([
            'tenant_id' => $otherTenant->id,
            'center_id' => $otherCenter->id,
            'name' => 'Other Supplier',
            'phone' => '0555999999',
            'is_active' => true,
        ]);

        $otherPurchaseInvoice = PurchaseInvoice::create([
            'tenant_id' => $otherTenant->id,
            'center_id' => $otherCenter->id,
            'supplier_id' => $otherSupplier->id,
            'invoice_number' => 'FOREIGN-INV-1',
            'code' => 'PINV-FOREIGN-1',
            'issue_date' => now()->toDateString(),
            'status' => PurchaseInvoice::STATUS_OPEN,
            'subtotal' => 100.00,
            'tax_amount' => 15.00,
            'total' => 115.00,
            'balance' => 115.00,
        ]);

        $this->actingAs($this->user);

        // Cannot allocate purchase invoice belonging to another tenant
        $response = $this->postJson(route('app.work-orders.purchase-allocations.store', $this->workOrder), [
            'purchase_invoice_id' => $otherPurchaseInvoice->id,
            'type' => WorkOrderPurchaseAllocation::TYPE_EXTERNAL_PART,
            'item_description' => 'Sneaky Part',
            'allocated_qty' => 1,
            'unit_cost' => 100.00,
        ]);

        $response->assertStatus(422);

        // Cannot map allocation belonging to another work order
        $otherCustomer = Customer::create([
            'tenant_id' => $otherTenant->id,
            'center_id' => $otherCenter->id,
            'type' => 'individual',
            'name' => 'Foreign Customer',
            'phone' => '0555888888',
        ]);
        $otherVehicle = Vehicle::create([
            'tenant_id' => $otherTenant->id,
            'center_id' => $otherCenter->id,
            'customer_id' => $otherCustomer->id,
            'plate_number' => 'FOREIGN 1',
        ]);
        $otherWorkOrder = WorkOrder::create([
            'tenant_id' => $otherTenant->id,
            'center_id' => $otherCenter->id,
            'customer_id' => $otherCustomer->id,
            'vehicle_id' => $otherVehicle->id,
            'code' => 'WO-FOREIGN-01',
            'status' => WorkOrder::STATUS_OPEN,
            'entry_date' => now()->toDateString(),
        ]);

        $otherAllocation = WorkOrderPurchaseAllocation::create([
            'tenant_id' => $otherTenant->id,
            'center_id' => $otherCenter->id,
            'work_order_id' => $otherWorkOrder->id,
            'purchase_invoice_id' => $otherPurchaseInvoice->id,
            'type' => WorkOrderPurchaseAllocation::TYPE_EXTERNAL_PART,
            'item_description' => 'Foreign Allocation',
            'allocated_qty' => 1,
            'unit_cost' => 50.00,
            'total_cost' => 50.00,
        ]);

        // 1. Cross-tenant allocation is completely invisible to route model binding (404)
        $crossTenantMapResponse = $this->postJson(route('app.work-orders.purchase-allocations.map', [
            'workOrder' => $this->workOrder,
            'allocation' => $otherAllocation,
        ]), [
            'unmap' => true,
        ]);
        $crossTenantMapResponse->assertNotFound();

        // 2. Same tenant, but allocation belongs to a different work order (403)
        $sameTenantOtherWorkOrder = WorkOrder::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $this->workOrder->customer_id,
            'vehicle_id' => $this->workOrder->vehicle_id,
            'code' => 'WO-SAME-TENANT-02',
            'status' => WorkOrder::STATUS_OPEN,
            'entry_date' => now()->toDateString(),
        ]);

        $sameTenantOtherAllocation = WorkOrderPurchaseAllocation::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'work_order_id' => $sameTenantOtherWorkOrder->id,
            'purchase_invoice_id' => $this->purchaseInvoice->id,
            'type' => WorkOrderPurchaseAllocation::TYPE_EXTERNAL_PART,
            'item_description' => 'Other WO Allocation',
            'allocated_qty' => 1,
            'unit_cost' => 50.00,
            'total_cost' => 50.00,
        ]);

        $crossWoMapResponse = $this->postJson(route('app.work-orders.purchase-allocations.map', [
            'workOrder' => $this->workOrder,
            'allocation' => $sameTenantOtherAllocation,
        ]), [
            'unmap' => true,
        ]);
        $crossWoMapResponse->assertStatus(403);
    }
}
