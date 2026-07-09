<?php

namespace Tests\Feature\TenantIsolation;

use App\Models\Customer;
use App\Models\Part;
use App\Models\Supplier;
use App\Models\Vehicle;
use App\Models\WorkOrder;

/**
 * Verifies the auto-fill behavior of the TenantScoped / CenterScoped traits:
 * when a user with a tenant context creates a row, the row automatically gets
 * the user's tenant_id and center_id even if the caller forgets to set them.
 *
 * This guards against a class of bugs where a developer creates a record
 * without explicitly passing tenant_id and accidentally produces a row that
 * doesn't belong to any tenant (and therefore is invisible everywhere).
 */
class TenantContextAutoFillTest extends TenantIsolationTestCase
{
    /** @test */
    public function supplier_created_under_user_context_inherits_tenant_and_center(): void
    {
        $this->actingAs($this->userA);

        $supplier = Supplier::create([
            // Notice: tenant_id and center_id are NOT passed by the caller.
            'name' => 'Auto-fill Supplier',
            'type' => 'parts',
        ]);

        $this->assertSame($this->tenantA->id, $supplier->tenant_id);
        $this->assertSame($this->centerA->id, $supplier->center_id);
    }

    /** @test */
    public function customer_created_under_user_context_inherits_tenant_and_center(): void
    {
        $this->actingAs($this->userA);

        $customer = Customer::create([
            'name' => 'Auto-fill Customer',
            'phone' => '+966509876543',
        ]);

        $this->assertSame($this->tenantA->id, $customer->tenant_id);
        $this->assertSame($this->centerA->id, $customer->center_id);
    }

    /** @test */
    public function part_created_under_user_context_inherits_tenant(): void
    {
        $this->actingAs($this->userA);

        $part = Part::create([
            'sku' => 'AUTO-FILL-001',
            'name_ar' => 'قطعة',
            'name_en' => 'Part',
            'is_active' => true,
        ]);

        $this->assertSame($this->tenantA->id, $part->tenant_id);
    }

    /** @test */
    public function work_order_created_under_user_context_inherits_tenant_and_center(): void
    {
        $this->actingAs($this->userA);

        $wo = WorkOrder::create([
            'customer_id' => Customer::factory()->create([
                'tenant_id' => $this->tenantA->id,
                'center_id' => $this->centerA->id,
            ])->id,
            'vehicle_id' => Vehicle::factory()->create([
                'tenant_id' => $this->tenantA->id,
                'center_id' => $this->centerA->id,
            ])->id,
            'code' => 'WO-AUTO-'.random_int(1, 9999),
            'status' => 'open',
            'tax_enabled_snapshot' => false,
            'pricing_mode_snapshot' => 'exclusive',
            'tax_rate_snapshot' => 15,
            'currency_code' => 'SAR',
        ]);

        $this->assertSame($this->tenantA->id, $wo->tenant_id);
        $this->assertSame($this->centerA->id, $wo->center_id);
    }

    /** @test */
    public function two_tenants_can_have_rows_with_the_same_natural_key(): void
    {
        // Same SKU can exist in both tenants because the scoped uniqueness is per-tenant.
        $partA = Part::create([
            'tenant_id' => $this->tenantA->id,
            'sku' => 'SHARED-SKU',
            'name_ar' => 'A',
            'name_en' => 'A',
            'is_active' => true,
        ]);
        $partB = Part::create([
            'tenant_id' => $this->tenantB->id,
            'sku' => 'SHARED-SKU',
            'name_ar' => 'B',
            'name_en' => 'B',
            'is_active' => true,
        ]);

        $this->assertNotSame($partA->id, $partB->id);
        $this->assertSame($partA->sku, $partB->sku);

        // And each user only sees their own.
        $this->actingAs($this->userA);
        $this->assertNotNull(Part::query()->find($partA->id));
        $this->assertNull(Part::query()->find($partB->id));
    }

    protected function createRecordInTenantB(): mixed
    {
        return null;
    }
}
