<?php

namespace App\Models;

use App\Models\Concerns\CenterScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderDamageMark extends Model
{
    use CenterScoped;

    protected $fillable = [
        'work_order_id',
        'tenant_id',
        'center_id',
        'x',
        'y',
        'color',
        'description',
    ];

    protected $casts = [
        'x' => 'float',
        'y' => 'float',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }
}
