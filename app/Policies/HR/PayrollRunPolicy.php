<?php

declare(strict_types=1);

namespace App\Policies\HR;

use App\Models\HR\PayrollRun;
use App\Models\User;
use App\Support\Permissions;
use App\Support\TenancyContext;

class PayrollRunPolicy
{
    /**
     * Determine whether the user can view any payroll runs.
     */
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::HR_PAYROLL_VIEW);
    }

    /**
     * Determine whether the user can view the payroll run.
     */
    public function view(User $user, PayrollRun $payrollRun): bool
    {
        if ($payrollRun->tenant_id !== $user->tenant_id) {
            return false;
        }

        if (! $this->checkCenterAccess($user, $payrollRun)) {
            return false;
        }

        return $user->can(Permissions::HR_PAYROLL_VIEW);
    }

    /**
     * Determine whether the user can create payroll runs.
     */
    public function create(User $user): bool
    {
        return $user->can(Permissions::HR_PAYROLL_CREATE);
    }

    /**
     * Determine whether the user can update the payroll run.
     */
    public function update(User $user, PayrollRun $payrollRun): bool
    {
        if ($payrollRun->tenant_id !== $user->tenant_id) {
            return false;
        }

        if (! $this->checkCenterAccess($user, $payrollRun)) {
            return false;
        }

        return $user->can(Permissions::HR_PAYROLL_UPDATE);
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

        if (! $this->checkCenterAccess($user, $payrollRun)) {
            return false;
        }

        return $user->can(Permissions::HR_PAYROLL_DELETE);
    }

    /**
     * Enforce center isolation for multi-tenant and multi-center workspaces.
     */
    protected function checkCenterAccess(User $user, PayrollRun $payrollRun): bool
    {
        if ($payrollRun->center_id === null) {
            return true;
        }

        $activeCenterId = TenancyContext::centerId() ?? $user->current_center_id;

        if ($activeCenterId !== null && (int) $payrollRun->center_id === (int) $activeCenterId) {
            return true;
        }

        // Super admin / platform admin can view across centers within tenant
        if ($user->hasRole('super_admin') || ($user->is_system_admin ?? false)) {
            return true;
        }

        // User must be assigned to the target center
        return $user->centers()->where('centers.id', $payrollRun->center_id)->exists();
    }
}
