<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\VehicleColor;
use App\Support\Permissions;

class VehicleColorPolicy
{
    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, VehicleColor $color): bool
    {
        // Must have manage permission AND cannot modify system-level data
        return $user->can(Permissions::VEHICLE_SETTINGS_MANAGE)
            && $color->source !== 'system';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, VehicleColor $color): bool
    {
        // Must have manage permission AND cannot delete system-level data
        return $user->can(Permissions::VEHICLE_SETTINGS_MANAGE)
            && $color->source !== 'system';
    }

    /**
     * Determine whether the user can toggle active status.
     */
    public function toggleActive(User $user, VehicleColor $color): bool
    {
        // Must have manage permission AND cannot toggle system-level data
        return $user->can(Permissions::VEHICLE_SETTINGS_MANAGE)
            && $color->source !== 'system';
    }
}
