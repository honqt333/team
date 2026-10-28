<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Customer;
use App\Models\InventoryBalance;
use App\Models\Part;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Warehouse;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Models\WorkOrderItemNote;
use App\Models\WorkOrderItemPart;
use App\Services\Inventory\InventoryService;
use App\Services\Inventory\WorkOrderPartsService;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Tests for the link between a work order and its children
 * (audit finding #20).
 *
 * Several endpoints authorized the work order in the URL and then
 * worked on a WorkOrderItem, WorkOrderItemNote or WorkOrderItemPart
 * that was identified by its own id. The global scope of those
 * models only covers the tenant and the centre — never the parent —
 * so an open work order could be used to edit, cancel, annotate or
 * staff a line of a closed one, in the same centre.
 *
 * The children are now resolved through the work order's own
 * relations, so a line of another work order is simply not found.
 */
class WorkOrderChildBindingTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Center $center;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ([
            Permissions::WORK_ORDERS_VIEW,
            Permissions::WORK_ORDERS_UPDATE,
            Permissions::WORK_ORDERS_CREATE,
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $this->tenant = Tenant::factory()->create();
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);
        $this->user->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
        $this->user->givePermissionTo([
            Permissions::WORK_ORDERS_VIEW,
            Permissions::WORK_ORDERS_UPDATE,
            Permissions::WORK_ORDERS_CREATE,
        ]);
        $this->user->refresh();

        // The inventory moves are attributed to the warehouse, but the
        // ambient context is what the rest of the app reads, so the
        // test user has to be logged in for the whole class.
        $this->actingAs($this->user);
    }

    private function workOrder(string $status = WorkOrder::STATUS_OPEN): WorkOrder
    {
        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
        ]);
        $vehicle = Vehicle::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $customer->id,
        ]);

        return WorkOrder::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'code' => 'WO-'.uniqid(),
            'status' => $status,
            'currency_code' => 'SAR',
            'tax_enabled_snapshot' => true,
            'pricing_mode_snapshot' => 'exclusive',
            'tax_rate_snapshot' => 15,
        ]);
    }

    private function line(WorkOrder $wo, string $status = WorkOrderItem::STATUS_PENDING): WorkOrderItem
    {
        return WorkOrderItem::create([
            'work_order_id' => $wo->id,
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'title' => 'Service line',
            'qty' => 1,
            'unit_price' => 100,
            'base_price_snapshot' => 100,
            'final_unit_price' => 100,
            'line_total' => 100,
            'total' => 100,
            'status' => $status,
        ]);
    }

    /**
     * A line of a closed work order cannot be updated through the URL
     * of an open one.
     */
    public function test_a_line_of_another_work_order_cannot_be_updated(): void
    {
        $openWorkOrder = $this->workOrder();
        $closedWorkOrder = $this->workOrder(WorkOrder::STATUS_DONE);

        $foreignLine = $this->line($closedWorkOrder);

        $response = $this->actingAs($this->user)->put(
            '/app/work-orders/'.$openWorkOrder->id.'/items/'.$foreignLine->id,
            [
                'title' => 'Hijacked',
                'qty' => 5,
                'unit_price' => 999,
            ]
        );

        $response->assertNotFound();

        $foreignLine->refresh();
        $this->assertNotSame('Hijacked', $foreignLine->title);
        $this->assertEquals(100, (float) $foreignLine->unit_price);
    }

    /**
     * A line of another work order cannot be cancelled through the URL
     * of an open one.
     */
    public function test_a_line_of_another_work_order_cannot_be_cancelled(): void
    {
        $openWorkOrder = $this->workOrder();
        $closedWorkOrder = $this->workOrder(WorkOrder::STATUS_DONE);

        $foreignLine = $this->line($closedWorkOrder);

        $response = $this->actingAs($this->user)
            ->delete('/app/work-orders/'.$openWorkOrder->id.'/items/'.$foreignLine->id);

        $response->assertNotFound();

        $foreignLine->refresh();
        $this->assertNotSame(WorkOrderItem::STATUS_CANCELLED, $foreignLine->status);
    }

    /**
     * The status of a line of another work order cannot be changed.
     */
    public function test_a_line_of_another_work_order_cannot_change_status(): void
    {
        $openWorkOrder = $this->workOrder();
        $closedWorkOrder = $this->workOrder(WorkOrder::STATUS_DONE);

        $foreignLine = $this->line($closedWorkOrder);

        $response = $this->actingAs($this->user)->patch(
            '/app/work-orders/'.$openWorkOrder->id.'/items/'.$foreignLine->id.'/status',
            ['status' => WorkOrderItem::STATUS_COMPLETED]
        );

        $response->assertNotFound();

        $foreignLine->refresh();
        $this->assertSame(WorkOrderItem::STATUS_PENDING, $foreignLine->status);
    }

    /**
     * A technician cannot be assigned to a line of another work order.
     */
    public function test_a_technician_cannot_be_assigned_to_a_line_of_another_work_order(): void
    {
        $openWorkOrder = $this->workOrder();
        $closedWorkOrder = $this->workOrder(WorkOrder::STATUS_DONE);

        $foreignLine = $this->line($closedWorkOrder);

        $response = $this->actingAs($this->user)->post(
            '/app/work-orders/'.$openWorkOrder->id.'/items/'.$foreignLine->id.'/technicians',
            ['user_id' => $this->user->id]
        );

        $response->assertNotFound();

        $this->assertDatabaseMissing('work_order_item_technician', [
            'work_order_item_id' => $foreignLine->id,
        ]);
    }

    /**
     * A note cannot be added to a line of another work order.
     */
    public function test_a_note_cannot_be_added_to_a_line_of_another_work_order(): void
    {
        $openWorkOrder = $this->workOrder();
        $closedWorkOrder = $this->workOrder(WorkOrder::STATUS_DONE);

        $foreignLine = $this->line($closedWorkOrder);

        $response = $this->actingAs($this->user)->post(
            '/app/work-orders/'.$openWorkOrder->id.'/items/'.$foreignLine->id.'/notes',
            ['content' => 'Hijacked note']
        );

        $response->assertNotFound();

        $this->assertDatabaseMissing('work_order_item_notes', [
            'content' => 'Hijacked note',
        ]);
    }

    /**
     * A note of another line cannot be deleted through the URL of a
     * different line.
     */
    public function test_a_note_of_another_line_cannot_be_deleted(): void
    {
        $workOrder = $this->workOrder();

        $ownLine = $this->line($workOrder);
        $otherLine = $this->line($workOrder);

        $foreignNote = WorkOrderItemNote::create([
            'work_order_id' => $workOrder->id,
            'work_order_item_id' => $otherLine->id,
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'user_id' => $this->user->id,
            'content' => 'Note of the other line',
        ]);

        $response = $this->actingAs($this->user)->delete(
            '/app/work-orders/'.$workOrder->id.'/items/'.$ownLine->id.'/notes/'.$foreignNote->id
        );

        $response->assertNotFound();

        $this->assertDatabaseHas('work_order_item_notes', ['id' => $foreignNote->id]);
    }

    /**
     * A general note of another work order cannot be deleted through
     * the URL of this one.
     */
    public function test_a_general_note_of_another_work_order_cannot_be_deleted(): void
    {
        $workOrder = $this->workOrder();
        $otherWorkOrder = $this->workOrder();

        $foreignNote = WorkOrderItemNote::create([
            'work_order_id' => $otherWorkOrder->id,
            'work_order_item_id' => null,
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'user_id' => $this->user->id,
            'content' => 'Note of the other work order',
        ]);

        $response = $this->actingAs($this->user)->delete(
            '/app/work-orders/'.$workOrder->id.'/notes/'.$foreignNote->id
        );

        $response->assertNotFound();

        $this->assertDatabaseHas('work_order_item_notes', ['id' => $foreignNote->id]);
    }

    /**
     * A part of another line cannot be updated through the URL of a
     * different line.
     */
    public function test_a_part_of_another_line_cannot_be_updated(): void
    {
        $workOrder = $this->workOrder();

        $ownLine = $this->line($workOrder);
        $otherLine = $this->line($workOrder);

        $part = Part::factory()->create(['tenant_id' => $this->tenant->id]);

        $foreignPart = WorkOrderItemPart::create([
            'work_order_id' => $workOrder->id,
            'work_order_item_id' => $otherLine->id,
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'part_id' => $part->id,
            'name' => 'Part of the other line',
            'source' => WorkOrderItemPart::SOURCE_EXTERNAL,
            'qty' => 1,
            'unit_price' => 50,
            'status' => WorkOrderItemPart::STATUS_PENDING,
        ]);

        $response = $this->actingAs($this->user)->put(
            '/app/work-orders/'.$workOrder->id.'/items/'.$ownLine->id.'/parts/'.$foreignPart->id,
            [
                'name' => 'Hijacked part',
                'source' => WorkOrderItemPart::SOURCE_EXTERNAL,
                'qty' => 9,
                'unit_price' => 999,
            ]
        );

        $response->assertNotFound();

        $foreignPart->refresh();
        $this->assertNotSame('Hijacked part', $foreignPart->name);
        $this->assertEquals(1, (float) $foreignPart->qty);
    }

    /**
     * The own line is still editable — the fix must not break the
     * normal flow.
     */
    public function test_a_line_of_the_same_work_order_can_be_updated(): void
    {
        $workOrder = $this->workOrder();
        $line = $this->line($workOrder);

        $response = $this->actingAs($this->user)->put(
            '/app/work-orders/'.$workOrder->id.'/items/'.$line->id,
            [
                'title' => 'Updated title',
                'qty' => 2,
                'unit_price' => 120,
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $line->refresh();
        $this->assertSame('Updated title', $line->title);
        $this->assertEquals(2, (float) $line->qty);
    }

    /**
     * #21 — deleting a part line through the nested route has to
     * reverse the issue move.
     *
     * The nested controller used to delete the record directly, so
     * the stock that had left the warehouse stayed out of the balance
     * while the line disappeared from the work order.
     */
    public function test_deleting_a_part_through_the_nested_route_returns_the_stock(): void
    {
        $warehouse = Warehouse::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'is_default' => true,
        ]);
        $part = Part::factory()->create(['tenant_id' => $this->tenant->id]);

        $workOrder = $this->workOrder();
        $line = $this->line($workOrder);

        app(InventoryService::class)->receipt($warehouse->id, $part->id, 100, 50.0);

        $partLine = app(WorkOrderPartsService::class)->addPart([
            'work_order_id' => $workOrder->id,
            'work_order_item_id' => $line->id,
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'part_id' => $part->id,
            'warehouse_id' => $warehouse->id,
            'name' => 'Issued part',
            'source' => WorkOrderItemPart::SOURCE_WAREHOUSE,
            'qty' => 10,
            'unit_price' => 50,
        ]);

        $balance = InventoryBalance::where('warehouse_id', $warehouse->id)
            ->where('part_id', $part->id)
            ->first();

        $this->assertEquals(90, (float) $balance->qty_on_hand);

        $response = $this->actingAs($this->user)->delete(
            '/app/work-orders/'.$workOrder->id.'/items/'.$line->id.'/parts/'.$partLine->id
        );

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $balance->refresh();

        // The issued stock has to come back to the warehouse.
        $this->assertEquals(
            100,
            (float) $balance->qty_on_hand,
            'Deleting an issued part line must reverse the issue move.'
        );
    }
}
