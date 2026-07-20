<?php

namespace Tests\Feature\Auth;

use App\Models\Center;
use App\Models\Customer;
use App\Models\HR\Leave;
use App\Models\HR\PayrollRun;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Policies\CustomerPolicy;
use App\Policies\LeavePolicy;
use App\Policies\PaymentPolicy;
use App\Policies\PayrollPolicy;
use App\Policies\WorkOrderItemPolicy;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PolicyCoverageTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Center $center;
    protected User $adminUser;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        
        $permissions = [
            Permissions::PAYMENTS_VIEW,
            Permissions::PAYMENTS_CREATE,
            Permissions::PAYMENTS_UPDATE,
            Permissions::PAYMENTS_DELETE,
            Permissions::PAYMENTS_REFUND,
            Permissions::LEAVES_VIEW,
            Permissions::LEAVES_CREATE,
            Permissions::LEAVES_APPROVE,
            Permissions::PAYROLL_VIEW,
            Permissions::PAYROLL_PROCESS,
            Permissions::WORK_ORDER_ITEMS_VIEW,
            Permissions::WORK_ORDER_ITEMS_CREATE,
            Permissions::WORK_ORDER_ITEMS_UPDATE,
            Permissions::WORK_ORDER_ITEMS_DELETE,
            Permissions::CUSTOMERS_VIEW,
            Permissions::CUSTOMERS_CREATE,
            Permissions::CUSTOMERS_UPDATE,
            Permissions::CUSTOMERS_DELETE,
        ];

        $this->tenant = Tenant::factory()->create();
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);

        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);

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

    public function test_payment_policy_permits_authorized_and_denies_unauthorized()
    {
        $policy = new PaymentPolicy();
        $payment = Payment::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'type' => 'payment',
            'amount' => 100.00,
            'payment_date' => now(),
        ]);

        $this->assertTrue($policy->viewAny($this->adminUser));
        $this->assertFalse($policy->viewAny($this->regularUser));

        $this->assertTrue($policy->create($this->adminUser));
        $this->assertFalse($policy->create($this->regularUser));

        $this->assertTrue($policy->refund($this->adminUser, $payment));
        $this->assertFalse($policy->refund($this->regularUser, $payment));
    }

    public function test_payment_policy_blocks_cross_tenant_access()
    {
        $policy = new PaymentPolicy();
        $otherTenant = Tenant::factory()->create();
        $otherCenter = Center::factory()->create(['tenant_id' => $otherTenant->id]);
        $otherPayment = Payment::create([
            'tenant_id' => $otherTenant->id,
            'center_id' => $otherCenter->id,
            'type' => 'payment',
            'amount' => 100.00,
            'payment_date' => now(),
        ]);

        $this->assertFalse($policy->view($this->adminUser, $otherPayment));
        $this->assertFalse($policy->update($this->adminUser, $otherPayment));
        $this->assertFalse($policy->refund($this->adminUser, $otherPayment));
    }

    public function test_leave_policy_authorizes_leave_management()
    {
        $policy = new LeavePolicy();

        $employee = \App\Models\HR\Employee::create([
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

        $this->assertTrue($policy->viewAny($this->adminUser));
        $this->assertFalse($policy->viewAny($this->regularUser));

        $this->assertTrue($policy->approve($this->adminUser, $leave));
        $this->assertFalse($policy->approve($this->regularUser, $leave));
    }

    public function test_payroll_policy_authorizes_payroll_operations()
    {
        $policy = new PayrollPolicy();

        $this->assertTrue($policy->viewAny($this->adminUser));
        $this->assertFalse($policy->viewAny($this->regularUser));

        $this->assertTrue($policy->process($this->adminUser));
        $this->assertFalse($policy->process($this->regularUser));
    }

    public function test_work_order_item_policy_checks_parent_work_order_status()
    {
        $policy = new WorkOrderItemPolicy();
        
        $activeWo = WorkOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'status' => 'in_progress',
        ]);
        $activeItem = WorkOrderItem::create([
            'work_order_id' => $activeWo->id,
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'title' => 'خدمة صيانة',
            'qty' => 1,
            'unit_price' => 100,
        ]);
        $activeItem->setRelation('workOrder', $activeWo);

        $this->assertTrue($policy->update($this->adminUser, $activeItem));

        $doneWo = WorkOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'status' => 'done',
        ]);
        $doneItem = WorkOrderItem::create([
            'work_order_id' => $doneWo->id,
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'title' => 'خدمة منتهية',
            'qty' => 1,
            'unit_price' => 100,
        ]);
        $doneItem->setRelation('workOrder', $doneWo);

        $this->assertFalse($policy->update($this->adminUser, $doneItem));
    }

    public function test_customer_policy_authorizes_customer_management()
    {
        $policy = new CustomerPolicy();
        $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id, 'center_id' => $this->center->id]);

        $this->assertTrue($policy->viewAny($this->adminUser));
        $this->assertFalse($policy->viewAny($this->regularUser));

        $this->assertTrue($policy->update($this->adminUser, $customer));
        $this->assertFalse($policy->update($this->regularUser, $customer));
    }
}
