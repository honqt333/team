<?php

declare(strict_types=1);

namespace App\Policies\HR;

use App\Models\HR\Employee;
use App\Models\User;
use App\Support\Permissions;
use App\Support\TenancyContext;

class EmployeePolicy
{
    /**
     * Determine whether the user can view any employees.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(Permissions::HR_EMPLOYEES_VIEW);
    }

    /**
     * Determine whether the user can view their own employee record (Employee Portal).
     */
    public function viewOwn(User $user): bool
    {
        // User must have employee role and be linked to an employee record
        return $user->hasPermissionTo(Permissions::EMPLOYEE_PROFILE_VIEW) && $user->employee !== null;
    }

    /**
     * Determine whether the user can view the employee.
     */
    public function view(User $user, Employee $employee): bool
    {
        if (! $user->hasPermissionTo(Permissions::HR_EMPLOYEES_VIEW)) {
            return false;
        }

        return $this->canAccessCenter($user, $employee);
    }

    /**
     * Determine whether the user can create employees.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo(Permissions::HR_EMPLOYEES_CREATE);
    }

    /**
     * Determine whether the user can update the employee.
     */
    public function update(User $user, Employee $employee): bool
    {
        if (! $user->hasPermissionTo(Permissions::HR_EMPLOYEES_UPDATE)) {
            return false;
        }

        return $this->canAccessCenter($user, $employee);
    }

    /**
     * Determine whether the user can delete the employee.
     */
    public function delete(User $user, Employee $employee): bool
    {
        if (! $user->hasPermissionTo(Permissions::HR_EMPLOYEES_DELETE)) {
            return false;
        }

        return $this->canAccessCenter($user, $employee);
    }

    private function canAccessCenter(User $user, Employee $employee): bool
    {
        if ((int) $employee->tenant_id !== (int) $user->tenant_id) {
            return false;
        }

        if ($user->hasRole('super_admin')) {
            return true;
        }

        $currentCenterId = TenancyContext::centerId() ?? $user->current_center_id;

        return $employee->center_id === null || (int) $employee->center_id === (int) $currentCenterId;
    }
}
