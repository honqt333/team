<?php

declare(strict_types=1);

namespace Tests\Feature\HR;

use App\Models\Center;
use App\Models\HR\AttendanceCorrectionRequest;
use App\Models\HR\Employee;
use App\Models\HR\EmployeeType;
use App\Models\HR\OtherPayment;
use App\Models\HR\PayrollItem;
use App\Models\HR\PayrollRun;
use App\Models\HR\Shift;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantSetupService;
use App\Support\Permissions;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class HrPermissionsAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;

    private Tenant $tenantB;

    private Center $centerA1;

    private Center $centerA2;

    private Center $centerB1;

    private Employee $employeeA1;

    private Employee $employeeA2;

    private Employee $employeeB1;

    private Shift $shiftA;

    private Shift $shiftB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionsSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $setup = app(TenantSetupService::class);

        $this->tenantA = Tenant::factory()->create(['status' => 'active']);
        $this->centerA1 = Center::factory()->create(['tenant_id' => $this->tenantA->id]);
        $this->centerA2 = Center::factory()->create(['tenant_id' => $this->tenantA->id]);
        $setup->seedRolesForTenant($this->tenantA->id);

        $this->tenantB = Tenant::factory()->create(['status' => 'active']);
        $this->centerB1 = Center::factory()->create(['tenant_id' => $this->tenantB->id]);
        $setup->seedRolesForTenant($this->tenantB->id);

        $typeA = EmployeeType::create([
            'tenant_id' => $this->tenantA->id,
            'name_ar' => 'دوام كامل',
            'name_en' => 'Full Time',
            'code' => 'FT-A',
        ]);
        $typeB = EmployeeType::create([
            'tenant_id' => $this->tenantB->id,
            'name_ar' => 'دوام كامل',
            'name_en' => 'Full Time',
            'code' => 'FT-B',
        ]);

        $this->employeeA1 = Employee::create([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
            'employee_type_id' => $typeA->id,
            'employee_number' => 'EMP-A1',
            'name_ar' => 'موظف أ١',
            'name_en' => 'Employee A1',
            'basic_salary' => 5000,
            'is_active' => true,
        ]);

        $this->employeeA2 = Employee::create([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA2->id,
            'employee_type_id' => $typeA->id,
            'employee_number' => 'EMP-A2',
            'name_ar' => 'موظف أ٢',
            'name_en' => 'Employee A2',
            'basic_salary' => 5000,
            'is_active' => true,
        ]);

        $this->employeeB1 = Employee::create([
            'tenant_id' => $this->tenantB->id,
            'center_id' => $this->centerB1->id,
            'employee_type_id' => $typeB->id,
            'employee_number' => 'EMP-B1',
            'name_ar' => 'موظف ب١',
            'name_en' => 'Employee B1',
            'basic_salary' => 6000,
            'is_active' => true,
        ]);

        $this->shiftA = Shift::create([
            'tenant_id' => $this->tenantA->id,
            'name_ar' => 'صباحية أ',
            'name_en' => 'Morning A',
            'start_time' => '08:00',
            'end_time' => '17:00',
        ]);

        $this->shiftB = Shift::create([
            'tenant_id' => $this->tenantB->id,
            'name_ar' => 'صباحية ب',
            'name_en' => 'Morning B',
            'start_time' => '08:00',
            'end_time' => '17:00',
        ]);
    }

    private function userWithRole(string $roleName, ?Tenant $tenant = null, ?Center $center = null): User
    {
        $tenant ??= $this->tenantA;
        $center ??= $this->centerA1;

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
            'email_verified_at' => now(),
            'two_factor_confirmed_at' => now(),
        ]);
        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        $role = Role::where('name', $roleName)
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $user->assignRole($role);

        return $user;
    }

    public function test_hr_dashboard_requires_hr_view_permission(): void
    {
        $hr = $this->userWithRole('hr');
        $branchManager = $this->userWithRole('branch_manager');
        $receptionist = $this->userWithRole('receptionist');
        $technician = $this->userWithRole('technician');
        $accountant = $this->userWithRole('accountant');

        $this->actingAs($hr)->get('/app/hr')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('HR/Index')
                ->has('stats')
            );
        $this->actingAs($branchManager)->get('/app/hr')->assertOk();

        $this->actingAs($receptionist)->get('/app/hr')->assertForbidden();
        $this->actingAs($technician)->get('/app/hr')->assertForbidden();
        $this->actingAs($accountant)->get('/app/hr')->assertForbidden();
    }

    public function test_employee_reports_and_export_require_employees_view_permission(): void
    {
        $hr = $this->userWithRole('hr');
        $receptionist = $this->userWithRole('receptionist');
        $technician = $this->userWithRole('technician');
        $accountant = $this->userWithRole('accountant');

        $this->actingAs($hr)->get('/app/reports/employees')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Reports/Employees'));
        $exportResponse = $this->actingAs($hr)->get('/app/reports/employees/export')->assertOk();
        $csv = $exportResponse->streamedContent();
        $this->assertStringNotContainsString('EMP-B1', $csv);

        foreach ([$receptionist, $technician, $accountant] as $unauthorized) {
            $this->actingAs($unauthorized)->get('/app/reports/employees')->assertForbidden();
            $this->actingAs($unauthorized)->get('/app/reports/employees/export')->assertForbidden();
        }
    }

    public function test_employee_photo_and_shift_updates_require_employees_update_permission(): void
    {
        Storage::fake('public');

        $hr = $this->userWithRole('hr');
        $branchManager = $this->userWithRole('branch_manager');
        $technician = $this->userWithRole('technician');
        $receptionist = $this->userWithRole('receptionist');

        foreach ([$technician, $receptionist, $branchManager] as $unauthorized) {
            $this->actingAs($unauthorized)
                ->post(route('app.hr.employees.upload-photo', $this->employeeA1), [
                    'photo' => UploadedFile::fake()->image('avatar.jpg'),
                ])
                ->assertForbidden();

            $this->actingAs($unauthorized)
                ->put(route('app.hr.employees.default-shift.update', $this->employeeA1), [
                    'default_shift_id' => $this->shiftA->id,
                ])
                ->assertForbidden();

            $this->actingAs($unauthorized)
                ->put(route('app.hr.employees.weekly-schedule.update', $this->employeeA1), [
                    'schedule' => [0 => $this->shiftA->id],
                ])
                ->assertForbidden();
        }

        $this->actingAs($hr)
            ->post(route('app.hr.employees.upload-photo', $this->employeeA1), [
                'photo' => UploadedFile::fake()->image('avatar.jpg'),
            ])
            ->assertRedirect();

        $this->assertNotNull($this->employeeA1->fresh()->photo_path);

        $this->actingAs($hr)
            ->put(route('app.hr.employees.default-shift.update', $this->employeeA1), [
                'default_shift_id' => $this->shiftA->id,
            ])
            ->assertRedirect();

        $this->assertSame($this->shiftA->id, $this->employeeA1->fresh()->default_shift_id);

        // Cross-tenant shift ID cannot be attached
        $this->actingAs($hr)
            ->put(route('app.hr.employees.default-shift.update', $this->employeeA1), [
                'default_shift_id' => $this->shiftB->id,
            ])
            ->assertSessionHasErrors('default_shift_id');
    }

    public function test_other_payments_enforce_granular_payment_permissions(): void
    {
        $hr = $this->userWithRole('hr');
        $accountant = $this->userWithRole('accountant');
        $branchManager = $this->userWithRole('branch_manager');
        $technician = $this->userWithRole('technician');

        $payment = OtherPayment::create([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
            'employee_id' => $this->employeeA1->id,
            'created_by' => $hr->id,
            'title' => 'Bonus',
            'amount' => 500,
            'type' => 'other',
            'status' => OtherPayment::STATUS_PENDING,
        ]);

        // Technician has no payment permissions
        $this->actingAs($technician)->getJson(route('app.hr.payroll.other-payments.index'))->assertForbidden();
        $this->actingAs($technician)->postJson(route('app.hr.payroll.other-payments.store'), [
            'employee_id' => $this->employeeA1->id,
            'title' => 'Hack',
            'amount' => 100,
        ])->assertForbidden();

        // Accountant can view and approve, but NOT create/update/delete
        $this->actingAs($accountant)->getJson(route('app.hr.payroll.other-payments.index'))
            ->assertOk()
            ->assertJsonPath('data.0.id', $payment->id);
        $this->actingAs($accountant)->postJson(route('app.hr.payroll.other-payments.store'), [
            'employee_id' => $this->employeeA1->id,
            'title' => 'Bonus 2',
            'amount' => 100,
        ])->assertForbidden();
        $this->actingAs($accountant)->deleteJson(route('app.hr.payroll.other-payments.destroy', $payment))->assertForbidden();
        $this->actingAs($accountant)->putJson(route('app.hr.payroll.other-payments.approve', $payment))
            ->assertOk()
            ->assertJsonPath('payment.status', OtherPayment::STATUS_APPROVED);
        $this->assertSame(OtherPayment::STATUS_APPROVED, $payment->fresh()->status);
        $this->assertSame($accountant->id, $payment->fresh()->approved_by);

        // Branch manager can create/update/approve, but NOT delete
        $this->actingAs($branchManager)->deleteJson(route('app.hr.payroll.other-payments.destroy', $payment))->assertForbidden();
        $this->assertSame(OtherPayment::STATUS_APPROVED, $payment->fresh()->status);

        // HR can delete
        $this->actingAs($hr)->deleteJson(route('app.hr.payroll.other-payments.destroy', $payment))->assertOk();
        $this->assertSoftDeleted('hr_other_payments', ['id' => $payment->id]);
    }

    public function test_employee_payroll_and_payslip_require_payroll_view_permission(): void
    {
        $hr = $this->userWithRole('hr');
        $accountant = $this->userWithRole('accountant');
        $technician = $this->userWithRole('technician');

        // User with hr.employees.view ONLY (without hr.payroll.view) must be denied payroll endpoints
        $employeesOnlyUser = User::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'current_center_id' => $this->centerA1->id,
            'email_verified_at' => now(),
            'two_factor_confirmed_at' => now(),
        ]);
        $employeesOnlyUser->centers()->attach($this->centerA1->id, ['tenant_id' => $this->tenantA->id]);
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenantA->id);
        $employeesOnlyUser->givePermissionTo(Permissions::HR_EMPLOYEES_VIEW);

        $run = PayrollRun::create([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'status' => 'draft',
            'created_by' => $hr->id,
        ]);

        $item = PayrollItem::create([
            'payroll_run_id' => $run->id,
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
            'employee_id' => $this->employeeA1->id,
            'base_salary' => 5000,
            'total_allowances' => 0,
            'total_deductions' => 0,
            'gosi_amount' => 0,
            'net_salary' => 5000,
            'created_by' => $hr->id,
        ]);

        $this->actingAs($employeesOnlyUser)->getJson(route('app.hr.employees.payroll', $this->employeeA1))->assertForbidden();
        $this->actingAs($employeesOnlyUser)->get(route('app.hr.payroll.payslip', $item))->assertForbidden();
        $this->actingAs($technician)->getJson(route('app.hr.employees.payroll', $this->employeeA1))->assertForbidden();

        $this->actingAs($accountant)->getJson(route('app.hr.employees.payroll', $this->employeeA1))
            ->assertOk()
            ->assertJsonPath('payrollItems.data.0.id', $item->id);
        $this->actingAs($hr)->get(route('app.hr.payroll.payslip', $item))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('HR/Payroll/PrintPaySlip')
                ->where('payrollItem.id', $item->id)
            );
    }

    public function test_cross_tenant_and_cross_center_idor_is_blocked(): void
    {
        $hrA = $this->userWithRole('hr', $this->tenantA, $this->centerA1);
        $hrB = $this->userWithRole('hr', $this->tenantB, $this->centerB1);

        $correctionA2 = AttendanceCorrectionRequest::create([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA2->id,
            'employee_id' => $this->employeeA2->id,
            'date' => now()->toDateString(),
            'reason' => 'Forgot',
            'status' => AttendanceCorrectionRequest::STATUS_PENDING,
        ]);

        $correctionB1 = AttendanceCorrectionRequest::create([
            'tenant_id' => $this->tenantB->id,
            'center_id' => $this->centerB1->id,
            'employee_id' => $this->employeeB1->id,
            'date' => now()->toDateString(),
            'reason' => 'Forgot B',
            'status' => AttendanceCorrectionRequest::STATUS_PENDING,
        ]);

        // Cross-tenant correction approval/rejection blocked (404 via CenterScoped or 403 via controller guard)
        $approveCrossTenant = $this->actingAs($hrA)
            ->put(route('app.hr.attendance.corrections.approve', $correctionB1));
        $this->assertContains($approveCrossTenant->status(), [403, 404]);

        $rejectCrossTenant = $this->actingAs($hrA)
            ->put(route('app.hr.attendance.corrections.reject', $correctionB1));
        $this->assertContains($rejectCrossTenant->status(), [403, 404]);

        // Cross-center correction approval/rejection blocked for non-super_admin
        $approveCrossCenter = $this->actingAs($hrA)
            ->put(route('app.hr.attendance.corrections.approve', $correctionA2));
        $this->assertContains($approveCrossCenter->status(), [403, 404]);

        // Cross-tenant employee payroll blocked
        $this->actingAs($hrB)
            ->getJson(route('app.hr.employees.payroll', $this->employeeA1))
            ->assertNotFound();

        $this->assertSame(AttendanceCorrectionRequest::STATUS_PENDING, $correctionA2->fresh()->status);
        $this->assertSame(AttendanceCorrectionRequest::STATUS_PENDING, $correctionB1->fresh()->status);
    }

    public function test_other_payment_preserves_historical_center_id_and_isolates_null_center_records(): void
    {
        $hrCenter1 = $this->userWithRole('hr', $this->tenantA, $this->centerA1);
        $hrCenter2 = $this->userWithRole('hr', $this->tenantA, $this->centerA2);

        $response = $this->actingAs($hrCenter1)
            ->postJson(route('app.hr.payroll.other-payments.store'), [
                'employee_id' => $this->employeeA1->id,
                'title' => 'Historical Center Bonus',
                'amount' => 350,
                'type' => 'other',
            ]);

        $response->assertOk();

        $paymentId = (int) $response->json('payment.id');
        $payment = OtherPayment::findOrFail($paymentId);

        $this->assertSame($this->tenantA->id, (int) $payment->tenant_id);
        $this->assertSame($this->centerA1->id, (int) $payment->center_id);

        // Transfer employee to centerA2 later
        $this->employeeA1->update(['center_id' => $this->centerA2->id]);

        // Historical payment keeps its original center_id
        $this->assertSame($this->centerA1->id, (int) $payment->fresh()->center_id);

        // Center 1 still sees it; Center 2 does NOT see or mutate it
        $this->actingAs($hrCenter1)
            ->getJson(route('app.hr.payroll.other-payments.index'))
            ->assertOk()
            ->assertJsonFragment(['id' => $paymentId]);

        $this->actingAs($hrCenter2)
            ->getJson(route('app.hr.payroll.other-payments.index'))
            ->assertOk()
            ->assertJsonMissing(['id' => $paymentId]);

        $this->actingAs($hrCenter2)
            ->putJson(route('app.hr.payroll.other-payments.approve', $payment))
            ->assertForbidden();

        // Legacy record with center_id = null is not exposed or mutable to branch-scoped users
        $legacyNullPayment = OtherPayment::create([
            'tenant_id' => $this->tenantA->id,
            'center_id' => null,
            'employee_id' => $this->employeeA1->id,
            'created_by' => $hrCenter1->id,
            'title' => 'Legacy Null Center Bonus',
            'amount' => 200,
            'type' => 'other',
            'status' => OtherPayment::STATUS_PENDING,
        ]);

        $this->actingAs($hrCenter1)
            ->getJson(route('app.hr.payroll.other-payments.index'))
            ->assertOk()
            ->assertJsonMissing(['id' => $legacyNullPayment->id]);

        $this->actingAs($hrCenter2)
            ->getJson(route('app.hr.payroll.other-payments.index'))
            ->assertOk()
            ->assertJsonMissing(['id' => $legacyNullPayment->id]);

        $this->actingAs($hrCenter2)
            ->putJson(route('app.hr.payroll.other-payments.approve', $legacyNullPayment))
            ->assertForbidden();
    }
}
