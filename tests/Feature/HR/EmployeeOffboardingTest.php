<?php

declare(strict_types=1);

namespace Tests\Feature\HR;

use App\Models\Center;
use App\Models\HR\Employee;
use App\Models\HR\EmployeeAssetCustody;
use App\Models\HR\EmployeeOffboarding;
use App\Models\Nationality;
use App\Models\Tenant;
use App\Models\User;
use App\Services\HR\EndOfServiceCalculator;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class EmployeeOffboardingTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Center $centerA;

    private Center $centerB;

    private Nationality $nationality;

    private User $authorizedUser;

    private User $unauthorizedUser;

    private User $employeeUser;

    private Employee $employeeA;

    private Employee $employeeB;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();

        $this->tenant = Tenant::factory()->create();
        $this->centerA = Center::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->centerB = Center::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->nationality = Nationality::firstOrCreate(
            ['name_ar' => 'سعودي'],
            ['name_en' => 'Saudi', 'is_active' => true]
        );

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
        Permission::firstOrCreate(['name' => Permissions::HR_EMPLOYEES_VIEW, 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => Permissions::HR_EMPLOYEES_UPDATE, 'guard_name' => 'web']);

        $this->authorizedUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->centerA->id,
        ]);
        $this->authorizedUser->centers()->attach($this->centerA->id, ['tenant_id' => $this->tenant->id]);
        $this->authorizedUser->givePermissionTo([
            Permissions::HR_EMPLOYEES_VIEW,
            Permissions::HR_EMPLOYEES_UPDATE,
        ]);

        $this->unauthorizedUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->centerA->id,
        ]);
        $this->unauthorizedUser->centers()->attach($this->centerA->id, ['tenant_id' => $this->tenant->id]);

        $this->employeeUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->centerA->id,
            'is_active' => true,
        ]);
        $this->employeeUser->centers()->attach($this->centerA->id, ['tenant_id' => $this->tenant->id]);

        $this->employeeA = Employee::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerA->id,
            'user_id' => $this->employeeUser->id,
            'employee_number' => 'EMP-OFF-A',
            'name_ar' => 'موظف أ',
            'name_en' => 'Employee A',
            'phone' => '0502223344',
            'email' => 'empa@example.com',
            'gender' => 'male',
            'nationality_id' => $this->nationality->id,
            'hire_date' => '2020-01-01',
            'base_salary' => 8000,
            'status' => 'active',
        ]);

        $this->employeeB = Employee::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerB->id,
            'employee_number' => 'EMP-OFF-B',
            'name_ar' => 'موظف ب',
            'name_en' => 'Employee B',
            'phone' => '0502223355',
            'email' => 'empb@example.com',
            'gender' => 'male',
            'nationality_id' => $this->nationality->id,
            'hire_date' => '2022-01-01',
            'base_salary' => 6000,
            'status' => 'active',
        ]);
    }

    public function test_eosb_calculator_applies_saudi_labor_law_tiers(): void
    {
        $calculator = new EndOfServiceCalculator;

        // 1. Employer termination after 6 years (365 * 6 = 2190 days): 5*0.5 + 1*1.0 = 3.5 months
        $employerTerm = $calculator->calculate([
            'termination_type' => EmployeeOffboarding::TYPE_EMPLOYER_TERMINATION,
            'hire_date' => '2020-01-01',
            'termination_date' => '2025-12-30', // 2190 days = 6.0 years
            'base_salary' => 8000,
            'allowances_amount' => 2000,
            'unused_leave_days' => 15,
            'advances_deduction' => 1000,
        ]);

        $this->assertEquals(10000.0, $employerTerm['calculation_salary']);
        $this->assertEquals(1.0, $employerTerm['eosb_multiplier']);
        $this->assertEquals(35000.0, $employerTerm['eosb_amount']);
        $this->assertEquals(5000.0, $employerTerm['unused_leave_payout']);
        $this->assertEquals(39000.0, $employerTerm['net_settlement']);

        // 2. Resignation after 3 years (1095 days): 1/3 of (3 * 0.5 * 6000 = 9000) = 3000
        $resign3Years = $calculator->calculate([
            'termination_type' => EmployeeOffboarding::TYPE_RESIGNATION,
            'hire_date' => '2020-01-01',
            'termination_date' => '2022-12-31', // 1095 days = 3.0 years
            'base_salary' => 6000,
            'allowances_amount' => 0,
        ]);

        $this->assertEquals(round(1 / 3, 4), $resign3Years['eosb_multiplier']);
        $this->assertEquals(3000.0, $resign3Years['eosb_amount']);

        // 3. Article 80 dismissal: 0%
        $article80 = $calculator->calculate([
            'termination_type' => EmployeeOffboarding::TYPE_ARTICLE_80,
            'hire_date' => '2018-01-01',
            'termination_date' => '2025-01-01',
            'base_salary' => 6000,
        ]);

        $this->assertEquals(0.0, $article80['eosb_amount']);
    }

    public function test_unauthorized_user_cannot_save_or_finalize_offboarding(): void
    {
        $this->actingAs($this->unauthorizedUser)
            ->post(route('app.hr.employees.offboarding.store', $this->employeeA->id), [
                'termination_type' => EmployeeOffboarding::TYPE_EMPLOYER_TERMINATION,
                'hire_date' => '2020-01-01',
                'termination_date' => '2026-11-14',
                'base_salary' => 8000,
            ])
            ->assertForbidden();
    }

    public function test_user_cannot_create_offboarding_for_other_center_employee(): void
    {
        $this->actingAs($this->authorizedUser)
            ->post(route('app.hr.employees.offboarding.store', $this->employeeB->id), [
                'termination_type' => EmployeeOffboarding::TYPE_EMPLOYER_TERMINATION,
                'hire_date' => '2022-01-01',
                'termination_date' => '2026-11-14',
                'base_salary' => 6000,
            ])
            ->assertForbidden();
    }

    public function test_finalize_is_blocked_when_unreturned_custody_exists_then_succeeds_after_return(): void
    {
        $custody = EmployeeAssetCustody::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerA->id,
            'employee_id' => $this->employeeA->id,
            'asset_name' => 'جهاز فحص OBD',
            'category' => 'diagnostic_device',
            'condition_on_issue' => 'good',
            'issued_date' => '2025-01-01',
            'status' => EmployeeAssetCustody::STATUS_ISSUED,
            'created_by' => $this->authorizedUser->id,
        ]);

        $this->actingAs($this->authorizedUser)
            ->post(route('app.hr.employees.offboarding.store', $this->employeeA->id), [
                'termination_type' => EmployeeOffboarding::TYPE_EMPLOYER_TERMINATION,
                'hire_date' => '2020-01-01',
                'termination_date' => '2025-12-30',
                'base_salary' => 8000,
                'allowances_amount' => 2000,
                'unused_leave_days' => 6,
                'advances_deduction' => 500,
                'custody_deduction' => 0,
            ])
            ->assertRedirect();

        $offboarding = EmployeeOffboarding::firstOrFail();

        // Attempt to finalize while custody is still issued -> blocked
        $this->actingAs($this->authorizedUser)
            ->post(route('app.hr.employees.offboarding.finalize', [
                'employee' => $this->employeeA->id,
                'offboarding' => $offboarding->id,
            ]))
            ->assertSessionHasErrors('custody_cleared');

        $this->assertEquals(EmployeeOffboarding::STATUS_DRAFT, $offboarding->fresh()->status);

        // Return custody
        $custody->update([
            'status' => EmployeeAssetCustody::STATUS_RETURNED,
            'returned_date' => '2025-12-30',
            'condition_on_return' => 'good',
        ]);

        // Now finalize succeeds, marks employee terminated & deactivates user
        $this->actingAs($this->authorizedUser)
            ->post(route('app.hr.employees.offboarding.finalize', [
                'employee' => $this->employeeA->id,
                'offboarding' => $offboarding->id,
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertEquals(EmployeeOffboarding::STATUS_FINALIZED, $offboarding->fresh()->status);
        $this->assertEquals('terminated', $this->employeeA->fresh()->status);
        $this->assertFalse((bool) $this->employeeUser->fresh()->is_active);
    }
}
