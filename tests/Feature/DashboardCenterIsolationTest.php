<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\App\DashboardController;
use App\Models\Center;
use App\Models\Customer;
use App\Models\InventoryBalance;
use App\Models\Invoice;
use App\Models\Part;
use App\Models\Payment;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Warehouse;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class DashboardCenterIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;

    private Tenant $tenantB;

    private Center $centerA1;

    private Center $centerA2;

    private Center $centerB1;

    private User $userA;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        $this->tenantA = Tenant::factory()->create(['status' => 'active']);
        $this->tenantB = Tenant::factory()->create(['status' => 'active']);

        $this->centerA1 = Center::factory()->create(['tenant_id' => $this->tenantA->id, 'is_active' => true, 'name' => 'Branch A1']);
        $this->centerA2 = Center::factory()->create(['tenant_id' => $this->tenantA->id, 'is_active' => true, 'name' => 'Branch A2']);
        $this->centerB1 = Center::factory()->create(['tenant_id' => $this->tenantB->id, 'is_active' => true, 'name' => 'Branch B1']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ([Permissions::INVOICES_VIEW, Permissions::INVENTORY_VIEW, Permissions::WORK_ORDERS_CREATE] as $permName) {
            Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
        }

        setPermissionsTeamId($this->tenantA->id);
        $superAdminRole = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web',
            'tenant_id' => $this->tenantA->id,
        ]);

        $this->userA = User::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'current_center_id' => $this->centerA1->id,
        ]);
        $this->userA->assignRole($superAdminRole);
        $this->userA->centers()->attach([
            $this->centerA1->id => ['tenant_id' => $this->tenantA->id],
            $this->centerA2->id => ['tenant_id' => $this->tenantA->id],
        ]);
    }

    private function seedCrossCenterAndCrossTenantData(): void
    {
        $custA1 = Customer::factory()->create(['tenant_id' => $this->tenantA->id, 'center_id' => $this->centerA1->id]);
        $custA2 = Customer::factory()->create(['tenant_id' => $this->tenantA->id, 'center_id' => $this->centerA2->id]);
        $vehA1 = Vehicle::factory()->create(['tenant_id' => $this->tenantA->id, 'center_id' => $this->centerA1->id, 'customer_id' => $custA1->id]);
        $vehA2 = Vehicle::factory()->create(['tenant_id' => $this->tenantA->id, 'center_id' => $this->centerA2->id, 'customer_id' => $custA2->id]);

        $supplierA = Supplier::forceCreate(['tenant_id' => $this->tenantA->id, 'name' => 'Supplier A']);

        $techA1 = User::factory()->create(['tenant_id' => $this->tenantA->id, 'current_center_id' => $this->centerA1->id, 'name' => 'Tech A1']);
        $techA2 = User::factory()->create(['tenant_id' => $this->tenantA->id, 'current_center_id' => $this->centerA2->id, 'name' => 'Tech A2']);

        // Center A1 data
        $woA1 = WorkOrder::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
            'customer_id' => $custA1->id,
            'vehicle_id' => $vehA1->id,
            'status' => 'in_progress',
        ]);
        $itemA1 = WorkOrderItem::forceCreate([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
            'work_order_id' => $woA1->id,
            'title' => 'A1 Service',
            'qty' => 1,
            'unit_price' => 100,
            'total' => 100,
            'status' => 'in_progress',
        ]);
        DB::table('work_order_item_technician')->insert([
            'work_order_item_id' => $itemA1->id,
            'user_id' => $techA1->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $invA1 = Invoice::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
            'customer_id' => $custA1->id,
            'work_order_id' => $woA1->id,
            'type' => 'invoice',
            'status' => 'issued',
            'issue_date' => now(),
            'total_incl_tax' => 150,
        ]);
        Payment::forceCreate([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
            'invoice_id' => $invA1->id,
            'amount' => 150,
            'type' => 'payment',
            'payment_method' => 'cash',
            'payment_date' => now(),
        ]);
        PurchaseInvoice::forceCreate([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
            'supplier_id' => $supplierA->id,
            'code' => 'PI-A1',
            'invoice_number' => 'PI-A1',
            'issue_date' => now()->toDateString(),
            'subtotal' => 40,
            'tax_amount' => 0,
            'total' => 40,
            'status' => 'posted',
        ]);

        // Center A2 data
        $woA2 = WorkOrder::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA2->id,
            'customer_id' => $custA2->id,
            'vehicle_id' => $vehA2->id,
            'status' => 'in_progress',
        ]);
        $itemA2 = WorkOrderItem::forceCreate([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA2->id,
            'work_order_id' => $woA2->id,
            'title' => 'A2 Service',
            'qty' => 1,
            'unit_price' => 300,
            'total' => 300,
            'status' => 'in_progress',
        ]);
        DB::table('work_order_item_technician')->insert([
            'work_order_item_id' => $itemA2->id,
            'user_id' => $techA2->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $invA2 = Invoice::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA2->id,
            'customer_id' => $custA2->id,
            'work_order_id' => $woA2->id,
            'type' => 'invoice',
            'status' => 'issued',
            'issue_date' => now(),
            'total_incl_tax' => 300,
        ]);
        Payment::forceCreate([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA2->id,
            'invoice_id' => $invA2->id,
            'amount' => 300,
            'type' => 'payment',
            'payment_method' => 'card',
            'payment_date' => now(),
        ]);
        PurchaseInvoice::forceCreate([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA2->id,
            'supplier_id' => $supplierA->id,
            'code' => 'PI-A2',
            'invoice_number' => 'PI-A2',
            'issue_date' => now()->toDateString(),
            'subtotal' => 90,
            'tax_amount' => 0,
            'total' => 90,
            'status' => 'posted',
        ]);

        // Inventory: Part 1 has stock in A1 (10, min 5 -> healthy), 0 in A2 (outOfStock + lowStock)
        $whA1 = Warehouse::forceCreate([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
            'name' => 'WH A1',
            'code' => 'WH-A1',
            'is_default' => true,
            'is_active' => true,
        ]);
        $whA2 = Warehouse::forceCreate([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA2->id,
            'name' => 'WH A2',
            'code' => 'WH-A2',
            'is_default' => true,
            'is_active' => true,
        ]);
        $part1 = Part::forceCreate([
            'tenant_id' => $this->tenantA->id,
            'sku' => 'PART-1',
            'name_ar' => 'فلتر',
            'name_en' => 'Filter',
            'min_qty' => 5,
        ]);
        InventoryBalance::forceCreate([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
            'warehouse_id' => $whA1->id,
            'part_id' => $part1->id,
            'qty_on_hand' => 10,
            'min_stock' => 5,
        ]);
        InventoryBalance::forceCreate([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA2->id,
            'warehouse_id' => $whA2->id,
            'part_id' => $part1->id,
            'qty_on_hand' => 0,
            'min_stock' => 5,
        ]);

        // Tenant B data (must never appear for Tenant A)
        $custB = Customer::factory()->create(['tenant_id' => $this->tenantB->id, 'center_id' => $this->centerB1->id]);
        $vehB = Vehicle::factory()->create(['tenant_id' => $this->tenantB->id, 'center_id' => $this->centerB1->id, 'customer_id' => $custB->id]);
        $supplierB = Supplier::forceCreate(['tenant_id' => $this->tenantB->id, 'name' => 'Supplier B']);
        $woB1 = WorkOrder::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'center_id' => $this->centerB1->id,
            'customer_id' => $custB->id,
            'vehicle_id' => $vehB->id,
            'status' => 'in_progress',
        ]);
        $invB1 = Invoice::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'center_id' => $this->centerB1->id,
            'customer_id' => $custB->id,
            'work_order_id' => $woB1->id,
            'type' => 'invoice',
            'status' => 'issued',
            'issue_date' => now(),
            'total_incl_tax' => 999,
        ]);
        Payment::forceCreate([
            'tenant_id' => $this->tenantB->id,
            'center_id' => $this->centerB1->id,
            'invoice_id' => $invB1->id,
            'amount' => 999,
            'type' => 'payment',
            'payment_method' => 'cash',
            'payment_date' => now(),
        ]);
        PurchaseInvoice::forceCreate([
            'tenant_id' => $this->tenantB->id,
            'center_id' => $this->centerB1->id,
            'supplier_id' => $supplierB->id,
            'code' => 'PI-B1',
            'invoice_number' => 'PI-B1',
            'issue_date' => now()->toDateString(),
            'subtotal' => 777,
            'tax_amount' => 0,
            'total' => 777,
            'status' => 'posted',
        ]);
    }

    public function test_dashboard_isolates_center_a1_from_center_a2_and_tenant_b(): void
    {
        $this->seedCrossCenterAndCrossTenantData();

        $response = $this->actingAs($this->userA)->get('/dashboard?period=30d');
        $response->assertOk();

        $props = $response->viewData('page')['props'];

        // PurchaseInvoice expenses: only A1 (40), not A1+A2 (130), not B (777)
        $this->assertEquals(40.0, $props['stats']['revenue']['periodExpenses']);

        // Payment today: only A1 (150), not A1+A2 (450), not B (999)
        $this->assertEquals(150.0, $props['stats']['revenue']['paymentsToday']);

        // Payment methods chart: only cash=150 from A1, no card=300 from A2
        $methods = collect($props['charts']['paymentMethods'])->pluck('total', 'method')->all();
        $this->assertSame(['cash' => 150.0], $methods);

        // Inventory balances: in A1, part1 has qty=10 > min_stock=5 => outOfStock=0, lowStock=0
        $this->assertSame(0, $props['stats']['inventory']['outOfStock']);
        $this->assertSame(0, $props['alerts']['lowStock']);

        // Working technicians now: only Tech A1
        $workingNames = collect($props['workingTechniciansNow'])->pluck('technician_name')->all();
        $this->assertSame(['Tech A1'], $workingNames);

        // Technician performance: only Tech A1
        $perfNames = collect($props['technicianPerformance'])->pluck('name')->all();
        $this->assertSame(['Tech A1'], $perfNames);
    }

    public function test_dashboard_switches_cleanly_between_center_a1_and_center_a2(): void
    {
        $this->seedCrossCenterAndCrossTenantData();

        // First on A1
        $respA1 = $this->actingAs($this->userA)->get('/dashboard?period=30d');
        $respA1->assertOk();
        $propsA1 = $respA1->viewData('page')['props'];
        $this->assertEquals(40.0, $propsA1['stats']['revenue']['periodExpenses']);
        $this->assertEquals(150.0, $propsA1['stats']['revenue']['paymentsToday']);
        $this->assertSame(0, $propsA1['stats']['inventory']['outOfStock']);

        // Switch to A2
        $this->userA->forceFill(['current_center_id' => $this->centerA2->id])->save();

        $respA2 = $this->actingAs($this->userA->fresh())->get('/dashboard?period=30d');
        $respA2->assertOk();
        $propsA2 = $respA2->viewData('page')['props'];

        $this->assertEquals(90.0, $propsA2['stats']['revenue']['periodExpenses']);
        $this->assertEquals(300.0, $propsA2['stats']['revenue']['paymentsToday']);
        $methodsA2 = collect($propsA2['charts']['paymentMethods'])->pluck('total', 'method')->all();
        $this->assertSame(['card' => 300.0], $methodsA2);
        $this->assertSame(1, $propsA2['stats']['inventory']['outOfStock']);
        $this->assertSame(1, $propsA2['alerts']['lowStock']);
        $this->assertSame(['Tech A2'], collect($propsA2['workingTechniciansNow'])->pluck('technician_name')->all());
        $this->assertSame(['Tech A2'], collect($propsA2['technicianPerformance'])->pluck('name')->all());

        // Switch back to A1
        $this->userA->forceFill(['current_center_id' => $this->centerA1->id])->save();

        $respA1Again = $this->actingAs($this->userA->fresh())->get('/dashboard?period=30d');
        $respA1Again->assertOk();
        $propsA1Again = $respA1Again->viewData('page')['props'];

        $this->assertEquals(40.0, $propsA1Again['stats']['revenue']['periodExpenses']);
        $this->assertEquals(150.0, $propsA1Again['stats']['revenue']['paymentsToday']);
        $this->assertSame(['Tech A1'], collect($propsA1Again['workingTechniciansNow'])->pluck('technician_name')->all());
    }

    public function test_dashboard_cache_is_isolated_per_center_and_per_user_role(): void
    {
        $this->seedCrossCenterAndCrossTenantData();

        setPermissionsTeamId($this->tenantA->id);
        $techRole = Role::firstOrCreate([
            'name' => 'technician',
            'guard_name' => 'web',
            'tenant_id' => $this->tenantA->id,
        ]);
        $techUser = User::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'current_center_id' => $this->centerA1->id,
        ]);
        $techUser->assignRole($techRole);
        $techUser->centers()->attach([$this->centerA1->id => ['tenant_id' => $this->tenantA->id]]);

        $originalEnv = app()['env'];
        app()['env'] = 'local';

        try {
            // 1. Prime cache on Center A1 for super_admin
            $respA1 = $this->actingAs($this->userA)->get('/dashboard?period=30d');
            $respA1->assertOk();
            $this->assertEquals(40.0, $respA1->viewData('page')['props']['stats']['revenue']['periodExpenses']);

            // 2. Switch to Center A2 — must NOT read A1 cache!
            $this->userA->forceFill(['current_center_id' => $this->centerA2->id])->save();
            $respA2 = $this->actingAs($this->userA->fresh())->get('/dashboard?period=30d');
            $respA2->assertOk();
            $this->assertEquals(90.0, $respA2->viewData('page')['props']['stats']['revenue']['periodExpenses']);

            // 3. Switch back to Center A1 — must return A1 cached data (40.0), not A2 (90.0)
            $this->userA->forceFill(['current_center_id' => $this->centerA1->id])->save();
            $respA1Back = $this->actingAs($this->userA->fresh())->get('/dashboard?period=30d');
            $respA1Back->assertOk();
            $this->assertEquals(40.0, $respA1Back->viewData('page')['props']['stats']['revenue']['periodExpenses']);

            // 4. Different user/role in same Center A1 must NOT see super_admin's cached financials or roleName
            $respTech = $this->actingAs($techUser)->get('/dashboard?period=30d');
            $respTech->assertOk();
            $techProps = $respTech->viewData('page')['props'];
            $this->assertNull($techProps['stats']['revenue']);
            $this->assertSame('technician', $techProps['userContext']['roleName']);
        } finally {
            app()['env'] = $originalEnv;
        }
    }

    public function test_dashboard_rejects_missing_active_center(): void
    {
        $userNoCenter = User::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'current_center_id' => null,
        ]);

        // Via route (EnsureCenterContext)
        $this->actingAs($userNoCenter)->get('/dashboard')->assertForbidden();

        // Direct controller invocation (defense-in-depth)
        $this->actingAs($userNoCenter);
        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(fn () => $userNoCenter);

        $this->expectException(HttpException::class);
        app(DashboardController::class)->index($request);
    }
}
