<?php

declare(strict_types=1);

namespace Tests\Feature\HR;

use App\Models\Center;
use App\Models\HR\PayrollRun;
use App\Models\Tenant;
use App\Models\User;
use App\Policies\HR\PayrollRunPolicy;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PayrollCenterIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Center $centerA;

    protected Center $centerB;

    protected User $userCenterA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create(['status' => 'active']);
        $this->centerA = Center::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);
        $this->centerB = Center::factory()->create([
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

        $this->userCenterA = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->centerA->id,
        ]);
        $this->userCenterA->centers()->attach($this->centerA->id, ['tenant_id' => $this->tenant->id]);
        $this->userCenterA->givePermissionTo($permissions);
    }

    public function test_user_in_center_a_cannot_view_payroll_run_of_center_b(): void
    {
        // Payroll run created for Center B
        $payrollRunCenterB = PayrollRun::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerB->id,
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
            'status' => 'draft',
            'created_by' => $this->userCenterA->id,
        ]);

        // User belongs to Center A and attempts to access Center B's payroll run
        $response = $this->actingAs($this->userCenterA)
            ->get(route('app.hr.payroll.show', $payrollRunCenterB->id));

        // CenterScoped & PayrollRunPolicy protect the record (403 or 404)
        $this->assertTrue(in_array($response->getStatusCode(), [403, 404]));
    }

    public function test_policy_checks_cross_center_access_without_route_binding(): void
    {
        $run = new PayrollRun([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerB->id,
            'status' => 'draft',
        ]);
        $policy = app(PayrollRunPolicy::class);

        $this->assertFalse($policy->view($this->userCenterA, $run));

        $this->userCenterA->centers()->attach($this->centerB->id, ['tenant_id' => $this->tenant->id]);

        $this->assertTrue($policy->view($this->userCenterA, $run));
    }

    public function test_user_in_center_a_can_view_payroll_run_of_center_a(): void
    {
        $payrollRunCenterA = PayrollRun::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerA->id,
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
            'status' => 'draft',
            'created_by' => $this->userCenterA->id,
        ]);

        $response = $this->actingAs($this->userCenterA)
            ->get(route('app.hr.payroll.show', $payrollRunCenterA->id));

        $response->assertOk();
    }

    public function test_user_in_center_a_cannot_approve_payroll_run_of_center_b(): void
    {
        $payrollRunCenterB = PayrollRun::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerB->id,
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
            'status' => 'draft',
            'created_by' => $this->userCenterA->id,
        ]);

        $response = $this->actingAs($this->userCenterA)
            ->put(route('app.hr.payroll.approve', $payrollRunCenterB->id));

        $this->assertTrue(in_array($response->getStatusCode(), [403, 404]));
        $this->assertEquals('draft', $payrollRunCenterB->fresh()->status);
    }

    public function test_approving_payroll_run_logs_activity(): void
    {
        $this->withoutExceptionHandling();

        $payrollRunCenterA = PayrollRun::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerA->id,
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
            'status' => 'draft',
            'created_by' => $this->userCenterA->id,
        ]);

        Log::spy();

        $this->actingAs($this->userCenterA)
            ->put(route('app.hr.payroll.approve', $payrollRunCenterA->id))
            ->assertRedirect();

        $this->assertEquals('approved', $payrollRunCenterA->fresh()->status);

        Log::shouldHaveReceived('notice')
            ->once()
            ->withArgs(function ($message, $context) use ($payrollRunCenterA) {
                return $message === 'payroll.approved'
                    && ($context['payroll_run_id'] ?? null) === $payrollRunCenterA->id
                    && ($context['status'] ?? null) === 'approved';
            });
    }

    public function test_user_can_export_wps_csv_for_own_center_payroll_run(): void
    {
        $payrollRunCenterA = PayrollRun::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerA->id,
            'period_start' => '2026-02-01',
            'period_end' => '2026-02-28',
            'status' => 'approved',
            'created_by' => $this->userCenterA->id,
        ]);

        $response = $this->actingAs($this->userCenterA)
            ->get(route('app.hr.payroll.export-wps', $payrollRunCenterA->id));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $content = $response->streamedContent();
        $this->assertStringContainsString('Employee Number', $content);
        $this->assertStringContainsString('IBAN', $content);
        $this->assertStringContainsString('Net Salary', $content);
    }

    public function test_user_cannot_export_wps_csv_for_other_center_payroll_run(): void
    {
        $payrollRunCenterB = PayrollRun::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerB->id,
            'period_start' => '2026-02-01',
            'period_end' => '2026-02-28',
            'status' => 'approved',
            'created_by' => $this->userCenterA->id,
        ]);

        $this->actingAs($this->userCenterA)
            ->get(route('app.hr.payroll.export-wps', $payrollRunCenterB->id))
            ->assertNotFound();
    }
}
