<?php

declare(strict_types=1);

namespace Tests\Feature\HR;

use App\Models\Center;
use App\Models\HR\AttendanceSettings;
use App\Models\HR\Deduction;
use App\Models\HR\Employee;
use App\Models\HR\PayrollRun;
use App\Models\Nationality;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PayrollStatutoryDeductionCapTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Center $center;

    private User $hrManager;

    private Employee $employee;

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

        foreach ([
            Permissions::HR_PAYROLL_VIEW,
            Permissions::HR_PAYROLL_CREATE,
            Permissions::HR_PAYROLL_UPDATE,
            Permissions::HR_SETTINGS_MANAGE,
        ] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $this->hrManager = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
            'is_active' => true,
        ]);
        $this->hrManager->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);
        $this->hrManager->givePermissionTo([
            Permissions::HR_PAYROLL_VIEW,
            Permissions::HR_PAYROLL_CREATE,
            Permissions::HR_PAYROLL_UPDATE,
            Permissions::HR_SETTINGS_MANAGE,
        ]);

        $this->employee = Employee::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'employee_number' => 'EMP-CAP-01',
            'name_ar' => 'موظف سقف',
            'name_en' => 'Cap Employee',
            'phone' => '0507778899',
            'email' => 'cap@example.com',
            'gender' => 'male',
            'nationality_id' => $nationality->id,
            'status' => 'active',
            'base_salary' => 4000,
            'gosi_applicable' => false,
            'gosi_employee_rate' => 0,
        ]);
    }

    public function test_payroll_generation_caps_deductions_at_statutory_percentage(): void
    {
        $settings = AttendanceSettings::getForCenter($this->center->id);
        $settings->update([
            'max_deduction_percentage' => 50.0,
            'absence_deduction_value' => 0,
        ]);

        $deduction = Deduction::create([
            'tenant_id' => $this->tenant->id,
            'name_ar' => 'جزاء لائحة',
            'name_en' => 'Regulation Penalty',
            'type' => 'fixed',
            'amount' => 3000,
            'is_active' => true,
        ]);
        $this->employee->deductions()->attach($deduction->id, ['custom_amount' => 3000]);

        $this->actingAs($this->hrManager)
            ->post(route('app.hr.payroll.generate'), [
                'period_start' => '2026-07-01',
                'period_end' => '2026-07-05',
            ])
            ->assertRedirect();

        $run = PayrollRun::where('tenant_id', $this->tenant->id)->firstOrFail();
        $item = $run->items()->where('employee_id', $this->employee->id)->firstOrFail();

        // Gross = 4000, cap = 50% => max allowed deduction = 2000
        $expectedRaw = 3000.0 + (float) $item->gosi_amount + (float) $item->deductions_breakdown['attendance'];
        $this->assertEquals(2000.0, (float) $item->total_deductions);
        $this->assertEquals(2000.0, (float) $item->net_salary);
        $this->assertTrue((bool) $item->deductions_breakdown['is_capped']);
        $this->assertEquals($expectedRaw, (float) $item->deductions_breakdown['raw_total']);
        $this->assertEquals(round($expectedRaw - 2000.0, 2), (float) $item->deductions_breakdown['capped_amount']);
    }

    public function test_manual_payroll_item_update_rejects_deductions_above_statutory_cap(): void
    {
        $settings = AttendanceSettings::getForCenter($this->center->id);
        $settings->update(['max_deduction_percentage' => 40.0]);

        $this->actingAs($this->hrManager)
            ->post(route('app.hr.payroll.generate'), [
                'period_start' => '2026-08-01',
                'period_end' => '2026-08-05',
            ])
            ->assertRedirect();

        $run = PayrollRun::where('tenant_id', $this->tenant->id)->firstOrFail();
        $item = $run->items()->where('employee_id', $this->employee->id)->firstOrFail();

        // Attempt to set deductions to 2500 (> 40% of 4000 = 1600)
        $response = $this->actingAs($this->hrManager)
            ->put(route('app.hr.payroll.items.update', [$run->id, $item->id]), [
                'base_salary' => 4000,
                'total_allowances' => 0,
                'total_deductions' => 2500,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $item->refresh();
        $this->assertNotEquals(2500.0, (float) $item->total_deductions);
    }

    public function test_settings_update_persists_max_deduction_percentage(): void
    {
        $this->actingAs($this->hrManager)
            ->put(route('app.hr.settings.attendance.update'), [
                'grace_period_minutes' => 10,
                'late_deduction_per_minute' => 0,
                'absence_deduction_value' => 100,
                'absence_deduction_type' => 'percentage',
                'overtime_rate_per_hour' => 0,
                'overtime_enabled' => true,
                'working_days' => [0, 1, 2, 3, 4],
                'max_deduction_percentage' => 35.5,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertEquals(
            35.5,
            (float) AttendanceSettings::getForCenter($this->center->id)->max_deduction_percentage
        );
    }
}
