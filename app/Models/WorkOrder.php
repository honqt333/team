<?php

namespace App\Models;

use App\Models\Concerns\CenterScoped;
use App\Models\Concerns\HasTaxSnapshot;
use App\Traits\HasWorkOrderRelations;
use App\Traits\HasWorkOrderOperations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class WorkOrder extends Model
{
    use HasFactory, SoftDeletes, CenterScoped, HasTaxSnapshot;
    use HasWorkOrderRelations, HasWorkOrderOperations;
    
    protected $appends = [];

    /**
     * Fields hidden from JSON serialization.
     */
    protected $hidden = [
        'tax_breakdown',
    ];

    protected static function booted()
    {
        static::saving(function ($workOrder) {
            $workOrder->recalculateTotals();

            // Synchronize odometer and mileage columns to maintain compatibility
            if ($workOrder->isDirty('odometer') && !$workOrder->isDirty('mileage')) {
                $workOrder->mileage = $workOrder->odometer;
            } elseif ($workOrder->isDirty('mileage') && !$workOrder->isDirty('odometer')) {
                $workOrder->odometer = $workOrder->mileage;
            } elseif ($workOrder->odometer !== $workOrder->mileage) {
                $workOrder->mileage = $workOrder->odometer;
            }

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
        if ($this->canBeEdited()) {
            $this->refreshTaxSnapshot();
        }

        if (!$this->relationLoaded('items')) $this->load('items');
        if (!$this->relationLoaded('parts')) $this->load('parts');

        $activeItems = $this->items->reject(fn($item) => $item->status === WorkOrderItem::STATUS_CANCELLED);

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
        self::STATUS_DRAFT, self::STATUS_OPEN, self::STATUS_IN_PROGRESS, self::STATUS_ON_HOLD,
        self::STATUS_READY_FOR_QC, self::STATUS_DONE, self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'tenant_id', 'center_id', 'customer_id', 'vehicle_id', 'quote_id', 'code', 'status', 'odometer',
        'opened_at', 'closed_at', 'notes', 'hold_reason', 'entry_date', 'expected_end_date', 'customer_complaint',
        'initial_assessment', 'mileage', 'fuel_level', 'contact_name', 'contact_phone', 'tax_enabled_snapshot',
        'pricing_mode_snapshot', 'tax_rate_snapshot', 'currency_code', 'total_excl_tax', 'total_tax', 'total_incl_tax',
        'total_taxable_amount', 'tax_breakdown', 'reception_signature', 'delivery_signature', 'reception_signed_at',
        'delivery_signed_at', 'show_packages_section',
    ];

    protected $casts = [
        'opened_at' => 'datetime', 'closed_at' => 'datetime', 'entry_date' => 'date:Y-m-d',
        'expected_end_date' => 'date:Y-m-d', 'mileage' => 'integer', 'fuel_level' => 'float',
        'tax_breakdown' => 'array', 'reception_signed_at' => 'datetime', 'delivery_signed_at' => 'datetime',
        'show_packages_section' => 'boolean',
    ];

    /**
     * Generate a sequential code for the work order.
     */
    public static function generateCode(int $tenantId, int $centerId): string
    {
        return DB::transaction(function () use ($tenantId, $centerId) {
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
        if ($this->total_incl_tax > 0) {
            return (float) $this->total_incl_tax;
        }

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

        if (!($this->tax_enabled_snapshot ?? false)) {
            return (float) $netTotal;
        }

        if (($this->pricing_mode_snapshot ?? 'exclusive') === 'inclusive') {
            return (float) $netTotal;
        }

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
     */
    public function scopeHasOutstandingBalance($query): void
    {
        $query->whereRaw('(COALESCE((SELECT SUM((unit_price * qty) - discount_amount) FROM work_order_items WHERE work_order_id = work_orders.id), 0) + COALESCE((SELECT SUM((unit_price * qty) - discount) FROM work_order_item_parts WHERE work_order_id = work_orders.id), 0)) > (COALESCE((SELECT SUM(CASE WHEN type = "payment" THEN amount WHEN type = "refund" THEN -amount ELSE 0 END) FROM payments WHERE work_order_id = work_orders.id), 0))');
    }

    /**
     * Scope to filter work orders that are ready for exit.
     */
    public function scopeReadyForExit($query)
    {
        $query->whereIn('status', ['open', 'in_progress', 'on_hold', 'ready_for_qc'])
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('work_order_items')
                    ->whereColumn('work_order_items.work_order_id', 'work_orders.id')
                    ->whereColumn('work_order_items.tenant_id', 'work_orders.tenant_id');
            })
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('work_order_items')
                    ->whereColumn('work_order_items.work_order_id', 'work_orders.id')
                    ->whereColumn('work_order_items.tenant_id', 'work_orders.tenant_id')
                    ->where('work_order_items.status', 'completed');
            })
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('work_order_items')
                    ->whereColumn('work_order_items.work_order_id', 'work_orders.id')
                    ->whereColumn('work_order_items.tenant_id', 'work_orders.tenant_id')
                    ->whereNotIn('work_order_items.status', ['completed', 'cancelled']);
            });
    }

    /**
     * Get the raw SQL snippet for the outstanding balance condition.
     */
    public static function outstandingBalanceSql(): string
    {
        return '(COALESCE((SELECT SUM((unit_price * qty) - discount_amount) FROM work_order_items WHERE work_order_id = work_orders.id), 0) + COALESCE((SELECT SUM((unit_price * qty) - discount) FROM work_order_item_parts WHERE work_order_id = work_orders.id), 0)) > (COALESCE((SELECT SUM(CASE WHEN type = "payment" THEN amount WHEN type = "refund" THEN -amount ELSE 0 END) FROM payments WHERE work_order_id = work_orders.id), 0))';
    }

    /**
     * Retrieve the model for a bound value.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        return $this->withoutGlobalScope('center_scoped')
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->where('tenant_id', \App\Support\TenancyContext::tenantId())
            ->first();
    }
}
