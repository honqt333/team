<?php

namespace App\Policies;

use App\Models\HR\Payroll;
use App\Models\User;
use App\Support\Permissions;

class PayrollPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::PAYROLL_VIEW) && $user->tenant_id !== null;
    }

    public function view(User $user, Payroll $payroll): bool
    {
        if (! $user->can(Permissions::PAYROLL_VIEW)) {
            return false;
        }

        // Employee can see own
        if ($payroll->employee_id === $user->employee?->id) {
            return true;
        }

        // HR can see all
        return $payroll->tenant_id === $user->tenant_id
            && $payroll->center_id === $user->current_center_id;
    }

    public function process(User $user): bool
    {
        return $user->can(Permissions::PAYROLL_PROCESS);
    }

    public function approve(User $user, Payroll $payroll): bool
    {
        if (! $user->can(Permissions::PAYROLL_APPROVE)) {
            return false;
        }

        return $payroll->tenant_id === $user->tenant_id;
    }

    public function disburse(User $user, Payroll $payroll): bool
    {
        if (! $user->can(Permissions::PAYROLL_DISBURSE)) {
            return false;
        }

        return $payroll->tenant_id === $user->tenant_id;
    }
}
