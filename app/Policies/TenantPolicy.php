<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;
use App\Support\Permissions;

/**
 * Authorization rules for cross-tenant settings endpoints.
 *
 * The print-settings endpoints operate on the *current* tenant — there is
 * no {tenant} route parameter and the controller reads
 * `auth()->user()->tenant` directly. This policy exists primarily to plug
 * into Laravel's `$this->authorize()` API and to provide a single
 * permission gate (`settings.company.manage`) for everything that mutates
 * tenant-level configuration.
 */
class TenantPolicy
{
    /**
     * Determine whether the user can view the tenant.
     *
     * Any authenticated user belonging to the tenant can view their own
     * tenant's basic info.
     */
    public function view(User $user, ?Tenant $tenant = null): bool
    {
        if (! $tenant) {
            return false;
        }

        return $user->tenant_id === $tenant->id;
    }

    /**
     * Determine whether the user can update the tenant.
     *
     * The `$tenant` argument is nullable because Laravel's Gate can call
     * a policy with the class name only (e.g. `$user->can('update',
     * Tenant::class)`), in which case the model is not provided and
     * we conservatively deny.
     *
     * Requires:
     *   - A target tenant
     *   - Same tenant_id (cross-tenant access is impossible)
     *   - `settings.company.manage` permission
     */
    public function update(User $user, ?Tenant $tenant = null): bool
    {
        if (! $tenant) {
            return false;
        }

        if ($user->tenant_id !== $tenant->id) {
            return false;
        }

        return $user->can(Permissions::COMPANY_MANAGE);
    }

    /**
     * Determine whether the user can delete the tenant.
     *
     * Tenant deletion is reserved for system admins; the
     * `Gate::before` hook in AppServiceProvider handles the bypass for
     * `is_system_admin` users. For ordinary tenant users we deny.
     */
    public function delete(User $user, ?Tenant $tenant = null): bool
    {
        return false;
    }
}
