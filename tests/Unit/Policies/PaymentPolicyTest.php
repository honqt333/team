<?php

namespace Tests\Unit\Policies;

use App\Models\Center;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Policies\PaymentPolicy;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PaymentPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Center $center;
    protected User $adminUser;
    protected User $regularUser;
    protected PaymentPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new PaymentPolicy();
        $this->tenant = Tenant::factory()->create();
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);

        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);

        $permissions = [
            Permissions::PAYMENTS_VIEW,
            Permissions::PAYMENTS_CREATE,
            Permissions::PAYMENTS_UPDATE,
            Permissions::PAYMENTS_DELETE,
            Permissions::PAYMENTS_REFUND,
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

    public function test_view_any_payment_authorization()
    {
        $this->assertTrue($this->policy->viewAny($this->adminUser));
        $this->assertFalse($this->policy->viewAny($this->regularUser));
    }

    public function test_create_payment_authorization()
    {
        $this->assertTrue($this->policy->create($this->adminUser));
        $this->assertFalse($this->policy->create($this->regularUser));
    }

    public function test_refund_payment_authorization()
    {
        $payment = Payment::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'type' => 'payment',
            'amount' => 100.00,
            'payment_date' => now(),
        ]);

        $this->assertTrue($this->policy->refund($this->adminUser, $payment));
        $this->assertFalse($this->policy->refund($this->regularUser, $payment));
    }

    public function test_payment_policy_blocks_cross_tenant_access()
    {
        $otherTenant = Tenant::factory()->create();
        $otherCenter = Center::factory()->create(['tenant_id' => $otherTenant->id]);
        $otherPayment = Payment::create([
            'tenant_id' => $otherTenant->id,
            'center_id' => $otherCenter->id,
            'type' => 'payment',
            'amount' => 100.00,
            'payment_date' => now(),
        ]);

        $this->assertFalse($this->policy->view($this->adminUser, $otherPayment));
        $this->assertFalse($this->policy->update($this->adminUser, $otherPayment));
        $this->assertFalse($this->policy->refund($this->adminUser, $otherPayment));
    }
}
