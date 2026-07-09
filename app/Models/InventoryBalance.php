<?php

namespace App\Models;

use App\Models\Concerns\CenterScoped;
use App\Support\TenancyContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class InventoryBalance extends Model
{
    use CenterScoped, HasFactory;

    protected $fillable = [
        'warehouse_id',
        'tenant_id',
        'center_id',
        'part_id',
        'qty_on_hand',
        'wac_cost',
        'last_move_at',
        'sale_price',
        'min_sale_price',
        'min_stock',
        'storage_location',
        'allow_price_change',
        'is_active',
    ];

    protected $casts = [
        'qty_on_hand' => 'decimal:3',
        'wac_cost' => 'decimal:4',
        'last_move_at' => 'datetime',
        'sale_price' => 'decimal:2',
        'min_sale_price' => 'decimal:2',
        'min_stock' => 'decimal:3',
        'allow_price_change' => 'boolean',
        'is_active' => 'boolean',
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
        return $query->whereRaw('qty_on_hand <= min_stock');
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    /**
     * Get or create balance for a warehouse+part combination.
     */
    public static function getOrCreate(int $warehouseId, int $partId): self
    {
        // Without global scopes — we identify the balance by its natural key (warehouse+part),
        // not by tenant. The unique constraint at the DB level guarantees no duplicates.
        $balance = static::query()
            ->withoutGlobalScopes()
            ->where('warehouse_id', $warehouseId)
            ->where('part_id', $partId)
            ->first();

        if (! $balance) {
            // Derive tenant_id + center_id from the warehouse if not in context.
            $warehouseRow = DB::table('warehouses')
                ->where('id', $warehouseId)
                ->first(['tenant_id', 'center_id']);
            $tenantId = TenancyContext::tenantId() ?? $warehouseRow?->tenant_id;
            $centerId = TenancyContext::centerId() ?? $warehouseRow?->center_id;

            $balance = static::query()->withoutGlobalScopes()->create([
                'warehouse_id' => $warehouseId,
                'tenant_id' => $tenantId,
                'center_id' => $centerId,
                'part_id' => $partId,
                'qty_on_hand' => 0,
                'wac_cost' => 0,
            ]);
        }

        return $balance;
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
