<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderPhoto extends Model
{
    protected $fillable = [
        'work_order_id',
        'path',
        'type',
        'caption',
    ];

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

    /**
     * Get the full URL for the photo.
     */
    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->path);
    }
}
