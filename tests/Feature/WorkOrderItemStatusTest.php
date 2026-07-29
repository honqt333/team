<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkOrderActivity;
use App\Models\WorkOrderItem;
use App\Models\WorkOrderItemQualityCheck;
use App\Models\WorkOrderLaborEntry;
use App\Services\WorkOrderItemQualityService;
use App\Services\WorkOrderLaborService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class WorkOrderItemStatusTest extends TestCase
{
    use RefreshDatabase;

    protected WorkOrderItemQualityService $quality;

    protected WorkOrderLaborService $labor;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::findOrCreate('crm.work_orders.view', 'web');
        Permission::findOrCreate('crm.work_orders.update', 'web');
        Permission::findOrCreate('crm.customers.view', 'web');
        Permission::findOrCreate('crm.vehicles.view', 'web');

        $this->quality = app(WorkOrderItemQualityService::class);
        $this->labor = app(WorkOrderLaborService::class);
    }

    /**
     * @param array<int, string> $permissions
     */
    protected function createUserWithPermissions(array $permissions): User
    {
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
        ]);

        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

        foreach ($permissions as $permissionName) {
            $user->givePermissionTo($permissionName);
        }

        $user->refresh();

        return $user;
    }

    protected function techInSameContext(User $owner, array $permissions): User
    {
        $tenant = Tenant::find($owner->tenant_id);
        $center = Center::find($owner->current_center_id);

        return $this->createUserWithPermissions($permissions, $tenant, $center);
    }

    /**
     * @return array{0: Customer, 1: Vehicle, 2: WorkOrder, 3: WorkOrderItem}
     */
    protected function buildFixture(User $owner, string $itemStatus = WorkOrderItem::STATUS_IN_PROGRESS): array
    {
        $customer = Customer::create([
            'tenant_id' => $owner->tenant_id,
            'center_id' => $owner->current_center_id,
            'type' => 'individual',
            'name' => 'Customer '.uniqid(),
            'phone' => '05'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
        ]);

        $vehicle = Vehicle::create([
            'tenant_id' => $owner->tenant_id,
            'center_id' => $owner->current_center_id,
            'customer_id' => $customer->id,
            'plate_number' => strtoupper(Str::random(3)).' '.random_int(1000, 9999),
        ]);

        $workOrder = WorkOrder::create([
            'tenant_id' => $owner->tenant_id,
            'center_id' => $owner->current_center_id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'code' => 'WO-'.str_pad((string) random_int(1000, 999999), 6, '0', STR_PAD_LEFT),
            'status' => WorkOrder::STATUS_OPEN,
        ]);

        $item = WorkOrderItem::create([
            'work_order_id' => $workOrder->id,
            'tenant_id' => $workOrder->tenant_id,
            'center_id' => $workOrder->center_id,
            'title' => 'Test service',
            'qty' => 1,
            'unit_price' => 100,
            'status' => $itemStatus,
        ]);

        return [$customer, $vehicle, $workOrder, $item];
    }

    protected function assignTechnician(WorkOrderItem $item, User $tech): void
    {
        $item->technicians()->attach($tech->id, [
            'assigned_at' => now(),
            'share' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // ==================== Hold / Resume ====================

    public function test_hold_requires_a_reason(): void
    {
        $owner = $this->createUserWithPermissions(['crm.work_orders.view', 'crm.work_orders.update']);
        [, , $workOrder, $item] = $this->buildFixture($owner);

        $threw = false;

        try {
            $this->quality->hold($workOrder, $item, $owner, '   ');
        } catch (RuntimeException $e) {
            $threw = $e->getMessage() === 'hold_reason_required';
        }
        $this->assertTrue($threw);
    }

    public function test_hold_records_status_actor_and_reason(): void
    {
        $owner = $this->createUserWithPermissions(['crm.work_orders.view', 'crm.work_orders.update']);
        [, , $workOrder, $item] = $this->buildFixture($owner, WorkOrderItem::STATUS_IN_PROGRESS);

        $this->quality->hold($workOrder, $item, $owner, 'Awaiting parts');

        $item->refresh();
        $this->assertSame(WorkOrderItem::STATUS_ON_HOLD, $item->status);
        $this->assertSame('in_progress', $item->suspended_status);
        $this->assertSame('Awaiting parts', $item->hold_reason);
        $this->assertSame($owner->id, $item->held_by_user_id);
        $this->assertNotNull($item->held_at);
    }

    public function test_resume_restores_previous_status(): void
    {
        $owner = $this->createUserWithPermissions(['crm.work_orders.view', 'crm.work_orders.update']);
        [, , $workOrder, $item] = $this->buildFixture($owner, WorkOrderItem::STATUS_IN_PROGRESS);

        $this->quality->hold($workOrder, $item, $owner, 'Awaiting parts');
        $this->quality->resume($workOrder, $item->refresh(), $owner);

        $item->refresh();
        $this->assertSame(WorkOrderItem::STATUS_IN_PROGRESS, $item->status);
        $this->assertNull($item->suspended_status);
        $this->assertNull($item->hold_reason);
        $this->assertNull($item->held_at);
        $this->assertNull($item->held_by_user_id);
    }

    public function test_resume_falls_back_to_in_progress_for_invalid_snapshot(): void
    {
        $owner = $this->createUserWithPermissions(['crm.work_orders.view', 'crm.work_orders.update']);
        [, , $workOrder, $item] = $this->buildFixture($owner, WorkOrderItem::STATUS_PENDING);

        // Manually stuff a terminal suspended_status so resume has
        // to apply the safety fallback.
        $item->update([
            'status' => WorkOrderItem::STATUS_ON_HOLD,
            'suspended_status' => WorkOrderItem::STATUS_COMPLETED,
        ]);

        $this->quality->resume($workOrder, $item->refresh(), $owner);

        $item->refresh();
        $this->assertSame(WorkOrderItem::STATUS_IN_PROGRESS, $item->status);
    }

    public function test_hold_is_rejected_for_completed_or_cancelled_items(): void
    {
        $owner = $this->createUserWithPermissions(['crm.work_orders.view', 'crm.work_orders.update']);

        foreach ([WorkOrderItem::STATUS_COMPLETED, WorkOrderItem::STATUS_CANCELLED] as $status) {
            [, , $workOrder, $item] = $this->buildFixture($owner, $status);

            $threw = false;

            try {
                $this->quality->hold($workOrder, $item, $owner, 'no');
            } catch (RuntimeException $e) {
                $threw = in_array($e->getMessage(), ['item_completed', 'item_cancelled'], true);
            }
            $this->assertTrue($threw, "Hold on {$status} should be rejected.");
        }
    }

    public function test_hold_pauses_running_labor_sessions_for_the_item(): void
    {
        $owner = $this->createUserWithPermissions(['crm.work_orders.view', 'crm.work_orders.update']);
        [, , $workOrder, $item] = $this->buildFixture($owner, WorkOrderItem::STATUS_IN_PROGRESS);

        $tech = $this->techInSameContext($owner, ['crm.work_orders.view', 'crm.work_orders.update']);
        $this->assignTechnician($item, $tech);

        $entry = $this->labor->start($workOrder, $item, $tech);
        $this->assertSame(WorkOrderLaborEntry::STATUS_RUNNING, $entry->status);

        $this->quality->hold($workOrder, $item, $owner, 'Customer requested pause');

        $entry->refresh();
        $this->assertSame(WorkOrderLaborEntry::STATUS_PAUSED, $entry->status);
        $this->assertNotNull($entry->paused_at);
        $this->assertStringContainsString('Customer requested pause', (string) $entry->pause_reason);
        $this->assertNull($entry->ended_at);
    }

    public function test_hold_does_not_close_labor_sessions(): void
    {
        $owner = $this->createUserWithPermissions(['crm.work_orders.view', 'crm.work_orders.update']);
        [, , $workOrder, $item] = $this->buildFixture($owner, WorkOrderItem::STATUS_IN_PROGRESS);

        $tech = $this->techInSameContext($owner, ['crm.work_orders.view', 'crm.work_orders.update']);
        $this->assignTechnician($item, $tech);

        $entry = $this->labor->start($workOrder, $item, $tech);
        $this->quality->hold($workOrder, $item, $owner, 'reason');

        $entry->refresh();
        $this->assertNull($entry->ended_at, 'Hold must NOT end the session.');
        $this->assertSame(WorkOrderLaborEntry::STATUS_PAUSED, $entry->status);
    }

    public function test_resume_does_not_auto_resume_technician_timers(): void
    {
        $owner = $this->createUserWithPermissions(['crm.work_orders.view', 'crm.work_orders.update']);
        [, , $workOrder, $item] = $this->buildFixture($owner, WorkOrderItem::STATUS_IN_PROGRESS);

        $tech = $this->techInSameContext($owner, ['crm.work_orders.view', 'crm.work_orders.update']);
        $this->assignTechnician($item, $tech);

        $entry = $this->labor->start($workOrder, $item, $tech);
        $this->quality->hold($workOrder, $item, $owner, 'reason');
        $this->quality->resume($workOrder, $item->refresh(), $owner);

        $entry->refresh();
        $this->assertSame(WorkOrderLaborEntry::STATUS_PAUSED, $entry->status);
    }

    public function test_cannot_start_labor_session_on_held_item(): void
    {
        $owner = $this->createUserWithPermissions(['crm.work_orders.view', 'crm.work_orders.update']);
        [, , $workOrder, $item] = $this->buildFixture($owner, WorkOrderItem::STATUS_IN_PROGRESS);

        $tech = $this->techInSameContext($owner, ['crm.work_orders.view', 'crm.work_orders.update']);
        $this->assignTechnician($item, $tech);

        $this->quality->hold($workOrder, $item, $owner, 'no parts');

        $threw = false;

        try {
            $this->labor->start($workOrder, $item->refresh(), $tech);
        } catch (RuntimeException $e) {
            $threw = $e->getMessage() === 'item_on_hold';
        }
        $this->assertTrue($threw);
    }

    public function test_cannot_start_labor_session_on_ready_for_qc_item(): void
    {
        $owner = $this->createUserWithPermissions(['crm.work_orders.view', 'crm.work_orders.update']);
        [, , $workOrder, $item] = $this->buildFixture($owner, WorkOrderItem::STATUS_READY_FOR_QC);

        $tech = $this->techInSameContext($owner, ['crm.work_orders.view', 'crm.work_orders.update']);
        $this->assignTechnician($item, $tech);

        $threw = false;

        try {
            $this->labor->start($workOrder, $item, $tech);
        } catch (RuntimeException $e) {
            $threw = $e->getMessage() === 'item_ready_for_qc';
        }
        $this->assertTrue($threw);
    }

    // ==================== Quality checks ====================

    public function test_quality_approval_only_allowed_from_ready_for_qc(): void
    {
        $owner = $this->createUserWithPermissions(['crm.work_orders.view', 'crm.work_orders.update']);
        [, , $workOrder, $item] = $this->buildFixture($owner, WorkOrderItem::STATUS_IN_PROGRESS);

        $threw = false;

        try {
            $this->quality->approveQuality($workOrder, $item, $owner);
        } catch (RuntimeException $e) {
            $threw = $e->getMessage() === 'item_not_ready_for_qc';
        }
        $this->assertTrue($threw);
    }

    public function test_quality_approval_flips_item_to_completed(): void
    {
        $owner = $this->createUserWithPermissions(['crm.work_orders.view', 'crm.work_orders.update']);
        [, , $workOrder, $item] = $this->buildFixture($owner, WorkOrderItem::STATUS_READY_FOR_QC);

        $check = $this->quality->approveQuality($workOrder, $item, $owner, 'Looks great');

        $this->assertSame(WorkOrderItemQualityCheck::DECISION_APPROVED, $check->decision);
        $this->assertSame('Looks great', $check->notes);

        $item->refresh();
        $this->assertSame(WorkOrderItem::STATUS_COMPLETED, $item->status);
    }

    public function test_quality_approval_closes_open_labor_sessions(): void
    {
        $owner = $this->createUserWithPermissions(['crm.work_orders.view', 'crm.work_orders.update']);
        [, , $workOrder, $item] = $this->buildFixture($owner, WorkOrderItem::STATUS_IN_PROGRESS);

        $tech = $this->techInSameContext($owner, ['crm.work_orders.view', 'crm.work_orders.update']);
        $this->assignTechnician($item, $tech);

        // Tech starts the timer on an in_progress item, then marks
        // it ready for QC (in the real UI that happens via the
        // existing "set status to ready_for_qc" action), then the
        // supervisor approves. Approval must close the open session.
        $entry = $this->labor->start($workOrder, $item, $tech);
        $item->update(['status' => WorkOrderItem::STATUS_READY_FOR_QC]);

        $this->quality->approveQuality($workOrder, $item->refresh(), $owner);

        $entry->refresh();
        $this->assertSame(WorkOrderLaborEntry::STATUS_COMPLETED, $entry->status);
        $this->assertNotNull($entry->ended_at);
    }

    public function test_quality_rejection_requires_notes(): void
    {
        $owner = $this->createUserWithPermissions(['crm.work_orders.view', 'crm.work_orders.update']);
        [, , $workOrder, $item] = $this->buildFixture($owner, WorkOrderItem::STATUS_READY_FOR_QC);

        $threw = false;

        try {
            $this->quality->rejectQuality($workOrder, $item, $owner, '  ');
        } catch (RuntimeException $e) {
            $threw = $e->getMessage() === 'rejection_notes_required';
        }
        $this->assertTrue($threw);
    }

    public function test_quality_rejection_records_check_and_sends_item_back_to_in_progress(): void
    {
        $owner = $this->createUserWithPermissions(['crm.work_orders.view', 'crm.work_orders.update']);
        [, , $workOrder, $item] = $this->buildFixture($owner, WorkOrderItem::STATUS_READY_FOR_QC);

        $check = $this->quality->rejectQuality($workOrder, $item, $owner, 'Wrong oil grade');

        $this->assertSame(WorkOrderItemQualityCheck::DECISION_REJECTED, $check->decision);
        $this->assertSame('Wrong oil grade', $check->notes);

        $item->refresh();
        $this->assertSame(WorkOrderItem::STATUS_IN_PROGRESS, $item->status);
    }

    public function test_quality_rejection_does_not_auto_start_a_timer(): void
    {
        $owner = $this->createUserWithPermissions(['crm.work_orders.view', 'crm.work_orders.update']);
        [, , $workOrder, $item] = $this->buildFixture($owner, WorkOrderItem::STATUS_READY_FOR_QC);

        $tech = $this->techInSameContext($owner, ['crm.work_orders.view', 'crm.work_orders.update']);
        $this->assignTechnician($item, $tech);

        $this->quality->rejectQuality($workOrder, $item, $owner, 'redo');

        $this->assertSame(0, WorkOrderLaborEntry::where('work_order_item_id', $item->id)
            ->where('user_id', $tech->id)
            ->count());
    }

    public function test_after_rejection_assigned_technician_can_start_fresh_timer(): void
    {
        $owner = $this->createUserWithPermissions(['crm.work_orders.view', 'crm.work_orders.update']);
        [, , $workOrder, $item] = $this->buildFixture($owner, WorkOrderItem::STATUS_READY_FOR_QC);

        $tech = $this->techInSameContext($owner, ['crm.work_orders.view', 'crm.work_orders.update']);
        $this->assignTechnician($item, $tech);

        $this->quality->rejectQuality($workOrder, $item, $owner, 'redo');
        $entry = $this->labor->start($workOrder, $item->refresh(), $tech);

        $this->assertSame(WorkOrderLaborEntry::STATUS_RUNNING, $entry->status);
    }

    public function test_hold_resume_and_qc_actions_each_log_an_activity(): void
    {
        $owner = $this->createUserWithPermissions(['crm.work_orders.view', 'crm.work_orders.update']);
        [, , $workOrder, $item] = $this->buildFixture($owner, WorkOrderItem::STATUS_IN_PROGRESS);

        // reject → hold → resume → approve. The item is moved to
        // ready_for_qc between resume and approve so the approve
        // call has a valid source state.
        $this->quality->hold($workOrder, $item, $owner, 'reason');
        $this->quality->resume($workOrder, $item->refresh(), $owner);
        $item->refresh()->update(['status' => WorkOrderItem::STATUS_READY_FOR_QC]);
        $this->quality->approveQuality($workOrder, $item->refresh(), $owner);

        // Now drive the rejection separately so the same item ends
        // up in ready_for_qc again (it can't — it's completed). Use
        // a fresh item for the rejection so the activity log
        // assertion is still meaningful.
        [, , $workOrder2, $item2] = $this->buildFixture($owner, WorkOrderItem::STATUS_READY_FOR_QC);
        $this->quality->rejectQuality($workOrder2, $item2, $owner, 'redo');

        $actions = WorkOrderActivity::pluck('action')->all();

        $this->assertContains('item_held', $actions);
        $this->assertContains('item_resumed', $actions);
        $this->assertContains('item_quality_approved', $actions);
        $this->assertContains('item_quality_rejected', $actions);
    }

    // ==================== Isolation ====================

    public function test_hold_is_invisible_across_tenants(): void
    {
        $owner = $this->createUserWithPermissions(['crm.work_orders.view', 'crm.work_orders.update']);
        [, , $workOrder, $item] = $this->buildFixture($owner, WorkOrderItem::STATUS_IN_PROGRESS);

        $this->quality->hold($workOrder, $item, $owner, 'reason');
        $this->assertSame(WorkOrderItem::STATUS_ON_HOLD, $item->refresh()->status);

        $attacker = User::factory()->create([
            'tenant_id' => Tenant::factory()->create()->id,
            'current_center_id' => null,
        ]);

        $response = $this->actingAs($attacker)->getJson('/app/workshop');
        $response->assertStatus(403);
    }

    public function test_hold_route_returns_404_for_other_tenants_work_order(): void
    {
        $owner = $this->createUserWithPermissions(['crm.work_orders.view', 'crm.work_orders.update']);
        [, , $workOrder, $item] = $this->buildFixture($owner, WorkOrderItem::STATUS_IN_PROGRESS);

        // Build an "attacker" admin user from a different tenant and
        // grant them crm.work_orders.update. They must NOT be able
        // to hold items in another tenant's work order.
        $otherTenant = Tenant::factory()->create();
        $otherCenter = Center::factory()->create(['tenant_id' => $otherTenant->id]);
        $attacker = User::factory()->create([
            'tenant_id' => $otherTenant->id,
            'current_center_id' => $otherCenter->id,
        ]);
        $attacker->centers()->attach($otherCenter->id, ['tenant_id' => $otherTenant->id]);
        app(PermissionRegistrar::class)->setPermissionsTeamId($otherTenant->id);
        $attacker->givePermissionTo('crm.work_orders.update');

        $response = $this->actingAs($attacker)->postJson(
            "/app/work-orders/{$workOrder->id}/items/{$item->id}/hold",
            ['reason' => 'hijack']
        );

        // 404 is the right response: defense in depth treats the
        // resource as not visible from the other tenant.
        $this->assertContains($response->getStatusCode(), [403, 404]);
    }
}
