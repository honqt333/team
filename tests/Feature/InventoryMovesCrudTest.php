<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\InventoryMove;
use App\Models\InventoryUnit;
use App\Models\Part;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\Permissions;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class InventoryMovesCrudTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Center $center;

    protected User $user;

    protected Warehouse $warehouse;

    protected Part $partA;

    protected Part $partB;

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
        $this->user->givePermissionTo('inventory.moves.create');
        $this->user->refresh();

        $this->warehouse = Warehouse::getOrCreateDefault($this->center->id);

        $unit = InventoryUnit::create([
            'tenant_id' => $this->tenant->id,
            'name_ar' => 'قطعة',
            'name_en' => 'Piece',
            'code' => 'PCS',
            'is_active' => true,
        ]);

        $this->partA = Part::create([
            'tenant_id' => $this->tenant->id,
            'sku' => 'SKU-BRAKE-01',
            'name_ar' => 'فحمات فرامل أمامية',
            'name_en' => 'Front Brake Pads',
            'unit_id' => $unit->id,
            'sale_price' => 150,
            'is_active' => true,
        ]);

        $this->partB = Part::create([
            'tenant_id' => $this->tenant->id,
            'sku' => 'SKU-OIL-02',
            'name_ar' => 'فلتر زيت تويوتا',
            'name_en' => 'Toyota Oil Filter',
            'unit_id' => $unit->id,
            'sale_price' => 45,
            'is_active' => true,
        ]);
    }

    public function test_unauthorized_user_cannot_view_inventory_moves(): void
    {
        $unauthorized = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);
        $unauthorized->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($unauthorized)
            ->get(route('app.inventory.moves.index'));

        $response->assertStatus(403);
    }

    public function test_authorized_user_can_list_inventory_moves(): void
    {
        $move = InventoryMove::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'warehouse_id' => $this->warehouse->id,
            'part_id' => $this->partA->id,
            'move_type' => InventoryMove::TYPE_RECEIPT,
            'qty' => 10,
            'unit_cost' => 100,
            'total_cost' => 1000,
            'balance_after' => 10,
            'wac_after' => 100,
            'posted_at' => now(),
            'posted_by' => $this->user->id,
        ]);

        // Debug
        // dd('Move warehouse_id:', $move->warehouse_id, 'Warehouse ID:', $this->warehouse->id, 'Moves in DB:', InventoryMove::withoutGlobalScopes()->get()->toArray());

        $response = $this->actingAs($this->user)
            ->get(route('app.inventory.moves.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Inventory/Moves/Index')
            ->has('moves.data', 1)
            ->where('moves.data.0.part.sku', 'SKU-BRAKE-01')
            ->has('warehouse')
            ->has('moveTypes')
            ->has('filters')
        );
    }

    public function test_search_filters_inventory_moves_by_sku_and_part_name(): void
    {
        InventoryMove::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'warehouse_id' => $this->warehouse->id,
            'part_id' => $this->partA->id,
            'move_type' => InventoryMove::TYPE_RECEIPT,
            'qty' => 5,
            'unit_cost' => 100,
            'total_cost' => 500,
            'balance_after' => 5,
            'wac_after' => 100,
            'posted_at' => now()->subDay(),
            'posted_by' => $this->user->id,
        ]);

        InventoryMove::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'warehouse_id' => $this->warehouse->id,
            'part_id' => $this->partB->id,
            'move_type' => InventoryMove::TYPE_RECEIPT,
            'qty' => 20,
            'unit_cost' => 30,
            'total_cost' => 600,
            'balance_after' => 20,
            'wac_after' => 30,
            'posted_at' => now(),
            'posted_by' => $this->user->id,
        ]);

        // Search by SKU
        $responseSku = $this->actingAs($this->user)
            ->get(route('app.inventory.moves.index', ['search' => 'SKU-BRAKE']));

        $responseSku->assertInertia(fn (Assert $page) => $page
            ->has('moves.data', 1)
            ->where('moves.data.0.part.sku', 'SKU-BRAKE-01')
        );

        // Search by Arabic name
        $responseName = $this->actingAs($this->user)
            ->get(route('app.inventory.moves.index', ['search' => 'فلتر زيت']));

        $responseName->assertInertia(fn (Assert $page) => $page
            ->has('moves.data', 1)
            ->where('moves.data.0.part.sku', 'SKU-OIL-02')
        );
    }

    public function test_type_filter_filters_inventory_moves(): void
    {
        $move = InventoryMove::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'warehouse_id' => $this->warehouse->id,
            'part_id' => $this->partA->id,
            'move_type' => InventoryMove::TYPE_RECEIPT,
            'qty' => 10,
            'unit_cost' => 100,
            'total_cost' => 1000,
            'balance_after' => 10,
            'wac_after' => 100,
            'posted_at' => now(),
            'posted_by' => $this->user->id,
        ]);

        // Debug
        // dd('Move warehouse_id:', $move->warehouse_id, 'Warehouse ID:', $this->warehouse->id, 'Moves in DB:', InventoryMove::withoutGlobalScopes()->get()->toArray());

        InventoryMove::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'warehouse_id' => $this->warehouse->id,
            'part_id' => $this->partA->id,
            'move_type' => InventoryMove::TYPE_ADJUSTMENT_IN,
            'qty' => 2,
            'unit_cost' => 100,
            'total_cost' => 200,
            'balance_after' => 12,
            'wac_after' => 100,
            'posted_at' => now(),
            'posted_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('app.inventory.moves.index', ['type' => InventoryMove::TYPE_ADJUSTMENT_IN]));

        $response->assertInertia(fn (Assert $page) => $page
            ->has('moves.data', 1)
            ->where('moves.data.0.move_type', InventoryMove::TYPE_ADJUSTMENT_IN)
        );
    }

    public function test_whitelist_sorting_asc_and_desc(): void
    {
        InventoryMove::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'warehouse_id' => $this->warehouse->id,
            'part_id' => $this->partA->id,
            'move_type' => InventoryMove::TYPE_RECEIPT,
            'qty' => 5,
            'unit_cost' => 100,
            'total_cost' => 500,
            'balance_after' => 5,
            'wac_after' => 100,
            'posted_at' => '2026-11-01 10:00:00',
            'posted_by' => $this->user->id,
        ]);

        InventoryMove::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'warehouse_id' => $this->warehouse->id,
            'part_id' => $this->partB->id,
            'move_type' => InventoryMove::TYPE_RECEIPT,
            'qty' => 20,
            'unit_cost' => 30,
            'total_cost' => 600,
            'balance_after' => 20,
            'wac_after' => 30,
            'posted_at' => '2026-11-05 10:00:00',
            'posted_by' => $this->user->id,
        ]);

        // Sort by qty ascending
        $responseAsc = $this->actingAs($this->user)
            ->get(route('app.inventory.moves.index', ['sort' => 'qty', 'direction' => 'asc']));

        $responseAsc->assertInertia(fn (Assert $page) => $page
            ->has('moves.data', 2)
            ->where('moves.data.0.part.sku', 'SKU-BRAKE-01')
            ->where('moves.data.1.part.sku', 'SKU-OIL-02')
        );

        // Sort by qty descending
        $responseDesc = $this->actingAs($this->user)
            ->get(route('app.inventory.moves.index', ['sort' => 'qty', 'direction' => 'desc']));

        $responseDesc->assertInertia(fn (Assert $page) => $page
            ->has('moves.data', 2)
            ->where('moves.data.0.part.sku', 'SKU-OIL-02')
            ->where('moves.data.1.part.sku', 'SKU-BRAKE-01')
        );
    }

    public function test_warehouse_scoping_isolation(): void
    {
        $otherWarehouse = Warehouse::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name' => 'Secondary Warehouse',
            'is_default' => false,
            'is_active' => true,
        ]);

        InventoryMove::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'warehouse_id' => $otherWarehouse->id,
            'part_id' => $this->partA->id,
            'move_type' => InventoryMove::TYPE_RECEIPT,
            'qty' => 50,
            'unit_cost' => 100,
            'total_cost' => 5000,
            'balance_after' => 50,
            'wac_after' => 100,
            'posted_at' => now(),
            'posted_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('app.inventory.moves.index'));

        // Since the controller scopes to the default warehouse of the center, the other warehouse's move is excluded
        $response->assertInertia(fn (Assert $page) => $page
            ->has('moves.data', 0)
        );
    }
}
