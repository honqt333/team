<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Customer;
use App\Models\InventoryMove;
use App\Models\Invoice;
use App\Models\Part;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\PerformanceBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockActivitiesReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/app/reports/stock-activities');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_stock_activities_report(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $response = $this->actingAs($user)->get('/app/reports/stock-activities');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/StockActivities')
            ->has('filters')
            ->has('kpis')
            ->has('moves')
            ->has('centers')
            ->has('warehouses')
            ->has('categories')
            ->has('moveTypes')
        );
    }

    public function test_stock_activities_report_calculates_kpis_accurately(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $center = Center::where('tenant_id', $tenant->id)->first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $warehouse = Warehouse::firstOrCreate(
            ['tenant_id' => $tenant->id, 'center_id' => $center->id, 'code' => 'WH-TEST'],
            ['name' => 'مستودع اختبار']
        );

        $part = Part::create([
            'tenant_id' => $tenant->id,
            'sku' => 'SKU-TEST-100',
            'name_ar' => 'فلتر زيت تويوتا أصلي',
            'is_active' => true,
        ]);

        InventoryMove::query()->forceDelete();

        // 1. Receipt: +20 units @ 50 SAR = 1000 SAR cost
        InventoryMove::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'warehouse_id' => $warehouse->id,
            'part_id' => $part->id,
            'move_type' => InventoryMove::TYPE_RECEIPT,
            'qty' => 20.000,
            'unit_cost' => 50.00,
            'total_cost' => 1000.00,
            'balance_after' => 20.000,
            'posted_at' => now(),
            'posted_by' => $user->id,
        ]);

        // 2. Issue: -5 units @ 50 SAR = 250 SAR cost
        InventoryMove::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'warehouse_id' => $warehouse->id,
            'part_id' => $part->id,
            'move_type' => InventoryMove::TYPE_ISSUE_TO_WORKORDER,
            'qty' => -5.000,
            'unit_cost' => 50.00,
            'total_cost' => 250.00,
            'balance_after' => 15.000,
            'posted_at' => now(),
            'posted_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get('/app/reports/stock-activities?range=all&center_id='.$center->id);

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('kpis.total_records', 2)
            ->where('kpis.total_incoming_qty', fn ($v) => (float) $v === 20.0)
            ->where('kpis.total_outgoing_qty', fn ($v) => (float) $v === 5.0)
            ->where('kpis.net_qty', fn ($v) => (float) $v === 15.0)
            ->where('kpis.total_cost', fn ($v) => (float) $v === 1250.0)
        );
    }

    public function test_stock_activities_report_filters_by_move_type(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $center = Center::where('tenant_id', $tenant->id)->first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $warehouse = Warehouse::firstOrCreate(
            ['tenant_id' => $tenant->id, 'center_id' => $center->id, 'code' => 'WH-TEST'],
            ['name' => 'مستودع اختبار']
        );

        $part = Part::create([
            'tenant_id' => $tenant->id,
            'sku' => 'SKU-MOVE-FILTER',
            'name_ar' => 'قطعة اختبار فلترة',
            'is_active' => true,
        ]);

        InventoryMove::query()->forceDelete();

        InventoryMove::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'warehouse_id' => $warehouse->id,
            'part_id' => $part->id,
            'move_type' => InventoryMove::TYPE_RECEIPT,
            'qty' => 10.000,
            'unit_cost' => 100.00,
            'total_cost' => 1000.00,
            'balance_after' => 10.000,
            'posted_at' => now(),
        ]);

        InventoryMove::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'warehouse_id' => $warehouse->id,
            'part_id' => $part->id,
            'move_type' => InventoryMove::TYPE_SALE,
            'qty' => -2.000,
            'unit_cost' => 100.00,
            'total_cost' => 200.00,
            'balance_after' => 8.000,
            'posted_at' => now(),
        ]);

        $resReceipt = $this->actingAs($user)->get('/app/reports/stock-activities?range=all&move_type='.InventoryMove::TYPE_RECEIPT.'&center_id='.$center->id);
        $resReceipt->assertInertia(fn ($page) => $page
            ->where('kpis.total_records', 1)
            ->where('moves.data.0.move_type', InventoryMove::TYPE_RECEIPT)
        );

        $resSale = $this->actingAs($user)->get('/app/reports/stock-activities?range=all&move_type='.InventoryMove::TYPE_SALE.'&center_id='.$center->id);
        $resSale->assertInertia(fn ($page) => $page
            ->where('kpis.total_records', 1)
            ->where('moves.data.0.move_type', InventoryMove::TYPE_SALE)
        );
    }

    public function test_stock_activities_report_groups_data(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $center = Center::where('tenant_id', $tenant->id)->first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $warehouse = Warehouse::firstOrCreate(
            ['tenant_id' => $tenant->id, 'center_id' => $center->id, 'code' => 'WH-TEST'],
            ['name' => 'مستودع اختبار']
        );

        $partA = Part::create([
            'tenant_id' => $tenant->id,
            'sku' => 'SKU-GRP-A',
            'name_ar' => 'قطعة أ',
            'is_active' => true,
        ]);

        $partB = Part::create([
            'tenant_id' => $tenant->id,
            'sku' => 'SKU-GRP-B',
            'name_ar' => 'قطعة ب',
            'is_active' => true,
        ]);

        InventoryMove::query()->forceDelete();

        InventoryMove::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'warehouse_id' => $warehouse->id,
            'part_id' => $partA->id,
            'move_type' => InventoryMove::TYPE_RECEIPT,
            'qty' => 5.000,
            'unit_cost' => 50.00,
            'total_cost' => 250.00,
            'balance_after' => 5.000,
            'posted_at' => now(),
        ]);

        InventoryMove::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'warehouse_id' => $warehouse->id,
            'part_id' => $partB->id,
            'move_type' => InventoryMove::TYPE_RECEIPT,
            'qty' => 8.000,
            'unit_cost' => 30.00,
            'total_cost' => 240.00,
            'balance_after' => 8.000,
            'posted_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/app/reports/stock-activities?range=all&group_by=part&center_id='.$center->id);

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('moves', null)
            ->has('groupedMoves', 2)
        );
    }

    public function test_stock_activities_report_handles_credit_note_polymorphic_reference_cleanly(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $center = Center::where('tenant_id', $tenant->id)->first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $warehouse = Warehouse::firstOrCreate(
            ['tenant_id' => $tenant->id, 'center_id' => $center->id, 'code' => 'WH-TEST'],
            ['name' => 'مستودع اختبار']
        );

        $part = Part::create([
            'tenant_id' => $tenant->id,
            'sku' => 'SKU-CN-TEST',
            'name_ar' => 'قطعة مرتجعة',
            'is_active' => true,
        ]);

        $creditNote = Invoice::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => Customer::first()->id,
            'invoice_number' => 'CN-TEST-001',
            'type' => 'credit_note',
            'subtype' => 'simplified',
            'status' => 'valid',
            'payment_status' => 'paid',
            'issue_date' => now(),
            'supply_date' => now(),
            'total_excl_tax' => 100.00,
            'total_tax' => 15.00,
            'total_incl_tax' => 115.00,
            'total_paid' => 115.00,
        ]);

        InventoryMove::query()->forceDelete();

        InventoryMove::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'warehouse_id' => $warehouse->id,
            'part_id' => $part->id,
            'move_type' => InventoryMove::TYPE_RECEIPT,
            'qty' => 1.000,
            'unit_cost' => 100.00,
            'total_cost' => 100.00,
            'balance_after' => 1.000,
            'reference_type' => 'credit_note',
            'reference_id' => $creditNote->id,
            'posted_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/app/reports/stock-activities?range=all&center_id='.$center->id);

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('moves.data.0.reference_label', 'إشعار دائن')
            ->where('moves.data.0.reference_code', 'CN-TEST-001')
        );
    }

    public function test_stock_activities_report_exports_csv_successfully(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $response = $this->actingAs($user)->get('/app/reports/stock-activities/export?range=all');

        $response->assertStatus(200);
        $this->assertTrue($response->headers->contains('content-type', 'text/csv; charset=UTF-8'));
        $this->assertStringContainsString('attachment;', (string) $response->headers->get('content-disposition'));
    }
}
