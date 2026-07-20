<?php

namespace Tests\Unit\Policies;

use App\Models\Center;
use App\Models\HR\Employee;
use App\Models\HR\Leave;
use App\Models\Tenant;
use App\Models\User;
use App\Policies\LeavePolicy;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LeavePolicyTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Center $center;
    protected User $adminUser;
    protected User $regularUser;
    protected LeavePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new LeavePolicy();
        $this->tenant = Tenant::factory()->create();
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);

        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);

        $permissions = [
            Permissions::LEAVES_VIEW,
            Permissions::LEAVES_CREATE,
            Permissions::LEAVES_APPROVE,
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

    public function test_leave_policy_authorizes_leave_management()
    {
        $employee = Employee::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name_ar' => 'أحمد الموظف',
            'phone' => '0501112233',
        ]);

        $leave = Leave::create([
            'tenant_id' => $this->tenant->id,
            'employee_id' => $employee->id,
            'type' => 'annual',
            'start_date' => now(),
            'end_date' => now()->addDays(2),
            'days' => 2,
            'status' => 'pending',
        ]);

        $this->assertTrue($this->policy->viewAny($this->adminUser));
        $this->assertFalse($this->policy->viewAny($this->regularUser));

        $this->assertTrue($this->policy->approve($this->adminUser, $leave));
        $this->assertFalse($this->policy->approve($this->regularUser, $leave));
    }
}
