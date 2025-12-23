<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VehicleMake;

class VehicleMakePolicy
{
    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, VehicleMake $make): bool
    {
        // Cannot modify system-level data
        return $make->source !== 'system';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, VehicleMake $make): bool
    {
        // Cannot delete system-level data
        return $make->source !== 'system';
    }

    /**
     * Determine whether the user can toggle active status.
     */
    public function toggleActive(User $user, VehicleMake $make): bool
    {
        // Cannot toggle system-level data
        return $make->source !== 'system';
    }
}
