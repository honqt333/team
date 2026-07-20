<?php

namespace App\Policies;

use App\Models\HR\Leave;
use App\Models\User;

class LeavePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('leaves.view');
    }

    public function view(User $user, Leave $leave): bool
    {
        if (! $user->can('leaves.view')) {
            return false;
        }

        // Employees can see their own
        if ($leave->employee_id === $user->employee?->id) {
            return true;
        }

        // HR can see all in tenant
        if ($user->can('leaves.view-all') && $leave->tenant_id === $user->tenant_id) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('leaves.create');
    }

    public function approve(User $user, Leave $leave): bool
    {
        if (! $user->can('leaves.approve')) {
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
