<?php

namespace App\Models;

use App\Models\Concerns\CenterScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkOrderItemPart extends Model
{
    use CenterScoped, SoftDeletes;

    // Source constants
    public const SOURCE_WAREHOUSE = 'warehouse';
    public const SOURCE_EXTERNAL = 'external';
    public const SOURCE_CUSTOMER = 'customer';

    public const SOURCES = [
        self::SOURCE_WAREHOUSE,
        self::SOURCE_EXTERNAL,
        self::SOURCE_CUSTOMER,
    ];

    // Status constants for inventory tracking
    public const STATUS_PENDING = 'pending';
    public const STATUS_ISSUED = 'issued';
    public const STATUS_REVERSED = 'reversed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'work_order_item_parts';

    protected $fillable = [
        'work_order_id',
        'work_order_item_id',
        'tenant_id',
        'center_id',
        'part_id',
        'warehouse_id',
        'name',
        'part_number',
        'source',
        'qty',
        'unit_price',
        'total',
        'notes',
        'inventory_move_id',
        'cost_snapshot',
        'issued_at',
        'status',
        'reversed_at',
        'reversed_by',
        'reversal_move_id',
        // New Quote Fields
        'unit_id',
        'discount',
        'include_in_package',
        'hide_on_print',
    ];

    protected $appends = [
        'tax_amount',
        'grand_total'
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total' => 'decimal:2',
        'cost_snapshot' => 'decimal:4',
        'issued_at' => 'datetime',
        'reversed_at' => 'datetime',
        'discount' => 'decimal:2',
        'include_in_package' => 'boolean',
        'hide_on_print' => 'boolean',
    ];

    // ─────────────────────────────────────────────────────────────
    // Boot
    // ─────────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::saving(function (WorkOrderItemPart $part) {
            // total = (qty * unit_price) - discount
            $subtotal = bcmul($part->qty, $part->unit_price, 2);
            $part->total = bcsub($subtotal, $part->discount ?? 0, 2);
            if ($part->total < 0) $part->total = 0;
        });
    }

    // ─────────────────────────────────────────────────────────────
    // Accessors
    // ─────────────────────────────────────────────────────────────

    public function getTaxAmountAttribute(): float
    {
        $workOrder = $this->workOrder;
        if (!$workOrder || !$workOrder->tax_enabled_snapshot) {
            return 0;
        }

        $rate = (string) ($workOrder->tax_rate_snapshot ?? 15);
        $isInclusive = $workOrder->pricing_mode_snapshot === 'inclusive';
        $total = (string) $this->total;

        if ($isInclusive) {
            // formula: total - (total / (1 + (rate / 100)))
            $divisor = bcadd('1', bcdiv($rate, '100', 4), 4);
            $net = bcdiv($total, $divisor, 2);
            return (float) bcsub($total, $net, 2);
        }

        // formula: total * (rate / 100)
        return (float) bcmul($total, bcdiv($rate, '100', 4), 2);
    }

    public function getGrandTotalAttribute(): float
    {
        $workOrder = $this->workOrder;
        $total = (string) $this->total;
        
        if (!$workOrder || !$workOrder->tax_enabled_snapshot || $workOrder->pricing_mode_snapshot === 'inclusive') {
            return (float) $total;
        }

        return (float) bcadd($total, (string) $this->tax_amount, 2);
    }


    // ─────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function workOrderItem(): BelongsTo
    {
        return $this->belongsTo(WorkOrderItem::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function inventoryMove(): BelongsTo
    {
        return $this->belongsTo(InventoryMove::class);
    }

    public function reversalMove(): BelongsTo
    {
        return $this->belongsTo(InventoryMove::class, 'reversal_move_id');
    }

    public function reversedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    // ─────────────────────────────────────────────────────────────
    // Source Checks
    // ─────────────────────────────────────────────────────────────

    public function isFromWarehouse(): bool
    {
        return $this->source === self::SOURCE_WAREHOUSE;
    }

    public function isExternal(): bool
    {
        return $this->source === self::SOURCE_EXTERNAL;
    }

    public function isFromCustomer(): bool
    {
        return $this->source === self::SOURCE_CUSTOMER;
    }

    // ─────────────────────────────────────────────────────────────
    // Status Checks
    // ─────────────────────────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING || $this->status === null;
    }

    public function isIssued(): bool
    {
        return $this->status === self::STATUS_ISSUED;
    }

    public function isReversed(): bool
    {
        return $this->status === self::STATUS_REVERSED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function canBeReversed(): bool
    {
        return $this->isIssued() && $this->isFromWarehouse() && $this->inventory_move_id !== null;
    }

    public function canBeIssued(): bool
    {
        return $this->isPending() && $this->isFromWarehouse() && $this->part_id !== null && $this->warehouse_id !== null;
    }

    // ─────────────────────────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────────────────────────

    public function scopeFromWarehouse($query)
    {
        return $query->where('source', self::SOURCE_WAREHOUSE);
    }

    public function scopeIssued($query)
    {
        return $query->where('status', self::STATUS_ISSUED);
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [self::STATUS_REVERSED, self::STATUS_CANCELLED]);
    }
}
