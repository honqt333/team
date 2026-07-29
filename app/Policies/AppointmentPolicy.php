<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;
use App\Support\Permissions;

class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::APPOINTMENTS_MANAGE);
    }

    public function view(User $user, Appointment $appointment): bool
    {
        return $user->can(Permissions::APPOINTMENTS_MANAGE)
            && $appointment->tenant_id === $user->tenant_id
            && $appointment->center_id === $user->current_center_id;
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::APPOINTMENTS_MANAGE);
    }

    public function update(User $user, Appointment $appointment): bool
    {
        return $this->view($user, $appointment);
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return $this->view($user, $appointment);
    }
}
