<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Service;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Auth\Access\HandlesAuthorization;

class ServicePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any services.
     */
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::SERVICES_VIEW);
    }

    /**
     * Determine whether the user can view the service.
     */
    public function view(User $user, Service $service): bool
    {
        if (! $user->can(Permissions::SERVICES_VIEW)) {
            return false;
        }

        return $user->tenant_id === $service->tenant_id
            && $user->current_center_id === $service->center_id;
    }

    /**
     * Determine whether the user can create services.
     */
    public function create(User $user): bool
    {
        return $user->can(Permissions::SERVICES_CREATE);
    }

    /**
     * Can the user change the workshop-wide settings of the services
     * module — currently the systematic inspection steps?
     *
     * This is a class-level ability: the setting does not belong to
     * one service, it changes what every work order of the workshop
     * has to go through.
     */
    public function manageInspections(User $user, ?string $model = null): bool
    {
        return $user->can(Permissions::SERVICES_UPDATE);
    }

    /**
     * Determine whether the user can update the service.
     */
    public function update(User $user, Service $service): bool
    {
        if (! $user->can(Permissions::SERVICES_UPDATE)) {
            return false;
        }

        return $user->tenant_id === $service->tenant_id
            && $user->current_center_id === $service->center_id;
    }

    /**
     * Determine whether the user can delete the service.
     */
    public function delete(User $user, Service $service): bool
    {
        if (! $user->can(Permissions::SERVICES_DELETE)) {
            return false;
        }

        return $user->tenant_id === $service->tenant_id
            && $user->current_center_id === $service->center_id;
    }
}
