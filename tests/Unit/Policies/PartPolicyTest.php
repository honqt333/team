<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\Center;
use App\Models\Part;
use App\Models\Tenant;
use App\Models\User;
use App\Policies\PartPolicy;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PartPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Center $center;

    protected User $adminUser;

    protected User $regularUser;

    protected PartPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new PartPolicy;
        $this->tenant = Tenant::factory()->create();
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);

        $permissions = [
            Permissions::INVENTORY_VIEW,
            Permissions::INVENTORY_MOVES_CREATE,
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

    public function test_part_policy_authorizes_inventory_management()
    {
        $part = Part::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->assertTrue($this->policy->viewAny($this->adminUser));
        $this->assertFalse($this->policy->viewAny($this->regularUser));

        $this->assertTrue($this->policy->create($this->adminUser));
        $this->assertFalse($this->policy->create($this->regularUser));

        $this->assertTrue($this->policy->update($this->adminUser, $part));
        $this->assertFalse($this->policy->update($this->regularUser, $part));
    }
}
