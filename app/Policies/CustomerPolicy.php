<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;
use App\Support\Permissions;

class CustomerPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::CUSTOMERS_VIEW);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Customer $customer): bool
    {
        // Check permission
        if (! $user->can(Permissions::CUSTOMERS_VIEW)) {
            return false;
        }

        // Defense in depth: enforce tenant ownership
        return $customer->tenant_id === $user->tenant_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can(Permissions::CUSTOMERS_CREATE);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Customer $customer): bool
    {
        // Check permission
        if (! $user->can(Permissions::CUSTOMERS_UPDATE)) {
            return false;
        }

        // Defense in depth: enforce tenant ownership
        return $customer->tenant_id === $user->tenant_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Customer $customer): bool
    {
        // Check permission
        if (! $user->can(Permissions::CUSTOMERS_DELETE)) {
            return false;
        }

        // Defense in depth: enforce tenant ownership
        return $customer->tenant_id === $user->tenant_id;
    }
}
