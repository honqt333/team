<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\Center;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Policies\WorkOrderItemPolicy;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class WorkOrderItemPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Center $center;

    protected User $adminUser;

    protected User $regularUser;

    protected WorkOrderItemPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new WorkOrderItemPolicy;
        $this->tenant = Tenant::factory()->create();
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);

        $permissions = [
            Permissions::WORK_ORDER_ITEMS_VIEW,
            Permissions::WORK_ORDER_ITEMS_CREATE,
            Permissions::WORK_ORDER_ITEMS_UPDATE,
            Permissions::WORK_ORDER_ITEMS_DELETE,
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

    public function test_work_order_item_policy_checks_parent_work_order_status()
    {
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

        $this->assertTrue($this->policy->update($this->adminUser, $activeItem));

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

        $this->assertFalse($this->policy->update($this->adminUser, $doneItem));
    }
}
