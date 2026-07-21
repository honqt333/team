<?php

declare(strict_types=1);

namespace Tests\Feature\TenantIsolation;

use App\Models\Customer;
use App\Models\InventoryMove;
use App\Models\Part;
use App\Models\Supplier;
use App\Models\Vehicle;
use App\Models\Warehouse;
use App\Models\WorkOrder;

/**
 * Verifies that the global TenantScoped / CenterScoped scopes prevent
 * cross-tenant visibility for the most-trafficked business entities.
 *
 * Each test creates a row in tenant B, then acts as a user in tenant A and
 * confirms the row is not visible — neither via the Eloquent query nor via
 * the `tenant_id` column equality.
 */
class TenantScopedEntitiesTest extends TenantIsolationTestCase
{
    /** @test */
    public function supplier_in_tenant_b_is_not_visible_to_tenant_a(): void
    {
        $supplierB = Supplier::create([
            'tenant_id' => $this->tenantB->id,
            'center_id' => $this->centerB->id,
            'name' => 'Supplier B',
            'type' => 'parts',
            'contact_person' => 'Bob',
        ]);

        $this->actingAs($this->userA);

        // Cannot see via query
        $this->assertCount(0, Supplier::query()->where('id', $supplierB->id)->get());

        // Direct DB::table has the row but Eloquent scope filters it out.
        $this->assertDatabaseHas('suppliers', ['id' => $supplierB->id, 'tenant_id' => $this->tenantB->id]);
        $this->assertNull(Supplier::query()->find($supplierB->id));
    }

    /** @test */
    public function part_in_tenant_b_is_not_visible_to_tenant_a(): void
    {
        $partB = Part::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'sku' => 'TB-001',
        ]);

        $this->actingAs($this->userA);

        $this->assertCount(0, Part::query()->where('id', $partB->id)->get());
        $this->assertNull(Part::query()->find($partB->id));

        // Create the same SKU in A — it must succeed because the scoped uniqueness
        // treats each tenant's namespace independently.
        $partA = Part::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'sku' => 'TB-001',
        ]);

        $this->actingAs($this->userA);
        $this->assertNotNull(Part::query()->find($partA->id));
        $this->assertNull(Part::query()->find($partB->id));
    }

    /** @test */
    public function customer_in_tenant_b_is_not_visible_to_tenant_a(): void
    {
        $customerB = Customer::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'center_id' => $this->centerB->id,
            'name' => 'Customer B',
            'phone' => '+966500000002',
        ]);

        $this->actingAs($this->userA);

        $this->assertNull(Customer::query()->find($customerB->id));
        $this->assertCount(0, Customer::query()->where('id', $customerB->id)->get());
    }

    /** @test */
    public function vehicle_in_tenant_b_is_not_visible_to_tenant_a(): void
    {
        $vehicleB = Vehicle::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'center_id' => $this->centerB->id,
            'plate_number' => 'BBB-123',
        ]);

        $this->actingAs($this->userA);

        $this->assertNull(Vehicle::query()->find($vehicleB->id));
    }

    /** @test */
    public function work_order_in_tenant_b_is_not_visible_to_tenant_a(): void
    {
        $woB = WorkOrder::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'center_id' => $this->centerB->id,
        ]);

        $this->actingAs($this->userA);

        $this->assertNull(WorkOrder::query()->find($woB->id));
        $this->assertCount(0, WorkOrder::query()->where('id', $woB->id)->get());
    }

    /** @test */
    public function inventory_moves_in_tenant_b_are_not_visible_to_tenant_a(): void
    {
        // We need a warehouse in B for the move to be valid
        $whB = Warehouse::create([
            'tenant_id' => $this->tenantB->id,
            'center_id' => $this->centerB->id,
            'name' => 'B WH',
            'code' => 'B-WH',
            'is_default' => true,
            'is_active' => true,
        ]);

        $moveB = InventoryMove::create([
            'tenant_id' => $this->tenantB->id,
            'center_id' => $this->centerB->id,
            'warehouse_id' => $whB->id,
            'part_id' => Part::factory()->create(['tenant_id' => $this->tenantB->id])->id,
            'move_type' => 'receipt',
            'qty' => 10,
            'unit_cost' => 50,
            'total_cost' => 500,
            'posted_at' => now(),
        ]);

        $this->actingAs($this->userA);

        $this->assertNull(InventoryMove::query()->find($moveB->id));
    }

    protected function createRecordInTenantB(): mixed
    {
        return null; // not used; each test creates its own fixture
    }
}
