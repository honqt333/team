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
use App\Models\WorkOrderLaborEntry;
use App\Services\WorkOrderLaborService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class WorkOrderLaborEntryTest extends TestCase
{
    use RefreshDatabase;

    protected WorkOrderLaborService $service;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::findOrCreate('crm.work_orders.view', 'web');
        Permission::findOrCreate('crm.work_orders.update', 'web');
        Permission::findOrCreate('crm.customers.view', 'web');
        Permission::findOrCreate('crm.vehicles.view', 'web');

        $this->service = app(WorkOrderLaborService::class);
    }

    /**
     * Build a user with the given permissions, attached to the given
     * tenant + center. If no tenant/center is supplied, fresh ones
     * are created (used by the cross-tenant isolation test).
     *
     * @param array<int, string> $permissions
     */
    protected function createUserWithPermissions(
        array $permissions,
        ?Tenant $tenant = null,
        ?Center $center = null,
    ): User {
        $tenant ??= Tenant::factory()->create();
        $center ??= Center::factory()->create(['tenant_id' => $tenant->id]);

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

    /**
     * @return array{0: Customer, 1: Vehicle, 2: WorkOrder, 3: WorkOrderItem, 4: Center}
     */
    protected function buildFixture(User $owner, string $itemStatus = WorkOrderItem::STATUS_PENDING): array
    {
        $center = Center::find($owner->current_center_id);

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

        return [$customer, $vehicle, $workOrder, $item, $center];
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

    /**
     * Build a tech user in the SAME tenant + center as the owner so
     * the work-order route binding (which is tenant-scoped) finds
     * the fixture.
     *
     * @param array<int, string> $permissions
     */
    protected function techInSameContext(User $owner, array $permissions): User
    {
        $tenant = Tenant::find($owner->tenant_id);
        $center = Center::find($owner->current_center_id);

        return $this->createUserWithPermissions($permissions, $tenant, $center);
    }

    // ==================== Start ====================

    public function test_assigned_technician_can_start_labor_session(): void
    {
        $owner = $this->createUserWithPermissions(['crm.work_orders.view', 'crm.work_orders.update']);
        [, , $workOrder, $item] = $this->buildFixture($owner);

        $tech = $this->techInSameContext($owner, ['crm.work_orders.view', 'crm.work_orders.update']);
        $this->assignTechnician($item, $tech);

        $response = $this->actingAs($tech)
            ->withoutExceptionHandling()
            ->postJson("/app/work-orders/{$workOrder->id}/items/{$item->id}/labor-entries/start");

        $response->assertStatus(200);
        $this->assertDatabaseHas('work_order_labor_entries', [
            'work_order_id' => $workOrder->id,
            'work_order_item_id' => $item->id,
            'user_id' => $tech->id,
            'status' => WorkOrderLaborEntry::STATUS_RUNNING,
        ]);
    }

    public function test_starting_session_flips_pending_item_to_in_progress(): void
    {
        $owner = $this->createUserWithPermissions(['crm.work_orders.view', 'crm.work_orders.update']);
        [, , $workOrder, $item] = $this->buildFixture($owner, WorkOrderItem::STATUS_PENDING);

        $tech = $this->techInSameContext($owner, ['crm.work_orders.view', 'crm.work_orders.update']);
        $this->assignTechnician($item, $tech);

        $this->actingAs($tech)->postJson(
            "/app/work-orders/{$workOrder->id}/items/{$item->id}/labor-entries/start"
        )->assertStatus(200);

        $item->refresh();
        $this->assertSame(WorkOrderItem::STATUS_IN_PROGRESS, $item->status);
    }

    public function test_unassigned_technician_cannot_start(): void
    {
        $owner = $this->createUserWithPermissions(['crm.work_orders.view', 'crm.work_orders.update']);
        [, , $workOrder, $item] = $this->buildFixture($owner);

        $tech = $this->techInSameContext($owner, ['crm.work_orders.view', 'crm.work_orders.update']);
        // Not assigned to the item.

        $response = $this->actingAs($tech)->postJson(
            "/app/work-orders/{$workOrder->id}/items/{$item->id}/labor-entries/start"
        );

        $response->assertStatus(422);
        $this->assertDatabaseMissing('work_order_labor_entries', [
            'work_order_item_id' => $item->id,
            'user_id' => $tech->id,
        ]);
    }

    public function test_user_without_work_orders_update_permission_cannot_start(): void
    {
        $owner = $this->createUserWithPermissions(['crm.work_orders.view', 'crm.work_orders.update']);
        [, , $workOrder, $item] = $this->buildFixture($owner);

        $tech = $this->techInSameContext($owner, []);
        $this->assignTechnician($item, $tech);

        $this->actingAs($tech)->postJson(
            "/app/work-orders/{$workOrder->id}/items/{$item->id}/labor-entries/start"
        )->assertStatus(403);
    }

    public function test_technician_cannot_run_two_concurrent_sessions_in_same_tenant(): void
    {
        $owner = $this->createUserWithPermissions(['crm.work_orders.view', 'crm.work_orders.update']);
        [, , $workOrderA, $itemA] = $this->buildFixture($owner);
        [, , $workOrderB, $itemB] = $this->buildFixture($owner);

        $tech = $this->techInSameContext($owner, ['crm.work_orders.view', 'crm.work_orders.update']);
        $this->assignTechnician($itemA, $tech);
        $this->assignTechnician($itemB, $tech);

        // First session starts cleanly.
        $this->actingAs($tech)->postJson(
            "/app/work-orders/{$workOrderA->id}/items/{$itemA->id}/labor-entries/start"
        )->assertStatus(200);

        // Second start must be rejected (one running per user per tenant).
        $this->actingAs($tech)->postJson(
            "/app/work-orders/{$workOrderB->id}/items/{$itemB->id}/labor-entries/start"
        )->assertStatus(422);

        $this->assertSame(1, WorkOrderLaborEntry::where('user_id', $tech->id)
            ->where('status', WorkOrderLaborEntry::STATUS_RUNNING)
            ->count());
    }

    public function test_cannot_start_on_completed_item(): void
    {
        $owner = $this->createUserWithPermissions(['crm.work_orders.view', 'crm.work_orders.update']);
        [, , $workOrder, $item] = $this->buildFixture($owner, WorkOrderItem::STATUS_COMPLETED);

        $tech = $this->techInSameContext($owner, ['crm.work_orders.view', 'crm.work_orders.update']);
        $this->assignTechnician($item, $tech);

        $this->actingAs($tech)->postJson(
            "/app/work-orders/{$workOrder->id}/items/{$item->id}/labor-entries/start"
        )->assertStatus(422);
    }

    public function test_cannot_start_on_cancelled_item(): void
    {
        $owner = $this->createUserWithPermissions(['crm.work_orders.view', 'crm.work_orders.update']);
        [, , $workOrder, $item] = $this->buildFixture($owner, WorkOrderItem::STATUS_CANCELLED);

        $tech = $this->techInSameContext($owner, ['crm.work_orders.view', 'crm.work_orders.update']);
        $this->assignTechnician($item, $tech);

        $this->actingAs($tech)->postJson(
            "/app/work-orders/{$workOrder->id}/items/{$item->id}/labor-entries/start"
        )->assertStatus(422);
    }

    // ==================== Pause / resume math ====================

    public function test_pause_then_resume_accumulates_paused_duration(): void
    {
        $owner = $this->createUserWithPermissions(['crm.work_orders.view', 'crm.work_orders.update']);
        [, , $workOrder, $item] = $this->buildFixture($owner);

        $tech = $this->techInSameContext($owner, ['crm.work_orders.view', 'crm.work_orders.update']);
        $this->assignTechnician($item, $tech);

        $start = Carbon::now()->subMinutes(10);
        Carbon::setTestNow($start);

        $entry = $this->service->start($workOrder, $item, $tech);
        $this->assertSame(WorkOrderLaborEntry::STATUS_RUNNING, $entry->status);

        // 4 minutes of work, then pause.
        Carbon::setTestNow($start->copy()->addMinutes(4));
        $entry = $this->service->pause($entry, 'Waiting for parts');
        $this->assertSame(WorkOrderLaborEntry::STATUS_PAUSED, $entry->status);
        $this->assertNotNull($entry->paused_at);
        $this->assertSame('Waiting for parts', $entry->pause_reason);

        // 3 minute pause interval.
        Carbon::setTestNow($start->copy()->addMinutes(7));
        $entry = $this->service->resume($entry);
        $this->assertSame(WorkOrderLaborEntry::STATUS_RUNNING, $entry->status);
        $this->assertNull($entry->paused_at);

        // paused_duration_seconds should reflect 180s = 3 minutes.
        $this->assertSame(180, (int) $entry->paused_duration_seconds);

        // 2 more minutes of work, then end.
        Carbon::setTestNow($start->copy()->addMinutes(9));
        $entry = $this->service->end($entry);
        $this->assertSame(WorkOrderLaborEntry::STATUS_COMPLETED, $entry->status);
        $this->assertNotNull($entry->ended_at);

        // Total effective = (4 + 2) = 6 minutes of work, 3 minutes paused.
        $this->assertSame(360, $entry->effectiveDurationSeconds());

        Carbon::setTestNow();
    }

    public function test_pause_only_allowed_when_running(): void
    {
        $owner = $this->createUserWithPermissions(['crm.work_orders.view', 'crm.work_orders.update']);
        [, , $workOrder, $item] = $this->buildFixture($owner);

        $tech = $this->techInSameContext($owner, ['crm.work_orders.view', 'crm.work_orders.update']);
        $this->assignTechnician($item, $tech);

        $entry = $this->service->start($workOrder, $item, $tech);
        $entry = $this->service->pause($entry);

        $this->expectException(RuntimeException::class);
        $this->service->pause($entry);
    }

    public function test_resume_only_allowed_when_paused(): void
    {
        $owner = $this->createUserWithPermissions(['crm.work_orders.view', 'crm.work_orders.update']);
        [, , $workOrder, $item] = $this->buildFixture($owner);

        $tech = $this->techInSameContext($owner, ['crm.work_orders.view', 'crm.work_orders.update']);
        $this->assignTechnician($item, $tech);

        $entry = $this->service->start($workOrder, $item, $tech);

        $this->expectException(RuntimeException::class);
        $this->service->resume($entry);
    }

    // ==================== End & close-on-status-change ====================

    public function test_ending_session_does_not_complete_item(): void
    {
        $owner = $this->createUserWithPermissions(['crm.work_orders.view', 'crm.work_orders.update']);
        [, , $workOrder, $item] = $this->buildFixture($owner);

        $tech = $this->techInSameContext($owner, ['crm.work_orders.view', 'crm.work_orders.update']);
        $this->assignTechnician($item, $tech);

        $entry = $this->service->start($workOrder, $item, $tech);
        $this->service->end($entry);

        $item->refresh();
        // The timer is closed but the item is still in progress —
        // completing the item is a separate decision.
        $this->assertSame(WorkOrderItem::STATUS_IN_PROGRESS, $item->status);
    }

    public function test_completing_item_force_closes_open_labor_sessions(): void
    {
        $owner = $this->createUserWithPermissions(['crm.work_orders.view', 'crm.work_orders.update']);
        [, , $workOrder, $item] = $this->buildFixture($owner);

        $tech = $this->techInSameContext($owner, ['crm.work_orders.view', 'crm.work_orders.update']);
        $this->assignTechnician($item, $tech);

        $entry = $this->service->start($workOrder, $item, $tech);
        $this->assertTrue($entry->isRunning());

        $item->update(['status' => WorkOrderItem::STATUS_COMPLETED]);

        $entry->refresh();
        $this->assertSame(WorkOrderLaborEntry::STATUS_COMPLETED, $entry->status);
        $this->assertNotNull($entry->ended_at);
    }

    public function test_cancelling_item_force_closes_paused_labor_sessions(): void
    {
        $owner = $this->createUserWithPermissions(['crm.work_orders.view', 'crm.work_orders.update']);
        [, , $workOrder, $item] = $this->buildFixture($owner);

        $tech = $this->techInSameContext($owner, ['crm.work_orders.view', 'crm.work_orders.update']);
        $this->assignTechnician($item, $tech);

        $entry = $this->service->start($workOrder, $item, $tech);
        $this->service->pause($entry);
        $this->assertTrue($entry->isPaused());

        $item->update(['status' => WorkOrderItem::STATUS_CANCELLED]);

        $entry->refresh();
        $this->assertSame(WorkOrderLaborEntry::STATUS_COMPLETED, $entry->status);
        $this->assertNotNull($entry->ended_at);
    }

    // ==================== Isolation ====================

    public function test_labor_entry_is_invisible_across_tenants(): void
    {
        $owner = $this->createUserWithPermissions(['crm.work_orders.view', 'crm.work_orders.update']);
        [, , $workOrder, $item] = $this->buildFixture($owner);

        $tech = $this->techInSameContext($owner, ['crm.work_orders.view', 'crm.work_orders.update']);
        $this->assignTechnician($item, $tech);

        $this->service->start($workOrder, $item, $tech);

        // Build an "attacker" from another tenant and center.
        $otherTenant = Tenant::factory()->create();
        $otherCenter = Center::factory()->create(['tenant_id' => $otherTenant->id]);
        $attacker = User::factory()->create([
            'tenant_id' => $otherTenant->id,
            'current_center_id' => $otherCenter->id,
        ]);

        $response = $this->actingAs($attacker)->getJson('/app/workshop');
        $response->assertStatus(403);

        $this->assertSame(0, WorkOrderLaborEntry::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $otherTenant->id)
            ->count());
    }

    public function test_user_in_other_center_cannot_act_on_session(): void
    {
        $owner = $this->createUserWithPermissions(['crm.work_orders.view', 'crm.work_orders.update']);
        [, , $workOrder, $item] = $this->buildFixture($owner);

        $tech = $this->techInSameContext($owner, ['crm.work_orders.view', 'crm.work_orders.update']);
        $this->assignTechnician($item, $tech);

        $entry = $this->service->start($workOrder, $item, $tech);

        // Build a "neighbor" who is admin-level in the same tenant but
        // attached to a different center.
        $tenant = $owner->tenant_id;
        $otherCenter = Center::factory()->create(['tenant_id' => $tenant]);
        $neighbor = User::factory()->create([
            'tenant_id' => $tenant,
            'current_center_id' => $otherCenter->id,
        ]);
        $neighbor->centers()->attach($otherCenter->id, ['tenant_id' => $tenant]);
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant);
        $neighbor->givePermissionTo('crm.work_orders.update');

        $response = $this->actingAs($neighbor)->postJson(
            "/app/work-orders/{$workOrder->id}/labor-entries/{$entry->id}/end"
        );

        // 404 is the right response: defense-in-depth treats the
        // entry as not visible to a user outside its center, so the
        // resource is reported as missing. This is preferred over
        // 403 because it doesn't leak the entry's existence.
        $response->assertStatus(404);
    }

    public function test_another_technician_cannot_end_someone_elses_session(): void
    {
        $owner = $this->createUserWithPermissions(['crm.work_orders.view', 'crm.work_orders.update']);
        [, , $workOrder, $item] = $this->buildFixture($owner);

        $techA = $this->techInSameContext($owner, ['crm.work_orders.view', 'crm.work_orders.update']);
        $techB = $this->techInSameContext($owner, ['crm.work_orders.view', 'crm.work_orders.update']);
        $this->assignTechnician($item, $techA);
        $this->assignTechnician($item, $techB);

        // Make sure techA's prior session is cleared (they shouldn't
        // have one but be defensive) and then start one for techA.
        $this->service->start($workOrder, $item, $techA);
        $entryA = WorkOrderLaborEntry::query()
            ->where('user_id', $techA->id)
            ->where('status', WorkOrderLaborEntry::STATUS_RUNNING)
            ->firstOrFail();

        $response = $this->actingAs($techB)->postJson(
            "/app/work-orders/{$workOrder->id}/labor-entries/{$entryA->id}/end"
        );

        $response->assertStatus(403);
        $entryA->refresh();
        $this->assertSame(WorkOrderLaborEntry::STATUS_RUNNING, $entryA->status);
    }

    public function test_attempt_to_act_on_another_works_order_entry_returns_404(): void
    {
        $owner = $this->createUserWithPermissions(['crm.work_orders.view', 'crm.work_orders.update']);
        [, , $workOrderA, $itemA] = $this->buildFixture($owner);
        [, , $workOrderB, $itemB] = $this->buildFixture($owner);

        $tech = $this->techInSameContext($owner, ['crm.work_orders.view', 'crm.work_orders.update']);
        $this->assignTechnician($itemA, $tech);
        $this->assignTechnician($itemB, $tech);

        $entryA = $this->service->start($workOrderA, $itemA, $tech);
        // End entryA via workOrderB's URL — must 404.
        $response = $this->actingAs($tech)->postJson(
            "/app/work-orders/{$workOrderB->id}/labor-entries/{$entryA->id}/end"
        );

        $response->assertStatus(404);
    }

    // ==================== Activity log ====================

    public function test_lifecycle_actions_log_activity_on_work_order(): void
    {
        $owner = $this->createUserWithPermissions(['crm.work_orders.view', 'crm.work_orders.update']);
        [, , $workOrder, $item] = $this->buildFixture($owner);

        $tech = $this->techInSameContext($owner, ['crm.work_orders.view', 'crm.work_orders.update']);
        $this->assignTechnician($item, $tech);

        $entry = $this->service->start($workOrder, $item, $tech);
        $this->service->pause($entry);
        $this->service->resume($entry);
        $this->service->end($entry);

        $actions = WorkOrderActivity::where('work_order_id', $workOrder->id)
            ->pluck('action')
            ->all();

        $this->assertContains('labor_started', $actions);
        $this->assertContains('labor_paused', $actions);
        $this->assertContains('labor_resumed', $actions);
        $this->assertContains('labor_ended', $actions);
    }
}
