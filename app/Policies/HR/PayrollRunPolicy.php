<?php

namespace App\Policies\HR;

use App\Models\HR\PayrollRun;
use App\Models\User;
use App\Support\TenancyContext;

class PayrollRunPolicy
{
    /**
     * Determine whether the user can view any payroll runs.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('hr.payroll.view');
    }

    /**
     * Determine whether the user can view the payroll run.
     */
    public function view(User $user, PayrollRun $payrollRun): bool
    {
        if ($payrollRun->tenant_id !== $user->tenant_id) {
            return false;
        }

        return $user->hasPermissionTo('hr.payroll.view');
    }

    /**
     * Determine whether the user can create payroll runs.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('hr.payroll.create');
    }

    /**
     * Determine whether the user can update the payroll run.
     */
    public function update(User $user, PayrollRun $payrollRun): bool
    {
        if ($payrollRun->tenant_id !== $user->tenant_id) {
            return false;
        }

        return $user->hasPermissionTo('hr.payroll.update');
    }

    /**
     * Determine whether the user can delete the payroll run.
     */
    public function delete(User $user, PayrollRun $payrollRun): bool
    {
        if ($payrollRun->tenant_id !== $user->tenant_id) {
            return false;
        }

        // Only draft payrolls can be deleted
        if ($payrollRun->status !== 'draft') {
            return false;
        }

        return $user->hasPermissionTo('hr.payroll.delete');
    }
}
