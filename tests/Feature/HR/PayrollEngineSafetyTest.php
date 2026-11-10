<?php

declare(strict_types=1);

namespace Tests\Feature\HR;

use App\Models\Center;
use App\Models\HR\Attendance;
use App\Models\HR\AttendanceSettings;
use App\Models\HR\Employee;
use App\Models\HR\Leave;
use App\Models\HR\PayrollItem;
use App\Models\HR\PayrollRun;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Permissions;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PayrollEngineSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Center $center;

    protected User $hrManager;

    protected Employee $employee;

    protected AttendanceSettings $settings;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();

        $this->tenant = Tenant::factory()->create(['status' => 'active']);
        $this->center = Center::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);

        $permissions = [
            Permissions::HR_PAYROLL_VIEW,
            Permissions::HR_PAYROLL_CREATE,
            Permissions::HR_PAYROLL_UPDATE,
            Permissions::HR_PAYROLL_DELETE,
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $this->hrManager = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);
        $this->hrManager->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);
        $this->hrManager->givePermissionTo($permissions);

        $this->settings = AttendanceSettings::getForCenter($this->center->id);
        $this->settings->update([
            'working_days' => [0, 1, 2, 3, 4], // Sun - Thu
            'absence_deduction_type' => 'percentage',
            'absence_deduction_value' => 100, // 100% daily wage
            'late_deduction_per_minute' => 0.5,
        ]);

        $this->employee = Employee::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'employee_number' => 'EMP-PAY-001',
            'name_ar' => 'أحمد المالي',
            'name_en' => 'Ahmed Finance',
            'phone' => '0551122339',
            'email' => 'ahmed.pay@example.com',
            'gender' => 'male',
            'base_salary' => 6000.00, // Daily wage = 6000 / 30 = 200 SAR
            'status' => 'active',
        ]);
    }

    public function test_approved_paid_leave_is_not_deducted_as_absence(): void
    {
        // Period: 2026-05-03 (Sun) to 2026-05-07 (Thu) = 5 working days
        $start = Carbon::parse('2026-05-03');
        $end = Carbon::parse('2026-05-07');

        // Employee attended 3 days: 2026-05-03, 2026-05-04, 2026-05-05
        foreach (['2026-05-03', '2026-05-04', '2026-05-05'] as $date) {
            Attendance::create([
                'tenant_id' => $this->tenant->id,
                'center_id' => $this->center->id,
                'employee_id' => $this->employee->id,
                'date' => $date,
                'status' => 'present',
                'late_minutes' => 0,
            ]);
        }

        // Employee was on approved annual leave for the remaining 2 days: 2026-05-06, 2026-05-07
        Leave::create([
            'tenant_id' => $this->tenant->id,
            'employee_id' => $this->employee->id,
            'type' => 'annual',
            'start_date' => '2026-05-06',
            'end_date' => '2026-05-07',
            'days' => 2,
            'status' => 'approved',
        ]);

        // Generate payroll
        $response = $this->actingAs($this->hrManager)
            ->post(route('app.hr.payroll.generate'), [
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
            ]);

        $response->assertRedirect();

        $run = PayrollRun::where('tenant_id', $this->tenant->id)->firstOrFail();
        $item = $run->items()->where('employee_id', $this->employee->id)->firstOrFail();

        // Attendance deduction should be exactly 0 (no absence penalty because leave was approved!)
        $this->assertEquals(0, $item->deductions_breakdown['attendance']);
    }

    public function test_unexcused_absence_is_deducted_properly(): void
    {
        // Period: 2026-06-07 (Sun) to 2026-06-11 (Thu) = 5 working days
        $start = Carbon::parse('2026-06-07');
        $end = Carbon::parse('2026-06-11');

        // Employee attended 4 days, absent on 2026-06-11 without leave
        foreach (['2026-06-07', '2026-06-08', '2026-06-09', '2026-06-10'] as $date) {
            Attendance::create([
                'tenant_id' => $this->tenant->id,
                'center_id' => $this->center->id,
                'employee_id' => $this->employee->id,
                'date' => $date,
                'status' => 'present',
                'late_minutes' => 0,
            ]);
        }

        // Generate payroll
        $response = $this->actingAs($this->hrManager)
            ->post(route('app.hr.payroll.generate'), [
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
            ]);

        $run = PayrollRun::where('tenant_id', $this->tenant->id)->firstOrFail();
        $item = $run->items()->where('employee_id', $this->employee->id)->firstOrFail();

        // 1 day absence * 200 SAR daily wage = 200 SAR
        $this->assertEquals(200.00, $item->deductions_breakdown['attendance']);
    }

    public function test_hr_manager_can_update_draft_payroll_item(): void
    {
        $run = PayrollRun::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'period_start' => '2026-07-01',
            'period_end' => '2026-07-31',
            'status' => 'draft',
            'created_by' => $this->hrManager->id,
        ]);

        $item = PayrollItem::create([
            'payroll_run_id' => $run->id,
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'employee_id' => $this->employee->id,
            'base_salary' => 6000.00,
            'gosi_rate' => 0,
            'gosi_amount' => 0,
            'total_allowances' => 500.00,
            'total_deductions' => 200.00,
            'net_salary' => 6300.00,
        ]);

        // Update item with bonus allowance and custom deduction
        $response = $this->actingAs($this->hrManager)
            ->put(route('app.hr.payroll.items.update', [$run->id, $item->id]), [
                'base_salary' => 6000.00,
                'total_allowances' => 1000.00, // +500 bonus
                'total_deductions' => 100.00,
                'notes' => 'مكافأة أداء استثنائي',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $item->refresh();
        $this->assertEquals(1000.00, (float) $item->total_allowances);
        $this->assertEquals(100.00, (float) $item->total_deductions);
        $this->assertEquals(6900.00, (float) $item->net_salary); // 6000 + 1000 - 100
        $this->assertEquals('مكافأة أداء استثنائي', $item->notes);
    }

    public function test_mark_paid_records_payment_date_and_method(): void
    {
        $run = PayrollRun::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'period_start' => '2026-07-01',
            'period_end' => '2026-07-31',
            'status' => 'approved',
            'approved_by' => $this->hrManager->id,
        ]);

        $response = $this->actingAs($this->hrManager)
            ->put(route('app.hr.payroll.mark-paid', $run->id), [
                'payment_date' => '2026-07-28',
                'payment_method' => 'bank_transfer',
                'reference_number' => 'SARIE-99887766',
            ]);

        $response->assertRedirect();
        $run->refresh();

        $this->assertEquals('paid', $run->status);
        $this->assertEquals('2026-07-28', $run->payment_date->toDateString());
    }
}
