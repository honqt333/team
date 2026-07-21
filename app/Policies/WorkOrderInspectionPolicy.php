<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\WorkOrderInspection;
use App\Support\Permissions;

class WorkOrderInspectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::WORK_ORDERS_INSPECT);
    }

    public function view(User $user, WorkOrderInspection $inspection): bool
    {
        return $user->can(Permissions::WORK_ORDERS_INSPECT)
            && $user->tenant_id === $inspection->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::WORK_ORDERS_INSPECT);
    }

    public function update(User $user, WorkOrderInspection $inspection): bool
    {
        return $user->can(Permissions::WORK_ORDERS_INSPECT)
            && $user->tenant_id === $inspection->tenant_id;
    }

    public function delete(User $user, WorkOrderInspection $inspection): bool
    {
        return $user->can(Permissions::WORK_ORDERS_INSPECT)
            && $user->tenant_id === $inspection->tenant_id;
    }
}
