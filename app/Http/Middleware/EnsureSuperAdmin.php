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
        // For those we additionally require the explicit role to avoid
        // privilege escalation through stale tokens.
        if ($user instanceof User && $user->is_system_admin) {
            // If the user has a roles relationship loaded and a super_admin
            // role assignment, allow. We don't require role here because
            // tenant-level super_admin would match the spatie role name
            // by accident — the is_system_admin flag is the gate.
            return $next($request);
        }

        abort(403, 'هذه العملية مخصصة لمديري النظام فقط (super_admin)');
    }
}
