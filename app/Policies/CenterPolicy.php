<?php

namespace App\Policies;

use App\Models\Center;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Auth\Access\Response;

class CenterPolicy
{
    /**
     * Determine whether the user can view any centers.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(Permissions::CENTERS_VIEW)
            || $user->hasPermissionTo(Permissions::CENTERS_MANAGE);
    }

    /**
     * Determine whether the user can create centers.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo(Permissions::CENTERS_MANAGE);
    }

    /**
     * Determine whether the user can view the center.
     */
    public function view(User $user, Center $center): bool
    {
        // User must belong to the same tenant
        return $user->tenant_id === $center->tenant_id;
    }

    /**
     * Determine whether the user can update the center.
     */
    public function update(User $user, Center $center): bool
    {
        // User must belong to the same tenant
        // TODO: Add role check (only admin/manager can update)
        return $user->tenant_id === $center->tenant_id;
    }

    /**
     * Determine whether the user can delete the center.
     */
    public function delete(User $user, Center $center): bool
    {
        // User must belong to the same tenant
        // TODO: Add role check (only admin can delete)
        return $user->tenant_id === $center->tenant_id;
    }
}
