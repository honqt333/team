<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use App\Support\Permissions;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(Permissions::USERS_VIEW);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        if (! $user->hasPermissionTo(Permissions::USERS_VIEW)) {
            return false;
        }

        // Tenant isolation
        return $model->tenant_id === $user->tenant_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo(Permissions::USERS_CREATE);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        if (! $user->hasPermissionTo(Permissions::USERS_UPDATE)) {
            return false;
        }

        // Tenant isolation
        return $model->tenant_id === $user->tenant_id
            && $model->getAllPermissions()->every(fn ($permission) => $user->can($permission->name))
            && ! $model->is_system_admin
            && (! $model->hasRole('super_admin') || $user->hasRole('super_admin'));
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        if (! $user->hasPermissionTo(Permissions::USERS_DELETE)) {
            return false;
        }

        // Prevent self-deletion
        if ($user->id === $model->id) {
            return false;
        }

        // Tenant isolation
        return $model->tenant_id === $user->tenant_id
            && $model->getAllPermissions()->every(fn ($permission) => $user->can($permission->name))
            && ! $model->is_system_admin
            && (! $model->hasRole('super_admin') || $user->hasRole('super_admin'));
    }

    public function assignRole(User $user, Role $role): bool
    {
        return (int) $role->tenant_id === (int) $user->tenant_id
            && $role->guard_name === 'web'
            && ($role->name !== 'super_admin' || $user->hasRole('super_admin'))
            && $role->permissions->every(fn ($permission) => $user->can($permission->name));
    }
}
