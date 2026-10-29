<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use Database\Seeders\PerformanceBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServicesReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/app/reports/services');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_services_report(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $response = $this->actingAs($user)->get('/app/reports/services');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Services')
            ->has('filters')
            ->has('kpis')
            ->has('departments')
            ->has('servicesList')
            ->has('centers')
        );
    }

    public function test_filtering_services_report_by_department_and_status(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $center = Center::where('tenant_id', $tenant->id)->first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();
        $customer = Customer::where('tenant_id', $tenant->id)->first();
        $vehicle = Vehicle::where('tenant_id', $tenant->id)->first();

        $dept1 = Department::withoutGlobalScope('center_scoped')->create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name_ar' => 'قسم المحركات',
            'name_en' => 'Engine Dept',
            'is_active' => true,
        ]);

        $dept2 = Department::withoutGlobalScope('center_scoped')->create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name_ar' => 'قسم الكهرباء',
            'name_en' => 'Electrical Dept',
            'is_active' => true,
        ]);

        $wo = WorkOrder::withoutGlobalScope('center_scoped')->create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'code' => 'WO-TEST-SERV-1',
            'status' => 'in_progress',
            'entry_date' => now()->toDateString(),
            'expected_end_date' => now()->addDays(2)->toDateString(),
        ]);

        $item1 = WorkOrderItem::withoutGlobalScope('center_scoped')->create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'work_order_id' => $wo->id,
            'department_id' => $dept1->id,
            'title' => 'فحص كمبيوتر المحرك',
            'qty' => 1,
            'unit_price' => 200,
            'discount_amount' => 20,
            'line_total' => 180,
            'status' => WorkOrderItem::STATUS_COMPLETED,
            'due_date' => now()->toDateString(),
        ]);

        $item2 = WorkOrderItem::withoutGlobalScope('center_scoped')->create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'work_order_id' => $wo->id,
            'department_id' => $dept2->id,
            'title' => 'تبديل بطارية وتشييك دينامو',
            'qty' => 1,
            'unit_price' => 150,
            'discount_amount' => 0,
            'line_total' => 150,
            'status' => WorkOrderItem::STATUS_PENDING,
            'due_date' => now()->toDateString(),
        ]);

        // Filter by dept1
        $response = $this->actingAs($user)->get("/app/reports/services?department_id={$dept1->id}&range=all_time");
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Services')
            ->where('kpis.total_records', 1)
            ->where('services.data.0.id', $item1->id)
            ->where('services.data.0.department_name', 'قسم المحركات')
        );

        // Filter by status completed
        $response2 = $this->actingAs($user)->get('/app/reports/services?service_status=completed&range=all_time');
        $response2->assertStatus(200);
        $response2->assertInertia(fn ($page) => $page
            ->component('Reports/Services')
            ->where('services.data.0.id', $item1->id)
            ->where('services.data.0.status', 'completed')
        );
    }

    public function test_services_report_filters_by_specific_branch(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();
        $customer = Customer::where('tenant_id', $tenant->id)->first();
        $vehicle = Vehicle::where('tenant_id', $tenant->id)->first();

        $centerNorth = Center::create([
            'tenant_id' => $tenant->id,
            'name' => 'فرع الشمال',
            'slug' => 'branch-north-'.uniqid(),
            'is_active' => true,
        ]);

        $centerSouth = Center::create([
            'tenant_id' => $tenant->id,
            'name' => 'فرع الجنوب',
            'slug' => 'branch-south-'.uniqid(),
            'is_active' => true,
        ]);

        $woNorth = WorkOrder::withoutGlobalScope('center_scoped')->create([
            'tenant_id' => $tenant->id,
            'center_id' => $centerNorth->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'code' => 'WO-NORTH-01',
            'status' => 'open',
            'entry_date' => now()->toDateString(),
        ]);

        $itemNorth = WorkOrderItem::withoutGlobalScope('center_scoped')->create([
            'tenant_id' => $tenant->id,
            'center_id' => $centerNorth->id,
            'work_order_id' => $woNorth->id,
            'title' => 'صيانة فرع الشمال',
            'qty' => 1,
            'unit_price' => 500,
            'discount_amount' => 50,
            'line_total' => 450,
            'status' => WorkOrderItem::STATUS_IN_PROGRESS,
            'due_date' => now()->toDateString(),
        ]);

        $woSouth = WorkOrder::withoutGlobalScope('center_scoped')->create([
            'tenant_id' => $tenant->id,
            'center_id' => $centerSouth->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'code' => 'WO-SOUTH-01',
            'status' => 'open',
            'entry_date' => now()->toDateString(),
        ]);

        $itemSouth = WorkOrderItem::withoutGlobalScope('center_scoped')->create([
            'tenant_id' => $tenant->id,
            'center_id' => $centerSouth->id,
            'work_order_id' => $woSouth->id,
            'title' => 'صيانة فرع الجنوب',
            'qty' => 1,
            'unit_price' => 300,
            'discount_amount' => 0,
            'line_total' => 300,
            'status' => WorkOrderItem::STATUS_IN_PROGRESS,
            'due_date' => now()->toDateString(),
        ]);

        // Filter by North branch
        $responseNorth = $this->actingAs($user)->get("/app/reports/services?center_id={$centerNorth->id}&range=all_time");
        $responseNorth->assertStatus(200);
        $responseNorth->assertInertia(fn ($page) => $page
            ->component('Reports/Services')
            ->where('kpis.total_records', 1)
            ->where('services.data.0.id', $itemNorth->id)
            ->where('services.data.0.center_name', 'فرع الشمال')
        );

        // Filter by South branch
        $responseSouth = $this->actingAs($user)->get("/app/reports/services?center_id={$centerSouth->id}&range=all_time");
        $responseSouth->assertStatus(200);
        $responseSouth->assertInertia(fn ($page) => $page
            ->component('Reports/Services')
            ->where('kpis.total_records', 1)
            ->where('services.data.0.id', $itemSouth->id)
            ->where('services.data.0.center_name', 'فرع الجنوب')
        );

        // Filter by All branches
        $responseAll = $this->actingAs($user)->get('/app/reports/services?center_id=all&range=all_time');
        $responseAll->assertStatus(200);
        $responseAll->assertInertia(fn ($page) => $page
            ->component('Reports/Services')
            ->where('services.data', fn ($data) => collect($data)->contains('id', $itemNorth->id) && collect($data)->contains('id', $itemSouth->id))
        );
    }

    public function test_services_report_group_by(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $center = Center::where('tenant_id', $tenant->id)->first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();
        $customer = Customer::where('tenant_id', $tenant->id)->first();
        $vehicle = Vehicle::where('tenant_id', $tenant->id)->first();

        $dept = Department::withoutGlobalScope('center_scoped')->create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name_ar' => 'قسم التكييف والكهرباء',
            'name_en' => 'AC & Electrical',
            'is_active' => true,
        ]);

        $wo = WorkOrder::withoutGlobalScope('center_scoped')->create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'code' => 'WO-GRP-01',
            'status' => 'open',
            'entry_date' => now()->toDateString(),
        ]);

        WorkOrderItem::withoutGlobalScope('center_scoped')->create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'work_order_id' => $wo->id,
            'department_id' => $dept->id,
            'title' => 'شحن فريون وفحص تسريب',
            'qty' => 1,
            'unit_price' => 350,
            'discount_amount' => 50,
            'line_total' => 300,
            'status' => WorkOrderItem::STATUS_COMPLETED,
            'due_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($user)->get('/app/reports/services?group_by=department&range=all_time');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Services')
            ->has('groupedServices')
            ->has('groupedServices.0.group_name')
            ->has('groupedServices.0.total_service_cost')
        );
    }

    public function test_services_report_under_warranty_and_overdue_filters(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $center = Center::where('tenant_id', $tenant->id)->first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();
        $customer = Customer::where('tenant_id', $tenant->id)->first();
        $vehicle = Vehicle::where('tenant_id', $tenant->id)->first();

        $wo = WorkOrder::withoutGlobalScope('center_scoped')->create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'code' => 'WO-WARR-01',
            'status' => 'open',
            'entry_date' => now()->toDateString(),
        ]);

        // Item under warranty
        $warrantyItem = WorkOrderItem::withoutGlobalScope('center_scoped')->create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'work_order_id' => $wo->id,
            'title' => 'خدمة صيانة مجانية بالضمان',
            'qty' => 1,
            'unit_price' => 0,
            'line_total' => 0,
            'is_warranty' => true,
            'warranty_expires_at' => now()->addMonths(6),
            'status' => WorkOrderItem::STATUS_COMPLETED,
            'due_date' => now()->toDateString(),
        ]);

        // Overdue item
        $overdueItem = WorkOrderItem::withoutGlobalScope('center_scoped')->create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'work_order_id' => $wo->id,
            'title' => 'خدمة متأخرة التسليم',
            'qty' => 1,
            'unit_price' => 400,
            'line_total' => 400,
            'is_warranty' => false,
            'status' => WorkOrderItem::STATUS_IN_PROGRESS,
            'due_date' => now()->subDays(5)->toDateString(),
        ]);

        // Filter warranty only
        $responseWarranty = $this->actingAs($user)->get('/app/reports/services?is_warranty=1&range=all_time');
        $responseWarranty->assertStatus(200);
        $responseWarranty->assertInertia(fn ($page) => $page
            ->component('Reports/Services')
            ->where('services.data', fn ($data) => collect($data)->contains('id', $warrantyItem->id))
        );

        // Filter overdue only
        $responseOverdue = $this->actingAs($user)->get('/app/reports/services?is_overdue=1&range=all_time');
        $responseOverdue->assertStatus(200);
        $responseOverdue->assertInertia(fn ($page) => $page
            ->component('Reports/Services')
            ->where('services.data', fn ($data) => collect($data)->contains('id', $overdueItem->id))
        );
    }

    public function test_export_services_report_csv(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $center = Center::where('tenant_id', $tenant->id)->first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();
        $customer = Customer::where('tenant_id', $tenant->id)->first();
        $vehicle = Vehicle::where('tenant_id', $tenant->id)->first();

        $wo = WorkOrder::withoutGlobalScope('center_scoped')->create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'code' => 'WO-EXP-01',
            'status' => 'open',
            'entry_date' => now()->toDateString(),
        ]);

        WorkOrderItem::withoutGlobalScope('center_scoped')->create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'work_order_id' => $wo->id,
            'title' => 'خدمة للتصدير',
            'qty' => 1,
            'unit_price' => 250,
            'discount_amount' => 0,
            'line_total' => 250,
            'status' => WorkOrderItem::STATUS_COMPLETED,
            'due_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($user)->get('/app/reports/services/export?range=all_time');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('services_report_', (string) $response->headers->get('content-disposition'));
    }
}
