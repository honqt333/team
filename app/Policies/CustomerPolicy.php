<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('crm.customers.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Customer $customer): bool
    {
        // Check permission
        if (!$user->hasPermissionTo('crm.customers.view')) {
            return false;
        }

        // Defense in depth: enforce tenant/center ownership
        return $customer->tenant_id === $user->tenant_id
            && $customer->center_id === $user->current_center_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('crm.customers.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Customer $customer): bool
    {
        // Check permission
        if (!$user->hasPermissionTo('crm.customers.update')) {
            return false;
        }

        // Defense in depth: enforce tenant/center ownership
        return $customer->tenant_id === $user->tenant_id
            && $customer->center_id === $user->current_center_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Customer $customer): bool
    {
        // Check permission
        if (!$user->hasPermissionTo('crm.customers.delete')) {
            return false;
        }

        // Defense in depth: enforce tenant/center ownership
        return $customer->tenant_id === $user->tenant_id
            && $customer->center_id === $user->current_center_id;
    }
}
