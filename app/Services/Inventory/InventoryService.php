<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Models\InventoryBalance;
use App\Models\InventoryMove;
use App\Models\InventoryTransfer;
use App\Models\InventoryTransferItem;
use App\Models\Part;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\TenancyContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    /**
     * Verify that the warehouse and the part belong to the authorized
     * tenant before any stock is moved.
     *
     * WHY THIS EXISTS
     *
     * The balance row is identified by its natural key
     * (warehouse_id + part_id), so every lookup in this service
     * deliberately bypasses the global scopes:
     *
     *     InventoryBalance::query()->withoutGlobalScopes()
     *         ->where('warehouse_id', $warehouseId)
     *         ->where('part_id', $partId)
     *
     * That bypass is fine for the LOOKUP, but it also meant the
     * service never checked whose resources it was touching. The
     * controllers validated the two ids with a bare
     * `exists:warehouses,id` / `exists:parts,id`, so a request could
     * name another tenant's warehouse and part and the service would
     * happily adjust that tenant's stock — recording the movement
     * under the CURRENT tenant's context while pointing at the other
     * tenant's resources.
     *
     * The check is TENANT-level, not center-level, on purpose: a
     * stock transfer between two branches of the same tenant is a
     * legitimate flow (InventoryTransfer is TenantScoped, and its
     * from/to warehouses may sit in different centers). Which centers
     * a given user may pick from is a controller concern; the tenant
     * boundary is the service's.
     */
    protected function assertResourcesInScope(int $warehouseId, int $partId): void
    {
        $tenantId = TenancyContext::tenantId();

        if ($tenantId === null) {
            // No tenant context — the caller (a console command or a
            // job that sets the context itself) owns the
            // authorization. The scope check only applies to a
            // request that has a tenant.
            return;
        }

        $warehouseBelongs = DB::table('warehouses')
            ->where('id', $warehouseId)
            ->where('tenant_id', $tenantId)
            ->exists();

        if (! $warehouseBelongs) {
            throw ValidationException::withMessages([
                'warehouse_id' => [__('inventory.errors.warehouse_not_in_scope')],
            ]);
        }

        $partBelongs = DB::table('parts')
            ->where('id', $partId)
            ->where('tenant_id', $tenantId)
            ->exists();

        if (! $partBelongs) {
            throw ValidationException::withMessages([
                'part_id' => [__('inventory.errors.part_not_in_scope')],
            ]);
        }
    }

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

        $this->assertResourcesInScope($warehouseId, $partId);

        return DB::transaction(function () use ($warehouseId, $partId, $qty, $unitCost, $userId, $referenceType, $referenceId, $notes) {
            // Lock balance row (bypass global scope — we identify by natural key warehouse_id+part_id).
            $balance = InventoryBalance::query()
                ->withoutGlobalScopes()
                ->where('warehouse_id', $warehouseId)
                ->where('part_id', $partId)
                ->lockForUpdate()
                ->first();

            if (! $balance) {
                $balance = InventoryBalance::query()->withoutGlobalScopes()->create([
                    'warehouse_id' => $warehouseId,
                    'tenant_id' => $this->resolveTenantId($warehouseId),
                    'center_id' => $this->resolveCenterId($warehouseId),
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
                // The move has to carry the tenant and the centre of
                // the warehouse it moved stock in. Without them the
                // row is invisible to every scoped query — including
                // WorkOrderItemPart::inventoryMove() — so reversing
                // the issue silently did nothing and the stock was
                // lost.
                'tenant_id' => $this->resolveTenantId($warehouseId),
                'center_id' => $this->resolveCenterId($warehouseId),
                'part_id' => $partId,
                'move_type' => InventoryMove::TYPE_RECEIPT,
                'qty' => $qty,
                'unit_cost' => $unitCost,
                'total_cost' => $qty * $unitCost,
                'balance_before' => $oldQty,
                'balance_after' => $newQty,
                'wac_before' => $oldWac,
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
     * Process a purchase return (stock out to supplier) with reverse WAC recalculation.
     */
    public function purchaseReturn(
        int $warehouseId,
        int $partId,
        float $qty,
        float $unitCost,
        ?int $userId = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null,
        bool $allowNegative = false
    ): InventoryMove {
        return $this->issue(
            warehouseId: $warehouseId,
            partId: $partId,
            qty: $qty,
            userId: $userId,
            referenceType: $referenceType,
            referenceId: $referenceId,
            notes: $notes,
            allowNegative: $allowNegative,
            moveType: InventoryMove::TYPE_PURCHASE_RETURN,
            unitCost: $unitCost
        );
    }

    /**
     * Process an issue (stock out).
     * Used for work order parts consumption or purchase returns.
     */
    public function issue(
        int $warehouseId,
        int $partId,
        float $qty,
        ?int $userId = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null,
        bool $allowNegative = false,
        ?string $moveType = null,
        ?float $unitCost = null
    ): InventoryMove {
        if ($qty <= 0) {
            throw ValidationException::withMessages([
                'qty' => ['Quantity must be positive for issue.'],
            ]);
        }

        $this->assertResourcesInScope($warehouseId, $partId);

        return DB::transaction(function () use ($warehouseId, $partId, $qty, $userId, $referenceType, $referenceId, $notes, $allowNegative, $moveType, $unitCost) {
            // Lock balance row (bypass global scope — we identify by natural key warehouse_id+part_id).
            $balance = InventoryBalance::query()
                ->withoutGlobalScopes()
                ->where('warehouse_id', $warehouseId)
                ->where('part_id', $partId)
                ->lockForUpdate()
                ->first();

            if (! $balance) {
                $balance = InventoryBalance::query()->withoutGlobalScopes()->create([
                    'warehouse_id' => $warehouseId,
                    'tenant_id' => $this->resolveTenantId($warehouseId),
                    'center_id' => $this->resolveCenterId($warehouseId),
                    'part_id' => $partId,
                    'qty_on_hand' => 0,
                    'wac_cost' => 0,
                ]);
            }

            // Check sufficient stock
            if (! $allowNegative && $balance->qty_on_hand < $qty) {
                throw ValidationException::withMessages([
                    'qty' => ['Insufficient stock. Available: '.$balance->qty_on_hand.', Requested: '.$qty],
                ]);
            }

            $oldQty = (float) $balance->qty_on_hand;
            $oldWac = (float) $balance->wac_cost;
            $newQty = $oldQty - $qty;

            $resolvedMoveType = $moveType ?? InventoryMove::TYPE_ISSUE_TO_WORKORDER;
            $effectiveUnitCost = $unitCost ?? $oldWac;

            if ($resolvedMoveType === InventoryMove::TYPE_PURCHASE_RETURN) {
                $newWac = $this->calculateReverseWAC($oldQty, $oldWac, $qty, $effectiveUnitCost);
            } else {
                $newWac = $oldWac;
            }

            // Update balance
            $balance->update([
                'qty_on_hand' => $newQty,
                'wac_cost' => $newWac,
                'last_move_at' => now(),
            ]);

            // Create move (negative qty for issue)
            return InventoryMove::create([
                'warehouse_id' => $warehouseId,
                'tenant_id' => $this->resolveTenantId($warehouseId),
                'center_id' => $this->resolveCenterId($warehouseId),
                'part_id' => $partId,
                'move_type' => $resolvedMoveType,
                'qty' => -$qty, // Negative for outgoing
                'unit_cost' => $effectiveUnitCost,
                'total_cost' => $qty * $effectiveUnitCost,
                'balance_before' => $oldQty,
                'balance_after' => $newQty,
                'wac_before' => $oldWac,
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

        $this->assertResourcesInScope($warehouseId, $partId);

        return DB::transaction(function () use ($warehouseId, $partId, $qty, $unitCost, $userId, $notes, $allowNegative) {
            // Lock balance row (bypass global scope — we identify by natural key warehouse_id+part_id).
            $balance = InventoryBalance::query()
                ->withoutGlobalScopes()
                ->where('warehouse_id', $warehouseId)
                ->where('part_id', $partId)
                ->lockForUpdate()
                ->first();

            if (! $balance) {
                $balance = InventoryBalance::query()->withoutGlobalScopes()->create([
                    'warehouse_id' => $warehouseId,
                    'tenant_id' => $this->resolveTenantId($warehouseId),
                    'center_id' => $this->resolveCenterId($warehouseId),
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
                if (! $allowNegative && $oldQty < abs($qty)) {
                    throw ValidationException::withMessages([
                        'qty' => ['Insufficient stock. Available: '.$oldQty.', Adjustment: '.abs($qty)],
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
                'tenant_id' => $this->resolveTenantId($warehouseId),
                'center_id' => $this->resolveCenterId($warehouseId),
                'part_id' => $partId,
                'move_type' => $moveType,
                'qty' => $qty,
                'unit_cost' => $cost,
                'total_cost' => abs($qty) * $cost,
                'balance_before' => $oldQty,
                'balance_after' => $newQty,
                'wac_before' => $oldWac,
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
        if (! $move->canBeReversed()) {
            throw ValidationException::withMessages([
                'move' => ['This move cannot be reversed.'],
            ]);
        }

        $this->assertResourcesInScope($move->warehouse_id, $move->part_id);

        return DB::transaction(function () use ($move, $userId, $notes) {
            // Lock balance row (bypass global scope — we identify by natural key warehouse_id+part_id).
            $balance = InventoryBalance::query()
                ->withoutGlobalScopes()
                ->where('warehouse_id', $move->warehouse_id)
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
                $newWac = $this->calculateReverseWAC($oldQty, $oldWac, abs($reversalQty), (float) $move->unit_cost);
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
                'tenant_id' => $this->resolveTenantId($move->warehouse_id),
                'center_id' => $this->resolveCenterId($move->warehouse_id),
                'part_id' => $move->part_id,
                'move_type' => InventoryMove::TYPE_REVERSAL,
                'qty' => $reversalQty,
                'unit_cost' => $move->unit_cost,
                'total_cost' => abs($reversalQty) * $move->unit_cost,
                'balance_before' => $oldQty,
                'balance_after' => $newQty,
                'wac_before' => $oldWac,
                'wac_after' => $newWac,
                'reverses_move_id' => $move->id,
                'notes' => $notes ?? 'Reversal of move #'.$move->id,
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
     * Calculate Reverse Weighted Average Cost when deducting returned goods.
     * new_wac = new_qty > 0 ? max(0, ((old_qty * old_wac) - (returned_qty * return_unit_cost)) / new_qty) : old_wac
     */
    protected function calculateReverseWAC(float $oldQty, float $oldWac, float $returnedQty, float $returnUnitCost): float
    {
        $newQty = $oldQty - $returnedQty;

        if ($newQty <= 0) {
            return $oldWac;
        }

        $remainingValue = ($oldQty * $oldWac) - ($returnedQty * $returnUnitCost);

        return round(max(0.0, $remainingValue / $newQty), 4);
    }

    /**
     * Resolve the tenant_id for a warehouse when there is no current tenant context.
     * Used by service methods that identify records by natural warehouse_id keys
     * (and therefore bypass CenterScoped globally) but still need to satisfy
     * the tenant_id NOT NULL constraint when creating new rows.
     */
    protected function resolveTenantId(int $warehouseId): ?int
    {
        $ctx = TenancyContext::tenantId();

        if ($ctx !== null) {
            return $ctx;
        }

        return DB::table('warehouses')
            ->where('id', $warehouseId)
            ->value('tenant_id');
    }

    /**
     * Resolve the center_id for a warehouse when there is no current center context.
     * Used by service methods that identify records by natural warehouse_id keys
     * (and therefore bypass CenterScoped globally) but still need to satisfy
     * the center_id NOT NULL constraint when creating new rows.
     */
    protected function resolveCenterId(int $warehouseId): ?int
    {
        $ctx = TenancyContext::centerId();

        if ($ctx !== null) {
            return $ctx;
        }

        return DB::table('warehouses')
            ->where('id', $warehouseId)
            ->value('center_id');
    }

    /**
     * Get current stock level for a part in a warehouse.
     */
    public function getStockLevel(int $warehouseId, int $partId): array
    {
        // A read of another tenant's stock level is still a
        // disclosure, and this lookup bypasses the global scopes.
        $this->assertResourcesInScope($warehouseId, $partId);

        $balance = InventoryBalance::query()
            ->withoutGlobalScopes()
            ->where('warehouse_id', $warehouseId)
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

        $this->assertResourcesInScope($originalMove->warehouse_id, $originalMove->part_id);

        return DB::transaction(function () use ($originalMove, $qty, $userId, $notes) {
            // Lock balance row (bypass global scope — we identify by natural key warehouse_id+part_id).
            $balance = InventoryBalance::query()
                ->withoutGlobalScopes()
                ->where('warehouse_id', $originalMove->warehouse_id)
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
                'tenant_id' => $this->resolveTenantId($originalMove->warehouse_id),
                'center_id' => $this->resolveCenterId($originalMove->warehouse_id),
                'part_id' => $originalMove->part_id,
                'move_type' => InventoryMove::TYPE_REVERSAL,
                'qty' => $qty, // Positive - returning to stock
                'unit_cost' => $originalMove->unit_cost,
                'total_cost' => $qty * $originalMove->unit_cost,
                'balance_before' => $oldQty,
                'balance_after' => $newQty,
                'wac_before' => $wac,
                'wac_after' => $wac,
                'reverses_move_id' => $originalMove->id,
                'notes' => $notes ?? 'Partial reversal of move #'.$originalMove->id,
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
        InventoryTransfer $transfer,
        ?int $userId = null
    ): InventoryTransfer {
        if (! $transfer->canBeSent()) {
            throw ValidationException::withMessages([
                'transfer' => ['Transfer cannot be sent in current state.'],
            ]);
        }

        return DB::transaction(function () use ($transfer, $userId) {
            foreach ($transfer->items as $item) {
                // Get WAC from source (bypass global scope — natural key lookup).
                $balance = InventoryBalance::query()
                    ->withoutGlobalScopes()
                    ->where('warehouse_id', $transfer->from_warehouse_id)
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
                    referenceType: InventoryTransferItem::class,
                    referenceId: $item->id,
                    notes: 'Transfer to '.$transfer->toWarehouse->name
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
                'status' => InventoryTransfer::STATUS_SENT,
                'sent_at' => now(),
                'sent_by' => $userId,
            ]);

            return $transfer->fresh(['items.part', 'fromWarehouse', 'toWarehouse']);
        });
    }

    /**
     * Receive a transfer (receipts into destination warehouse).
     *
     * The quantity received may never exceed the quantity that was
     * actually sent. Receiving more creates stock that never left the
     * source warehouse — the issue move stays at the original
     * quantity while the receipt inflates the destination.
     */
    public function receiveTransfer(
        InventoryTransfer $transfer,
        array $receivedQtys = [],
        ?int $userId = null
    ): InventoryTransfer {
        if (! $transfer->canBeReceived()) {
            throw ValidationException::withMessages([
                'transfer' => ['Transfer cannot be received in current state.'],
            ]);
        }

        return DB::transaction(function () use ($transfer, $receivedQtys, $userId) {
            // Reload the transfer and its items under a lock: the
            // quantity that may be received is read from the database
            // at this moment, not from a model that was loaded
            // earlier.
            $lockedTransfer = InventoryTransfer::query()
                ->lockForUpdate()
                ->findOrFail($transfer->id);

            $items = $lockedTransfer->items()->lockForUpdate()->get();

            $knownItemIds = $items->pluck('id')->map(fn ($id): string => (string) $id)->all();

            foreach (array_keys($receivedQtys) as $itemId) {
                if (! in_array((string) $itemId, $knownItemIds, true)) {
                    throw ValidationException::withMessages([
                        'received_qtys' => ['One of the received quantities does not belong to this transfer.'],
                    ]);
                }
            }

            foreach ($items as $item) {
                $qtySent = (float) $item->qty_sent;

                // Default to the full sent quantity when the caller did
                // not specify one.
                $qtyToReceive = array_key_exists($item->id, $receivedQtys)
                    ? (float) $receivedQtys[$item->id]
                    : $qtySent;

                if ($qtyToReceive < 0) {
                    throw ValidationException::withMessages([
                        'received_qtys' => ['A received quantity cannot be negative.'],
                    ]);
                }

                if ($qtyToReceive - 0.000001 > $qtySent) {
                    throw ValidationException::withMessages([
                        'received_qtys' => [
                            'The received quantity ('.$qtyToReceive.') exceeds the quantity sent ('.$qtySent.').',
                        ],
                    ]);
                }

                if ($qtyToReceive <= 0) {
                    continue;
                }

                // Create receipt move in destination warehouse
                $move = $this->receipt(
                    warehouseId: $lockedTransfer->to_warehouse_id,
                    partId: $item->part_id,
                    qty: $qtyToReceive,
                    unitCost: (float) $item->unit_cost,
                    userId: $userId,
                    referenceType: InventoryTransferItem::class,
                    referenceId: $item->id,
                    notes: 'Transfer from '.$lockedTransfer->fromWarehouse->name
                );

                // Update item with receive info
                $item->update([
                    'qty_received' => $qtyToReceive,
                    'receive_move_id' => $move->id,
                ]);
            }

            // Update transfer status
            $lockedTransfer->update([
                'status' => InventoryTransfer::STATUS_RECEIVED,
                'received_at' => now(),
                'received_by' => $userId,
            ]);

            return $lockedTransfer->fresh(['items.part', 'fromWarehouse', 'toWarehouse']);
        });
    }

    /**
     * Cancel a transfer (reverses if already sent).
     */
    public function cancelTransfer(
        InventoryTransfer $transfer,
        ?int $userId = null,
        ?string $reason = null
    ): InventoryTransfer {
        if (! $transfer->canBeCancelled()) {
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
                            $this->reverseMove($move, $userId, 'Transfer cancelled: '.($reason ?? 'No reason'));
                        }
                    }
                }
            }

            // Update transfer status
            $transfer->update([
                'status' => InventoryTransfer::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'cancelled_by' => $userId,
                'cancel_reason' => $reason,
            ]);

            return $transfer->fresh();
        });
    }
}
