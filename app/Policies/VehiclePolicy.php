<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vehicle;

class VehiclePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('crm.vehicles.view');
    }

    public function view(User $user, Vehicle $vehicle): bool
    {
        return $user->can('crm.vehicles.view')
            && $vehicle->tenant_id === $user->tenant_id
            && $vehicle->center_id === $user->current_center_id;
    }

    public function create(User $user): bool
    {
        return $user->can('crm.vehicles.create');
    }

    public function update(User $user, Vehicle $vehicle): bool
    {
        return $user->can('crm.vehicles.update')
            && $vehicle->tenant_id === $user->tenant_id
            && $vehicle->center_id === $user->current_center_id;
    }

    public function delete(User $user, Vehicle $vehicle): bool
    {
        return $user->can('crm.vehicles.delete')
            && $vehicle->tenant_id === $user->tenant_id
            && $vehicle->center_id === $user->current_center_id;
    }
}
