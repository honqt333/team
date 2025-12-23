<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VehicleColor;

class VehicleColorPolicy
{
    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, VehicleColor $color): bool
    {
        // Cannot modify system-level data
        return $color->source !== 'system';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, VehicleColor $color): bool
    {
        // Cannot delete system-level data
        return $color->source !== 'system';
    }

    /**
     * Determine whether the user can toggle active status.
     */
    public function toggleActive(User $user, VehicleColor $color): bool
    {
        // Cannot toggle system-level data
        return $color->source !== 'system';
    }
}
