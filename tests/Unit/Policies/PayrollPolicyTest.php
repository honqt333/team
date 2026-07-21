<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\Center;
use App\Models\Tenant;
use App\Models\User;
use App\Policies\PayrollPolicy;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PayrollPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Center $center;

    protected User $adminUser;

    protected User $regularUser;

    protected PayrollPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new PayrollPolicy;
        $this->tenant = Tenant::factory()->create();
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);

        $permissions = [
            Permissions::PAYROLL_VIEW,
            Permissions::PAYROLL_PROCESS,
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $this->adminUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);
        $this->adminUser->givePermissionTo($permissions);

        $this->regularUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);
    }

    public function test_payroll_policy_authorizes_payroll_operations()
    {
        $this->assertTrue($this->policy->viewAny($this->adminUser));
        $this->assertFalse($this->policy->viewAny($this->regularUser));

        $this->assertTrue($this->policy->process($this->adminUser));
        $this->assertFalse($this->policy->process($this->regularUser));
    }
}
