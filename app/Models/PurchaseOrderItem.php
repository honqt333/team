<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\CenterScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    use CenterScoped, HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'tenant_id',
        'center_id',
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

    protected $appends = [
        'qty_pending',
        'is_fully_received',
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
        $subtotal = bcmul((string) $this->qty_ordered, (string) $this->unit_cost, 6);
        $taxRateDecimal = bcdiv((string) ($this->tax_rate ?? 15.00), '100', 6);
        $tax = bcmul($subtotal, $taxRateDecimal, 6);
        $total = bcadd($subtotal, $tax, 6);

        $this->update([
            'line_subtotal' => round((float) $subtotal, 2),
            'line_tax' => round((float) $tax, 2),
            'line_total' => round((float) $total, 2),
        ]);
    }

    /**
     * Boot method to auto-calculate totals.
     */
    protected static function booted(): void
    {
        static::saving(function (PurchaseOrderItem $item) {
            $subtotal = bcmul((string) $item->qty_ordered, (string) $item->unit_cost, 6);
            $taxRateDecimal = bcdiv((string) ($item->tax_rate ?? 15.00), '100', 6);
            $tax = bcmul($subtotal, $taxRateDecimal, 6);
            $total = bcadd($subtotal, $tax, 6);

            $item->line_subtotal = round((float) $subtotal, 2);
            $item->line_tax = round((float) $tax, 2);
            $item->line_total = round((float) $total, 2);
        });

        static::saved(function (PurchaseOrderItem $item) {
            $item->purchaseOrder->recalculateTotals();
        });

        static::deleted(function (PurchaseOrderItem $item) {
            $item->purchaseOrder->recalculateTotals();
        });
    }
}
