<?php

namespace App\Models;

use App\Models\Concerns\CenterScoped;
use App\Models\Concerns\HasTaxSnapshot;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class WorkOrder extends Model
{
    use HasFactory, SoftDeletes, CenterScoped, HasTaxSnapshot;
    
    protected $appends = [];

    /**
     * Fields hidden from JSON serialization.
     *
     * tax_breakdown is computed/stored on the model but no current view
     * (WorkOrders/Show.vue, printable templates, JSON API responses) reads
     * it for work orders. It is also written only by TaxCalculator when
     * generating invoices, not when persisting work orders. Hiding it cuts
     * payload size on every WorkOrder show/index response until a feature
     * actually needs it. Use ->makeVisible('tax_breakdown') if/when needed.
     */
    protected $hidden = [
        'tax_breakdown',
    ];

    protected static function booted()
    {
        static::saving(function ($workOrder) {
            $workOrder->recalculateTotals();

            // Auto complete work order if all items are completed and status is not done/cancelled/on_hold
            if (in_array($workOrder->status, [self::STATUS_OPEN, self::STATUS_IN_PROGRESS]) && $workOrder->allItemsCompleted()) {
                $workOrder->status = self::STATUS_READY_FOR_QC;
            }
        });

        static::saved(function ($workOrder) {
            if ($workOrder->status === self::STATUS_DONE && $workOrder->wasChanged('status')) {
                $workOrder->logActivity('status_changed', __('work_orders.activities.actions.status_changed', ['status' => __('work_orders.status.done')]));
            }
        });
    }

    /**
     * Recalculate totals from items and parts.
     */
    public function recalculateTotals(): void
    {
        // Load relationships if not loaded to ensure all items are summed
        if (!$this->relationLoaded('items')) $this->load('items');
        if (!$this->relationLoaded('parts')) $this->load('parts');

        $activeItems = $this->items->reject(fn($item) => $item->status === WorkOrderItem::STATUS_CANCELLED);

        // Services totals
        $servicesPrice = $activeItems->sum(fn($l) => (float)$l->unit_price * (float)$l->qty);
        $servicesDiscount = $activeItems->sum('discount_amount');
        
        $activeItemIds = $activeItems->pluck('id')->all();
        $activeParts = $this->parts->filter(function ($part) use ($activeItemIds) {
            if (in_array($part->status, [WorkOrderItemPart::STATUS_CANCELLED, WorkOrderItemPart::STATUS_REVERSED])) {
                return false;
            }
            if ($part->work_order_item_id !== null && !in_array($part->work_order_item_id, $activeItemIds)) {
                return false;
            }
            return true;
        });

        // Parts totals
        $partsPrice = $activeParts->sum(fn($p) => (float)$p->unit_price * (float)$p->qty);
        $partsDiscount = $activeParts->sum('discount');

        $netTotal = ($servicesPrice - $servicesDiscount) + ($partsPrice - $partsDiscount);
        $this->total_excl_tax = $netTotal;

        if (!($this->tax_enabled_snapshot ?? false)) {
            $this->total_tax = 0;
            $this->total_incl_tax = $netTotal;
        } else {
            $taxRate = (float) ($this->tax_rate_snapshot ?: 15.00);
            if (($this->pricing_mode_snapshot ?? 'exclusive') === 'inclusive') {
                $this->total_incl_tax = $netTotal;
                $this->total_excl_tax = round($netTotal / (1 + ($taxRate / 100)), 2);
                $this->total_tax = round($this->total_incl_tax - $this->total_excl_tax, 2);
            } else {
                $this->total_tax = round($netTotal * ($taxRate / 100), 2);
                $this->total_incl_tax = round($netTotal + $this->total_tax, 2);
            }
        }
    }

    // Status constants
    public const STATUS_DRAFT = 'draft';
    public const STATUS_OPEN = 'open';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_ON_HOLD = 'on_hold';
    public const STATUS_DONE = 'done';
    public const STATUS_READY_FOR_QC = 'ready_for_qc';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_OPEN,
        self::STATUS_IN_PROGRESS,
        self::STATUS_ON_HOLD,
        self::STATUS_READY_FOR_QC,
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
        'odometer',
        'opened_at',
        'closed_at',
        'notes',
        'hold_reason',
        // New fields
        'entry_date',
        'expected_end_date',
        'customer_complaint',
        'initial_assessment',
        'mileage',
        'fuel_level',
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
        'reception_signature',
        'delivery_signature',
        'reception_signed_at',
        'delivery_signed_at',
        'show_packages_section',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'entry_date' => 'date:Y-m-d',
        'expected_end_date' => 'date:Y-m-d',
        'mileage' => 'integer',
        'fuel_level' => 'float',
        'tax_breakdown' => 'array',
        'reception_signed_at' => 'datetime',
        'delivery_signed_at' => 'datetime',
        'show_packages_section' => 'boolean',
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

    public function invoice(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Invoice::class);
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

    /**
     * Get the payments for this work order.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get all parts associated with this work order (both general and item-linked).
     */
    public function parts(): HasMany
    {
        return $this->hasMany(WorkOrderItemPart::class);
    }

    /**
     * Get the attachments for this work order.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(WorkOrderAttachment::class)->orderByDesc('created_at');
    }

    /**
     * Get the activities for this work order.
     */
    public function activities(): HasMany
    {
        return $this->hasMany(WorkOrderActivity::class)->orderByDesc('created_at');
    }

    /**
     * Get the inspections for this work order.
     */
    public function inspections(): HasMany
    {
        return $this->hasMany(WorkOrderInspection::class)->orderByDesc('performed_at');
    }

    /**
     * Get all notes for this work order (both general and item-linked).
     */
    public function generalNotes(): HasMany
    {
        return $this->hasMany(WorkOrderItemNote::class, 'work_order_id');
    }

    /**
     * Log an activity for this work order.
     */
    public function logActivity(string $action, ?string $description = null, ?array $changes = null): void
    {
        $this->activities()->create([
            'tenant_id' => $this->tenant_id,
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
            'changes' => $changes,
        ]);
    }

    // ==================== Payment Helpers ====================

    /**
     * Get total paid amount.
     */
    public function getTotalPaidAttribute(): float
    {
        // Use relationship sum to avoid loading all payment objects
        if ($this->relationLoaded('payments')) {
            return (float) $this->payments->sum(fn($p) => ($p->type === 'payment' || $p->type === 'Payment') ? $p->amount : -$p->amount);
        }
        return (float) $this->payments()->selectRaw('SUM(CASE WHEN type IN ("payment", "Payment") THEN amount WHEN type IN ("refund", "Refund") THEN -amount ELSE 0 END) as paid')->value('paid');
    }

    public function getBalanceAttribute(): float
    {
        return $this->total - $this->total_paid;
    }

    /**
     * Check if fully paid.
     */
    public function isFullyPaid(): bool
    {
        return $this->balance <= 0;
    }

    // ==================== Business Rules ====================

    /**
     * Check if work order can be cancelled.
     * Rule R5: Cannot cancel if any item has technicians
     * Rule R6: Cannot cancel if any item has parts
     */
    public function canBeCancelled(): bool
    {
        // Check if has active payments (not fully refunded)
        if ($this->total_paid > 0) {
            return false;
        }

        // Check if has items (services) or technicians
        if ($this->items()->exists()) {
            return false;
        }

        // Check if has any parts
        if ($this->parts()->exists()) {
            return false;
        }

        return true;
    }

    /**
     * Check if work order can be put on hold.
     */
    public function canBeOnHold(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    /**
     * Put work order on hold.
     * Rule R7: Putting on hold suspends all items.
     */
    public function putOnHold(?string $reason = null): bool
    {
        if (!$this->canBeOnHold()) {
            return false;
        }

        // Store current status in suspended_status, then put on hold
        foreach ($this->items()->whereIn('status', [
            WorkOrderItem::STATUS_PENDING,
            WorkOrderItem::STATUS_IN_PROGRESS,
        ])->get() as $item) {
            $item->update([
                'suspended_status' => $item->status,
                'status' => WorkOrderItem::STATUS_ON_HOLD,
            ]);
        }

        $this->update([
            'status' => self::STATUS_ON_HOLD,
            'hold_reason' => $reason,
        ]);

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

        // Restore status from suspended_status, fallback to pending
        foreach ($this->items()->where('status', WorkOrderItem::STATUS_ON_HOLD)->get() as $item) {
            $originalStatus = $item->suspended_status ?: WorkOrderItem::STATUS_PENDING;
            $item->update([
                'status' => $originalStatus,
                'suspended_status' => null,
            ]);
        }

        $this->update([
            'status' => self::STATUS_IN_PROGRESS,
            'hold_reason' => null,
        ]);

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

        // Must have at least one completed service
        $hasCompleted = $this->items()->where('status', WorkOrderItem::STATUS_COMPLETED)->exists();
        if (!$hasCompleted) {
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
    public function markAsCompleted(?\Carbon\Carbon $exitDate = null, ?string $notes = null): bool
    {
        if (!$this->canVehicleExit()) {
            return false;
        }

        // Create mileage log if odometer is set
        if ($this->odometer !== null) {
            $vehicle = $this->vehicle;
            
            // Validation for lower mileage
            if (!$vehicle->allow_lower_mileage && $vehicle->odometer !== null && $this->odometer < $vehicle->odometer) {
                // We cannot use ValidationException here easily without request context, 
                // but Controller expects boolean. Ideally we throw exception and catch it in controller.
                // For now, let's assume valid or return false?
                // Returning false will show generic error. 
                // Let's rely on validation before this action? request validation handles min:0 but not comparison to old value.
                // We should probably check this earlier, but check logic here to be safe.
                return false; 
            }

            // Create log
            \App\Models\VehicleMileageLog::create([
                'vehicle_id' => $vehicle->id,
                'mileage' => $this->odometer,
                'previous_mileage' => $vehicle->odometer,
                'difference' => $this->odometer - ($vehicle->odometer ?? 0),
                'reference_type' => self::class,
                'reference_id' => $this->id,
                'reference_code' => $this->code,
                'created_by' => auth()->id(),
                'recorded_at' => now(),
            ]);

            // Update vehicle odometer
            $vehicle->update(['odometer' => $this->odometer]);
        }

        $updateData = [
            'status' => self::STATUS_DONE,
            'closed_at' => $exitDate ?: now(),
        ];

        if ($notes !== null) {
            $updateData['notes'] = $notes;
        }

        $this->update($updateData);

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
            // Find the highest numeric suffix currently in use for this tenant
            // + center. orderByDesc('id')->first() is unsafe because the
            // id and the code's numeric tail don't always agree (manually
            // seeded fixtures, test rows, legacy data, etc.). Pull the max
            // number from all matching codes in one pass instead.
            $maxNumber = 0;
            $rows = static::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('center_id', $centerId)
                ->where('code', 'like', 'WO-%')
                ->lockForUpdate()
                ->pluck('code');

            foreach ($rows as $code) {
                if (preg_match('/WO-(\d+)/', (string) $code, $matches)) {
                    $n = (int) $matches[1];
                    if ($n > $maxNumber) {
                        $maxNumber = $n;
                    }
                }
            }

            return 'WO-' . str_pad($maxNumber + 1, 6, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Get the total amount (calculated if stored value is 0).
     */
    public function getTotalAttribute(): float
    {
        // Use stored value if available and not zero
        if ($this->total_incl_tax > 0) {
            return (float) $this->total_incl_tax;
        }

        // Fast fallback calculation via direct DB queries
        $servicesNet = (float) $this->items()
            ->where('status', '!=', WorkOrderItem::STATUS_CANCELLED)
            ->selectRaw('SUM((unit_price * qty) - discount_amount) as net')
            ->value('net');

        $partsNet = (float) $this->parts()
            ->where('status', '!=', WorkOrderItemPart::STATUS_CANCELLED)
            ->where('status', '!=', WorkOrderItemPart::STATUS_REVERSED)
            ->where(function ($query) {
                $query->whereNull('work_order_item_id')
                    ->orWhereHas('workOrderItem', function ($q) {
                        $q->where('status', '!=', WorkOrderItem::STATUS_CANCELLED);
                    });
            })
            ->selectRaw('SUM((unit_price * qty) - discount) as net')
            ->value('net');
        
        $netTotal = $servicesNet + $partsNet;

        // If tax is disabled, return net total
        if (!($this->tax_enabled_snapshot ?? false)) {
            return (float) $netTotal;
        }

        // If pricing is inclusive, net total already includes tax
        if (($this->pricing_mode_snapshot ?? 'exclusive') === 'inclusive') {
            return (float) $netTotal;
        }

        // If exclusive, add tax
        $taxRate = (float) ($this->tax_rate_snapshot ?: 15.00);
        return round($netTotal * (1 + ($taxRate / 100)), 2);
    }



    /**
     * Check if work order can be edited.
     */
    public function canBeEdited(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_OPEN, self::STATUS_IN_PROGRESS, self::STATUS_READY_FOR_QC]);
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

    /**
     * Scope to filter work orders with outstanding balance (credit invoices).
     * Total charges (items + parts) > total payments.
     */
    public function scopeHasOutstandingBalance($query): void
    {
        $query->whereRaw('(COALESCE((SELECT SUM((unit_price * qty) - discount_amount) FROM work_order_items WHERE work_order_id = work_orders.id), 0) + COALESCE((SELECT SUM((unit_price * qty) - discount) FROM work_order_item_parts WHERE work_order_id = work_orders.id), 0)) > (COALESCE((SELECT SUM(CASE WHEN type IN ("payment", "Payment") THEN amount WHEN type IN ("refund", "Refund") THEN -amount ELSE 0 END) FROM payments WHERE work_order_id = work_orders.id), 0))');
    }

    /**
     * Scope to filter work orders that are ready for exit (all items completed but not yet done/closed).
     */
    public function scopeReadyForExit($query)
    {
        $query->whereIn('status', ['open', 'in_progress', 'on_hold', 'ready_for_qc'])
            // Must have at least one item belonging to this work order
            ->whereExists(function ($q) {
                $q->select(\Illuminate\Support\Facades\DB::raw(1))
                    ->from('work_order_items')
                    ->whereColumn('work_order_items.work_order_id', 'work_orders.id')
                    ->whereColumn('work_order_items.tenant_id', 'work_orders.tenant_id');
            })
            // Must have at least one completed item
            ->whereExists(function ($q) {
                $q->select(\Illuminate\Support\Facades\DB::raw(1))
                    ->from('work_order_items')
                    ->whereColumn('work_order_items.work_order_id', 'work_orders.id')
                    ->whereColumn('work_order_items.tenant_id', 'work_orders.tenant_id')
                    ->where('work_order_items.status', 'completed');
            })
            // Must NOT have any item that is not completed or cancelled
            ->whereNotExists(function ($q) {
                $q->select(\Illuminate\Support\Facades\DB::raw(1))
                    ->from('work_order_items')
                    ->whereColumn('work_order_items.work_order_id', 'work_orders.id')
                    ->whereColumn('work_order_items.tenant_id', 'work_orders.tenant_id')
                    ->whereNotIn('work_order_items.status', ['completed', 'cancelled']);
            });
    }

    /**
     * Get the raw SQL snippet for the outstanding balance condition.
     * Useful when combining with other whereRaw calls.
     */
    public static function outstandingBalanceSql(): string
    {
        return '(COALESCE((SELECT SUM((unit_price * qty) - discount_amount) FROM work_order_items WHERE work_order_id = work_orders.id), 0) + COALESCE((SELECT SUM((unit_price * qty) - discount) FROM work_order_item_parts WHERE work_order_id = work_orders.id), 0)) > (COALESCE((SELECT SUM(CASE WHEN type IN ("payment", "Payment") THEN amount WHEN type IN ("refund", "Refund") THEN -amount ELSE 0 END) FROM payments WHERE work_order_id = work_orders.id), 0))';
    }
}
