<?php

declare(strict_types=1);

namespace Tests\Feature\HR;

use App\Models\Center;
use App\Models\HR\Employee;
use App\Models\HR\PayrollItem;
use App\Models\HR\PayrollRun;
use App\Models\Nationality;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Services\HR\TechnicianCommissionService;
use App\Support\Permissions;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TechnicianCommissionPayrollTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Center $center;

    private User $hrAdmin;

    private User $techUser;

    private User $coTechUser;

    private Employee $technician;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();

        $this->tenant = Tenant::factory()->create();
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);
        $nationality = Nationality::firstOrCreate(
            ['name_ar' => 'سعودي'],
            ['name_en' => 'Saudi', 'is_active' => true]
        );

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
        Permission::firstOrCreate(['name' => Permissions::HR_PAYROLL_VIEW, 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => Permissions::HR_PAYROLL_CREATE, 'guard_name' => 'web']);

        $this->hrAdmin = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);
        $this->hrAdmin->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);
        $this->hrAdmin->givePermissionTo([
            Permissions::HR_PAYROLL_VIEW,
            Permissions::HR_PAYROLL_CREATE,
        ]);

        $this->techUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);
        $this->techUser->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);

        $this->coTechUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);
        $this->coTechUser->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);

        $this->technician = Employee::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'user_id' => $this->techUser->id,
            'employee_number' => 'EMP-TECH-01',
            'name_ar' => 'فني صيانة',
            'name_en' => 'Workshop Tech',
            'phone' => '0504445566',
            'email' => 'tech01@example.com',
            'gender' => 'male',
            'nationality_id' => $nationality->id,
            'base_salary' => 5000,
            'gosi_rate' => 0,
            'commission_enabled' => true,
            'commission_type' => 'percentage',
            'commission_rate' => 10,
            'status' => 'active',
        ]);
    }

    public function test_commission_service_excludes_warranty_splits_shared_tasks_and_feeds_payroll(): void
    {
        $workOrder = WorkOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
        ]);

        // 1. Solo billable item: 1000 SAR, estimated 120m, actual 100m -> 100% revenue (1000)
        $soloItem = WorkOrderItem::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'work_order_id' => $workOrder->id,
            'title' => 'تغيير فحمات فرامل',
            'qty' => 1,
            'unit_price' => 1000,
            'line_total_excl_tax' => 1000,
            'line_total' => 1000,
            'total' => 1000,
            'status' => WorkOrderItem::STATUS_COMPLETED,
            'is_warranty' => false,
            'estimated_duration' => 120,
            'actual_duration' => 100,
            'completed_at' => '2026-11-10 14:00:00',
        ]);
        $soloItem->technicians()->attach($this->techUser->id);

        // 2. Shared billable item with co-technician: 1000 SAR / 2 = 500 SAR, estimated 60m, actual 80m
        $sharedItem = WorkOrderItem::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'work_order_id' => $workOrder->id,
            'title' => 'فحص شامل للمحرك',
            'qty' => 1,
            'unit_price' => 1000,
            'line_total_excl_tax' => 1000,
            'line_total' => 1000,
            'total' => 1000,
            'status' => WorkOrderItem::STATUS_COMPLETED,
            'is_warranty' => false,
            'estimated_duration' => 60,
            'actual_duration' => 80,
            'completed_at' => '2026-11-12 16:00:00',
        ]);
        $sharedItem->technicians()->attach([$this->techUser->id, $this->coTechUser->id]);

        // 3. Warranty / rework item: 800 SAR -> excluded from commission, lowers first-time fix rate (2/3 = 66.7%)
        $warrantyItem = WorkOrderItem::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'work_order_id' => $workOrder->id,
            'title' => 'إعادة ضبط ضمان',
            'qty' => 1,
            'unit_price' => 800,
            'line_total_excl_tax' => 800,
            'line_total' => 800,
            'total' => 800,
            'status' => WorkOrderItem::STATUS_COMPLETED,
            'is_warranty' => true,
            'estimated_duration' => 60,
            'actual_duration' => 60,
            'completed_at' => '2026-11-13 11:00:00',
        ]);
        $warrantyItem->technicians()->attach($this->techUser->id);

        $service = new TechnicianCommissionService;
        $metrics = $service->calculateForPeriod(
            $this->technician,
            Carbon::parse('2026-11-01'),
            Carbon::parse('2026-11-30')
        );

        // Billable revenue = 1000 + 500 = 1500 -> 10% = 150 SAR
        $this->assertEquals(2, $metrics['billable_items_count']);
        $this->assertEquals(1, $metrics['warranty_items_count']);
        $this->assertEquals(1500.0, $metrics['billable_revenue']);
        $this->assertEquals(150.0, $metrics['commission_amount']);
        $this->assertEquals(66.7, $metrics['non_warranty_share']);
        $this->assertArrayNotHasKey('first_time_fix_rate', $metrics);
        // Estimated = 240m, Actual = 240m => 100.0%
        $this->assertEquals(100.0, $metrics['efficiency_rate']);

        // Generate payroll run and verify commission is rolled into PayrollItem
        $this->actingAs($this->hrAdmin)
            ->post(route('app.hr.payroll.generate'), [
                'period_start' => '2026-11-01',
                'period_end' => '2026-11-30',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $payrollRun = PayrollRun::latest('id')->firstOrFail();
        $item = PayrollItem::where('payroll_run_id', $payrollRun->id)
            ->where('employee_id', $this->technician->id)
            ->firstOrFail();

        $this->assertEquals(150.0, (float) ($item->allowances_breakdown['commission'] ?? 0));
        $this->assertEquals(150.0, (float) $item->total_allowances);
        $this->assertEquals(
            round((float) $item->base_salary + 150.0 - (float) $item->total_deductions, 2),
            (float) $item->net_salary
        );
    }
}
