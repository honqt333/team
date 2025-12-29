<?php

namespace App\Services\Inventory;

use App\Models\InventoryBalance;
use App\Models\InventoryMove;
use App\Models\Part;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    /**
     * Process a receipt (stock in).
     * Used for GRN, manual receipt, opening balance.
     */
    public function receipt(
        int $warehouseId,
        int $partId,
        float $qty,
        float $unitCost,
        ?int $userId = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null
    ): InventoryMove {
        if ($qty <= 0) {
            throw ValidationException::withMessages([
                'qty' => ['Quantity must be positive for receipt.'],
            ]);
        }

        return DB::transaction(function () use ($warehouseId, $partId, $qty, $unitCost, $userId, $referenceType, $referenceId, $notes) {
            // Lock balance row
            $balance = InventoryBalance::where('warehouse_id', $warehouseId)
                ->where('part_id', $partId)
                ->lockForUpdate()
                ->first();

            if (!$balance) {
                $balance = InventoryBalance::create([
                    'warehouse_id' => $warehouseId,
                    'part_id' => $partId,
                    'qty_on_hand' => 0,
                    'wac_cost' => 0,
                ]);
            }

            // Calculate new WAC
            $oldQty = (float) $balance->qty_on_hand;
            $oldWac = (float) $balance->wac_cost;
            $newWac = $this->calculateWAC($oldQty, $oldWac, $qty, $unitCost);

            // Update balance
            $newQty = $oldQty + $qty;
            $balance->update([
                'qty_on_hand' => $newQty,
                'wac_cost' => $newWac,
                'last_move_at' => now(),
            ]);

            // Create move
            return InventoryMove::create([
                'warehouse_id' => $warehouseId,
                'part_id' => $partId,
                'move_type' => InventoryMove::TYPE_RECEIPT,
                'qty' => $qty,
                'unit_cost' => $unitCost,
                'total_cost' => $qty * $unitCost,
                'balance_after' => $newQty,
                'wac_after' => $newWac,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'notes' => $notes,
                'posted_at' => now(),
                'posted_by' => $userId,
            ]);
        });
    }

    /**
     * Process an issue (stock out).
     * Used for work order parts consumption.
     */
    public function issue(
        int $warehouseId,
        int $partId,
        float $qty,
        ?int $userId = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null,
        bool $allowNegative = false
    ): InventoryMove {
        if ($qty <= 0) {
            throw ValidationException::withMessages([
                'qty' => ['Quantity must be positive for issue.'],
            ]);
        }

        return DB::transaction(function () use ($warehouseId, $partId, $qty, $userId, $referenceType, $referenceId, $notes, $allowNegative) {
            // Lock balance row
            $balance = InventoryBalance::where('warehouse_id', $warehouseId)
                ->where('part_id', $partId)
                ->lockForUpdate()
                ->first();

            if (!$balance) {
                $balance = InventoryBalance::create([
                    'warehouse_id' => $warehouseId,
                    'part_id' => $partId,
                    'qty_on_hand' => 0,
                    'wac_cost' => 0,
                ]);
            }

            // Check sufficient stock
            if (!$allowNegative && $balance->qty_on_hand < $qty) {
                throw ValidationException::withMessages([
                    'qty' => ['Insufficient stock. Available: ' . $balance->qty_on_hand . ', Requested: ' . $qty],
                ]);
            }

            // Calculate new balance (WAC unchanged on issue)
            $oldQty = (float) $balance->qty_on_hand;
            $wac = (float) $balance->wac_cost;
            $newQty = $oldQty - $qty;

            // Update balance
            $balance->update([
                'qty_on_hand' => $newQty,
                'last_move_at' => now(),
            ]);

            // Create move (negative qty for issue)
            return InventoryMove::create([
                'warehouse_id' => $warehouseId,
                'part_id' => $partId,
                'move_type' => InventoryMove::TYPE_ISSUE_TO_WORKORDER,
                'qty' => -$qty, // Negative for outgoing
                'unit_cost' => $wac,
                'total_cost' => $qty * $wac,
                'balance_after' => $newQty,
                'wac_after' => $wac,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'notes' => $notes,
                'posted_at' => now(),
                'posted_by' => $userId,
            ]);
        });
    }

    /**
     * Process an adjustment (positive or negative).
     */
    public function adjust(
        int $warehouseId,
        int $partId,
        float $qty,
        ?float $unitCost = null,
        ?int $userId = null,
        ?string $notes = null,
        bool $allowNegative = false
    ): InventoryMove {
        if ($qty == 0) {
            throw ValidationException::withMessages([
                'qty' => ['Adjustment quantity cannot be zero.'],
            ]);
        }

        return DB::transaction(function () use ($warehouseId, $partId, $qty, $unitCost, $userId, $notes, $allowNegative) {
            // Lock balance row
            $balance = InventoryBalance::where('warehouse_id', $warehouseId)
                ->where('part_id', $partId)
                ->lockForUpdate()
                ->first();

            if (!$balance) {
                $balance = InventoryBalance::create([
                    'warehouse_id' => $warehouseId,
                    'part_id' => $partId,
                    'qty_on_hand' => 0,
                    'wac_cost' => 0,
                ]);
            }

            $oldQty = (float) $balance->qty_on_hand;
            $oldWac = (float) $balance->wac_cost;
            $isIncrease = $qty > 0;

            // Use provided cost or current WAC
            $cost = $unitCost ?? $oldWac;

            if ($isIncrease) {
                // Adjustment In - recalculate WAC
                $newWac = $this->calculateWAC($oldQty, $oldWac, $qty, $cost);
                $moveType = InventoryMove::TYPE_ADJUSTMENT_IN;
            } else {
                // Adjustment Out - WAC unchanged
                if (!$allowNegative && $oldQty < abs($qty)) {
                    throw ValidationException::withMessages([
                        'qty' => ['Insufficient stock. Available: ' . $oldQty . ', Adjustment: ' . abs($qty)],
                    ]);
                }
                $newWac = $oldWac;
                $moveType = InventoryMove::TYPE_ADJUSTMENT_OUT;
            }

            $newQty = $oldQty + $qty;

            // Update balance
            $balance->update([
                'qty_on_hand' => $newQty,
                'wac_cost' => $newWac,
                'last_move_at' => now(),
            ]);

            // Create move
            return InventoryMove::create([
                'warehouse_id' => $warehouseId,
                'part_id' => $partId,
                'move_type' => $moveType,
                'qty' => $qty,
                'unit_cost' => $cost,
                'total_cost' => abs($qty) * $cost,
                'balance_after' => $newQty,
                'wac_after' => $newWac,
                'notes' => $notes,
                'posted_at' => now(),
                'posted_by' => $userId,
            ]);
        });
    }

    /**
     * Reverse a posted move.
     */
    public function reverseMove(
        InventoryMove $move,
        ?int $userId = null,
        ?string $notes = null
    ): InventoryMove {
        if (!$move->canBeReversed()) {
            throw ValidationException::withMessages([
                'move' => ['This move cannot be reversed.'],
            ]);
        }

        return DB::transaction(function () use ($move, $userId, $notes) {
            // Lock balance row
            $balance = InventoryBalance::where('warehouse_id', $move->warehouse_id)
                ->where('part_id', $move->part_id)
                ->lockForUpdate()
                ->firstOrFail();

            // Reverse the qty
            $reversalQty = -$move->qty;
            $oldQty = (float) $balance->qty_on_hand;
            $oldWac = (float) $balance->wac_cost;
            $newQty = $oldQty + $reversalQty;

            // Recalculate WAC if it was an incoming move
            if ($move->isIncoming()) {
                // Reversing a receipt - complicated WAC adjustment
                // For simplicity, keep current WAC (audit-friendly)
                $newWac = $oldWac;
            } else {
                $newWac = $oldWac;
            }

            // Update balance
            $balance->update([
                'qty_on_hand' => $newQty,
                'wac_cost' => $newWac,
                'last_move_at' => now(),
            ]);

            // Create reversal move
            $reversalMove = InventoryMove::create([
                'warehouse_id' => $move->warehouse_id,
                'part_id' => $move->part_id,
                'move_type' => InventoryMove::TYPE_REVERSAL,
                'qty' => $reversalQty,
                'unit_cost' => $move->unit_cost,
                'total_cost' => abs($reversalQty) * $move->unit_cost,
                'balance_after' => $newQty,
                'wac_after' => $newWac,
                'reverses_move_id' => $move->id,
                'notes' => $notes ?? 'Reversal of move #' . $move->id,
                'posted_at' => now(),
                'posted_by' => $userId,
            ]);

            // Mark original move as reversed
            $move->update([
                'reversed_at' => now(),
                'reversed_by' => $userId,
                'reversed_by_move_id' => $reversalMove->id,
            ]);

            return $reversalMove;
        });
    }

    /**
     * Calculate Weighted Average Cost.
     * WAC = (Old Qty * Old WAC + New Qty * New Cost) / (Old Qty + New Qty)
     */
    protected function calculateWAC(float $oldQty, float $oldWac, float $newQty, float $newCost): float
    {
        $totalQty = $oldQty + $newQty;

        if ($totalQty <= 0) {
            return $newCost; // If zero or negative, use new cost
        }

        $totalValue = ($oldQty * $oldWac) + ($newQty * $newCost);

        return round($totalValue / $totalQty, 4);
    }

    /**
     * Get current stock level for a part in a warehouse.
     */
    public function getStockLevel(int $warehouseId, int $partId): array
    {
        $balance = InventoryBalance::where('warehouse_id', $warehouseId)
            ->where('part_id', $partId)
            ->first();

        return [
            'qty_on_hand' => $balance?->qty_on_hand ?? 0,
            'wac_cost' => $balance?->wac_cost ?? 0,
            'total_value' => $balance?->total_value ?? 0,
        ];
    }

    /**
     * Check if user can override negative stock.
     */
    public function canOverrideNegativeStock(?User $user): bool
    {
        return $user?->can('inventory.override_negative_stock') ?? false;
    }

    /**
     * Create a partial reversal for quantity reduction.
     * Used when work order part qty is reduced but not fully removed.
     */
    public function createPartialReversal(
        InventoryMove $originalMove,
        float $qty,
        ?int $userId = null,
        ?string $notes = null
    ): InventoryMove {
        if ($qty <= 0) {
            throw ValidationException::withMessages([
                'qty' => ['Reversal quantity must be positive.'],
            ]);
        }

        return DB::transaction(function () use ($originalMove, $qty, $userId, $notes) {
            // Lock balance row
            $balance = InventoryBalance::where('warehouse_id', $originalMove->warehouse_id)
                ->where('part_id', $originalMove->part_id)
                ->lockForUpdate()
                ->firstOrFail();

            $oldQty = (float) $balance->qty_on_hand;
            $wac = (float) $balance->wac_cost;
            $newQty = $oldQty + $qty; // Adding back

            // Update balance
            $balance->update([
                'qty_on_hand' => $newQty,
                'last_move_at' => now(),
            ]);

            // Create partial reversal move (positive qty - returning stock)
            return InventoryMove::create([
                'warehouse_id' => $originalMove->warehouse_id,
                'part_id' => $originalMove->part_id,
                'move_type' => InventoryMove::TYPE_REVERSAL,
                'qty' => $qty, // Positive - returning to stock
                'unit_cost' => $originalMove->unit_cost,
                'total_cost' => $qty * $originalMove->unit_cost,
                'balance_after' => $newQty,
                'wac_after' => $wac,
                'reverses_move_id' => $originalMove->id,
                'notes' => $notes ?? 'Partial reversal of move #' . $originalMove->id,
                'posted_at' => now(),
                'posted_by' => $userId,
            ]);
        });
    }

    // ─────────────────────────────────────────────────────────────
    // Transfer Methods
    // ─────────────────────────────────────────────────────────────

    /**
     * Send a transfer (issues from source warehouse).
     */
    public function sendTransfer(
        \App\Models\InventoryTransfer $transfer,
        ?int $userId = null
    ): \App\Models\InventoryTransfer {
        if (!$transfer->canBeSent()) {
            throw ValidationException::withMessages([
                'transfer' => ['Transfer cannot be sent in current state.'],
            ]);
        }

        return DB::transaction(function () use ($transfer, $userId) {
            foreach ($transfer->items as $item) {
                // Get WAC from source
                $balance = InventoryBalance::where('warehouse_id', $transfer->from_warehouse_id)
                    ->where('part_id', $item->part_id)
                    ->lockForUpdate()
                    ->first();

                $wac = $balance?->wac_cost ?? 0;

                // Create issue move from source warehouse
                $move = $this->issue(
                    warehouseId: $transfer->from_warehouse_id,
                    partId: $item->part_id,
                    qty: (float) $item->qty_requested,
                    userId: $userId,
                    referenceType: \App\Models\InventoryTransferItem::class,
                    referenceId: $item->id,
                    notes: "Transfer to " . $transfer->toWarehouse->name
                );

                // Update item with send info
                $item->update([
                    'qty_sent' => $item->qty_requested,
                    'unit_cost' => $wac,
                    'send_move_id' => $move->id,
                ]);
            }

            // Update transfer status
            $transfer->update([
                'status' => \App\Models\InventoryTransfer::STATUS_SENT,
                'sent_at' => now(),
                'sent_by' => $userId,
            ]);

            return $transfer->fresh(['items.part', 'fromWarehouse', 'toWarehouse']);
        });
    }

    /**
     * Receive a transfer (receipts into destination warehouse).
     */
    public function receiveTransfer(
        \App\Models\InventoryTransfer $transfer,
        array $receivedQtys = [],
        ?int $userId = null
    ): \App\Models\InventoryTransfer {
        if (!$transfer->canBeReceived()) {
            throw ValidationException::withMessages([
                'transfer' => ['Transfer cannot be received in current state.'],
            ]);
        }

        return DB::transaction(function () use ($transfer, $receivedQtys, $userId) {
            foreach ($transfer->items as $item) {
                // Get qty to receive (default to qty_sent if not specified)
                $qtyToReceive = $receivedQtys[$item->id] ?? (float) $item->qty_sent;
                
                if ($qtyToReceive <= 0) {
                    continue;
                }

                // Create receipt move in destination warehouse
                $move = $this->receipt(
                    warehouseId: $transfer->to_warehouse_id,
                    partId: $item->part_id,
                    qty: $qtyToReceive,
                    unitCost: (float) $item->unit_cost,
                    userId: $userId,
                    referenceType: \App\Models\InventoryTransferItem::class,
                    referenceId: $item->id,
                    notes: "Transfer from " . $transfer->fromWarehouse->name
                );

                // Update item with receive info
                $item->update([
                    'qty_received' => $qtyToReceive,
                    'receive_move_id' => $move->id,
                ]);
            }

            // Update transfer status
            $transfer->update([
                'status' => \App\Models\InventoryTransfer::STATUS_RECEIVED,
                'received_at' => now(),
                'received_by' => $userId,
            ]);

            return $transfer->fresh(['items.part', 'fromWarehouse', 'toWarehouse']);
        });
    }

    /**
     * Cancel a transfer (reverses if already sent).
     */
    public function cancelTransfer(
        \App\Models\InventoryTransfer $transfer,
        ?int $userId = null,
        ?string $reason = null
    ): \App\Models\InventoryTransfer {
        if (!$transfer->canBeCancelled()) {
            throw ValidationException::withMessages([
                'transfer' => ['Transfer cannot be cancelled in current state.'],
            ]);
        }

        return DB::transaction(function () use ($transfer, $userId, $reason) {
            // If sent, reverse the issue moves
            if ($transfer->isSent()) {
                foreach ($transfer->items as $item) {
                    if ($item->send_move_id) {
                        $move = InventoryMove::find($item->send_move_id);
                        if ($move && $move->canBeReversed()) {
                            $this->reverseMove($move, $userId, 'Transfer cancelled: ' . ($reason ?? 'No reason'));
                        }
                    }
                }
            }

            // Update transfer status
            $transfer->update([
                'status' => \App\Models\InventoryTransfer::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'cancelled_by' => $userId,
                'cancel_reason' => $reason,
            ]);

            return $transfer->fresh();
        });
    }
}

