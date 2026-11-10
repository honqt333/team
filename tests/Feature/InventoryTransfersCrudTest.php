<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\InventoryTransfer;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\Permissions;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class InventoryTransfersCrudTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Center $center;

    protected User $user;

    protected Warehouse $warehouseFrom;

    protected Warehouse $warehouseTo;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $this->seed(PermissionsSeeder::class);

        $this->tenant = Tenant::factory()->create();
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);

        $this->user->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
        $this->user->givePermissionTo(Permissions::INVENTORY_MOVES_VIEW);
        $this->user->givePermissionTo(Permissions::INVENTORY_MOVES_CREATE);
        $this->user->refresh();

        $this->warehouseFrom = Warehouse::getOrCreateDefault($this->center->id);
        $this->warehouseTo = Warehouse::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name' => 'Secondary Warehouse',
            'is_default' => false,
            'is_active' => true,
        ]);
    }

    public function test_unauthorized_user_cannot_view_transfers(): void
    {
        $unauthorized = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);
        $unauthorized->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($unauthorized)
            ->get(route('app.inventory.transfers.index'));

        $response->assertStatus(403);
    }

    public function test_authorized_user_can_list_transfers(): void
    {
        InventoryTransfer::create([
            'tenant_id' => $this->tenant->id,
            'from_warehouse_id' => $this->warehouseFrom->id,
            'to_warehouse_id' => $this->warehouseTo->id,
            'code' => 'TR-2026-0001',
            'status' => InventoryTransfer::STATUS_DRAFT,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('app.inventory.transfers.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Inventory/Transfers/Index')
            ->has('transfers.data', 1)
            ->where('transfers.data.0.code', 'TR-2026-0001')
            ->has('warehouses')
            ->has('filters')
        );
    }

    public function test_search_filters_transfers_by_code_and_warehouse(): void
    {
        $thirdWarehouse = Warehouse::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name' => 'North Central Hub',
            'is_default' => false,
            'is_active' => true,
        ]);

        InventoryTransfer::create([
            'tenant_id' => $this->tenant->id,
            'from_warehouse_id' => $this->warehouseFrom->id,
            'to_warehouse_id' => $this->warehouseTo->id,
            'code' => 'TR-ALPHA-100',
            'status' => InventoryTransfer::STATUS_DRAFT,
            'created_by' => $this->user->id,
        ]);

        InventoryTransfer::create([
            'tenant_id' => $this->tenant->id,
            'from_warehouse_id' => $this->warehouseFrom->id,
            'to_warehouse_id' => $thirdWarehouse->id,
            'code' => 'TR-BETA-200',
            'status' => InventoryTransfer::STATUS_SENT,
            'created_by' => $this->user->id,
        ]);

        // Search by code
        $responseCode = $this->actingAs($this->user)
            ->get(route('app.inventory.transfers.index', ['search' => 'ALPHA']));

        $responseCode->assertInertia(fn (Assert $page) => $page
            ->has('transfers.data', 1)
            ->where('transfers.data.0.code', 'TR-ALPHA-100')
        );

        // Search by warehouse name
        $responseWarehouse = $this->actingAs($this->user)
            ->get(route('app.inventory.transfers.index', ['search' => 'North Central']));

        $responseWarehouse->assertInertia(fn (Assert $page) => $page
            ->has('transfers.data', 1)
            ->where('transfers.data.0.code', 'TR-BETA-200')
        );
    }

    public function test_status_and_warehouse_filters(): void
    {
        InventoryTransfer::create([
            'tenant_id' => $this->tenant->id,
            'from_warehouse_id' => $this->warehouseFrom->id,
            'to_warehouse_id' => $this->warehouseTo->id,
            'code' => 'TR-DRAFT',
            'status' => InventoryTransfer::STATUS_DRAFT,
            'created_by' => $this->user->id,
        ]);

        InventoryTransfer::create([
            'tenant_id' => $this->tenant->id,
            'from_warehouse_id' => $this->warehouseFrom->id,
            'to_warehouse_id' => $this->warehouseTo->id,
            'code' => 'TR-SENT',
            'status' => InventoryTransfer::STATUS_SENT,
            'created_by' => $this->user->id,
        ]);

        // Filter by status
        $responseStatus = $this->actingAs($this->user)
            ->get(route('app.inventory.transfers.index', ['status' => InventoryTransfer::STATUS_SENT]));

        $responseStatus->assertInertia(fn (Assert $page) => $page
            ->has('transfers.data', 1)
            ->where('transfers.data.0.code', 'TR-SENT')
        );

        // Filter by warehouse
        $responseWarehouse = $this->actingAs($this->user)
            ->get(route('app.inventory.transfers.index', ['warehouse_id' => $this->warehouseTo->id]));

        $responseWarehouse->assertInertia(fn (Assert $page) => $page
            ->has('transfers.data', 2)
        );
    }

    public function test_whitelist_sorting_asc_and_desc(): void
    {
        InventoryTransfer::create([
            'tenant_id' => $this->tenant->id,
            'from_warehouse_id' => $this->warehouseFrom->id,
            'to_warehouse_id' => $this->warehouseTo->id,
            'code' => 'TR-001',
            'status' => InventoryTransfer::STATUS_DRAFT,
            'created_by' => $this->user->id,
            'created_at' => '2026-11-01 10:00:00',
        ]);

        InventoryTransfer::create([
            'tenant_id' => $this->tenant->id,
            'from_warehouse_id' => $this->warehouseFrom->id,
            'to_warehouse_id' => $this->warehouseTo->id,
            'code' => 'TR-002',
            'status' => InventoryTransfer::STATUS_SENT,
            'created_by' => $this->user->id,
            'created_at' => '2026-11-05 10:00:00',
        ]);

        // Sort by code asc
        $responseAsc = $this->actingAs($this->user)
            ->get(route('app.inventory.transfers.index', ['sort' => 'code', 'direction' => 'asc']));

        $responseAsc->assertInertia(fn (Assert $page) => $page
            ->has('transfers.data', 2)
            ->where('transfers.data.0.code', 'TR-001')
            ->where('transfers.data.1.code', 'TR-002')
        );

        // Sort by code desc
        $responseDesc = $this->actingAs($this->user)
            ->get(route('app.inventory.transfers.index', ['sort' => 'code', 'direction' => 'desc']));

        $responseDesc->assertInertia(fn (Assert $page) => $page
            ->has('transfers.data', 2)
            ->where('transfers.data.0.code', 'TR-002')
            ->where('transfers.data.1.code', 'TR-001')
        );
    }

    public function test_tenant_scoping_isolation(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherCenter = Center::factory()->create(['tenant_id' => $otherTenant->id]);
        $otherWarehouseA = Warehouse::getOrCreateDefault($otherCenter->id);
        $otherWarehouseB = Warehouse::create([
            'tenant_id' => $otherTenant->id,
            'center_id' => $otherCenter->id,
            'name' => 'Other Tenant Warehouse',
            'is_default' => false,
            'is_active' => true,
        ]);

        InventoryTransfer::create([
            'tenant_id' => $otherTenant->id,
            'from_warehouse_id' => $otherWarehouseA->id,
            'to_warehouse_id' => $otherWarehouseB->id,
            'code' => 'TR-OTHER-TENANT',
            'status' => InventoryTransfer::STATUS_DRAFT,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('app.inventory.transfers.index'));

        $response->assertInertia(fn (Assert $page) => $page
            ->has('transfers.data', 0)
        );
    }
}
