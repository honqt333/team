<?php

namespace App\Policies\HR;

use App\Models\HR\Employee;
use App\Models\User;
use App\Support\Permissions;

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
     * Determine whether the user can view the employee.
     */
    public function view(User $user, Employee $employee): bool
    {
        if (!$user->hasPermissionTo(Permissions::HR_EMPLOYEES_VIEW)) {
            return false;
        }

        // Tenant isolation
        return $employee->tenant_id === $user->tenant_id;
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
        if (!$user->hasPermissionTo(Permissions::HR_EMPLOYEES_UPDATE)) {
            return false;
        }

        // Tenant isolation
        return $employee->tenant_id === $user->tenant_id;
    }

    /**
     * Determine whether the user can delete the employee.
     */
    public function delete(User $user, Employee $employee): bool
    {
        if (!$user->hasPermissionTo(Permissions::HR_EMPLOYEES_DELETE)) {
            return false;
        }

        // Tenant isolation
        return $employee->tenant_id === $user->tenant_id;
    }
}
