<?php

namespace App\Policies;

use App\Models\HR\Payroll;
use App\Models\User;

class PayrollPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('payroll.view') && $user->tenant_id !== null;
    }

    public function view(User $user, Payroll $payroll): bool
    {
        if (! $user->can('payroll.view')) {
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
        return $user->can('payroll.process');
    }

    public function approve(User $user, Payroll $payroll): bool
    {
        if (! $user->can('payroll.approve')) {
            return false;
        }

        return $payroll->tenant_id === $user->tenant_id;
    }

    public function disburse(User $user, Payroll $payroll): bool
    {
        if (! $user->can('payroll.disburse')) {
            return false;
        }

        return $payroll->tenant_id === $user->tenant_id;
    }
}
