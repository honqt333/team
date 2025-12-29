<?php

namespace App\Models;

use App\Models\Concerns\CenterScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderItemPart extends Model
{
    use CenterScoped;

    public const SOURCE_WAREHOUSE = 'warehouse';
    public const SOURCE_EXTERNAL = 'external';
    public const SOURCE_CUSTOMER = 'customer';

    public const SOURCES = [
        self::SOURCE_WAREHOUSE,
        self::SOURCE_EXTERNAL,
        self::SOURCE_CUSTOMER,
    ];

    protected $table = 'work_order_item_parts';

    protected $fillable = [
        'work_order_item_id',
        'tenant_id',
        'center_id',
        'name',
        'part_number',
        'source',
        'qty',
        'unit_price',
        'total',
        'notes',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    /**
     * Auto-calculate total on saving.
     */
    protected static function booted(): void
    {
        static::saving(function (WorkOrderItemPart $part) {
            $part->total = $part->qty * $part->unit_price;
        });
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

    /**
     * Check if part is from warehouse.
     */
    public function isFromWarehouse(): bool
    {
        return $this->source === self::SOURCE_WAREHOUSE;
    }

    /**
     * Check if part is external purchase.
     */
    public function isExternal(): bool
    {
        return $this->source === self::SOURCE_EXTERNAL;
    }

    /**
     * Check if part is provided by customer.
     */
    public function isFromCustomer(): bool
    {
        return $this->source === self::SOURCE_CUSTOMER;
    }
}
