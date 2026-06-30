<?php

namespace App\Models;

use App\Models\Concerns\CenterScoped;
use App\Support\PricingHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkOrderItem extends Model
{
    use HasFactory, CenterScoped;

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_READY_FOR_QC = 'ready_for_qc';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_ON_HOLD = 'on_hold';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_IN_PROGRESS,
        self::STATUS_READY_FOR_QC,
        self::STATUS_COMPLETED,
        self::STATUS_ON_HOLD,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'work_order_id',
        'service_id',
        'department_id',
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
        'total',
        // New fields
        'status',
        'suspended_status',
        'notes',
        'started_at',
        'completed_at',
        'duration_value',
        'duration_unit',
        'due_date',
        // New Tax Fields
        'is_taxable',
        'tax_category_code',
        'tax_rate_snapshot',
        'tax_amount',
        'line_total_excl_tax',
        'line_total_incl_tax',
        'warranty_expires_at',
        'warranty_value_snapshot',
        'warranty_unit_snapshot',
        'is_warranty',
    ];

    protected $appends = [
        'parts_total',
        'grand_total',
    ];

    protected $casts = [
        'department_id' => 'integer',
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
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'due_date' => 'date',
        'tax_rate_snapshot' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'line_total_excl_tax' => 'decimal:2',
        'line_total_incl_tax' => 'decimal:2',
        'is_taxable' => 'boolean',
        'is_warranty' => 'boolean',
        'warranty_expires_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // Auto-calculate totals on creating/updating using PricingHelper
        static::saving(function (WorkOrderItem $item) {
            if ($item->service_id && $item->service) {
                $item->department_id = $item->service->department_id;
            }

            // Use PricingHelper to compute all values
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
            
            // Keep legacy total field in sync
            $item->total = $computed['line_total'];

            // Auto-set timestamps based on status changes
            if ($item->isDirty('status')) {
                if ($item->status === self::STATUS_IN_PROGRESS && !$item->started_at) {
                    $item->started_at = now();
                }
                if ($item->status === self::STATUS_COMPLETED && !$item->completed_at) {
                    $item->completed_at = now();

                    if ($item->is_warranty) {
                        $item->warranty_expires_at = null;
                        $item->warranty_value_snapshot = null;
                        $item->warranty_unit_snapshot = null;
                    } else {
                        // Use snapshotted values if defined (which might be user-customized)
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

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the technicians assigned to this item.
     */
    public function technicians(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'work_order_item_technician')
            ->withPivot(['assigned_at', 'completed_at', 'notes', 'share'])
            ->withTimestamps();
    }

    /**
     * Get the parts for this item.
     */
    public function parts(): HasMany
    {
        return $this->hasMany(WorkOrderItemPart::class);
    }

    /**
     * Get the notes for this item.
     */
    public function itemNotes(): HasMany
    {
        return $this->hasMany(WorkOrderItemNote::class);
    }

    // ==================== Business Rules ====================

    /**
     * Check if this item can be cancelled.
     * Rule R1: Cannot cancel if technicians assigned
     * Rule R2: Cannot cancel if parts attached
     */
    public function canBeCancelled(): bool
    {
        return $this->technicians()->count() === 0 
            && $this->parts()->count() === 0;
    }

    /**
     * Check if status can be changed.
     */
    public function canChangeStatusTo(string $newStatus): bool
    {
        // Cannot cancel if has technicians or parts
        if ($newStatus === self::STATUS_CANCELLED) {
            return $this->canBeCancelled();
        }

        // Rule: Once work has started on the work order, an item cannot go back to pending
        // if it has already transitioned to a non-pending status.
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

    /**
     * Check if price modification is allowed for this line.
     */
    public function canModifyPrice(): bool
    {
        return !$this->price_locked;
    }

    /**
     * Check if item is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if item is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if item is on hold.
     */
    public function isOnHold(): bool
    {
        return $this->status === self::STATUS_ON_HOLD;
    }

    /**
     * Check if item is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    // ==================== Accessors ====================

    /**
     * Get technicians count.
     */
    public function getTechniciansCountAttribute(): int
    {
        return $this->technicians()->count();
    }

    /**
     * Get parts count.
     */
    public function getPartsCountAttribute(): int
    {
        return $this->parts()->count();
    }

    /**
     * Get notes count.
     */
    public function getNotesCountAttribute(): int
    {
        return $this->itemNotes()->count();
    }

    /**
     * Get total cost of parts.
     */
    public function getPartsTotalAttribute(): float
    {
        return $this->parts->sum('total');
    }

    /**
     * Get grand total (Service Labor + Parts).
     */
    public function getGrandTotalAttribute(): float
    {
        return $this->total + $this->parts_total;
    }
}
