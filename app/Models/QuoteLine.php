<?php

namespace App\Models;

use App\Support\PricingHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteLine extends Model
{
    protected $fillable = [
        'quote_id',
        'service_id',
        'description',
        'qty',
        'unit_price',
        'base_price_snapshot',
        'min_price_snapshot',
        'discount_type',
        'discount_value',
        'discount_amount',
        'final_unit_price',
        'line_total',
        // New Tax Fields
        'is_taxable',
        'tax_category_code',
        'tax_rate_snapshot',
        'tax_amount',
        'line_total_excl_tax',
        'line_total_incl_tax',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'base_price_snapshot' => 'decimal:2',
        'min_price_snapshot' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        // New Tax Fields
        'tax_rate_snapshot' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'line_total_excl_tax' => 'decimal:2',
        'line_total_incl_tax' => 'decimal:2',
        'is_taxable' => 'boolean',
    ];

    protected static function booted(): void
    {
        // Auto-calculate totals on creating/updating using PricingHelper
        static::saving(function (QuoteLine $line) {
            $computed = PricingHelper::computeLineTotal(
                (float) $line->unit_price,
                $line->discount_type ?? 'none',
                $line->discount_value,
                (float) $line->qty,
                (float) $line->min_price_snapshot
            );

            $line->discount_amount = $computed['discount_amount'];
            $line->final_unit_price = $computed['final_unit_price'];
            $line->line_total = $computed['line_total'];
        });
    }

    // ─────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
