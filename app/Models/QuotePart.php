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
        'is_taxable',
        'tax_rate_snapshot',
        'tax_amount',
        'total_excl_tax',
        'total_incl_tax',
        'include_in_package',
        'hide_on_print',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'tax_rate_snapshot' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_excl_tax' => 'decimal:2',
        'total_incl_tax' => 'decimal:2',
        'is_taxable' => 'boolean',
        'include_in_package' => 'boolean',
        'hide_on_print' => 'boolean',
    ];

    // ─────────────────────────────────────────────────────────────
    // Boot
    // ─────────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::saving(function (QuotePart $part) {
            // Auto-calculate base total: (qty * unit_price) - discount
            $subtotal = bcmul($part->qty, $part->unit_price, 2);
            $netAmount = bcsub($subtotal, $part->discount, 2);
            $part->total = $netAmount; // Base amount used for overall subtotal

            // Calculate tax based on quote's tax settings
            $quote = $part->quote;
            if ($quote && $quote->tax_enabled_snapshot) {
                $taxRate = $part->tax_rate_snapshot ?: $quote->tax_rate_snapshot ?: 15;
                $part->is_taxable = true;
                $part->tax_rate_snapshot = $taxRate;

                if ($quote->pricing_mode_snapshot === 'inclusive') {
                    $taxFactor = (float)bcadd('1', bcdiv($taxRate, '100', 4), 4);
                    $part->total_incl_tax = (float)$netAmount;
                    
                    // Use round for base price to ensure Base + Tax = Total
                    $baseExclTax = round($part->total_incl_tax / $taxFactor, 2);
                    $part->total_excl_tax = $baseExclTax;
                    $part->tax_amount = round($part->total_incl_tax - $baseExclTax, 2);
                } else {
                    $part->tax_amount = bcmul($netAmount, bcdiv($taxRate, '100', 4), 2);
                    $part->total_incl_tax = bcadd($netAmount, $part->tax_amount, 2);
                    $part->total_excl_tax = $netAmount;
                }
            } else {
                $part->is_taxable = false;
                $part->tax_rate_snapshot = 0;
                $part->tax_amount = 0;
                $part->total_excl_tax = (float)$netAmount;
                $part->total_incl_tax = (float)$netAmount;
            }
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
