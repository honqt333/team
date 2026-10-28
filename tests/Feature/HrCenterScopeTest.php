<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\HR\Attendance;
use App\Models\HR\Employee;
use App\Models\HR\EmployeeContract;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Tests for the branch boundary of the HR records (audit findings #27
 * and #13).
 *
 * #27 — a contract was created for any employee id that existed. The
 * employee list is centre-scoped for everybody except the tenant super
 * admin, but the contract endpoint was not: a contract could be
 * created for an employee of another branch, carrying that branch's
 * salary data, and the policy only compared the tenant.
 *
 * #13 — an attendance record accepted any employee id and then
 * stamped the record with the CURRENT centre of whoever was logged
 * in. The payroll reads the attendance by employee and date, so a
 * record created in branch A ended up in the salary of an employee
 * of branch B.
 */
class HrCenterScopeTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Center $centerA;

    private Center $centerB;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ([
            Permissions::HR_CONTRACTS_VIEW,
            Permissions::HR_CONTRACTS_CREATE,
            Permissions::HR_CONTRACTS_UPDATE,
            Permissions::HR_CONTRACTS_DELETE,
            Permissions::HR_ATTENDANCE_VIEW,
            Permissions::HR_ATTENDANCE_MANAGE,
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $this->tenant = Tenant::factory()->create();
        $this->centerA = Center::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->centerB = Center::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    private function userIn(Center $center, array $permissions, bool $superAdmin = false): User
    {
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $center->id,
        ]);

        $user->centers()->attach($center->id, ['tenant_id' => $this->tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
        $user->givePermissionTo($permissions);

        if ($superAdmin) {
            Role::findOrCreate('super_admin', 'web');
            $user->assignRole('super_admin');
        }

        $user->refresh();

        return $user;
    }

    private function employeeIn(Center $center, string $name = 'Employee'): Employee
    {
        return Employee::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $center->id,
            'employee_number' => 'EMP-'.uniqid(),
            'name_ar' => $name,
            'name_en' => $name,
            'hire_date' => now()->toDateString(),
            'status' => 'active',
            'base_salary' => 5000,
        ]);
    }

    // ── #27 — employee contracts ──────────────────────────────────────

    public function test_a_contract_cannot_be_created_for_an_employee_of_another_branch(): void
    {
        $employee = $this->employeeIn($this->centerB);

        $user = $this->userIn($this->centerA, [Permissions::HR_CONTRACTS_CREATE]);

        $response = $this->actingAs($user)->post('/app/hr/employees/'.$employee->id.'/contracts', [
            'contract_number' => 'CT-'.uniqid(),
            'start_date' => now()->toDateString(),
            'status' => 'active',
            'salary_details' => ['basic' => 9000],
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('hr_employee_contracts', 0);
    }

    public function test_a_contract_can_be_created_for_an_employee_of_the_same_branch(): void
    {
        $employee = $this->employeeIn($this->centerA);

        $user = $this->userIn($this->centerA, [Permissions::HR_CONTRACTS_CREATE]);

        $response = $this->actingAs($user)->post('/app/hr/employees/'.$employee->id.'/contracts', [
            'contract_number' => 'CT-'.uniqid(),
            'start_date' => now()->toDateString(),
            'status' => 'active',
            'salary_details' => ['basic' => 9000],
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseCount('hr_employee_contracts', 1);
    }

    public function test_a_contract_of_another_branch_cannot_be_updated_or_deleted(): void
    {
        $employee = $this->employeeIn($this->centerB);

        $contract = EmployeeContract::create([
            'tenant_id' => $this->tenant->id,
            'employee_id' => $employee->id,
            'contract_number' => 'CT-'.uniqid(),
            'start_date' => now()->toDateString(),
            'status' => 'active',
            'salary_details' => ['basic' => 9000],
        ]);

        $user = $this->userIn($this->centerA, [
            Permissions::HR_CONTRACTS_UPDATE,
            Permissions::HR_CONTRACTS_DELETE,
        ]);

        $this->actingAs($user)->put('/app/hr/contracts/'.$contract->id, [
            'contract_number' => $contract->contract_number,
            'start_date' => now()->toDateString(),
            'status' => 'cancelled',
        ])->assertForbidden();

        $this->actingAs($user)
            ->delete('/app/hr/contracts/'.$contract->id)
            ->assertForbidden();

        $contract->refresh();
        $this->assertSame('active', $contract->status);
        $this->assertDatabaseHas('hr_employee_contracts', ['id' => $contract->id]);
    }

    public function test_the_tenant_super_admin_manages_every_branch(): void
    {
        $employee = $this->employeeIn($this->centerB);

        $user = $this->userIn(
            $this->centerA,
            [Permissions::HR_CONTRACTS_CREATE, Permissions::HR_CONTRACTS_UPDATE],
            true
        );

        $this->actingAs($user)->post('/app/hr/employees/'.$employee->id.'/contracts', [
            'contract_number' => 'CT-'.uniqid(),
            'start_date' => now()->toDateString(),
            'status' => 'active',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('hr_employee_contracts', 1);
    }

    // ── #13 — attendance ──────────────────────────────────────────────

    public function test_attendance_cannot_be_recorded_for_an_employee_of_another_branch(): void
    {
        $employee = $this->employeeIn($this->centerB);

        $user = $this->userIn($this->centerA, [Permissions::HR_ATTENDANCE_MANAGE]);

        $response = $this->actingAs($user)->post('/app/hr/attendance', [
            'employee_id' => $employee->id,
            'date' => now()->toDateString(),
            'status' => 'absent',
        ]);

        $response->assertSessionHasErrors('employee_id');
        $this->assertDatabaseCount('hr_attendances', 0);
    }

    public function test_attendance_cannot_be_recorded_for_an_employee_of_another_tenant(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherCenter = Center::factory()->create(['tenant_id' => $otherTenant->id]);
        $foreignEmployee = $this->employeeIn($otherCenter);

        $user = $this->userIn($this->centerA, [Permissions::HR_ATTENDANCE_MANAGE]);

        $response = $this->actingAs($user)->post('/app/hr/attendance', [
            'employee_id' => $foreignEmployee->id,
            'date' => now()->toDateString(),
            'status' => 'absent',
        ]);

        $response->assertSessionHasErrors('employee_id');
        $this->assertDatabaseCount('hr_attendances', 0);
    }

    public function test_attendance_for_an_employee_of_the_same_branch_works(): void
    {
        $employee = $this->employeeIn($this->centerA);

        $user = $this->userIn($this->centerA, [Permissions::HR_ATTENDANCE_MANAGE]);

        $response = $this->actingAs($user)->post('/app/hr/attendance', [
            'employee_id' => $employee->id,
            'date' => now()->toDateString(),
            'status' => 'absent',
        ]);

        $response->assertSessionHasNoErrors();

        $attendance = Attendance::where('employee_id', $employee->id)->firstOrFail();

        // The record carries the branch of the employee, not the branch
        // of whoever recorded it.
        $this->assertSame($employee->center_id, $attendance->center_id);
        $this->assertSame($employee->tenant_id, $attendance->tenant_id);
    }

    public function test_the_super_admin_may_record_attendance_for_another_branch(): void
    {
        $employee = $this->employeeIn($this->centerB);

        $user = $this->userIn(
            $this->centerA,
            [Permissions::HR_ATTENDANCE_MANAGE],
            true
        );

        $response = $this->actingAs($user)->post('/app/hr/attendance', [
            'employee_id' => $employee->id,
            'date' => now()->toDateString(),
            'status' => 'absent',
        ]);

        $response->assertSessionHasNoErrors();

        $attendance = Attendance::where('employee_id', $employee->id)->firstOrFail();

        $this->assertSame($employee->center_id, $attendance->center_id);
    }
}
