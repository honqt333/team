<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\InventoryBalance;
use App\Models\InventoryCategory;
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

class InventoryStockBalancesTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Center $center;

    protected User $user;

    protected Warehouse $warehouse;

    protected InventoryCategory $category;

    protected Part $partA;

    protected Part $partB;

    protected Part $partC;

    protected InventoryBalance $balanceA;

    protected InventoryBalance $balanceB;

    protected InventoryBalance $balanceC;

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
        $this->user->givePermissionTo(Permissions::INVENTORY_STOCK_VIEW);
        $this->user->refresh();

        $this->warehouse = Warehouse::getOrCreateDefault($this->center->id);

        $unit = InventoryUnit::create([
            'tenant_id' => $this->tenant->id,
            'name_ar' => 'قطعة',
            'name_en' => 'Piece',
            'code' => 'PCS',
        ]);

        $this->category = InventoryCategory::create([
            'tenant_id' => $this->tenant->id,
            'name_ar' => 'زيوت وفلاتر',
            'name_en' => 'Oils & Filters',
            'code' => 'OILS',
            'is_active' => true,
        ]);

        $otherCat = InventoryCategory::create([
            'tenant_id' => $this->tenant->id,
            'name_ar' => 'فرامل',
            'name_en' => 'Brakes',
            'code' => 'BRK',
            'is_active' => true,
        ]);

        // Part A: In Stock (qty 50 > min 10)
        $this->partA = Part::create([
            'tenant_id' => $this->tenant->id,
            'sku' => 'SKU-AAA-01',
            'name_ar' => 'زيت محرك تويوتا',
            'name_en' => 'Toyota Engine Oil',
            'unit_id' => $unit->id,
            'category_id' => $this->category->id,
            'min_qty' => 10,
            'default_sale_price' => 50.00,
        ]);

        $this->balanceA = InventoryBalance::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'warehouse_id' => $this->warehouse->id,
            'part_id' => $this->partA->id,
            'qty_on_hand' => 50,
            'min_stock' => 10,
            'wac_cost' => 30.00,
            'sale_price' => 50.00,
            'min_sale_price' => 45.00,
            'is_active' => true,
        ]);

        // Part B: Low Stock (qty 5 <= min 10)
        $this->partB = Part::create([
            'tenant_id' => $this->tenant->id,
            'sku' => 'SKU-BBB-02',
            'name_ar' => 'فلتر هواء نيسان',
            'name_en' => 'Nissan Air Filter',
            'unit_id' => $unit->id,
            'category_id' => $this->category->id,
            'min_qty' => 10,
            'default_sale_price' => 80.00,
        ]);

        $this->balanceB = InventoryBalance::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'warehouse_id' => $this->warehouse->id,
            'part_id' => $this->partB->id,
            'qty_on_hand' => 5,
            'min_stock' => 10,
            'wac_cost' => 40.00,
            'sale_price' => 80.00,
            'min_sale_price' => 70.00,
            'is_active' => true,
        ]);

        // Part C: Out of Stock (qty 0)
        $this->partC = Part::create([
            'tenant_id' => $this->tenant->id,
            'sku' => 'SKU-CCC-03',
            'name_ar' => 'فحمات فرامل خلفية',
            'name_en' => 'Rear Brake Pads',
            'unit_id' => $unit->id,
            'category_id' => $otherCat->id,
            'min_qty' => 5,
            'default_sale_price' => 120.00,
        ]);

        $this->balanceC = InventoryBalance::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'warehouse_id' => $this->warehouse->id,
            'part_id' => $this->partC->id,
            'qty_on_hand' => 0,
            'min_stock' => 5,
            'wac_cost' => 60.00,
            'sale_price' => 120.00,
            'min_sale_price' => 100.00,
            'is_active' => true,
        ]);
    }

    public function test_unauthenticated_user_cannot_access_stock_page(): void
    {
        $response = $this->get(route('app.inventory.stock.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_user_without_permission_cannot_access_stock_page(): void
    {
        $unauthorizedUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);

        $response = $this->actingAs($unauthorizedUser)->get(route('app.inventory.stock.index'));
        $response->assertForbidden();
    }

    public function test_user_can_view_stock_index_page(): void
    {
        $response = $this->actingAs($this->user)->get(route('app.inventory.stock.index'));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Inventory/Stock/Index')
                ->has('balances.data', 3)
                ->has('warehouse')
                ->has('warehouses')
                ->has('categories')
                ->has('stats')
                ->has('filters')
            );
    }

    public function test_can_filter_stock_by_search(): void
    {
        // Search by SKU
        $response = $this->actingAs($this->user)->get(route('app.inventory.stock.index', [
            'search' => 'AAA',
        ]));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Inventory/Stock/Index')
                ->has('balances.data', 1)
                ->where('balances.data.0.part.sku', 'SKU-AAA-01')
            );

        // Search by Arabic Name
        $response = $this->actingAs($this->user)->get(route('app.inventory.stock.index', [
            'search' => 'فحمات',
        ]));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Inventory/Stock/Index')
                ->has('balances.data', 1)
                ->where('balances.data.0.part.sku', 'SKU-CCC-03')
            );
    }

    public function test_can_filter_stock_by_category(): void
    {
        $response = $this->actingAs($this->user)->get(route('app.inventory.stock.index', [
            'category' => $this->category->id,
        ]));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Inventory/Stock/Index')
                ->has('balances.data', 2)
            );
    }

    public function test_can_filter_stock_by_stock_status(): void
    {
        // in_stock
        $response = $this->actingAs($this->user)->get(route('app.inventory.stock.index', [
            'stock_status' => 'in_stock',
        ]));
        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Inventory/Stock/Index')
                ->has('balances.data', 2) // Part A (50) and Part B (5)
            );

        // low_stock
        $response = $this->actingAs($this->user)->get(route('app.inventory.stock.index', [
            'stock_status' => 'low_stock',
        ]));
        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Inventory/Stock/Index')
                ->has('balances.data', 2) // Part B (5 <= 10) and Part C (0 <= 5)
            );

        // out_of_stock
        $response = $this->actingAs($this->user)->get(route('app.inventory.stock.index', [
            'stock_status' => 'out_of_stock',
        ]));
        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Inventory/Stock/Index')
                ->has('balances.data', 1)
                ->where('balances.data.0.part.sku', 'SKU-CCC-03')
            );
    }

    public function test_stock_whitelist_sorting(): void
    {
        // Sort by SKU ascending
        $response = $this->actingAs($this->user)->get(route('app.inventory.stock.index', [
            'sort' => 'sku',
            'direction' => 'asc',
        ]));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Inventory/Stock/Index')
                ->where('balances.data.0.part.sku', 'SKU-AAA-01')
                ->where('balances.data.2.part.sku', 'SKU-CCC-03')
            );

        // Sort by qty_on_hand descending
        $response = $this->actingAs($this->user)->get(route('app.inventory.stock.index', [
            'sort' => 'qty_on_hand',
            'direction' => 'desc',
        ]));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Inventory/Stock/Index')
                ->where('balances.data.0.part.sku', 'SKU-AAA-01') // 50
                ->where('balances.data.2.part.sku', 'SKU-CCC-03') // 0
            );

        // Sort by wac_cost ascending
        $response = $this->actingAs($this->user)->get(route('app.inventory.stock.index', [
            'sort' => 'wac_cost',
            'direction' => 'asc',
        ]));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Inventory/Stock/Index')
                ->where('balances.data.0.part.sku', 'SKU-AAA-01') // 30.00
                ->where('balances.data.2.part.sku', 'SKU-CCC-03') // 60.00
            );
    }

    public function test_stock_stats_are_calculated_accurately(): void
    {
        $response = $this->actingAs($this->user)->get(route('app.inventory.stock.index'));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Inventory/Stock/Index')
                ->where('stats.total_items', 3)
                ->where('stats.in_stock', 2)
                ->where('stats.low_stock', 2)
                ->where('stats.total_value', fn ($val) => (float) $val === (50 * 30.00 + 5 * 40.00 + 0 * 60.00)) // 1700.0
            );
    }

    public function test_tenancy_isolation_prevents_viewing_other_tenant_balances(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherCenter = Center::factory()->create(['tenant_id' => $otherTenant->id]);
        $otherWarehouse = Warehouse::getOrCreateDefault($otherCenter->id);

        $otherUnit = InventoryUnit::create([
            'tenant_id' => $otherTenant->id,
            'name_ar' => 'قطعة',
            'code' => 'OT-PCS',
        ]);

        $otherPart = Part::create([
            'tenant_id' => $otherTenant->id,
            'sku' => 'SKU-OTHER-99',
            'name_ar' => 'قطعة مستأجر آخر',
            'unit_id' => $otherUnit->id,
        ]);

        InventoryBalance::create([
            'tenant_id' => $otherTenant->id,
            'center_id' => $otherCenter->id,
            'warehouse_id' => $otherWarehouse->id,
            'part_id' => $otherPart->id,
            'qty_on_hand' => 100,
            'min_stock' => 5,
            'wac_cost' => 10.00,
            'sale_price' => 20.00,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->get(route('app.inventory.stock.index'));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Inventory/Stock/Index')
                ->has('balances.data', 3)
                ->where('balances.data.0.part.sku', fn ($sku) => $sku !== 'SKU-OTHER-99')
                ->where('balances.data.1.part.sku', fn ($sku) => $sku !== 'SKU-OTHER-99')
                ->where('balances.data.2.part.sku', fn ($sku) => $sku !== 'SKU-OTHER-99')
            );
    }
}
