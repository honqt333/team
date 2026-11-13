<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\CenterScoped;
use App\Support\PricingHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property numeric-string|null $tax_amount
 * @property bool $is_taxable
 * @property numeric-string|null $tax_rate_snapshot
 */
class WorkOrderItem extends Model
{
    use CenterScoped, HasFactory;

    // Status constants
    public const STATUS_PENDING = 'pending';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_READY_FOR_QC = 'ready_for_qc';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_ON_HOLD = 'on_hold';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING, self::STATUS_IN_PROGRESS, self::STATUS_READY_FOR_QC,
        self::STATUS_COMPLETED, self::STATUS_ON_HOLD, self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'work_order_id', 'service_id', 'department_id', 'tenant_id', 'center_id', 'title', 'qty', 'unit_price',
        'base_price_snapshot', 'min_price_snapshot', 'discount_type', 'discount_value', 'discount_amount',
        'final_unit_price', 'line_total', 'price_locked', 'total', 'status', 'suspended_status', 'notes',
        'started_at', 'completed_at', 'duration_value', 'duration_unit', 'due_date', 'is_taxable',
        'tax_category_code', 'tax_rate_snapshot', 'tax_amount', 'line_total_excl_tax', 'line_total_incl_tax',
        'warranty_expires_at', 'warranty_value_snapshot', 'warranty_unit_snapshot', 'is_warranty',
        'hold_reason', 'held_at', 'held_by_user_id',
        // Technician fields
        'technician_notes', 'estimated_duration', 'actual_duration', 'priority',
        'paused_at', 'paused_duration', 'checklist',
    ];

    protected $appends = ['parts_total', 'grand_total'];

    protected $casts = [
        'department_id' => 'integer', 'qty' => 'decimal:2', 'unit_price' => 'decimal:2',
        'base_price_snapshot' => 'decimal:2', 'min_price_snapshot' => 'decimal:2', 'discount_value' => 'decimal:2',
        'discount_amount' => 'decimal:2', 'final_unit_price' => 'decimal:2', 'line_total' => 'decimal:2',
        'total' => 'decimal:2', 'price_locked' => 'boolean', 'started_at' => 'datetime', 'completed_at' => 'datetime',
        'due_date' => 'date', 'tax_rate_snapshot' => 'decimal:2', 'tax_amount' => 'decimal:2',
        'line_total_excl_tax' => 'decimal:2', 'line_total_incl_tax' => 'decimal:2', 'is_taxable' => 'boolean',
        'is_warranty' => 'boolean', 'warranty_expires_at' => 'datetime', 'held_at' => 'datetime',
        // Technician fields
        'estimated_duration' => 'integer',
        'actual_duration' => 'integer',
        'paused_duration' => 'integer',
        'paused_at' => 'datetime',
        'checklist' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (WorkOrderItem $item) {
            if ($item->service_id && $item->service) {
                $item->department_id = $item->service->department_id;
            }

            $computed = PricingHelper::computeLineTotal(
                (float) $item->unit_price,
                $item->discount_type ?? 'none',
                $item->discount_value,
                (float) $item->qty,
                $item->is_warranty ? 0 : (float) $item->min_price_snapshot
            );

            $item->discount_amount = $computed['discount_amount'];
            $item->final_unit_price = $computed['final_unit_price'];
            $item->line_total = $computed['line_total'];
            $item->total = $computed['line_total'];

            if ($item->isDirty('status')) {
                if ($item->status === self::STATUS_IN_PROGRESS && ! $item->started_at) {
                    $item->started_at = now();
                }

                if ($item->status === self::STATUS_COMPLETED && ! $item->completed_at) {
                    $item->completed_at = now();

                    if ($item->is_warranty) {
                        $item->warranty_expires_at = null;
                        $item->warranty_value_snapshot = null;
                        $item->warranty_unit_snapshot = null;
                    } else {
                        $value = $item->warranty_value_snapshot ?? ($item->service ? $item->service->warranty_value : 0);
                        $unit = $item->warranty_unit_snapshot ?? ($item->service ? $item->service->warranty_unit : null);

                        if ($value > 0 && $unit) {
                            $item->warranty_value_snapshot = $value;
                            $item->warranty_unit_snapshot = $unit;
                            $item->warranty_expires_at = now()->add($value, $unit);
                        }
                    }
                }
            }
        });

        static::saved(function (WorkOrderItem $item) {
            if ($item->workOrder) {
                $item->workOrder->save();
            }
        });

        static::deleted(function (WorkOrderItem $item) {
            if ($item->workOrder) {
                $item->workOrder->save();
            }
        });
    }

    // ==================== Relationships ====================

    /** @return BelongsTo<WorkOrder, $this> */
    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class)->withoutGlobalScope('center_scoped');
    }

    /** @return BelongsTo<Center, $this> */
    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    /** @return BelongsTo<Service, $this> */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /** @return BelongsTo<Department, $this> */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function technicians(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'work_order_item_technician')
            ->withPivot(['assigned_at', 'completed_at', 'notes', 'share'])
            ->withTimestamps();
    }

    public function parts(): HasMany
    {
        return $this->hasMany(WorkOrderItemPart::class);
    }

    public function itemNotes(): HasMany
    {
        return $this->hasMany(WorkOrderItemNote::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(WorkOrderPhoto::class);
    }

    /**
     * Resolve a note that belongs to THIS line.
     *
     * The note has to belong to the line in the URL. Loading it by its
     * own id let a note of another line — or of another work order —
     * be deleted through the URL of an open work order.
     *
     * The centre scope is dropped on purpose: the work order decides
     * which centre its lines belong to. The tenant is re-checked
     * explicitly, because that scope carried it.
     */
    public function scopedNote(mixed $noteId): WorkOrderItemNote
    {
        /** @var WorkOrderItemNote $note */
        $note = $this->itemNotes()
            ->withoutGlobalScope('center_scoped')
            ->where('tenant_id', $this->tenant_id)
            ->whereKey($noteId)
            ->firstOrFail();

        return $note;
    }

    /**
     * Resolve a part line that belongs to THIS line.
     *
     * The part has to belong to the line in the URL. Loading it by its
     * own id let a part of another line be edited or deleted through
     * the URL of an open work order.
     */
    public function scopedPart(mixed $partId): WorkOrderItemPart
    {
        /** @var WorkOrderItemPart $part */
        $part = $this->parts()
            ->withoutGlobalScope('center_scoped')
            ->where('tenant_id', $this->tenant_id)
            ->whereKey($partId)
            ->firstOrFail();

        return $part;
    }

    public function laborEntries(): HasMany
    {
        return $this->hasMany(WorkOrderLaborEntry::class);
    }

    public function qualityChecks(): HasMany
    {
        return $this->hasMany(WorkOrderItemQualityCheck::class);
    }

    public function latestQualityCheck()
    {
        return $this->hasOne(WorkOrderItemQualityCheck::class)->latestOfMany('created_at');
    }

    /** @return HasOne<WorkOrderPurchaseAllocation, $this> */
    public function supplierServiceAllocation(): HasOne
    {
        return $this->hasOne(WorkOrderPurchaseAllocation::class, 'work_order_item_id');
    }

    /** @return BelongsTo<User, $this> */
    public function heldBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'held_by_user_id');
    }

    // ==================== Hold helpers ====================

    public function isHeld(): bool
    {
        return $this->status === self::STATUS_ON_HOLD;
    }

    public function isReadyForQc(): bool
    {
        return $this->status === self::STATUS_READY_FOR_QC;
    }

    // ==================== Business Rules ====================

    public function canBeCancelled(): bool
    {
        return $this->technicians()->count() === 0 && $this->parts()->count() === 0;
    }

    public function canChangeStatusTo(string $newStatus): bool
    {
        if ($newStatus === self::STATUS_CANCELLED) {
            return $this->canBeCancelled();
        }

        if ($newStatus === self::STATUS_PENDING) {
            $parentStatus = $this->workOrder()->first()?->status;

            if (in_array($parentStatus, [WorkOrder::STATUS_IN_PROGRESS, WorkOrder::STATUS_READY_FOR_QC, WorkOrder::STATUS_DONE])) {
                if ($this->status !== self::STATUS_PENDING) {
                    return false;
                }
            }
        }

        return true;
    }

    public function canModifyPrice(): bool
    {
        return ! $this->price_locked;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isOnHold(): bool
    {
        return $this->status === self::STATUS_ON_HOLD;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    // ==================== Accessors ====================

    public function getTechniciansCountAttribute(): int
    {
        return $this->technicians()->count();
    }

    public function getPartsCountAttribute(): int
    {
        return $this->parts()->count();
    }

    public function getNotesCountAttribute(): int
    {
        return $this->itemNotes()->count();
    }

    public function getPartsTotalAttribute(): float
    {
        $sum = $this->relationLoaded('parts')
            ? $this->parts->sum('total')
            : $this->parts()->sum('total');

        return (float) ($sum ?? 0);
    }

    public function getGrandTotalAttribute(): float
    {
        return (float) $this->total + $this->parts_total;
    }
}
