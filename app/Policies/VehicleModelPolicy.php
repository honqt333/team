<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VehicleModel;
use App\Support\Permissions;

class VehicleModelPolicy
{
    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, VehicleModel $model): bool
    {
        return $user->can(Permissions::VEHICLE_SETTINGS_MANAGE)
            && $model->source !== 'system';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, VehicleModel $model): bool
    {
        return $user->can(Permissions::VEHICLE_SETTINGS_MANAGE)
            && $model->source !== 'system';
    }

    /**
     * Determine whether the user can toggle active status.
     */
    public function toggleActive(User $user, VehicleModel $model): bool
    {
        return $user->can(Permissions::VEHICLE_SETTINGS_MANAGE)
            && $model->source !== 'system';
    }
}
