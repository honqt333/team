<?php

declare(strict_types=1);

namespace Tests\Feature\TenantIsolation;

use App\Models\Customer;
use App\Models\GoodsReceivedNote;
use App\Models\HR\Employee;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Vehicle;
use App\Models\Warehouse;
use App\Models\WorkOrder;
use App\Models\WorkOrderPhoto;

/**
 * Contract tests for HR and purchasing domains.
 * A row in tenant B (employee, purchase order, child rows) must never be
 * queryable by a user authenticated as tenant A.
 */
class HrAndPurchasingTenantIsolationTest extends TenantIsolationTestCase
{
    /** @test */
    public function employee_in_tenant_b_is_not_visible_to_tenant_a(): void
    {
        $empB = Employee::create([
            'tenant_id' => $this->tenantB->id,
            'center_id' => $this->centerB->id,
            'user_id' => null,
            'employee_number' => 'EMP-B-'.random_int(1, 9999),
            'name_ar' => 'موظف ب',
            'name_en' => 'Bob B',
            'national_id' => '2222222222',
            'hired_at' => now()->toDateString(),
            'status' => 'active',
        ]);

        $this->actingAs($this->userA);

        $this->assertNull(Employee::query()->find($empB->id));
    }

    /** @test */
    public function purchase_order_in_tenant_b_is_not_visible_to_tenant_a(): void
    {
        $supplierB = Supplier::create([
            'tenant_id' => $this->tenantB->id,
            'center_id' => $this->centerB->id,
            'name' => 'B Supplier',
            'type' => 'parts',
        ]);
        $whB = Warehouse::create([
            'tenant_id' => $this->tenantB->id,
            'center_id' => $this->centerB->id,
            'name' => 'B WH',
            'code' => 'B-WH-002',
            'is_default' => true,
            'is_active' => true,
        ]);

        $poB = PurchaseOrder::create([
            'tenant_id' => $this->tenantB->id,
            'center_id' => $this->centerB->id,
            'supplier_id' => $supplierB->id,
            'warehouse_id' => $whB->id,
            'code' => 'PO-B-'.random_int(1, 9999),
            'status' => 'draft',
            'currency_code' => 'SAR',
            'order_date' => now()->toDateString(),
            'subtotal' => 0,
            'total' => 0,
        ]);

        $this->actingAs($this->userA);

        $this->assertNull(PurchaseOrder::query()->find($poB->id));
    }

    /** @test */
    public function goods_received_note_in_tenant_b_is_not_visible_to_tenant_a(): void
    {
        $supplierB = Supplier::create([
            'tenant_id' => $this->tenantB->id,
            'center_id' => $this->centerB->id,
            'name' => 'B Supplier',
            'type' => 'parts',
        ]);
        $whB = Warehouse::create([
            'tenant_id' => $this->tenantB->id,
            'center_id' => $this->centerB->id,
            'name' => 'B WH',
            'code' => 'B-WH-003',
            'is_default' => true,
            'is_active' => true,
        ]);
        $poB = PurchaseOrder::create([
            'tenant_id' => $this->tenantB->id,
            'center_id' => $this->centerB->id,
            'supplier_id' => $supplierB->id,
            'warehouse_id' => $whB->id,
            'code' => 'PO-B-'.random_int(1, 9999),
            'status' => 'draft',
            'currency_code' => 'SAR',
            'order_date' => now()->toDateString(),
            'subtotal' => 0,
            'total' => 0,
        ]);

        $grnB = GoodsReceivedNote::create([
            'tenant_id' => $this->tenantB->id,
            'center_id' => $this->centerB->id,
            'purchase_order_id' => $poB->id,
            'warehouse_id' => Warehouse::create([
                'tenant_id' => $this->tenantB->id,
                'center_id' => $this->centerB->id,
                'name' => 'B WH',
                'code' => 'B-WH-'.random_int(1, 999),
                'is_default' => true,
                'is_active' => true,
            ])->id,
            'code' => 'GRN-B-0001',
            'status' => 'draft',
            'received_date' => now()->toDateString(),
        ]);

        $this->actingAs($this->userA);

        $this->assertNull(GoodsReceivedNote::query()->find($grnB->id));
    }

    /** @test */
    public function warehouse_in_tenant_b_is_not_visible_to_tenant_a(): void
    {
        $whB = Warehouse::create([
            'tenant_id' => $this->tenantB->id,
            'center_id' => $this->centerB->id,
            'name' => 'B Main',
            'code' => 'B-WH-001',
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->actingAs($this->userA);

        $this->assertNull(Warehouse::query()->find($whB->id));
    }

    /** @test */
    public function work_order_photo_in_tenant_b_is_not_visible_to_tenant_a(): void
    {
        $customerB = Customer::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'center_id' => $this->centerB->id,
            'phone' => '+966500000030',
        ]);
        $vehicleB = Vehicle::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'center_id' => $this->centerB->id,
            'customer_id' => $customerB->id,
            'plate_number' => 'BBB-100',
        ]);
        $woB = WorkOrder::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'center_id' => $this->centerB->id,
            'customer_id' => $customerB->id,
            'vehicle_id' => $vehicleB->id,
        ]);

        $photoB = WorkOrderPhoto::create([
            'tenant_id' => $this->tenantB->id,
            'center_id' => $this->centerB->id,
            'work_order_id' => $woB->id,
            'path' => 'photos/b.jpg',
            'type' => 'general',
        ]);

        $this->actingAs($this->userA);

        $this->assertNull(WorkOrderPhoto::query()->find($photoB->id));
    }

    protected function createRecordInTenantB(): mixed
    {
        return null;
    }
}
