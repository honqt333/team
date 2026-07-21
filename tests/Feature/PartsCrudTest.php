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

        // Set the team id for permissions based on the user's tenant
        app()[PermissionRegistrar::class]->setPermissionsTeamId($tenant->id);

        $this->user->givePermissionTo('inventory.moves.create');
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
}
