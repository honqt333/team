<?php

declare(strict_types=1);

namespace Tests\Feature\HR;

use App\Models\Center;
use App\Models\CompanyTransaction;
use App\Models\HR\Employee;
use App\Models\HR\PayrollItem;
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

class PayrollAccountingTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Center $centerA;

    private Center $centerB;

    private User $authorizedUser;

    private User $unauthorizedUser;

    private Employee $employeeA;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();

        $this->tenant = Tenant::factory()->create();
        $this->centerA = Center::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->centerB = Center::factory()->create(['tenant_id' => $this->tenant->id]);

        $nationality = Nationality::firstOrCreate(
            ['name_ar' => 'سعودي'],
            ['name_en' => 'Saudi', 'is_active' => true]
        );

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
        Permission::firstOrCreate(['name' => Permissions::HR_PAYROLL_VIEW, 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => Permissions::HR_PAYROLL_UPDATE, 'guard_name' => 'web']);

        $this->authorizedUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->centerA->id,
        ]);
        $this->authorizedUser->centers()->attach($this->centerA->id, ['tenant_id' => $this->tenant->id]);
        $this->authorizedUser->givePermissionTo([
            Permissions::HR_PAYROLL_VIEW,
            Permissions::HR_PAYROLL_UPDATE,
        ]);

        $this->unauthorizedUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->centerA->id,
        ]);
        $this->unauthorizedUser->centers()->attach($this->centerA->id, ['tenant_id' => $this->tenant->id]);

        $this->employeeA = Employee::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerA->id,
            'employee_number' => 'EMP-PAY-A',
            'name_ar' => 'موظف رواتب أ',
            'name_en' => 'Payroll Emp A',
            'phone' => '0503334455',
            'email' => 'payempa@example.com',
            'gender' => 'male',
            'nationality_id' => $nationality->id,
            'base_salary' => 7000,
            'status' => 'active',
        ]);
    }

    public function test_unauthorized_user_cannot_approve_payroll_run(): void
    {
        $payrollRun = PayrollRun::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerA->id,
            'period_start' => '2026-11-01',
            'period_end' => '2026-11-30',
            'status' => 'draft',
            'created_by' => $this->authorizedUser->id,
        ]);

        $this->actingAs($this->unauthorizedUser)
            ->put(route('app.hr.payroll.approve', $payrollRun->id))
            ->assertForbidden();

        $this->assertEquals('draft', $payrollRun->fresh()->status);
        $this->assertDatabaseCount('company_transactions', 0);
    }

    public function test_approving_payroll_posts_branch_expense_and_mark_paid_is_idempotent(): void
    {
        $payrollRun = PayrollRun::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerA->id,
            'period_start' => '2026-11-01',
            'period_end' => '2026-11-30',
            'status' => 'draft',
            'created_by' => $this->authorizedUser->id,
        ]);

        PayrollItem::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerA->id,
            'payroll_run_id' => $payrollRun->id,
            'employee_id' => $this->employeeA->id,
            'base_salary' => 7000,
            'gosi_rate' => 9.75,
            'gosi_amount' => 682.50,
            'total_allowances' => 1500,
            'total_deductions' => 682.50,
            'net_salary' => 7817.50,
        ]);

        // 1. Approve payroll run -> creates CompanyTransaction expense for centerA
        $this->actingAs($this->authorizedUser)
            ->put(route('app.hr.payroll.approve', $payrollRun->id))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $payrollRun->refresh();
        $this->assertEquals('approved', $payrollRun->status);
        $this->assertNotNull($payrollRun->company_transaction_id);
        $this->assertNotNull($payrollRun->accounting_posted_at);

        $tx = CompanyTransaction::findOrFail($payrollRun->company_transaction_id);
        $this->assertEquals($this->tenant->id, $tx->tenant_id);
        $this->assertEquals($this->centerA->id, $tx->center_id);
        $this->assertEquals('expense', $tx->transaction_type);
        $this->assertEquals('approved', $tx->status);
        $this->assertEquals('7817.50', (string) $tx->total_amount);

        // 2. Mark paid -> updates payment_date without creating a duplicate transaction
        $this->actingAs($this->authorizedUser)
            ->put(route('app.hr.payroll.mark-paid', $payrollRun->id), [
                'payment_date' => '2026-11-28',
                'payment_method' => 'bank_transfer',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertEquals('paid', $payrollRun->fresh()->status);
        $this->assertDatabaseCount('company_transactions', 1);
        $this->assertEquals('2026-11-28', $tx->fresh()->transaction_date?->format('Y-m-d'));
    }

    public function test_user_cannot_approve_other_center_payroll_run(): void
    {
        $otherCenterRun = PayrollRun::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerB->id,
            'period_start' => '2026-11-01',
            'period_end' => '2026-11-30',
            'status' => 'draft',
            'created_by' => $this->authorizedUser->id,
        ]);

        $this->actingAs($this->authorizedUser)
            ->put(route('app.hr.payroll.approve', $otherCenterRun->id))
            ->assertNotFound();
    }
}
