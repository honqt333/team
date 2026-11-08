<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\CenterScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderPhoto extends Model
{
    use CenterScoped;

    protected $fillable = [
        'work_order_id',
        'work_order_item_id',
        'tenant_id',
        'center_id',
        'path',
        'type',
        'caption',
    ];

    protected $appends = ['url'];

    public const TYPE_BEFORE = 'before';

    public const TYPE_AFTER = 'after';

    public const TYPE_DAMAGE = 'damage';

    public const TYPE_GENERAL = 'general';

    public const TYPES = [
        self::TYPE_BEFORE,
        self::TYPE_AFTER,
        self::TYPE_DAMAGE,
        self::TYPE_GENERAL,
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function workOrderItem(): BelongsTo
    {
        return $this->belongsTo(WorkOrderItem::class);
    }

    /**
     * Get the full URL for the photo.
     */
    public function getUrlAttribute(): string
    {
        return asset('storage/'.$this->path);
    }
}
