<?php

namespace Tests\Unit\Policies;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Quote;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Policies\QuotePolicy;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class QuotePolicyTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Center $center;
    protected User $adminUser;
    protected User $regularUser;
    protected QuotePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new QuotePolicy();
        $this->tenant = Tenant::factory()->create();
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);

        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);

        $permissions = [
            Permissions::QUOTES_VIEW,
            Permissions::QUOTES_CREATE,
            Permissions::QUOTES_UPDATE,
            Permissions::QUOTES_DELETE,
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

    public function test_quote_policy_authorizes_management()
    {
        $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id, 'center_id' => $this->center->id]);
        $vehicle = Vehicle::factory()->create(['tenant_id' => $this->tenant->id, 'customer_id' => $customer->id]);

        $quote = Quote::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'code' => 'QT-000001',
            'status' => 'draft',
        ]);

        $this->assertTrue($this->policy->viewAny($this->adminUser));
        $this->assertFalse($this->policy->viewAny($this->regularUser));

        $this->assertTrue($this->policy->create($this->adminUser));
        $this->assertFalse($this->policy->create($this->regularUser));

        $this->assertTrue($this->policy->view($this->adminUser, $quote));
        $this->assertFalse($this->policy->view($this->regularUser, $quote));
    }
}
