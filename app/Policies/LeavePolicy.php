<?php

namespace App\Policies;

use App\Models\HR\Leave;
use App\Models\User;
use App\Support\Permissions;

class LeavePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::LEAVES_VIEW);
    }

    public function view(User $user, Leave $leave): bool
    {
        if (! $user->can(Permissions::LEAVES_VIEW)) {
            return false;
        }

        // Employees can see their own
        if ($leave->employee_id === $user->employee?->id) {
            return true;
        }

        // HR can see all in tenant
        if ($user->can(Permissions::LEAVES_VIEW_ALL) && $leave->tenant_id === $user->tenant_id) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::LEAVES_CREATE);
    }

    public function approve(User $user, Leave $leave): bool
    {
        if (! $user->can(Permissions::LEAVES_APPROVE)) {
            return false;
        }

        // Cannot approve own leave
        if ($leave->employee_id === $user->employee?->id) {
            return false;
        }

        return $leave->tenant_id === $user->tenant_id;
    }

    public function reject(User $user, Leave $leave): bool
    {
        return $this->approve($user, $leave);
    }

    public function cancel(User $user, Leave $leave): bool
    {
        if ($leave->employee_id === $user->employee?->id) {
            return $leave->status === 'pending';
        }

        return $this->approve($user, $leave);
    }
}
