<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vehicle;
use App\Support\Permissions;

class VehiclePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::VEHICLES_VIEW);
    }

    public function view(User $user, Vehicle $vehicle): bool
    {
        return $user->can(Permissions::VEHICLES_VIEW)
            && $vehicle->tenant_id === $user->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::VEHICLES_CREATE);
    }

    public function update(User $user, Vehicle $vehicle): bool
    {
        return $user->can(Permissions::VEHICLES_UPDATE)
            && $vehicle->tenant_id === $user->tenant_id;
    }

    public function delete(User $user, Vehicle $vehicle): bool
    {
        return $user->can(Permissions::VEHICLES_DELETE)
            && $vehicle->tenant_id === $user->tenant_id;
    }
}
