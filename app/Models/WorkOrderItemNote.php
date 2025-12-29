<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderItemNote extends Model
{
    protected $table = 'work_order_item_notes';

    protected $fillable = [
        'work_order_item_id',
        'user_id',
        'content',
    ];

    public function workOrderItem(): BelongsTo
    {
        return $this->belongsTo(WorkOrderItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
