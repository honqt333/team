<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotePart extends Model
{
    // Source constants
    public const SOURCE_WAREHOUSE = 'warehouse';
    public const SOURCE_EXTERNAL = 'external';
    public const SOURCE_CUSTOMER = 'customer';

    public const SOURCES = [
        self::SOURCE_WAREHOUSE,
        self::SOURCE_EXTERNAL,
        self::SOURCE_CUSTOMER,
    ];

    protected $fillable = [
        'quote_id',
        'quote_line_id',
        'part_id',
        'source',
        'name',
        'part_number',
        'unit_id',
        'description',
        'qty',
        'unit_price',
        'discount',
        'total',
        'include_in_package',
        'hide_on_print',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'include_in_package' => 'boolean',
        'hide_on_print' => 'boolean',
    ];

    // ─────────────────────────────────────────────────────────────
    // Boot
    // ─────────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::saving(function (QuotePart $part) {
            // Auto-calculate total: (qty * unit_price) - discount
            $subtotal = bcmul($part->qty, $part->unit_price, 2);
            $part->total = bcsub($subtotal, $part->discount, 2);
        });
    }

    // ─────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function quoteLine(): BelongsTo
    {
        return $this->belongsTo(QuoteLine::class);
    }

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(InventoryUnit::class, 'unit_id');
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
}
