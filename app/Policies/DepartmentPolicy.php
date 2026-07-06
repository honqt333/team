<?php
namespace App\Policies;

use App\Models\Department;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

use App\Support\Permissions;
class DepartmentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any departments.
     */
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::DEPARTMENTS_VIEW);
    }

    /**
     * Determine whether the user can view the department.
     */
    public function view(User $user, Department $department): bool
    {
        if (!$user->can(Permissions::DEPARTMENTS_VIEW)) {
            return false;
        }

        return $user->tenant_id === $department->tenant_id
            && $user->current_center_id === $department->center_id;
    }

    /**
     * Determine whether the user can create departments.
     */
    public function create(User $user): bool
    {
        return $user->can(Permissions::DEPARTMENTS_MANAGE);
    }

    /**
     * Determine whether the user can update the department.
     */
    public function update(User $user, Department $department): bool
    {
        if (!$user->can(Permissions::DEPARTMENTS_MANAGE)) {
            return false;
        }

        return $user->tenant_id === $department->tenant_id
            && $user->current_center_id === $department->center_id;
    }

    /**
     * Determine whether the user can delete the department.
     */
    public function delete(User $user, Department $department): bool
    {
        if (!$user->can(Permissions::DEPARTMENTS_MANAGE)) {
            return false;
        }

        return $user->tenant_id === $department->tenant_id
            && $user->current_center_id === $department->center_id;
    }
}
