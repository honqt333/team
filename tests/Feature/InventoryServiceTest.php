<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\InventoryBalance;
use App\Models\InventoryMove;
use App\Models\Part;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InventoryService $service;
    protected User $user;
    protected Warehouse $warehouse;
    protected Part $part;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = app(InventoryService::class);
        
        // Create tenant and center
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);
        
        $this->user = User::factory()->create(['tenant_id' => $tenant->id]);
        $this->warehouse = Warehouse::factory()->create(['center_id' => $center->id, 'is_default' => true]);
        $this->part = Part::factory()->create(['tenant_id' => $tenant->id, 'sku' => 'TEST-001']);
    }

    /** @test */
    public function it_can_receipt_stock()
    {
        $move = $this->service->receipt(
            warehouseId: $this->warehouse->id,
            partId: $this->part->id,
            qty: 10,
            unitCost: 50.00,
            userId: $this->user->id
        );

        $this->assertInstanceOf(InventoryMove::class, $move);
        $this->assertEquals(InventoryMove::TYPE_RECEIPT, $move->move_type);
        $this->assertEquals(10, $move->qty);
        $this->assertEquals(50.00, $move->unit_cost);
        $this->assertEquals(10, $move->balance_after);
        
        // Check balance updated
        $balance = InventoryBalance::where('warehouse_id', $this->warehouse->id)
            ->where('part_id', $this->part->id)
            ->first();
        
        $this->assertNotNull($balance);
        $this->assertEquals(10, $balance->qty_on_hand);
        $this->assertEquals(50.00, $balance->wac_cost);
    }

    /** @test */
    public function it_calculates_wac_correctly_on_multiple_receipts()
    {
        // First receipt: 10 units at 50
        $this->service->receipt($this->warehouse->id, $this->part->id, 10, 50.00);
        
        // Second receipt: 10 units at 70
        $move = $this->service->receipt($this->warehouse->id, $this->part->id, 10, 70.00);
        
        // WAC should be (10*50 + 10*70) / 20 = 1200/20 = 60
        $balance = InventoryBalance::where('warehouse_id', $this->warehouse->id)
            ->where('part_id', $this->part->id)
            ->first();
        
        $this->assertEquals(20, $balance->qty_on_hand);
        $this->assertEquals(60.00, $balance->wac_cost);
        $this->assertEquals(20, $move->balance_after);
    }

    /** @test */
    public function it_can_issue_stock()
    {
        // First add some stock
        $this->service->receipt($this->warehouse->id, $this->part->id, 20, 50.00);
        
        // Issue 5 units
        $move = $this->service->issue(
            warehouseId: $this->warehouse->id,
            partId: $this->part->id,
            qty: 5,
            userId: $this->user->id
        );

        $this->assertEquals(InventoryMove::TYPE_ISSUE_TO_WORKORDER, $move->move_type);
        $this->assertEquals(-5, $move->qty); // Negative for outgoing
        $this->assertEquals(15, $move->balance_after);
        
        // Check balance
        $balance = InventoryBalance::where('warehouse_id', $this->warehouse->id)
            ->where('part_id', $this->part->id)
            ->first();
        
        $this->assertEquals(15, $balance->qty_on_hand);
        $this->assertEquals(50.00, $balance->wac_cost); // WAC unchanged on issue
    }

    /** @test */
    public function it_blocks_issue_when_insufficient_stock()
    {
        // Add 5 units
        $this->service->receipt($this->warehouse->id, $this->part->id, 5, 50.00);
        
        // Try to issue 10 (should fail)
        $this->expectException(ValidationException::class);
        
        $this->service->issue(
            warehouseId: $this->warehouse->id,
            partId: $this->part->id,
            qty: 10
        );
    }

    /** @test */
    public function it_allows_negative_stock_when_permitted()
    {
        // Add 5 units
        $this->service->receipt($this->warehouse->id, $this->part->id, 5, 50.00);
        
        // Issue 10 with allowNegative flag
        $move = $this->service->issue(
            warehouseId: $this->warehouse->id,
            partId: $this->part->id,
            qty: 10,
            allowNegative: true
        );

        $this->assertEquals(-5, $move->balance_after);
        
        $balance = InventoryBalance::where('warehouse_id', $this->warehouse->id)
            ->where('part_id', $this->part->id)
            ->first();
        
        $this->assertEquals(-5, $balance->qty_on_hand);
    }

    /** @test */
    public function it_can_adjust_stock_positive()
    {
        // Positive adjustment
        $move = $this->service->adjust(
            warehouseId: $this->warehouse->id,
            partId: $this->part->id,
            qty: 10,
            unitCost: 45.00,
            notes: 'Opening balance'
        );

        $this->assertEquals(InventoryMove::TYPE_ADJUSTMENT_IN, $move->move_type);
        $this->assertEquals(10, $move->qty);
        $this->assertEquals(10, $move->balance_after);
        
        $balance = InventoryBalance::where('warehouse_id', $this->warehouse->id)
            ->where('part_id', $this->part->id)
            ->first();
        
        $this->assertEquals(10, $balance->qty_on_hand);
        $this->assertEquals(45.00, $balance->wac_cost);
    }

    /** @test */
    public function it_can_adjust_stock_negative()
    {
        // Add stock first
        $this->service->receipt($this->warehouse->id, $this->part->id, 20, 50.00);
        
        // Negative adjustment
        $move = $this->service->adjust(
            warehouseId: $this->warehouse->id,
            partId: $this->part->id,
            qty: -5,
            notes: 'Inventory count variance'
        );

        $this->assertEquals(InventoryMove::TYPE_ADJUSTMENT_OUT, $move->move_type);
        $this->assertEquals(-5, $move->qty);
        $this->assertEquals(15, $move->balance_after);
        
        $balance = InventoryBalance::where('warehouse_id', $this->warehouse->id)
            ->where('part_id', $this->part->id)
            ->first();
        
        $this->assertEquals(15, $balance->qty_on_hand);
    }

    /** @test */
    public function it_can_reverse_a_move()
    {
        // Create a receipt
        $originalMove = $this->service->receipt($this->warehouse->id, $this->part->id, 10, 50.00);
        
        // Reverse it
        $reversalMove = $this->service->reverseMove($originalMove, $this->user->id);

        $this->assertEquals(InventoryMove::TYPE_REVERSAL, $reversalMove->move_type);
        $this->assertEquals(-10, $reversalMove->qty);
        $this->assertEquals($originalMove->id, $reversalMove->reverses_move_id);
        
        // Original should be marked as reversed
        $originalMove->refresh();
        $this->assertNotNull($originalMove->reversed_at);
        $this->assertEquals($reversalMove->id, $originalMove->reversed_by_move_id);
        
        // Balance should be 0
        $balance = InventoryBalance::where('warehouse_id', $this->warehouse->id)
            ->where('part_id', $this->part->id)
            ->first();
        
        $this->assertEquals(0, $balance->qty_on_hand);
    }

    /** @test */
    public function it_cannot_reverse_same_move_twice()
    {
        $move = $this->service->receipt($this->warehouse->id, $this->part->id, 10, 50.00);
        
        // First reversal succeeds
        $this->service->reverseMove($move);
        
        // Second reversal should fail
        $this->expectException(ValidationException::class);
        
        $move->refresh();
        $this->service->reverseMove($move);
    }

    /** @test */
    public function it_rejects_zero_qty_for_adjustment()
    {
        $this->expectException(ValidationException::class);
        
        $this->service->adjust(
            warehouseId: $this->warehouse->id,
            partId: $this->part->id,
            qty: 0
        );
    }

    /** @test */
    public function it_rejects_negative_qty_for_receipt()
    {
        $this->expectException(ValidationException::class);
        
        $this->service->receipt(
            warehouseId: $this->warehouse->id,
            partId: $this->part->id,
            qty: -5,
            unitCost: 50.00
        );
    }
}
