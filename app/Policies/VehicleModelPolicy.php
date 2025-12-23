<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VehicleModel;

class VehicleModelPolicy
{
    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, VehicleModel $model): bool
    {
        // Cannot modify system-level data
        return $model->source !== 'system';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, VehicleModel $model): bool
    {
        // Cannot delete system-level data
        return $model->source !== 'system';
    }

    /**
     * Determine whether the user can toggle active status.
     */
    public function toggleActive(User $user, VehicleModel $model): bool
    {
        // Cannot toggle system-level data
        return $model->source !== 'system';
    }
}
