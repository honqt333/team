<?php

namespace App\Services\Inventory;

use App\Models\InventoryBalance;
use App\Models\InventoryMove;
use App\Models\Part;
use App\Models\Warehouse;
use App\Models\WorkOrderItemPart;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkOrderPartsService
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    /**
     * Add a part to a work order with auto-issue from inventory.
     * 
     * @throws ValidationException if insufficient stock
     */
    public function addPart(array $data, bool $allowNegative = false): WorkOrderItemPart
    {
        return DB::transaction(function () use ($data, $allowNegative) {
            $isWarehouseSource = ($data['source'] ?? 'warehouse') === WorkOrderItemPart::SOURCE_WAREHOUSE;
            $partId = $data['part_id'] ?? null;
            $warehouseId = $data['warehouse_id'] ?? null;

            // Create the line item first
            $partLine = WorkOrderItemPart::create([
                'work_order_id' => $data['work_order_id'] ?? null,
                'work_order_item_id' => $data['work_order_item_id'],
                'tenant_id' => $data['tenant_id'],
                'center_id' => $data['center_id'],
                'part_id' => $partId,
                'warehouse_id' => $warehouseId,
                'name' => $data['name'],
                'part_number' => $data['part_number'] ?? null,
                'source' => $data['source'] ?? WorkOrderItemPart::SOURCE_WAREHOUSE,
                'qty' => $data['qty'],
                'unit_price' => $data['unit_price'],
                'notes' => $data['notes'] ?? null,
                'status' => WorkOrderItemPart::STATUS_PENDING,
            ]);

            // Auto-issue if from warehouse
            if ($isWarehouseSource && $partId && $warehouseId) {
                $this->issuePartFromInventory($partLine, $allowNegative);
            }

            return $partLine->fresh(['part', 'warehouse', 'inventoryMove']);
        });
    }

    /**
     * Update part quantity - handles delta issue/reversal.
     */
    public function updatePart(WorkOrderItemPart $partLine, array $data, bool $allowNegative = false): WorkOrderItemPart
    {
        return DB::transaction(function () use ($partLine, $data, $allowNegative) {
            $oldQty = (float) $partLine->qty;
            $newQty = (float) ($data['qty'] ?? $oldQty);
            $delta = $newQty - $oldQty;

            // Update basic fields
            $partLine->fill([
                'name' => $data['name'] ?? $partLine->name,
                'part_number' => $data['part_number'] ?? $partLine->part_number,
                'unit_price' => $data['unit_price'] ?? $partLine->unit_price,
                'notes' => $data['notes'] ?? $partLine->notes,
            ]);

            // Handle quantity delta for warehouse items
            if ($delta != 0 && $partLine->isFromWarehouse() && $partLine->isIssued()) {
                $this->handleQuantityDelta($partLine, $delta, $allowNegative);
            }

            $partLine->qty = $newQty;
            $partLine->save();

            return $partLine->fresh(['part', 'warehouse', 'inventoryMove']);
        });
    }

    /**
     * Change part or warehouse - full reversal and re-issue.
     */
    public function changePart(WorkOrderItemPart $partLine, array $data, bool $allowNegative = false): WorkOrderItemPart
    {
        return DB::transaction(function () use ($partLine, $data, $allowNegative) {
            // Reverse old if issued
            if ($partLine->canBeReversed()) {
                $this->reversePartIssue($partLine, null, 'Part/warehouse changed');
            }

            // Update to new part/warehouse
            $partLine->update([
                'part_id' => $data['part_id'],
                'warehouse_id' => $data['warehouse_id'],
                'name' => $data['name'] ?? $partLine->name,
                'qty' => $data['qty'] ?? $partLine->qty,
                'unit_price' => $data['unit_price'] ?? $partLine->unit_price,
                'status' => WorkOrderItemPart::STATUS_PENDING,
                'inventory_move_id' => null,
                'cost_snapshot' => null,
                'issued_at' => null,
            ]);

            // Issue new
            if ($partLine->canBeIssued()) {
                $this->issuePartFromInventory($partLine, $allowNegative);
            }

            return $partLine->fresh(['part', 'warehouse', 'inventoryMove']);
        });
    }

    /**
     * Remove/cancel a part line - soft delete with reversal.
     */
    public function removePart(WorkOrderItemPart $partLine, ?int $userId = null): void
    {
        DB::transaction(function () use ($partLine, $userId) {
            // Reverse inventory if issued
            if ($partLine->canBeReversed()) {
                $this->reversePartIssue($partLine, $userId, 'Part line removed');
            }

            // Soft delete - keep for audit
            $partLine->update(['status' => WorkOrderItemPart::STATUS_CANCELLED]);
            $partLine->delete();
        });
    }

    /**
     * Issue a part from inventory.
     */
    protected function issuePartFromInventory(WorkOrderItemPart $partLine, bool $allowNegative = false): void
    {
        // Idempotency guard
        if ($partLine->inventory_move_id !== null) {
            return;
        }

        $qty = (float) $partLine->qty;

        // Create issue move
        $move = $this->inventoryService->issue(
            warehouseId: $partLine->warehouse_id,
            partId: $partLine->part_id,
            qty: $qty,
            userId: auth()->id(),
            allowNegative: $allowNegative,
            referenceType: WorkOrderItemPart::class,
            referenceId: $partLine->id,
            notes: "WO Part Issue"
        );

        // Update line with issue info
        $partLine->update([
            'inventory_move_id' => $move->id,
            'cost_snapshot' => $move->unit_cost,
            'issued_at' => now(),
            'status' => WorkOrderItemPart::STATUS_ISSUED,
        ]);
    }

    /**
     * Handle quantity delta - issue more or reverse partial.
     */
    protected function handleQuantityDelta(WorkOrderItemPart $partLine, float $delta, bool $allowNegative): void
    {
        if ($delta > 0) {
            // Need more - issue additional
            $move = $this->inventoryService->issue(
                warehouseId: $partLine->warehouse_id,
                partId: $partLine->part_id,
                qty: $delta,
                userId: auth()->id(),
                allowNegative: $allowNegative,
                referenceType: WorkOrderItemPart::class,
                referenceId: $partLine->id,
                notes: "WO Part Qty Increase"
            );
            
            // Update cost snapshot to latest WAC
            $partLine->cost_snapshot = $move->unit_cost;
            
        } elseif ($delta < 0) {
            // Returning some - create reversal for the delta
            $originalMove = $partLine->inventoryMove;
            if ($originalMove) {
                $this->inventoryService->createPartialReversal(
                    originalMove: $originalMove,
                    qty: abs($delta),
                    userId: auth()->id(),
                    notes: "WO Part Qty Decrease"
                );
            }
        }
    }

    /**
     * Fully reverse a part issue.
     */
    public function reversePartIssue(WorkOrderItemPart $partLine, ?int $userId = null, ?string $notes = null): ?InventoryMove
    {
        if (!$partLine->canBeReversed()) {
            return null;
        }

        $originalMove = $partLine->inventoryMove;
        if (!$originalMove) {
            return null;
        }

        // Create reversal move
        $reversalMove = $this->inventoryService->reverseMove($originalMove, $userId, $notes);

        // Update line status
        $partLine->update([
            'status' => WorkOrderItemPart::STATUS_REVERSED,
            'reversed_at' => now(),
            'reversed_by' => $userId ?? auth()->id(),
            'reversal_move_id' => $reversalMove->id,
        ]);

        return $reversalMove;
    }

    /**
     * Check if sufficient stock exists.
     */
    public function checkStock(int $warehouseId, int $partId, float $qty): array
    {
        $balance = InventoryBalance::where('warehouse_id', $warehouseId)
            ->where('part_id', $partId)
            ->first();

        $onHand = $balance ? (float) $balance->qty_on_hand : 0;
        $sufficient = $onHand >= $qty;

        return [
            'on_hand' => $onHand,
            'requested' => $qty,
            'sufficient' => $sufficient,
            'shortage' => $sufficient ? 0 : $qty - $onHand,
            'wac_cost' => $balance ? (float) $balance->wac_cost : 0,
        ];
    }
}
