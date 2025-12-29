<?php

namespace App\Models;

use App\Models\Concerns\CenterScoped;
use App\Models\Concerns\HasTaxSnapshot;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class WorkOrder extends Model
{
    use SoftDeletes, CenterScoped, HasTaxSnapshot;

    // Status constants
    public const STATUS_DRAFT = 'draft';
    public const STATUS_OPEN = 'open';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_ON_HOLD = 'on_hold';
    public const STATUS_DONE = 'done';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_OPEN,
        self::STATUS_IN_PROGRESS,
        self::STATUS_ON_HOLD,
        self::STATUS_DONE,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'tenant_id',
        'center_id',
        'customer_id',
        'vehicle_id',
        'quote_id',
        'code',
        'status',
        'opened_at',
        'closed_at',
        'notes',
        // New fields
        'entry_date',
        'expected_end_date',
        'customer_complaint',
        'initial_assessment',
        'mileage',
        'contact_name',
        'contact_phone',
        // Tax Snapshots
        'tax_enabled_snapshot',
        'pricing_mode_snapshot',
        'tax_rate_snapshot',
        'currency_code',
        'total_excl_tax',
        'total_tax',
        'total_incl_tax',
        'total_taxable_amount',
        'tax_breakdown',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'entry_date' => 'date:Y-m-d',
        'expected_end_date' => 'date:Y-m-d',
        'mileage' => 'integer',
        'tax_breakdown' => 'array',
    ];

    // ==================== Relationships ====================

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(WorkOrderItem::class);
    }

    /**
     * Get the damage marks for the vehicle condition report.
     */
    public function damageMarks(): HasMany
    {
        return $this->hasMany(WorkOrderDamageMark::class);
    }

    /**
     * Get the photos for this work order.
     */
    public function photos(): HasMany
    {
        return $this->hasMany(WorkOrderPhoto::class);
    }

    /**
     * Get the departments associated with this work order.
     */
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'work_order_departments')
            ->withTimestamps();
    }

    // ==================== Business Rules ====================

    /**
     * Check if work order can be cancelled.
     * Rule R5: Cannot cancel if any item has technicians
     * Rule R6: Cannot cancel if any item has parts
     */
    public function canBeCancelled(): bool
    {
        // Check if any item has technicians
        $hasTechnicians = $this->items()
            ->whereHas('technicians')
            ->exists();

        if ($hasTechnicians) {
            return false;
        }

        // Check if any item has parts
        $hasParts = $this->items()
            ->whereHas('parts')
            ->exists();

        if ($hasParts) {
            return false;
        }

        return true;
    }

    /**
     * Check if work order can be put on hold.
     */
    public function canBeOnHold(): bool
    {
        return in_array($this->status, [
            self::STATUS_OPEN,
            self::STATUS_IN_PROGRESS,
        ]);
    }

    /**
     * Put work order on hold.
     * Rule R7: Putting on hold suspends all items.
     */
    public function putOnHold(): bool
    {
        if (!$this->canBeOnHold()) {
            return false;
        }

        // Suspend all pending/in-progress items
        $this->items()
            ->whereIn('status', [
                WorkOrderItem::STATUS_PENDING,
                WorkOrderItem::STATUS_IN_PROGRESS,
            ])
            ->update(['status' => WorkOrderItem::STATUS_ON_HOLD]);

        $this->update(['status' => self::STATUS_ON_HOLD]);

        return true;
    }

    /**
     * Resume work order from hold.
     */
    public function resume(): bool
    {
        if ($this->status !== self::STATUS_ON_HOLD) {
            return false;
        }

        // Resume all on-hold items to pending
        $this->items()
            ->where('status', WorkOrderItem::STATUS_ON_HOLD)
            ->update(['status' => WorkOrderItem::STATUS_PENDING]);

        $this->update(['status' => self::STATUS_IN_PROGRESS]);

        return true;
    }

    /**
     * Check if all items are completed.
     * Rule R8: Vehicle exit button appears when all items completed.
     */
    public function allItemsCompleted(): bool
    {
        if ($this->items()->count() === 0) {
            return false;
        }

        return $this->items()
            ->whereNotIn('status', [
                WorkOrderItem::STATUS_COMPLETED,
                WorkOrderItem::STATUS_CANCELLED,
            ])
            ->count() === 0;
    }

    /**
     * Check if vehicle can exit.
     */
    public function canVehicleExit(): bool
    {
        return $this->allItemsCompleted() 
            && $this->status !== self::STATUS_ON_HOLD
            && $this->status !== self::STATUS_CANCELLED;
    }

    /**
     * Mark vehicle as exited (done).
     */
    public function markAsCompleted(): bool
    {
        if (!$this->canVehicleExit()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_DONE,
            'closed_at' => now(),
        ]);

        return true;
    }

    // ==================== Helpers ====================

    /**
     * Generate a sequential code for the work order.
     * Uses DB locking to ensure uniqueness in concurrent scenarios.
     */
    public static function generateCode(int $tenantId, int $centerId): string
    {
        return DB::transaction(function () use ($tenantId, $centerId) {
            // Lock the table row to prevent race conditions
            $lastOrder = static::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('center_id', $centerId)
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();

            if ($lastOrder && preg_match('/WO-(\d+)/', $lastOrder->code, $matches)) {
                $nextNumber = (int)$matches[1] + 1;
            } else {
                $nextNumber = 1;
            }

            return 'WO-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Calculate total amount of all items (Service + Parts).
     */
    public function getTotalAttribute(): float
    {
        // Sum the grand_total accessor of each item
        return $this->items->sum('grand_total');
    }

    /**
     * Check if work order can be edited.
     */
    public function canBeEdited(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_OPEN, self::STATUS_IN_PROGRESS]);
    }

    /**
     * Check if work order is in draft status.
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Check if work order is on hold.
     */
    public function isOnHold(): bool
    {
        return $this->status === self::STATUS_ON_HOLD;
    }

    /**
     * Check if work order is completed/done.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_DONE;
    }

    /**
     * Check if work order is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }
}
