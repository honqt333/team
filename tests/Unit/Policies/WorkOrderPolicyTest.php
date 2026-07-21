<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\Center;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkOrder;
use App\Policies\WorkOrderPolicy;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class WorkOrderPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Center $center;

    protected User $adminUser;

    protected User $regularUser;

    protected WorkOrderPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new WorkOrderPolicy;
        $this->tenant = Tenant::factory()->create();
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);

        $permissions = [
            Permissions::WORK_ORDERS_VIEW,
            Permissions::WORK_ORDERS_CREATE,
            Permissions::WORK_ORDERS_UPDATE,
            Permissions::WORK_ORDERS_DELETE,
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

    public function test_work_order_policy_authorizes_management()
    {
        $wo = WorkOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
        ]);

        $this->assertTrue($this->policy->viewAny($this->adminUser));
        $this->assertFalse($this->policy->viewAny($this->regularUser));

        $this->assertTrue($this->policy->create($this->adminUser));
        $this->assertFalse($this->policy->create($this->regularUser));

        $this->assertTrue($this->policy->view($this->adminUser, $wo));
        $this->assertFalse($this->policy->view($this->regularUser, $wo));
    }
}
