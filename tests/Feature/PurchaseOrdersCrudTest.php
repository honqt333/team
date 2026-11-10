<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PurchaseOrdersCrudTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Center $center;

    protected User $user;

    protected Warehouse $warehouse;

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
        $this->user->givePermissionTo('purchasing.pos.view');
        $this->user->givePermissionTo('purchasing.pos.create');
        $this->user->refresh();

        $this->warehouse = Warehouse::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name' => 'Main Warehouse',
            'is_active' => true,
        ]);
    }

    public function test_unauthorized_user_cannot_view_orders(): void
    {
        $unauthorizedUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);
        $unauthorizedUser->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($unauthorizedUser)
            ->get(route('app.purchasing.orders.index'));

        $response->assertForbidden();
    }

    public function test_authorized_user_can_list_purchase_orders(): void
    {
        $supplier = Supplier::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name' => 'Al-Amal Auto Parts',
            'is_active' => true,
        ]);

        PurchaseOrder::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'supplier_id' => $supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'code' => 'PO-0001',
            'status' => PurchaseOrder::STATUS_DRAFT,
            'order_date' => now()->toDateString(),
            'total' => 1500.00,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('app.purchasing.orders.index'));

        $response->assertOk();
        $orders = $response->inertiaPage()['props']['orders']['data'];
        $this->assertCount(1, $orders);
        $this->assertEquals('PO-0001', $orders[0]['code']);
        $this->assertEquals('Al-Amal Auto Parts', $orders[0]['supplier']['name']);
    }

    public function test_orders_can_be_filtered_by_search_and_status(): void
    {
        $supplierA = Supplier::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name' => 'Supplier Alpha',
            'is_active' => true,
        ]);

        $supplierB = Supplier::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name' => 'Supplier Beta',
            'is_active' => true,
        ]);

        PurchaseOrder::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'supplier_id' => $supplierA->id,
            'warehouse_id' => $this->warehouse->id,
            'code' => 'PO-ALPHA-01',
            'status' => PurchaseOrder::STATUS_DRAFT,
            'order_date' => now()->toDateString(),
            'total' => 200.00,
        ]);

        PurchaseOrder::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'supplier_id' => $supplierB->id,
            'warehouse_id' => $this->warehouse->id,
            'code' => 'PO-BETA-02',
            'status' => PurchaseOrder::STATUS_RECEIVED,
            'order_date' => now()->toDateString(),
            'total' => 500.00,
        ]);

        // Search by code
        $respSearch = $this->actingAs($this->user)
            ->get(route('app.purchasing.orders.index', ['search' => 'ALPHA']));
        $respSearch->assertOk();
        $this->assertCount(1, $respSearch->inertiaPage()['props']['orders']['data']);
        $this->assertEquals('PO-ALPHA-01', $respSearch->inertiaPage()['props']['orders']['data'][0]['code']);

        // Search by supplier name
        $respSupplierSearch = $this->actingAs($this->user)
            ->get(route('app.purchasing.orders.index', ['search' => 'Beta']));
        $respSupplierSearch->assertOk();
        $this->assertCount(1, $respSupplierSearch->inertiaPage()['props']['orders']['data']);
        $this->assertEquals('PO-BETA-02', $respSupplierSearch->inertiaPage()['props']['orders']['data'][0]['code']);

        // Filter by supplier_id
        $respSupplierId = $this->actingAs($this->user)
            ->get(route('app.purchasing.orders.index', ['supplier_id' => $supplierA->id]));
        $respSupplierId->assertOk();
        $this->assertCount(1, $respSupplierId->inertiaPage()['props']['orders']['data']);
        $this->assertEquals('PO-ALPHA-01', $respSupplierId->inertiaPage()['props']['orders']['data'][0]['code']);
    }

    public function test_orders_include_items_count_and_scope_by_center(): void
    {
        $supplier = Supplier::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name' => 'Supplier Gamma',
            'is_active' => true,
        ]);

        $order = PurchaseOrder::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'supplier_id' => $supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'code' => 'PO-GAMMA-01',
            'status' => PurchaseOrder::STATUS_DRAFT,
            'order_date' => now()->toDateString(),
            'total' => 300.00,
        ]);

        // Another center order
        $otherCenter = Center::factory()->create(['tenant_id' => $this->tenant->id]);
        $otherWarehouse = Warehouse::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $otherCenter->id,
            'name' => 'Other Warehouse',
            'is_active' => true,
        ]);
        $otherSupplier = Supplier::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $otherCenter->id,
            'name' => 'Other Center Supplier',
            'is_active' => true,
        ]);

        PurchaseOrder::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $otherCenter->id,
            'supplier_id' => $otherSupplier->id,
            'warehouse_id' => $otherWarehouse->id,
            'code' => 'PO-OTHER-99',
            'status' => PurchaseOrder::STATUS_DRAFT,
            'order_date' => now()->toDateString(),
            'total' => 999.00,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('app.purchasing.orders.index'));

        $response->assertOk();
        $orders = $response->inertiaPage()['props']['orders']['data'];
        $this->assertCount(1, $orders);
        $this->assertEquals('PO-GAMMA-01', $orders[0]['code']);
        $this->assertArrayHasKey('items_count', $orders[0]);
    }

    public function test_orders_sort_by_columns_asc_and_desc(): void
    {
        $supplierA = Supplier::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name' => 'AAA Supplies',
            'is_active' => true,
        ]);

        $supplierB = Supplier::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name' => 'ZZZ Supplies',
            'is_active' => true,
        ]);

        PurchaseOrder::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'supplier_id' => $supplierA->id,
            'warehouse_id' => $this->warehouse->id,
            'code' => 'PO-100',
            'status' => PurchaseOrder::STATUS_DRAFT,
            'order_date' => '2026-01-01',
            'total' => 100.00,
        ]);

        PurchaseOrder::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'supplier_id' => $supplierB->id,
            'warehouse_id' => $this->warehouse->id,
            'code' => 'PO-900',
            'status' => PurchaseOrder::STATUS_SENT,
            'order_date' => '2026-05-01',
            'total' => 900.00,
        ]);

        // Sort total asc
        $respTotalAsc = $this->actingAs($this->user)
            ->get(route('app.purchasing.orders.index', ['sort' => 'total', 'direction' => 'asc']));
        $respTotalAsc->assertOk();
        $ordersAsc = $respTotalAsc->inertiaPage()['props']['orders']['data'];
        $this->assertEquals('PO-100', $ordersAsc[0]['code']);
        $this->assertEquals('PO-900', $ordersAsc[1]['code']);

        // Sort total desc
        $respTotalDesc = $this->actingAs($this->user)
            ->get(route('app.purchasing.orders.index', ['sort' => 'total', 'direction' => 'desc']));
        $respTotalDesc->assertOk();
        $ordersDesc = $respTotalDesc->inertiaPage()['props']['orders']['data'];
        $this->assertEquals('PO-900', $ordersDesc[0]['code']);
        $this->assertEquals('PO-100', $ordersDesc[1]['code']);

        // Sort supplier asc
        $respSupplierAsc = $this->actingAs($this->user)
            ->get(route('app.purchasing.orders.index', ['sort' => 'supplier', 'direction' => 'asc']));
        $respSupplierAsc->assertOk();
        $ordersSuppAsc = $respSupplierAsc->inertiaPage()['props']['orders']['data'];
        $this->assertEquals('AAA Supplies', $ordersSuppAsc[0]['supplier']['name']);
        $this->assertEquals('ZZZ Supplies', $ordersSuppAsc[1]['supplier']['name']);
    }
}
