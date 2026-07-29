<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ServiceBay;
use App\Models\User;
use App\Support\Permissions;

class ServiceBayPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::APPOINTMENTS_MANAGE);
    }

    public function view(User $user, ServiceBay $bay): bool
    {
        return $user->can(Permissions::APPOINTMENTS_MANAGE)
            && $bay->tenant_id === $user->tenant_id
            && $bay->center_id === $user->current_center_id;
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::APPOINTMENTS_MANAGE);
    }

    public function update(User $user, ServiceBay $bay): bool
    {
        return $this->view($user, $bay);
    }

    public function delete(User $user, ServiceBay $bay): bool
    {
        return $this->view($user, $bay);
    }
}
