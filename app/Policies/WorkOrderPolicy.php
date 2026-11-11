<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\WorkOrder;
use App\Support\Permissions;

class WorkOrderPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::WORK_ORDERS_VIEW);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, WorkOrder $workOrder): bool
    {
        if (! $user->can(Permissions::WORK_ORDERS_VIEW)) {
            return false;
        }

        // Defense in depth: enforce tenant and current-center ownership.
        // The model's resolveRouteBinding() bypasses the center global
        // scope so a work order from a sibling branch still binds; the
        // policy is what stops it from being displayed.
        return $workOrder->tenant_id === $user->tenant_id
            && (int) $workOrder->center_id === (int) $user->current_center_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can(Permissions::WORK_ORDERS_CREATE);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, WorkOrder $workOrder): bool
    {
        if (! $user->can(Permissions::WORK_ORDERS_UPDATE)) {
            return false;
        }

        // Cannot edit closed/done/cancelled work orders
        if (! $workOrder->canBeEdited()) {
            // Exception: allow credit note on completed work order
            $isCreditNoteRoute = request()->routeIs('*credit-note*');
            // Exception: allow recording payments on completed work order if unpaid or deferred invoice
            $isPaymentRoute = request()->routeIs('work-orders.payments.*') || request()->routeIs('payments.*');
            $hasUnpaidOrDeferred = ($workOrder->balance > 0) || ($workOrder->invoice && $workOrder->invoice->due_date !== null);

            if ($isCreditNoteRoute && in_array($workOrder->status, [WorkOrder::STATUS_DONE, 'delivered'])) {
                // Allow credit note issuance on completed/delivered work orders
            } elseif ($isPaymentRoute && $workOrder->status === WorkOrder::STATUS_DONE && $hasUnpaidOrDeferred) {
                // Allow payment updates
            } else {
                return false;
            }
        }

        // Defense in depth: enforce tenant/center ownership
        return $workOrder->tenant_id === $user->tenant_id
            && $workOrder->center_id === $user->current_center_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, WorkOrder $workOrder): bool
    {
        if (! $user->can(Permissions::WORK_ORDERS_DELETE)) {
            return false;
        }

        // Cannot delete closed/done/cancelled work orders
        if (! $workOrder->canBeEdited()) {
            return false;
        }

        // Defense in depth: enforce tenant/center ownership
        return $workOrder->tenant_id === $user->tenant_id
            && $workOrder->center_id === $user->current_center_id;
    }

    /**
     * Determine whether the user can resume the model.
     */
    public function resume(User $user, WorkOrder $workOrder): bool
    {
        if (! $user->can(Permissions::WORK_ORDERS_UPDATE)) {
            return false;
        }

        return $workOrder->status === WorkOrder::STATUS_ON_HOLD
            && $workOrder->tenant_id === $user->tenant_id
            && $workOrder->center_id === $user->current_center_id;
    }

    /**
     * Determine whether the user can cancel the model.
     */
    public function cancel(User $user, WorkOrder $workOrder): bool
    {
        if (! $user->can(Permissions::WORK_ORDERS_UPDATE)) {
            return false;
        }

        return in_array($workOrder->status, [
            WorkOrder::STATUS_OPEN,
            WorkOrder::STATUS_IN_PROGRESS,
            WorkOrder::STATUS_ON_HOLD,
        ])
            && $workOrder->tenant_id === $user->tenant_id
            && $workOrder->center_id === $user->current_center_id;
    }
}
