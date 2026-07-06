<?php
namespace App\Policies;

use App\Models\Service;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

use App\Support\Permissions;
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
        if (!$user->can(Permissions::SERVICES_VIEW)) {
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
     * Determine whether the user can update the service.
     */
    public function update(User $user, Service $service): bool
    {
        if (!$user->can(Permissions::SERVICES_UPDATE)) {
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
        if (!$user->can(Permissions::SERVICES_DELETE)) {
            return false;
        }

        return $user->tenant_id === $service->tenant_id
            && $user->current_center_id === $service->center_id;
    }
}
