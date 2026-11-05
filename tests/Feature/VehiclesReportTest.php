<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleMake;
use App\Models\VehicleModel;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Models\WorkOrderItemPart;
use Database\Seeders\PerformanceBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehiclesReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/app/reports/vehicles');
        $response->assertRedirect('/login');
    }

    public function test_wrshah_redirect_to_vehicles_report(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $response = $this->actingAs($user)->get('/reports/vehicles');
        $response->assertRedirect(route('app.reports.vehicles'));
    }

    public function test_authenticated_user_can_view_vehicles_report(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $response = $this->actingAs($user)->get('/app/reports/vehicles');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Vehicles')
            ->has('items')
            ->has('filters')
            ->has('kpis')
            ->has('lookups.centers')
            ->has('lookups.makes')
            ->has('lookups.models')
            ->has('lookups.service_statuses')
            ->has('lookups.groups')
        );
    }

    public function test_vehicles_report_calculates_kpis_accurately(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $center = Center::where('tenant_id', $tenant->id)->first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name' => 'فهد العتيبي',
            'phone' => '0501112233',
        ]);

        $make = VehicleMake::firstOrCreate(['name_ar' => 'تويوتا', 'name_en' => 'Toyota']);
        $model = VehicleModel::firstOrCreate(['make_id' => $make->id, 'name_ar' => 'كامري', 'name_en' => 'Camry']);

        $vehicle = Vehicle::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'plate_number' => '1234 KSA',
            'make_id' => $make->id,
            'model_id' => $model->id,
            'year' => 2022,
            'odometer' => 50000,
        ]);

        $workOrder = WorkOrder::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'code' => 'WO-VEH-001',
            'status' => WorkOrder::STATUS_IN_PROGRESS,
            'entry_date' => now()->toDateString(),
            'expected_end_date' => now()->addDays(2)->toDateString(),
        ]);

        $item1 = WorkOrderItem::create([
            'work_order_id' => $workOrder->id,
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'title' => 'فحص كمبيوتر وصيانة دورية',
            'qty' => 1,
            'unit_price' => 300.00,
            'total' => 300.00,
            'status' => WorkOrderItem::STATUS_IN_PROGRESS,
            'due_date' => now()->toDateString(),
        ]);

        $item2 = WorkOrderItem::create([
            'work_order_id' => $workOrder->id,
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'title' => 'تغيير زيت وفلتر',
            'qty' => 1,
            'unit_price' => 200.00,
            'total' => 200.00,
            'status' => WorkOrderItem::STATUS_COMPLETED,
            'due_date' => now()->toDateString(),
        ]);

        WorkOrderItemPart::create([
            'work_order_id' => $workOrder->id,
            'work_order_item_id' => $item2->id,
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name' => 'فلتر زيت أصلي',
            'part_number' => 'FL-001',
            'qty' => 1,
            'unit_price' => 75.00,
            'total' => 75.00,
            'status' => 'issued',
        ]);

        $response = $this->actingAs($user)->get(route('app.reports.vehicles', [
            'range' => 'this_month',
            'ticket_no' => 'WO-VEH-001',
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Vehicles')
            ->where('kpis.total_records', 2)
            ->where('kpis.total_vehicles', 1)
            ->where('kpis.total_tickets', 1)
            ->where('kpis.total_service_cost', 500)
            ->where('kpis.total_spare_part_cost', 75)
            ->where('kpis.grand_total', 575)
        );
    }

    public function test_vehicles_report_filters_by_make_model_and_status(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $center = Center::where('tenant_id', $tenant->id)->first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name' => 'عميل تجريبي',
            'phone' => '0509998877',
        ]);

        $makeFerrari = VehicleMake::firstOrCreate(['name_ar' => 'فيراري', 'name_en' => 'Ferrari']);
        $makeHonda = VehicleMake::firstOrCreate(['name_ar' => 'هوندا فريدة', 'name_en' => 'Unique Honda']);

        $modelF8 = VehicleModel::firstOrCreate(['make_id' => $makeFerrari->id, 'name_ar' => 'F8', 'name_en' => 'F8']);
        $modelAccord = VehicleModel::firstOrCreate(['make_id' => $makeHonda->id, 'name_ar' => 'أكورد فريد', 'name_en' => 'Unique Accord']);

        $vehFerrari = Vehicle::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'plate_number' => '9999 FER',
            'make_id' => $makeFerrari->id,
            'model_id' => $modelF8->id,
        ]);

        $vehHonda = Vehicle::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'plate_number' => '8888 HON',
            'make_id' => $makeHonda->id,
            'model_id' => $modelAccord->id,
        ]);

        $wo1 = WorkOrder::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehFerrari->id,
            'code' => 'WO-FER-1',
            'status' => WorkOrder::STATUS_IN_PROGRESS,
            'entry_date' => now()->toDateString(),
        ]);

        $wo2 = WorkOrder::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehHonda->id,
            'code' => 'WO-HON-1',
            'status' => WorkOrder::STATUS_IN_PROGRESS,
            'entry_date' => now()->toDateString(),
        ]);

        WorkOrderItem::create([
            'work_order_id' => $wo1->id,
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'title' => 'خدمة فيراري',
            'qty' => 1,
            'unit_price' => 150.00,
            'total' => 150.00,
            'status' => WorkOrderItem::STATUS_COMPLETED,
            'due_date' => now()->toDateString(),
        ]);

        WorkOrderItem::create([
            'work_order_id' => $wo2->id,
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'title' => 'خدمة هوندا',
            'qty' => 1,
            'unit_price' => 250.00,
            'total' => 250.00,
            'status' => WorkOrderItem::STATUS_IN_PROGRESS,
            'due_date' => now()->toDateString(),
        ]);

        // Filter by Ferrari
        $response = $this->actingAs($user)->get(route('app.reports.vehicles', [
            'make_id' => $makeFerrari->id,
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('kpis.total_records', 1)
            ->where('kpis.total_service_cost', 150)
        );

        // Filter by Ferrari + service status: completed
        $responseStatus = $this->actingAs($user)->get(route('app.reports.vehicles', [
            'make_id' => $makeFerrari->id,
            'service_status' => 'completed',
        ]));

        $responseStatus->assertStatus(200);
        $responseStatus->assertInertia(fn ($page) => $page
            ->where('kpis.total_records', 1)
            ->where('kpis.total_service_cost', 150)
        );
    }

    public function test_vehicles_report_groups_data_by_shop_and_status(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $center = Center::where('tenant_id', $tenant->id)->first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name' => 'عميل المجموعة',
            'phone' => '0501234567',
        ]);

        $vehicle = Vehicle::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'plate_number' => '5555 GRP',
        ]);

        $wo = WorkOrder::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'code' => 'WO-GRP-1',
            'status' => WorkOrder::STATUS_IN_PROGRESS,
            'entry_date' => now()->toDateString(),
        ]);

        WorkOrderItem::create([
            'work_order_id' => $wo->id,
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'title' => 'خدمة 1',
            'qty' => 1,
            'unit_price' => 100.00,
            'total' => 100.00,
            'status' => WorkOrderItem::STATUS_COMPLETED,
            'due_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($user)->get(route('app.reports.vehicles', [
            'group_by' => 'shopName',
            'ticket_no' => 'WO-GRP-1',
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('groups', 1)
            ->where('groups.0.summary.total_records', 1)
            ->where('groups.0.summary.total_service_cost', 100)
        );
    }

    public function test_vehicles_report_exports_csv_successfully(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $response = $this->actingAs($user)->get(route('app.reports.vehicles.export', [
            'range' => 'this_month',
        ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $content = $response->streamedContent();
        $this->assertStringContainsString('المركبة', $content);
        $this->assertStringContainsString('رقم اللوحة', $content);
        $this->assertStringContainsString('رقم كرت العمل', $content);
    }

    public function test_vehicles_report_filters_by_warehouse_parts_only(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $center = Center::where('tenant_id', $tenant->id)->first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name' => 'عميل قطع غيار',
            'phone' => '0507776655',
        ]);

        $vehicle = Vehicle::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'plate_number' => '7777 WRP',
        ]);

        $wo = WorkOrder::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'code' => 'WO-WH-001',
            'status' => WorkOrder::STATUS_IN_PROGRESS,
            'entry_date' => now()->toDateString(),
        ]);

        // Item 1: Has warehouse part
        $item1 = WorkOrderItem::create([
            'work_order_id' => $wo->id,
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'title' => 'خدمة بقطع من المستودع',
            'qty' => 1,
            'unit_price' => 100.00,
            'total' => 100.00,
            'status' => WorkOrderItem::STATUS_COMPLETED,
            'due_date' => now()->toDateString(),
        ]);

        WorkOrderItemPart::create([
            'work_order_id' => $wo->id,
            'work_order_item_id' => $item1->id,
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name' => 'قطعة مستودع',
            'part_number' => 'WH-01',
            'source' => WorkOrderItemPart::SOURCE_WAREHOUSE,
            'qty' => 1,
            'unit_price' => 80.00,
            'total' => 80.00,
            'status' => 'issued',
        ]);

        // Item 2: Has external part only
        $item2 = WorkOrderItem::create([
            'work_order_id' => $wo->id,
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'title' => 'خدمة بقطع خارجية',
            'qty' => 1,
            'unit_price' => 120.00,
            'total' => 120.00,
            'status' => WorkOrderItem::STATUS_COMPLETED,
            'due_date' => now()->toDateString(),
        ]);

        WorkOrderItemPart::create([
            'work_order_id' => $wo->id,
            'work_order_item_id' => $item2->id,
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name' => 'قطعة خارجية',
            'part_number' => 'EXT-01',
            'source' => WorkOrderItemPart::SOURCE_EXTERNAL,
            'qty' => 1,
            'unit_price' => 50.00,
            'total' => 50.00,
            'status' => 'issued',
        ]);

        // Request with warehouse_parts_only = 1
        $response = $this->actingAs($user)->get(route('app.reports.vehicles', [
            'ticket_no' => 'WO-WH-001',
            'warehouse_parts_only' => 1,
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('kpis.total_records', 1)
            ->where('kpis.total_service_cost', 100)
            ->where('kpis.total_spare_part_cost', 80)
            ->where('kpis.grand_total', 180)
            ->where('items.data.0.service_name', 'خدمة بقطع من المستودع')
        );
    }
}
