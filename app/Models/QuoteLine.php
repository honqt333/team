<?php

namespace App\Models;

use App\Support\PricingHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
            $line->line_total_excl_tax = $computed['line_total'];

            // Calculate tax based on quote's tax_enabled_snapshot
            $quote = $line->quote;
            if ($quote && $quote->tax_enabled_snapshot) {
                $taxRate = $quote->tax_rate_snapshot ?? 15; // Default 15%
                $line->is_taxable = true;
                $line->tax_rate_snapshot = $taxRate;
                
                if ($quote->pricing_mode_snapshot === 'inclusive') {
                    $taxFactor = 1 + ($taxRate / 100);
                    $line->line_total_incl_tax = $computed['line_total'];
                    $line->line_total_excl_tax = round($computed['line_total'] / $taxFactor, 2);
                    $line->tax_amount = round($line->line_total_incl_tax - $line->line_total_excl_tax, 2);
                    $line->line_total = $line->line_total_incl_tax;
                } else {
                    $line->tax_amount = round($computed['line_total'] * ($taxRate / 100), 2);
                    $line->line_total_incl_tax = $computed['line_total'] + $line->tax_amount;
                    $line->line_total = $line->line_total_incl_tax;
                }
            } else {
                $line->is_taxable = false;
                $line->tax_rate_snapshot = 0;
                $line->tax_amount = 0;
                $line->line_total_incl_tax = $computed['line_total'];
                $line->line_total = $computed['line_total'];
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

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function parts(): HasMany
    {
        return $this->hasMany(QuotePart::class, 'quote_line_id');
    }

    // ─────────────────────────────────────────────────────────────
    // Accessors
    // ─────────────────────────────────────────────────────────────

    /**
     * Calculate total price of linked parts (only non-included ones for display)
     */
    public function getPartsTotalAttribute(): float
    {
        return $this->parts()
            ->sum('total') ?: 0;
    }

    protected $appends = ['parts_total'];
}

