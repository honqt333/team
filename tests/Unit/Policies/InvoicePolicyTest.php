<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\User;
use App\Policies\InvoicePolicy;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class InvoicePolicyTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Center $center;

    protected User $adminUser;

    protected User $regularUser;

    protected InvoicePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new InvoicePolicy;
        $this->tenant = Tenant::factory()->create();
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);

        $permissions = [
            Permissions::INVOICES_VIEW,
            Permissions::INVOICES_CREATE,
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

    public function test_view_any_invoice_authorization()
    {
        $this->assertTrue($this->policy->viewAny($this->adminUser));
        $this->assertFalse($this->policy->viewAny($this->regularUser));
    }

    public function test_create_invoice_authorization()
    {
        $this->assertTrue($this->policy->create($this->adminUser));
        $this->assertFalse($this->policy->create($this->regularUser));
    }

    public function test_invoice_policy_blocks_cross_tenant_access()
    {
        $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id, 'center_id' => $this->center->id]);
        $otherTenant = Tenant::factory()->create();
        $otherCenter = Center::factory()->create(['tenant_id' => $otherTenant->id]);
        $otherCustomer = Customer::factory()->create(['tenant_id' => $otherTenant->id, 'center_id' => $otherCenter->id]);

        $otherInvoice = Invoice::create([
            'tenant_id' => $otherTenant->id,
            'center_id' => $otherCenter->id,
            'customer_id' => $otherCustomer->id,
            'invoice_number' => 'INV-OTHER-001',
            'issue_date' => now(),
            'supply_date' => now(),
            'status' => 'valid',
        ]);

        $this->assertFalse($this->policy->view($this->adminUser, $otherInvoice));
    }
}
