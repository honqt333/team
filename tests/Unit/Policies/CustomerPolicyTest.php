<?php

namespace Tests\Unit\Policies;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use App\Policies\CustomerPolicy;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CustomerPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Center $center;
    protected User $adminUser;
    protected User $regularUser;
    protected CustomerPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new CustomerPolicy();
        $this->tenant = Tenant::factory()->create();
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);

        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);

        $permissions = [
            Permissions::CUSTOMERS_VIEW,
            Permissions::CUSTOMERS_CREATE,
            Permissions::CUSTOMERS_UPDATE,
            Permissions::CUSTOMERS_DELETE,
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

    public function test_customer_policy_authorizes_customer_management()
    {
        $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id, 'center_id' => $this->center->id]);

        $this->assertTrue($this->policy->viewAny($this->adminUser));
        $this->assertFalse($this->policy->viewAny($this->regularUser));

        $this->assertTrue($this->policy->create($this->adminUser));
        $this->assertFalse($this->policy->create($this->regularUser));

        $this->assertTrue($this->policy->update($this->adminUser, $customer));
        $this->assertFalse($this->policy->update($this->regularUser, $customer));
    }
}
