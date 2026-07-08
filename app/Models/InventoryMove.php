<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InventoryMove extends Model
{
    use HasFactory, \App\Traits\HasInventoryMoveRelations;

    public const TYPE_RECEIPT = 'receipt';
    public const TYPE_ISSUE_TO_WORKORDER = 'issue_to_workorder';
    public const TYPE_ADJUSTMENT_IN = 'adjustment_in';
    public const TYPE_ADJUSTMENT_OUT = 'adjustment_out';
    public const TYPE_TRANSFER_OUT = 'transfer_out';
    public const TYPE_TRANSFER_IN = 'transfer_in';
    public const TYPE_REVERSAL = 'reversal';
    public const TYPE_PURCHASE_RETURN = 'purchase_return';

    protected $fillable = [
        'warehouse_id',
        'part_id',
        'move_type',
        'qty',
        'unit_cost',
        'total_cost',
        'balance_after',
        'wac_after',
        'reference_type',
        'reference_id',
        'reverses_move_id',
        'reversed_by_move_id',
        'notes',
        'posted_at',
        'posted_by',
        'reversed_at',
        'reversed_by',
    ];

    protected $casts = [
        'qty' => 'decimal:3',
        'unit_cost' => 'decimal:4',
        'total_cost' => 'decimal:2',
        'balance_after' => 'decimal:3',
        'wac_after' => 'decimal:4',
        'posted_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];

    // ─────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────



    /**
     * Polymorphic accessor: returns the WorkOrder linked to this move, if any.
     * Safe across all reference types — only WorkOrderItemPart carries a WorkOrder.
     * Use this instead of `with('reference.workOrder')` in eager loads, because
     * `workOrder` only exists on WorkOrderItemPart (and the move's reference_type
     * may legitimately be other models that don't have a `workOrder` relation).
     */
    public function getWorkOrderAttribute(): ?\App\Models\WorkOrder
    {
        $ref = $this->reference;
        if ($ref instanceof \App\Models\WorkOrderItemPart) {
            return $ref->workOrder;
        }
        if ($ref instanceof \App\Models\WorkOrder) {
            return $ref;
        }
        return null;
    }

    // ─────────────────────────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────────────────────────

    public function scopeForWarehouse($query, int $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeForPart($query, int $partId)
    {
        return $query->where('part_id', $partId);
    }

    public function scopePosted($query)
    {
        return $query->whereNotNull('posted_at');
    }

    public function scopeNotReversed($query)
    {
        return $query->whereNull('reversed_at');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('move_type', $type);
    }

    // ─────────────────────────────────────────────────────────────
    // Status Checks
    // ─────────────────────────────────────────────────────────────

    public function isPosted(): bool
    {
        return $this->posted_at !== null;
    }

    public function isReversed(): bool
    {
        return $this->reversed_at !== null;
    }

    public function canBeReversed(): bool
    {
        return $this->isPosted() && !$this->isReversed();
    }

    public function isIncoming(): bool
    {
        return in_array($this->move_type, [
            self::TYPE_RECEIPT,
            self::TYPE_ADJUSTMENT_IN,
            self::TYPE_TRANSFER_IN,
        ]);
    }

    public function isOutgoing(): bool
    {
        return in_array($this->move_type, [
            self::TYPE_ISSUE_TO_WORKORDER,
            self::TYPE_ADJUSTMENT_OUT,
            self::TYPE_TRANSFER_OUT,
            self::TYPE_PURCHASE_RETURN,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    public static function getMoveTypeLabel(string $type): string
    {
        return match ($type) {
            self::TYPE_RECEIPT => 'استلام',
            self::TYPE_ISSUE_TO_WORKORDER => 'صرف لكرت عمل',
            self::TYPE_ADJUSTMENT_IN => 'تسوية زيادة',
            self::TYPE_ADJUSTMENT_OUT => 'تسوية نقص',
            self::TYPE_TRANSFER_OUT => 'تحويل صادر',
            self::TYPE_TRANSFER_IN => 'تحويل وارد',
            self::TYPE_REVERSAL => 'عكس حركة',
            self::TYPE_PURCHASE_RETURN => 'مرتجع شراء',
            default => $type,
        };
    }
}
