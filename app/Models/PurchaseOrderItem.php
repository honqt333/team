<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'part_id',
        'qty_ordered',
        'qty_received',
        'unit_cost',
        'tax_rate',
        'line_subtotal',
        'line_tax',
        'line_total',
        'notes',
    ];

    protected $casts = [
        'qty_ordered' => 'decimal:3',
        'qty_received' => 'decimal:3',
        'unit_cost' => 'decimal:4',
        'tax_rate' => 'decimal:2',
        'line_subtotal' => 'decimal:2',
        'line_tax' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    // ─────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }

    // ─────────────────────────────────────────────────────────────
    // Accessors
    // ─────────────────────────────────────────────────────────────

    public function getQtyPendingAttribute(): float
    {
        return (float) bcsub($this->qty_ordered, $this->qty_received, 3);
    }

    public function getIsFullyReceivedAttribute(): bool
    {
        return $this->qty_received >= $this->qty_ordered;
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    /**
     * Calculate line totals based on qty and unit cost.
     */
    public function calculateTotals(): void
    {
        $subtotal = bcmul($this->qty_ordered, $this->unit_cost, 2);
        $tax = bcmul($subtotal, bcdiv($this->tax_rate, 100, 4), 2);
        $total = bcadd($subtotal, $tax, 2);

        $this->update([
            'line_subtotal' => $subtotal,
            'line_tax' => $tax,
            'line_total' => $total,
        ]);
    }

    /**
     * Boot method to auto-calculate totals.
     */
    protected static function booted(): void
    {
        static::saving(function (PurchaseOrderItem $item) {
            $item->line_subtotal = bcmul($item->qty_ordered, $item->unit_cost, 2);
            $item->line_tax = bcmul($item->line_subtotal, bcdiv($item->tax_rate, 100, 4), 2);
            $item->line_total = bcadd($item->line_subtotal, $item->line_tax, 2);
        });

        static::saved(function (PurchaseOrderItem $item) {
            $item->purchaseOrder->recalculateTotals();
        });

        static::deleted(function (PurchaseOrderItem $item) {
            $item->purchaseOrder->recalculateTotals();
        });
    }
}
