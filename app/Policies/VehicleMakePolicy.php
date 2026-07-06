<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VehicleMake;
use App\Support\Permissions;

class VehicleMakePolicy
{
    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, VehicleMake $make): bool
    {
        return $user->can(Permissions::VEHICLE_SETTINGS_MANAGE)
            && $make->source !== 'system';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, VehicleMake $make): bool
    {
        return $user->can(Permissions::VEHICLE_SETTINGS_MANAGE)
            && $make->source !== 'system';
    }

    /**
     * Determine whether the user can toggle active status.
     */
    public function toggleActive(User $user, VehicleMake $make): bool
    {
        return $user->can(Permissions::VEHICLE_SETTINGS_MANAGE)
            && $make->source !== 'system';
    }
}
