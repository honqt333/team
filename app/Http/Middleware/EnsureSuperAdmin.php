<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\AdminUser;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate destructive / privileged system-panel actions to super_admin only.
 *
 * System admins come in three roles (AdminUser::$role):
 *   - super_admin: full system access (recommended single owner)
 *   - admin:       operational access; cannot delete tenants or impersonate
 *   - support:     read-only-ish support; cannot perform any destructive
 *                  action
 *
 * This middleware is intentionally separate from EnsureSystemAdmin so
 * admins can still read the system panel (tenants list, subscriptions,
 * settings pages) but cannot, for example, hit "Delete tenant" or start
 * impersonating a tenant.
 *
 * Apply via route group: ->middleware(['auth:web,admin', 'system.admin', 'super_admin'])
 */
class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated');
        }

        // AdminUser (system-admin guard) with role === 'super_admin' is allowed.
        if ($user instanceof AdminUser && $user->isSuperAdmin()) {
            return $next($request);
        }

        // The User model can also be a system admin if is_system_admin === true.
        //
        // NOTE: we deliberately do NOT check a Spatie role here.
        //
        // Every tenant owner is auto-assigned the tenant-scoped
        // `super_admin` role during registration (see
        // RegisteredUserController::store), so `hasRole('super_admin')`
        // carries no information about platform-level privilege — it is
        // true for essentially every tenant owner. Requiring it would
        // either lock out legitimate platform admins (who have the flag
        // but no such tenant role) or, worse, give a future maintainer
        // the false impression that the role is what grants access.
        //
        // The `is_system_admin` flag IS the platform-level gate. It is
        // not tenant-settable: StoreUserRequest and UpdateUserRequest
        // both mark it `forbidden`, so it can only be granted out of
        // band (seeders / a direct DB write).
        if ($user instanceof User && $user->is_system_admin) {
            return $next($request);
        }

        abort(403, 'هذه العملية مخصصة لمديري النظام فقط (super_admin)');
    }
}
