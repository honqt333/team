<?php

namespace App\Models;

use App\Models\Concerns\CenterScoped;
use App\Support\PricingHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderItem extends Model
{
    use CenterScoped;

    protected $fillable = [
        'work_order_id',
        'service_id',
        'tenant_id',
        'center_id',
        'title',
        'qty',
        'unit_price',
        'base_price_snapshot',
        'min_price_snapshot',
        'discount_type',
        'discount_value',
        'discount_amount',
        'final_unit_price',
        'line_total',
        'price_locked',
        'total', // Legacy field, kept for compatibility
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
        'total' => 'decimal:2',
        'price_locked' => 'boolean',
    ];

    protected static function booted(): void
    {
        // Auto-calculate totals on creating/updating using PricingHelper
        static::saving(function (WorkOrderItem $item) {
            // Use PricingHelper to compute all values
            $computed = PricingHelper::computeLineTotal(
                (float) $item->unit_price,
                $item->discount_type ?? 'none',
                $item->discount_value,
                (float) $item->qty,
                (float) $item->min_price_snapshot
            );

            $item->discount_amount = $computed['discount_amount'];
            $item->final_unit_price = $computed['final_unit_price'];
            $item->line_total = $computed['line_total'];
            
            // Keep legacy total field in sync
            $item->total = $computed['line_total'];
        });
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Check if price modification is allowed for this line.
     */
    public function canModifyPrice(): bool
    {
        return !$this->price_locked;
    }
}

