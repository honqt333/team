<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'part_id',
        'qty_on_hand',
        'wac_cost',
        'last_move_at',
    ];

    protected $casts = [
        'qty_on_hand' => 'decimal:3',
        'wac_cost' => 'decimal:4',
        'last_move_at' => 'datetime',
    ];

    // ─────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
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

    public function scopeWithStock($query)
    {
        return $query->where('qty_on_hand', '>', 0);
    }

    public function scopeLowStock($query)
    {
        return $query->whereRaw('qty_on_hand <= (SELECT min_qty FROM parts WHERE parts.id = inventory_balances.part_id)');
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    /**
     * Get or create balance for a warehouse+part combination.
     */
    public static function getOrCreate(int $warehouseId, int $partId): self
    {
        return static::firstOrCreate(
            ['warehouse_id' => $warehouseId, 'part_id' => $partId],
            ['qty_on_hand' => 0, 'wac_cost' => 0]
        );
    }

    /**
     * Check if sufficient qty is available.
     */
    public function hasSufficientQty(float $qty): bool
    {
        return $this->qty_on_hand >= $qty;
    }

    /**
     * Get total value (qty * wac).
     */
    public function getTotalValueAttribute(): float
    {
        return (float) bcmul($this->qty_on_hand, $this->wac_cost, 2);
    }
}
