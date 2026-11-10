<?php

declare(strict_types=1);

namespace Tests\Feature\HR;

use App\Models\Center;
use App\Models\HR\PayrollRun;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PayrollIndexCrudTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Center $centerA;

    protected Center $centerB;

    protected User $user;

    protected User $unauthorizedUser;

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

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->centerA->id,
        ]);
        $this->user->centers()->attach($this->centerA->id, ['tenant_id' => $this->tenant->id]);
        $this->user->givePermissionTo($permissions);

        $this->unauthorizedUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->centerA->id,
        ]);
        $this->unauthorizedUser->centers()->attach($this->centerA->id, ['tenant_id' => $this->tenant->id]);
    }

    public function test_unauthorized_user_cannot_view_payroll_index(): void
    {
        $response = $this->actingAs($this->unauthorizedUser)
            ->get(route('app.hr.payroll.index'));

        $response->assertStatus(403);
    }

    public function test_authorized_user_can_view_payroll_index(): void
    {
        PayrollRun::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerA->id,
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('app.hr.payroll.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('HR/Payroll/Index')
            ->has('payrollRuns.data', 1)
            ->has('employees')
            ->has('centers')
            ->has('filters')
        );
    }

    public function test_payroll_runs_are_scoped_to_current_center(): void
    {
        PayrollRun::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerA->id,
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        PayrollRun::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerB->id,
            'period_start' => '2026-02-01',
            'period_end' => '2026-02-28',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('app.hr.payroll.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('HR/Payroll/Index')
            ->has('payrollRuns.data', 1)
            ->where('payrollRuns.data.0.center_id', $this->centerA->id)
        );
    }

    public function test_status_filter_filters_payroll_runs(): void
    {
        PayrollRun::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerA->id,
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        PayrollRun::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerA->id,
            'period_start' => '2026-02-01',
            'period_end' => '2026-02-28',
            'status' => 'paid',
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('app.hr.payroll.index', ['status' => 'paid']));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('HR/Payroll/Index')
            ->has('payrollRuns.data', 1)
            ->where('payrollRuns.data.0.status', 'paid')
        );
    }

    public function test_search_filter_filters_payroll_runs(): void
    {
        PayrollRun::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerA->id,
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-31',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        PayrollRun::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerA->id,
            'period_start' => '2025-11-01',
            'period_end' => '2025-11-30',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('app.hr.payroll.index', ['search' => '2026-03']));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('HR/Payroll/Index')
            ->has('payrollRuns.data', 1)
            ->where('payrollRuns.data.0.period_start', fn ($val) => str_starts_with($val, '2026-03-01'))
        );
    }

    public function test_whitelisted_sorting_works(): void
    {
        PayrollRun::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerA->id,
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        PayrollRun::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerA->id,
            'period_start' => '2026-02-01',
            'period_end' => '2026-02-28',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('app.hr.payroll.index', ['sort' => 'period_start', 'direction' => 'asc']));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('HR/Payroll/Index')
            ->has('payrollRuns.data', 2)
            ->where('payrollRuns.data.0.period_start', fn ($val) => str_starts_with($val, '2026-01-01'))
            ->where('payrollRuns.data.1.period_start', fn ($val) => str_starts_with($val, '2026-02-01'))
        );
    }
}
