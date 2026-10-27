<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\InventoryMove;
use App\Models\InventoryUnit;
use App\Models\Part;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PartsCrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Part $part;

    protected InventoryUnit $baseUnit;

    protected InventoryUnit $purchaseUnit;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);
        $this->user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
        ]);

        $this->user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        // Reset permission cache and create permission
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        Permission::findOrCreate('inventory.moves.create', 'web');
        Permission::findOrCreate('inventory.view', 'web');

        // Set the team id for permissions based on the user's tenant
        app()[PermissionRegistrar::class]->setPermissionsTeamId($tenant->id);

        $this->user->givePermissionTo('inventory.moves.create');
        $this->user->givePermissionTo('inventory.view');
        $this->user->refresh();

        $this->baseUnit = InventoryUnit::create([
            'tenant_id' => $tenant->id,
            'name_ar' => 'حبة',
            'name_en' => 'Piece',
            'is_active' => true,
        ]);

        $this->purchaseUnit = InventoryUnit::create([
            'tenant_id' => $tenant->id,
            'name_ar' => 'كرتون',
            'name_en' => 'Carton',
            'is_active' => true,
        ]);

        $this->part = Part::create([
            'tenant_id' => $tenant->id,
            'sku' => 'TEST-001',
            'name_ar' => 'مستشعر فلتر',
            'name_en' => 'Filter Sensor',
            'unit_id' => $this->baseUnit->id,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_can_update_part_purchase_unit_and_conversion_factor()
    {
        $this->actingAs($this->user);

        $response = $this->put(route('app.inventory.parts.update', $this->part->id), [
            'sku' => 'TEST-001',
            'name_ar' => 'مستشعر فلتر معدل',
            'name_en' => 'Filter Sensor Edited',
            'unit_id' => $this->baseUnit->id,
            'purchase_unit_id' => $this->purchaseUnit->id,
            'purchase_conversion_factor' => 24.5,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('app.inventory.parts.index'));

        $this->part->refresh();
        $this->assertEquals($this->purchaseUnit->id, $this->part->purchase_unit_id);
        $this->assertEquals(24.5, (float) $this->part->purchase_conversion_factor);
    }

    /** @test */
    public function it_handles_null_purchase_conversion_factor_by_defaulting_to_one()
    {
        $this->actingAs($this->user);

        $response = $this->put(route('app.inventory.parts.update', $this->part->id), [
            'sku' => 'TEST-001',
            'name_ar' => 'مستشعر فلتر معدل',
            'name_en' => 'Filter Sensor Edited',
            'unit_id' => $this->baseUnit->id,
            'purchase_unit_id' => $this->purchaseUnit->id,
            'purchase_conversion_factor' => null,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('app.inventory.parts.index'));

        $this->part->refresh();
        $this->assertEquals($this->purchaseUnit->id, $this->part->purchase_unit_id);
        $this->assertEquals(1.0, (float) $this->part->purchase_conversion_factor);
    }

    /** @test */
    public function it_updates_part_and_converts_stock_qty_on_direct_purchase_invoice()
    {
        Permission::findOrCreate('purchasing.invoices.create', 'web');
        $this->user->givePermissionTo('purchasing.invoices.create');
        $this->user->refresh();

        $this->actingAs($this->user);

        $supplier = Supplier::create([
            'tenant_id' => $this->user->tenant_id,
            'name' => 'Test Supplier',
            'is_active' => true,
        ]);

        $warehouse = Warehouse::create([
            'center_id' => $this->user->current_center_id,
            'name' => 'Main Warehouse',
            'is_active' => true,
        ]);

        $response = $this->post(route('app.invoices.purchases.store'), [
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'invoice_number' => 'INV-2026-001',
            'issue_date' => now()->format('Y-m-d'),
            'create_credit_invoice' => false,
            'items' => [
                [
                    'part_id' => $this->part->id,
                    'qty' => 2.0, // Buying 2 Cartons
                    'unit_cost' => 100.0,
                    'tax_rate' => 15.0,
                    'purchase_unit_id' => $this->purchaseUnit->id,
                    'purchase_conversion_factor' => 24.0, // 24 pieces per Carton
                ],
            ],
        ]);

        $response->assertRedirect();

        // 1. Verify part's purchase settings were updated
        $this->part->refresh();
        $this->assertEquals($this->purchaseUnit->id, $this->part->purchase_unit_id);
        $this->assertEquals(24.0, (float) $this->part->purchase_conversion_factor);

        // 2. Verify stock inventory moves were posted with converted qty: 2 * 24 = 48
        $move = InventoryMove::where('part_id', $this->part->id)->first();
        $this->assertNotNull($move);
        $this->assertEquals(48.0, (float) $move->qty);
    }

    /** @test */
    public function it_can_sync_warehouse_balances_when_warehouse_id_is_string()
    {
        Permission::findOrCreate('inventory.parts.create', 'web');
        $this->user->givePermissionTo('inventory.parts.create');
        $this->user->refresh();

        $this->actingAs($this->user);

        $warehouse = Warehouse::create([
            'center_id' => $this->user->current_center_id,
            'name' => 'Store Warehouse',
            'is_active' => true,
        ]);

        $response = $this->post(route('app.inventory.parts.store'), [
            'sku' => 'TEST-WH-001',
            'name_ar' => 'قطعة اختبار',
            'name_en' => 'Test Part',
            'unit_id' => $this->baseUnit->id,
            'warehouse_data' => [
                [
                    'warehouse_id' => (string) $warehouse->id, // string representation from form/request
                    'initial_stock' => 10,
                    'cost_price' => 50,
                    'sale_price' => 75,
                ],
            ],
        ]);

        $response->assertRedirect(route('app.inventory.parts.index'));

        $part = Part::where('sku', 'TEST-WH-001')->first();
        $this->assertNotNull($part);

        $balance = $part->inventoryBalances()->where('warehouse_id', $warehouse->id)->first();
        $this->assertNotNull($balance);
        $this->assertEquals(10.0, (float) $balance->qty_on_hand);
        $this->assertEquals(50.0, (float) $balance->wac_cost);
    }

    /** @test */
    public function it_can_search_parts_with_hide_out_of_stock_and_warehouse()
    {
        $this->actingAs($this->user);

        $warehouse = Warehouse::create([
            'center_id' => $this->user->current_center_id,
            'name' => 'Main Warehouse',
            'is_active' => true,
        ]);

        // Part 1 with positive stock
        $inStockPart = Part::create([
            'tenant_id' => $this->user->tenant_id,
            'sku' => 'IN-STOCK-1',
            'barcode' => 'BAR12345',
            'name_ar' => 'قطعة متوفرة',
            'name_en' => 'In Stock Part',
            'unit_id' => $this->baseUnit->id,
            'is_active' => true,
        ]);
        $inStockPart->inventoryBalances()->create([
            'tenant_id' => $this->user->tenant_id,
            'center_id' => $this->user->current_center_id,
            'warehouse_id' => $warehouse->id,
            'qty_on_hand' => 15,
            'sale_price' => 100,
        ]);

        // Part 2 with zero stock
        $outOfStockPart = Part::create([
            'tenant_id' => $this->user->tenant_id,
            'sku' => 'OUT-STOCK-2',
            'barcode' => 'BAR99999',
            'name_ar' => 'قطعة منتهية',
            'name_en' => 'Out of Stock Part',
            'unit_id' => $this->baseUnit->id,
            'is_active' => true,
        ]);
        $outOfStockPart->inventoryBalances()->create([
            'tenant_id' => $this->user->tenant_id,
            'center_id' => $this->user->current_center_id,
            'warehouse_id' => $warehouse->id,
            'qty_on_hand' => 0,
            'sale_price' => 50,
        ]);

        // 1. Search with hide_out_of_stock=true without crashing SQL (the bug)
        $response = $this->getJson(route('app.inventory.parts.search', [
            'q' => '',
            'hide_out_of_stock' => 'true',
            'warehouse_id' => $warehouse->id,
        ]));

        $response->assertOk();
        $data = $response->json();
        $ids = collect($data)->pluck('id')->toArray();
        $this->assertContains($inStockPart->id, $ids);
        $this->assertNotContains($outOfStockPart->id, $ids);

        // 2. Search by barcode
        $barcodeResponse = $this->getJson(route('app.inventory.parts.search', [
            'q' => 'BAR12345',
        ]));
        $barcodeResponse->assertOk();
        $this->assertCount(1, $barcodeResponse->json());
        $this->assertEquals($inStockPart->id, $barcodeResponse->json()[0]['id']);
    }
}
