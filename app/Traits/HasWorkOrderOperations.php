<?php

namespace App\Traits;

use App\Models\WorkOrderItem;
use App\Models\WorkOrderItemPart;
use App\Models\VehicleMileageLog;
use Carbon\Carbon;

trait HasWorkOrderOperations
{
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

    /**
     * Get total paid amount.
     */
    public function getTotalPaidAttribute(): float
    {
        if ($this->relationLoaded('payments')) {
            return (float) $this->payments->sum(fn($p) => ($p->type === 'payment' || $p->type === 'Payment' || $p->type === 'bad_debt' || $p->type === 'Bad_debt') ? $p->amount : -$p->amount);
        }
        return (float) $this->payments()->selectRaw('SUM(CASE WHEN type IN ("payment", "Payment", "bad_debt", "Bad_debt") THEN amount WHEN type IN ("refund", "Refund") THEN -amount ELSE 0 END) as paid')->value('paid');
    }

    /**
     * Get total bad debt amount.
     */
    public function getBadDebtAttribute(): float
    {
        if ($this->relationLoaded('payments')) {
            return (float) $this->payments->sum(fn($p) => ($p->type === 'bad_debt' || $p->type === 'Bad_debt') ? $p->amount : 0);
        }
        return (float) $this->payments()->whereIn('type', ['bad_debt', 'Bad_debt'])->sum('amount');
    }

    /**
     * Get remaining balance.
     */
    public function getBalanceAttribute(): float
    {
        return $this->total - $this->total_paid;
    }

    /**
     * Get total discount.
     */
    public function getTotalDiscountAttribute(): float
    {
        if (!$this->relationLoaded('items')) $this->load('items');
        if (!$this->relationLoaded('parts')) $this->load('parts');

        $activeItems = $this->items->reject(fn($item) => $item->status === WorkOrderItem::STATUS_CANCELLED);
        $servicesDiscount = (float) $activeItems->sum('discount_amount');

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
        $partsDiscount = (float) $activeParts->sum('discount');

        return $servicesDiscount + $partsDiscount;
    }

    /**
     * Check if fully paid.
     */
    public function isFullyPaid(): bool
    {
        return $this->balance <= 0;
    }

    /**
     * Check if work order can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        if ($this->total_paid > 0) {
            return false;
        }

        if ($this->items()->where('status', '!=', WorkOrderItem::STATUS_CANCELLED)->exists()) {
            return false;
        }

        if ($this->parts()->whereNotIn('status', [
            WorkOrderItemPart::STATUS_CANCELLED,
            WorkOrderItemPart::STATUS_REVERSED,
        ])->exists()) {
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
     */
    public function putOnHold(?string $reason = null): bool
    {
        if (!$this->canBeOnHold()) {
            return false;
        }

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
     */
    public function allItemsCompleted(): bool
    {
        if ($this->items()->count() === 0) {
            return false;
        }

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
    public function markAsCompleted(?Carbon $exitDate = null, ?string $notes = null): bool
    {
        if (!$this->canVehicleExit()) {
            return false;
        }

        if ($this->odometer !== null) {
            $vehicle = $this->vehicle;
            
            if (!$vehicle->allow_lower_mileage && $vehicle->odometer !== null && $this->odometer < $vehicle->odometer) {
                return false; 
            }

            VehicleMileageLog::create([
                'vehicle_id' => $vehicle->id,
                'mileage' => $this->odometer,
                'previous_mileage' => $vehicle->odometer,
                'difference' => $this->odometer - ($vehicle->odometer ?? 0),
                'reference_type' => parent::class,
                'reference_id' => $this->id,
                'reference_code' => $this->code,
                'created_by' => auth()->id(),
                'recorded_at' => now(),
            ]);

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
}
