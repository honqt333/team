<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\CenterScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderItemQualityCheck extends Model
{
    use CenterScoped, HasFactory;

    public const DECISION_APPROVED = 'approved';

    public const DECISION_REJECTED = 'rejected';

    public const DECISIONS = [self::DECISION_APPROVED, self::DECISION_REJECTED];

    protected $table = 'work_order_item_quality_checks';

    protected $fillable = [
        'tenant_id', 'center_id', 'work_order_id', 'work_order_item_id',
        'checked_by_user_id', 'decision', 'notes', 'checked_at',
    ];

    protected $casts = [
        'checked_at' => 'datetime',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function workOrderItem(): BelongsTo
    {
        return $this->belongsTo(WorkOrderItem::class);
    }

    public function checkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by_user_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function isApproved(): bool
    {
        return $this->decision === self::DECISION_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->decision === self::DECISION_REJECTED;
    }
}
