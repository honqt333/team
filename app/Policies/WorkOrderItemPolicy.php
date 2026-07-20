<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkOrderItem;

class WorkOrderItemPolicy
{
    public function view(User $user, WorkOrderItem $item): bool
    {
        $wo = $item->workOrder;
        if (! $wo) {
            return false;
        }

        return $wo->tenant_id === $user->tenant_id
            && $wo->center_id === $user->current_center_id;
    }

    public function create(User $user, WorkOrderItem $item): bool
    {
        $wo = $item->workOrder;
        if (! $wo) {
            return false;
        }

        if (method_exists($wo, 'canBeEdited') && ! $wo->canBeEdited()) {
            return false;
        }

        return $wo->tenant_id === $user->tenant_id
            && $wo->center_id === $user->current_center_id;
    }

    public function update(User $user, WorkOrderItem $item): bool
    {
        return $this->create($user, $item);
    }

    public function delete(User $user, WorkOrderItem $item): bool
    {
        return $this->create($user, $item);
    }
}
