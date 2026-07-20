<?php

namespace Tests\Unit\Services\Inventory;

use App\Models\Center;
use App\Models\InventoryBalance;
use App\Models\Part;
use App\Models\Tenant;
use App\Models\Warehouse;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Models\WorkOrderItemPart;
use App\Services\Inventory\InventoryService;
use App\Services\Inventory\WorkOrderPartsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkOrderPartsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected WorkOrderPartsService $partsService;
    protected InventoryService $inventoryService;
    protected Tenant $tenant;
    protected Center $center;
    protected Warehouse $warehouse;
    protected Part $part;
    protected WorkOrder $workOrder;
    protected WorkOrderItem $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inventoryService = new InventoryService();
        $this->partsService = new WorkOrderPartsService($this->inventoryService);

        $this->tenant = Tenant::factory()->create();
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->warehouse = Warehouse::factory()->create(['tenant_id' => $this->tenant->id, 'center_id' => $this->center->id]);
        $this->part = Part::factory()->create(['tenant_id' => $this->tenant->id, 'sku' => 'OIL-FILTER-01']);

        // Initialize stock balance
        InventoryBalance::create([
            'tenant_id' => $this->tenant->id,
            'warehouse_id' => $this->warehouse->id,
            'part_id' => $this->part->id,
            'qty_on_hand' => 10.00,
            'wac_cost' => 25.00,
        ]);

        $this->workOrder = WorkOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
        ]);

        $this->item = WorkOrderItem::create([
            'work_order_id' => $this->workOrder->id,
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'title' => 'تغيير زيت',
            'qty' => 1,
            'unit_price' => 50,
            'status' => 'pending',
        ]);
    }

    public function test_add_part_from_warehouse_auto_issues_inventory()
    {
        $partLine = $this->partsService->addPart([
            'work_order_id' => $this->workOrder->id,
            'work_order_item_id' => $this->item->id,
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'part_id' => $this->part->id,
            'warehouse_id' => $this->warehouse->id,
            'name' => 'فلتر زيت الأصلي',
            'source' => WorkOrderItemPart::SOURCE_WAREHOUSE,
            'qty' => 2.00,
            'unit_price' => 35.00,
        ]);

        $this->assertEquals(WorkOrderItemPart::STATUS_ISSUED, $partLine->status);
        $this->assertNotNull($partLine->inventory_move_id);

        $balance = InventoryBalance::where('warehouse_id', $this->warehouse->id)->where('part_id', $this->part->id)->first();
        $this->assertEquals(8.00, (float)$balance->qty_on_hand);
    }

    public function test_add_part_from_external_source_does_not_deduct_inventory()
    {
        $partLine = $this->partsService->addPart([
            'work_order_id' => $this->workOrder->id,
            'work_order_item_id' => $this->item->id,
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'part_id' => null,
            'warehouse_id' => null,
            'name' => 'قطع خارجية مكسورة',
            'source' => WorkOrderItemPart::SOURCE_EXTERNAL,
            'qty' => 1.00,
            'unit_price' => 100.00,
        ]);

        $this->assertEquals(WorkOrderItemPart::STATUS_PENDING, $partLine->status);
        $this->assertNull($partLine->inventory_move_id);

        $balance = InventoryBalance::where('warehouse_id', $this->warehouse->id)->where('part_id', $this->part->id)->first();
        $this->assertEquals(10.00, (float)$balance->qty_on_hand);
    }

    public function test_update_part_handles_qty_increase_and_decrease()
    {
        $partLine = $this->partsService->addPart([
            'work_order_id' => $this->workOrder->id,
            'work_order_item_id' => $this->item->id,
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'part_id' => $this->part->id,
            'warehouse_id' => $this->warehouse->id,
            'name' => 'فلتر زيت',
            'source' => WorkOrderItemPart::SOURCE_WAREHOUSE,
            'qty' => 2.00,
            'unit_price' => 30.00,
        ]);

        // Increase quantity to 5
        $updatedLine = $this->partsService->updatePart($partLine, ['qty' => 5.00]);
        $this->assertEquals(5.00, (float)$updatedLine->qty);
        
        $balance = InventoryBalance::where('warehouse_id', $this->warehouse->id)->where('part_id', $this->part->id)->first();
        $this->assertEquals(5.00, (float)$balance->qty_on_hand);

        // Decrease quantity to 1
        $reducedLine = $this->partsService->updatePart($updatedLine, ['qty' => 1.00]);
        $this->assertEquals(1.00, (float)$reducedLine->qty);

        $balanceRefresh = InventoryBalance::where('warehouse_id', $this->warehouse->id)->where('part_id', $this->part->id)->first();
        $this->assertEquals(9.00, (float)$balanceRefresh->qty_on_hand);
    }

    public function test_remove_part_reverses_inventory_and_soft_deletes()
    {
        $partLine = $this->partsService->addPart([
            'work_order_id' => $this->workOrder->id,
            'work_order_item_id' => $this->item->id,
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'part_id' => $this->part->id,
            'warehouse_id' => $this->warehouse->id,
            'name' => 'فلتر زيت',
            'source' => WorkOrderItemPart::SOURCE_WAREHOUSE,
            'qty' => 3.00,
            'unit_price' => 30.00,
        ]);

        $this->partsService->removePart($partLine);

        $this->assertSoftDeleted('work_order_item_parts', ['id' => $partLine->id]);

        $balance = InventoryBalance::where('warehouse_id', $this->warehouse->id)->where('part_id', $this->part->id)->first();
        $this->assertEquals(10.00, (float)$balance->qty_on_hand);
    }

    public function test_check_stock_returns_accurate_on_hand_and_shortage()
    {
        $res = $this->partsService->checkStock($this->warehouse->id, $this->part->id, 5.00);
        $this->assertTrue($res['sufficient']);
        $this->assertEquals(10.00, $res['on_hand']);
        $this->assertEquals(0, $res['shortage']);

        $resShort = $this->partsService->checkStock($this->warehouse->id, $this->part->id, 15.00);
        $this->assertFalse($resShort['sufficient']);
        $this->assertEquals(5.00, $resShort['shortage']);
    }
}
