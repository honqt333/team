<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\InventoryBalance;
use App\Models\Part;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use App\Support\Permissions;
use App\Support\TenancyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Tests for the cross-tenant inventory balance hole
 * (audit finding #5).
 *
 * THE BUG
 *
 * The inventory balance row is identified by its natural key
 * (warehouse_id + part_id), so every lookup in InventoryService
 * deliberately bypasses the global scopes:
 *
 *     InventoryBalance::query()->withoutGlobalScopes()
 *         ->where('warehouse_id', $warehouseId)
 *         ->where('part_id', $partId)
 *
 * The controllers validated the two ids with a bare
 * `exists:warehouses,id` / `exists:parts,id`, which only checks that
 * the row EXISTS — not whose it is. A user with
 * `inventory.moves.create` could therefore send another tenant's
 * warehouse and part ids and the service would adjust THAT tenant's
 * stock, recording the movement under the current tenant's context.
 *
 * The same hole existed on the work-order parts path, which passed
 * the request's part_id/warehouse_id straight to
 * InventoryService::issue().
 *
 * THE FIX
 *
 *   - The service re-checks the warehouse and the part against the
 *     authorized tenant before opening a balance transaction
 *     (assertResourcesInScope).
 *   - The controllers scope their `exists` rules to the tenant.
 *
 * The service check is TENANT-level, not center-level: a transfer
 * between two branches of the same tenant is a legitimate flow.
 */
class CrossTenantInventoryScopeTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Center $center;

    private Warehouse $warehouse;

    private Part $part;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::findOrCreate(Permissions::INVENTORY_MOVES_CREATE, 'web');

        $this->tenant = Tenant::factory()->create();
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->warehouse = Warehouse::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
        ]);
        $this->part = Part::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    /**
     * Another tenant, with its own center, warehouse and part.
     */
    private function otherTenantResources(): array
    {
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);
        $warehouse = Warehouse::factory()->create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
        ]);
        $part = Part::factory()->create(['tenant_id' => $tenant->id]);

        return [$tenant, $center, $warehouse, $part];
    }

    private function userFor(Tenant $tenant, Center $center): User
    {
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
        ]);
        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $user->givePermissionTo(Permissions::INVENTORY_MOVES_CREATE);
        $user->refresh();

        return $user;
    }

    private function stockIn(Warehouse $warehouse, Part $part, float $qty = 100): InventoryBalance
    {
        return InventoryBalance::create([
            'tenant_id' => $warehouse->tenant_id,
            'center_id' => $warehouse->center_id,
            'warehouse_id' => $warehouse->id,
            'part_id' => $part->id,
            'qty_on_hand' => $qty,
            'wac_cost' => 10,
        ]);
    }

    /**
     * The headline attack: a receipt into ANOTHER tenant's warehouse.
     */
    public function test_a_receipt_cannot_target_another_tenants_warehouse(): void
    {
        [$otherTenant, $otherCenter, $otherWarehouse, $otherPart] = $this->otherTenantResources();

        $balance = $this->stockIn($otherWarehouse, $otherPart, 100);

        $user = $this->userFor($this->tenant, $this->center);

        $response = $this->actingAs($user)->post('/app/inventory/moves/receipt', [
            'warehouse_id' => $otherWarehouse->id,
            'part_id' => $otherPart->id,
            'qty' => 50,
            'unit_cost' => 10,
            'notes' => 'cross tenant attempt',
        ]);

        $response->assertSessionHasErrors();

        $this->assertEquals(
            100.0,
            (float) $balance->fresh()->qty_on_hand,
            'Another tenant\'s stock level must not change.'
        );
    }

    /**
     * The same hole through the adjustment endpoint.
     */
    public function test_an_adjustment_cannot_target_another_tenants_stock(): void
    {
        [$otherTenant, $otherCenter, $otherWarehouse, $otherPart] = $this->otherTenantResources();

        $balance = $this->stockIn($otherWarehouse, $otherPart, 100);

        $user = $this->userFor($this->tenant, $this->center);

        $response = $this->actingAs($user)->post('/app/inventory/moves/adjustment', [
            'warehouse_id' => $otherWarehouse->id,
            'part_id' => $otherPart->id,
            'qty' => -50,
            'unit_cost' => 10,
            'notes' => 'cross tenant attempt',
        ]);

        $response->assertSessionHasErrors();

        $this->assertEquals(
            100.0,
            (float) $balance->fresh()->qty_on_hand,
            'Another tenant\'s stock level must not change.'
        );
    }

    /**
     * A part from another tenant is refused even when the warehouse
     * is the caller's own.
     */
    public function test_a_part_from_another_tenant_is_refused(): void
    {
        [$otherTenant, $otherCenter, $otherWarehouse, $otherPart] = $this->otherTenantResources();

        $user = $this->userFor($this->tenant, $this->center);

        $response = $this->actingAs($user)->post('/app/inventory/moves/receipt', [
            'warehouse_id' => $this->warehouse->id,
            'part_id' => $otherPart->id,
            'qty' => 5,
            'unit_cost' => 10,
        ]);

        $response->assertSessionHasErrors('part_id');

        $this->assertDatabaseMissing('inventory_balances', [
            'warehouse_id' => $this->warehouse->id,
            'part_id' => $otherPart->id,
        ]);
    }

    /**
     * The service itself refuses, even when called directly with the
     * foreign ids — the controller check is the first layer, this is
     * the second.
     */
    public function test_the_service_refuses_another_tenants_resources(): void
    {
        [$otherTenant, $otherCenter, $otherWarehouse, $otherPart] = $this->otherTenantResources();

        $balance = $this->stockIn($otherWarehouse, $otherPart, 100);

        $user = $this->userFor($this->tenant, $this->center);
        $this->actingAs($user);

        $service = app(InventoryService::class);

        $this->expectException(ValidationException::class);

        try {
            $service->receipt(
                warehouseId: $otherWarehouse->id,
                partId: $otherPart->id,
                qty: 50,
                unitCost: 10,
                userId: $user->id,
            );
        } finally {
            $this->assertEquals(
                100.0,
                (float) $balance->fresh()->qty_on_hand,
                'Another tenant\'s stock level must not change.'
            );
        }
    }

    /**
     * Reading another tenant's stock level is a disclosure too.
     */
    public function test_the_stock_level_of_another_tenant_is_not_readable(): void
    {
        [$otherTenant, $otherCenter, $otherWarehouse, $otherPart] = $this->otherTenantResources();

        $this->stockIn($otherWarehouse, $otherPart, 250);

        $user = $this->userFor($this->tenant, $this->center);
        $this->actingAs($user);

        $service = app(InventoryService::class);

        $this->expectException(ValidationException::class);

        $service->getStockLevel($otherWarehouse->id, $otherPart->id);
    }

    /**
     * The legitimate flow must keep working.
     */
    public function test_a_receipt_into_the_callers_own_warehouse_still_works(): void
    {
        $user = $this->userFor($this->tenant, $this->center);

        $response = $this->actingAs($user)->post('/app/inventory/moves/receipt', [
            'warehouse_id' => $this->warehouse->id,
            'part_id' => $this->part->id,
            'qty' => 50,
            'unit_cost' => 10,
            'notes' => 'legitimate receipt',
        ]);

        $response->assertSessionHasNoErrors();

        $balance = InventoryBalance::where('warehouse_id', $this->warehouse->id)
            ->where('part_id', $this->part->id)
            ->first();

        $this->assertNotNull($balance);
        $this->assertEquals(50.0, (float) $balance->qty_on_hand);
    }

    /**
     * A warehouse of ANOTHER CENTER in the SAME tenant is allowed —
     * branch-to-branch stock movement is a legitimate flow.
     */
    public function test_another_center_of_the_same_tenant_is_allowed(): void
    {
        $siblingCenter = Center::factory()->create(['tenant_id' => $this->tenant->id]);
        $siblingWarehouse = Warehouse::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $siblingCenter->id,
        ]);

        $user = $this->userFor($this->tenant, $this->center);

        $response = $this->actingAs($user)->post('/app/inventory/moves/receipt', [
            'warehouse_id' => $siblingWarehouse->id,
            'part_id' => $this->part->id,
            'qty' => 25,
            'unit_cost' => 10,
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('inventory_balances', [
            'warehouse_id' => $siblingWarehouse->id,
            'part_id' => $this->part->id,
        ]);
    }

    /**
     * The tenant context is what the guard keys on — make sure it is
     * actually the caller's tenant during a request.
     */
    public function test_the_tenant_context_follows_the_authenticated_user(): void
    {
        $user = $this->userFor($this->tenant, $this->center);

        $this->actingAs($user);

        $this->assertSame($this->tenant->id, TenancyContext::tenantId());
    }
}
