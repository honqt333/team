<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\InventoryBalance;
use App\Models\InventoryMove;
use App\Models\Part;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Models\WorkOrderItemPart;
use App\Services\Inventory\InventoryService;
use App\Services\Inventory\WorkOrderPartsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class WorkOrderInventoryTest extends TestCase
{
    use RefreshDatabase;

    protected WorkOrderPartsService $partsService;
    protected InventoryService $inventoryService;
    protected User $user;
    protected Warehouse $warehouse;
    protected Part $part;
    protected WorkOrder $workOrder;
    protected WorkOrderItem $workOrderItem;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->inventoryService = app(InventoryService::class);
        $this->partsService = app(WorkOrderPartsService::class);
        
        // Create tenant and center
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);
        
        $this->user = User::factory()->create(['tenant_id' => $tenant->id, 'current_center_id' => $center->id]);
        $this->warehouse = Warehouse::factory()->create(['center_id' => $center->id, 'is_default' => true]);
        $this->part = Part::factory()->create(['tenant_id' => $tenant->id, 'sku' => 'TEST-001']);
        
        // Create work order
        $this->workOrder = WorkOrder::factory()->create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
        ]);
        
        $this->workOrderItem = WorkOrderItem::factory()->create([
            'work_order_id' => $this->workOrder->id,
        ]);
        
        // Add stock
        $this->inventoryService->receipt($this->warehouse->id, $this->part->id, 100, 50.00);
        
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_auto_issues_part_when_added_to_work_order()
    {
        $partLine = $this->partsService->addPart([
            'work_order_id' => $this->workOrder->id,
            'work_order_item_id' => $this->workOrderItem->id,
            'tenant_id' => $this->workOrder->tenant_id,
            'center_id' => $this->workOrder->center_id,
            'part_id' => $this->part->id,
            'warehouse_id' => $this->warehouse->id,
            'name' => $this->part->name_ar,
            'source' => 'warehouse',
            'qty' => 10,
            'unit_price' => 75.00,
        ]);

        // Check part line was created with issue tracking
        $this->assertNotNull($partLine->inventory_move_id);
        $this->assertEquals(WorkOrderItemPart::STATUS_ISSUED, $partLine->status);
        $this->assertNotNull($partLine->issued_at);
        $this->assertEquals(50.00, $partLine->cost_snapshot); // WAC

        // Check inventory was reduced
        $balance = InventoryBalance::where('warehouse_id', $this->warehouse->id)
            ->where('part_id', $this->part->id)
            ->first();
        
        $this->assertEquals(90, $balance->qty_on_hand); // 100 - 10

        // Check move was created
        $move = InventoryMove::find($partLine->inventory_move_id);
        $this->assertEquals(InventoryMove::TYPE_ISSUE_TO_WORKORDER, $move->move_type);
        $this->assertEquals(-10, $move->qty);
    }

    /** @test */
    public function it_blocks_issue_when_insufficient_stock()
    {
        $this->expectException(ValidationException::class);

        $this->partsService->addPart([
            'work_order_id' => $this->workOrder->id,
            'work_order_item_id' => $this->workOrderItem->id,
            'tenant_id' => $this->workOrder->tenant_id,
            'center_id' => $this->workOrder->center_id,
            'part_id' => $this->part->id,
            'warehouse_id' => $this->warehouse->id,
            'name' => $this->part->name_ar,
            'source' => 'warehouse',
            'qty' => 200, // Only 100 in stock
            'unit_price' => 75.00,
        ], allowNegative: false);
    }

    /** @test */
    public function it_handles_qty_increase_with_additional_issue()
    {
        // Add initial part
        $partLine = $this->partsService->addPart([
            'work_order_id' => $this->workOrder->id,
            'work_order_item_id' => $this->workOrderItem->id,
            'tenant_id' => $this->workOrder->tenant_id,
            'center_id' => $this->workOrder->center_id,
            'part_id' => $this->part->id,
            'warehouse_id' => $this->warehouse->id,
            'name' => $this->part->name_ar,
            'source' => 'warehouse',
            'qty' => 10,
            'unit_price' => 75.00,
        ]);

        // Increase qty
        $this->partsService->updatePart($partLine, [
            'qty' => 15, // Increase by 5
            'unit_price' => 75.00,
        ]);

        // Check inventory reduced by additional 5
        $balance = InventoryBalance::where('warehouse_id', $this->warehouse->id)
            ->where('part_id', $this->part->id)
            ->first();
        
        $this->assertEquals(85, $balance->qty_on_hand); // 100 - 10 - 5
    }

    /** @test */
    public function it_handles_qty_decrease_with_partial_reversal()
    {
        // Add initial part
        $partLine = $this->partsService->addPart([
            'work_order_id' => $this->workOrder->id,
            'work_order_item_id' => $this->workOrderItem->id,
            'tenant_id' => $this->workOrder->tenant_id,
            'center_id' => $this->workOrder->center_id,
            'part_id' => $this->part->id,
            'warehouse_id' => $this->warehouse->id,
            'name' => $this->part->name_ar,
            'source' => 'warehouse',
            'qty' => 10,
            'unit_price' => 75.00,
        ]);

        // Decrease qty
        $this->partsService->updatePart($partLine, [
            'qty' => 7, // Decrease by 3
            'unit_price' => 75.00,
        ]);

        // Check inventory returned by 3
        $balance = InventoryBalance::where('warehouse_id', $this->warehouse->id)
            ->where('part_id', $this->part->id)
            ->first();
        
        $this->assertEquals(93, $balance->qty_on_hand); // 100 - 10 + 3
    }

    /** @test */
    public function it_reverses_fully_on_part_removal()
    {
        // Add part
        $partLine = $this->partsService->addPart([
            'work_order_id' => $this->workOrder->id,
            'work_order_item_id' => $this->workOrderItem->id,
            'tenant_id' => $this->workOrder->tenant_id,
            'center_id' => $this->workOrder->center_id,
            'part_id' => $this->part->id,
            'warehouse_id' => $this->warehouse->id,
            'name' => $this->part->name_ar,
            'source' => 'warehouse',
            'qty' => 10,
            'unit_price' => 75.00,
        ]);

        // Remove part
        $this->partsService->removePart($partLine, $this->user->id);

        // Check inventory restored
        $balance = InventoryBalance::where('warehouse_id', $this->warehouse->id)
            ->where('part_id', $this->part->id)
            ->first();
        
        $this->assertEquals(100, $balance->qty_on_hand); // Back to original

        // Check status
        $partLine->refresh();
        $this->assertEquals(WorkOrderItemPart::STATUS_CANCELLED, $partLine->status);
        $this->assertNotNull($partLine->deleted_at);
    }

    /** @test */
    public function it_does_not_issue_for_external_parts()
    {
        $partLine = $this->partsService->addPart([
            'work_order_id' => $this->workOrder->id,
            'work_order_item_id' => $this->workOrderItem->id,
            'tenant_id' => $this->workOrder->tenant_id,
            'center_id' => $this->workOrder->center_id,
            'part_id' => null,
            'warehouse_id' => null,
            'name' => 'External Part',
            'source' => 'external',
            'qty' => 10,
            'unit_price' => 75.00,
        ]);

        // No inventory move created
        $this->assertNull($partLine->inventory_move_id);
        $this->assertEquals(WorkOrderItemPart::STATUS_PENDING, $partLine->status);

        // Stock unchanged
        $balance = InventoryBalance::where('warehouse_id', $this->warehouse->id)
            ->where('part_id', $this->part->id)
            ->first();
        
        $this->assertEquals(100, $balance->qty_on_hand);
    }

    /** @test */
    public function it_checks_stock_availability()
    {
        $stock = $this->partsService->checkStock($this->warehouse->id, $this->part->id, 50);
        
        $this->assertTrue($stock['sufficient']);
        $this->assertEquals(100, $stock['on_hand']);
        $this->assertEquals(50, $stock['requested']);
        $this->assertEquals(0, $stock['shortage']);
        
        // Check insufficient
        $stock = $this->partsService->checkStock($this->warehouse->id, $this->part->id, 150);
        
        $this->assertFalse($stock['sufficient']);
        $this->assertEquals(50, $stock['shortage']);
    }
}
