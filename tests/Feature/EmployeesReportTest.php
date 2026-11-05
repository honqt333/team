<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\HR\Employee;
use App\Models\HR\JobTitle;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use Carbon\Carbon;
use Database\Seeders\PerformanceBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EmployeesReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/app/reports/employees');
        $response->assertRedirect('/login');
    }

    public function test_wrshah_redirect_to_employees_report(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $response = $this->actingAs($user)->get('/reports/employees');
        $response->assertRedirect(route('app.reports.employees'));
    }

    public function test_authenticated_user_can_view_employees_report(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $response = $this->actingAs($user)->get('/app/reports/employees');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Employees')
            ->has('filters')
            ->has('kpis')
            ->has('centers')
            ->has('employeesList')
            ->has('jobTitles')
            ->has('departments')
            ->has('services')
            ->has('makes')
            ->has('models')
        );
    }

    public function test_employees_report_calculates_kpis_accurately(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $center = Center::where('tenant_id', $tenant->id)->first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        // Clear existing work order items and technician pivots for a pristine test calculation
        DB::table('work_order_item_technician')->truncate();
        WorkOrderItem::query()->forceDelete();

        $jobTitle = JobTitle::create([
            'tenant_id' => $tenant->id,
            'name_ar' => 'فني ميكانيكا اختبار',
            'name_en' => 'Test Mechanic',
        ]);

        $employeeUser = User::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'طارق الفني',
            'email_verified_at' => now(),
        ]);

        Employee::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'user_id' => $employeeUser->id,
            'name_ar' => 'طارق الفني',
            'name_en' => 'Tariq Tech',
            'job_title_id' => $jobTitle->id,
            'status' => 'active',
        ]);

        $workOrder = WorkOrder::factory()->create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'code' => 'WO-TEST-001',
            'status' => 'in_progress',
            'entry_date' => Carbon::now()->toDateString(),
        ]);

        $item = WorkOrderItem::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'work_order_id' => $workOrder->id,
            'title' => 'فحص محرك',
            'unit_price' => 500.00,
            'qty' => 1,
            'discount_type' => 'fixed',
            'discount_value' => 50.00,
            'status' => WorkOrderItem::STATUS_IN_PROGRESS,
            'actual_duration' => 120, // 2 hours
            'started_at' => Carbon::now(),
        ]);

        DB::table('work_order_item_technician')->insert([
            'work_order_item_id' => $item->id,
            'user_id' => $employeeUser->id,
            'share' => 100.00,
            'notes' => 'فني رئيسي',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/app/reports/employees?range=all_time');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('kpis.total_records', 1)
            ->where('kpis.total_service_hours', 2)
            ->where('kpis.total_service_price', 500)
            ->where('kpis.total_service_discount', 50)
            ->where('kpis.total_service_cost', 450)
            ->where('kpis.total_avg_percent_contribution', 450)
            ->where('kpis.total_avg_contribution_by_service_hours', 225)
            ->where('kpis.total_weighted_avg_contribution', 450)
        );
    }

    public function test_employees_report_filters_by_employee_and_job_title(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $center = Center::where('tenant_id', $tenant->id)->first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        // Clear existing assignments for targeted test
        DB::table('work_order_item_technician')->truncate();
        WorkOrderItem::query()->forceDelete();

        $jobTitle1 = JobTitle::create(['tenant_id' => $tenant->id, 'name_ar' => 'ميكانيكي خاص']);
        $jobTitle2 = JobTitle::create(['tenant_id' => $tenant->id, 'name_ar' => 'كهربائي خاص']);

        $empUser1 = User::factory()->create(['tenant_id' => $tenant->id, 'name' => 'فني 1', 'email_verified_at' => now()]);
        $empUser2 = User::factory()->create(['tenant_id' => $tenant->id, 'name' => 'فني 2', 'email_verified_at' => now()]);

        $emp1 = Employee::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'user_id' => $empUser1->id,
            'name_ar' => 'فني 1',
            'job_title_id' => $jobTitle1->id,
            'status' => 'active',
        ]);

        $emp2 = Employee::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'user_id' => $empUser2->id,
            'name_ar' => 'فني 2',
            'job_title_id' => $jobTitle2->id,
            'status' => 'active',
        ]);

        $workOrder = WorkOrder::factory()->create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'code' => 'WO-FLT-001',
            'entry_date' => Carbon::now()->toDateString(),
        ]);

        $item1 = WorkOrderItem::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'work_order_id' => $workOrder->id,
            'title' => 'ميكانيكا عامة',
            'unit_price' => 300,
            'qty' => 1,
            'discount_amount' => 0,
            'line_total' => 300,
            'status' => WorkOrderItem::STATUS_IN_PROGRESS,
            'started_at' => Carbon::now(),
        ]);

        $item2 = WorkOrderItem::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'work_order_id' => $workOrder->id,
            'title' => 'كهرباء سيارات',
            'unit_price' => 200,
            'qty' => 1,
            'discount_amount' => 0,
            'line_total' => 200,
            'status' => WorkOrderItem::STATUS_IN_PROGRESS,
            'started_at' => Carbon::now(),
        ]);

        DB::table('work_order_item_technician')->insert([
            ['work_order_item_id' => $item1->id, 'user_id' => $empUser1->id, 'share' => 100.00, 'notes' => 'رئيسي', 'created_at' => now(), 'updated_at' => now()],
            ['work_order_item_id' => $item2->id, 'user_id' => $empUser2->id, 'share' => 100.00, 'notes' => 'رئيسي', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Filter by employee 1
        $resEmp1 = $this->actingAs($user)->get('/app/reports/employees?range=all_time&employee_id='.$emp1->id);
        $resEmp1->assertStatus(200);
        $resEmp1->assertInertia(fn ($page) => $page
            ->where('kpis.total_records', 1)
            ->where('kpis.total_service_cost', 300)
        );

        // Filter by job title 2
        $resJob2 = $this->actingAs($user)->get('/app/reports/employees?range=all_time&job_title_id='.$jobTitle2->id);
        $resJob2->assertStatus(200);
        $resJob2->assertInertia(fn ($page) => $page
            ->where('kpis.total_records', 1)
            ->where('kpis.total_service_cost', 200)
        );
    }

    public function test_employees_report_groups_data_by_job_title(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $center = Center::where('tenant_id', $tenant->id)->first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        // Clear existing assignments for clean group assertion
        DB::table('work_order_item_technician')->truncate();
        WorkOrderItem::query()->forceDelete();

        $jobTitle = JobTitle::create(['tenant_id' => $tenant->id, 'name_ar' => 'فني دهان حصري']);
        $empUser = User::factory()->create(['tenant_id' => $tenant->id, 'name' => 'فني دهان 1', 'email_verified_at' => now()]);

        Employee::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'user_id' => $empUser->id,
            'name_ar' => 'فني دهان 1',
            'job_title_id' => $jobTitle->id,
            'status' => 'active',
        ]);

        $workOrder = WorkOrder::factory()->create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'code' => 'WO-GRP-001',
            'entry_date' => Carbon::now()->toDateString(),
        ]);

        $item = WorkOrderItem::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'work_order_id' => $workOrder->id,
            'title' => 'دهان رفرف',
            'unit_price' => 400,
            'qty' => 1,
            'discount_amount' => 0,
            'line_total' => 400,
            'status' => WorkOrderItem::STATUS_COMPLETED,
            'started_at' => Carbon::now(),
        ]);

        DB::table('work_order_item_technician')->insert([
            'work_order_item_id' => $item->id,
            'user_id' => $empUser->id,
            'share' => 100.00,
            'notes' => 'رئيسي',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/app/reports/employees?range=all_time&group_by=job_title');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('groupedEmployees', 1)
            ->where('groupedEmployees.0.group_name', 'فني دهان حصري')
            ->where('groupedEmployees.0.summary.total_records', 1)
            ->where('groupedEmployees.0.summary.total_service_cost', 400)
        );
    }

    public function test_employees_report_exports_csv_successfully(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $center = Center::where('tenant_id', $tenant->id)->first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        DB::table('work_order_item_technician')->truncate();
        WorkOrderItem::query()->forceDelete();

        $jobTitle = JobTitle::create(['tenant_id' => $tenant->id, 'name_ar' => 'سمكري خاص']);
        $empUser = User::factory()->create(['tenant_id' => $tenant->id, 'name' => 'سمكري 1', 'email_verified_at' => now()]);

        Employee::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'user_id' => $empUser->id,
            'name_ar' => 'سمكري 1',
            'job_title_id' => $jobTitle->id,
            'status' => 'active',
        ]);

        $workOrder = WorkOrder::factory()->create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'code' => 'WO-EXP-001',
            'entry_date' => Carbon::now()->toDateString(),
        ]);

        $item = WorkOrderItem::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'work_order_id' => $workOrder->id,
            'title' => 'تعديل صدمة',
            'unit_price' => 600,
            'qty' => 1,
            'discount_amount' => 0,
            'line_total' => 600,
            'status' => WorkOrderItem::STATUS_IN_PROGRESS,
            'started_at' => Carbon::now(),
        ]);

        DB::table('work_order_item_technician')->insert([
            'work_order_item_id' => $item->id,
            'user_id' => $empUser->id,
            'share' => 100.00,
            'notes' => 'رئيسي',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/app/reports/employees/export?range=all_time');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('الموظف', $content);
        $this->assertStringContainsString('المسمى الوظيفي', $content);
        $this->assertStringContainsString('WO-EXP-001', $content);
        $this->assertStringContainsString('تعديل صدمة', $content);
    }
}
