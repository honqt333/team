<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\AdminUser;
use App\Models\User;
use App\Support\SystemPermissions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforce a system-panel ability on a route.
 *
 * ## Why this exists
 *
 * The /system panel was guarded by middleware alone. Every route
 * inherited `system.admin` (may enter the panel) and the destructive
 * ones added `super_admin` (may do anything irreversible). Everything
 * in between — managing payment gateways, editing the plan catalogue,
 * cancelling invoices, granting credit balances — was reachable by
 * ANY admin account, including a `support` one.
 *
 * The accounts carry a `role` and an optional permission list, and the
 * panel renders checkboxes for them, but nothing ever READ that list:
 * the abilities were defined and never enforced.
 *
 * This middleware closes that gap.
 *
 * ## How it decides
 *
 *   - `super_admin` bypasses everything (see AdminUser::isSuperAdmin).
 *   - A tenant User flagged `is_system_admin` is the platform owner
 *     and bypasses too — it is the same person as `super_admin`,
 *     reached through the other guard.
 *   - Otherwise the account's effective permissions decide, and the
 *     route must carry the required ability.
 *
 * ## Usage
 *
 *     Route::middleware('system.permission:tenants.manage')->group(...)
 *
 * Several abilities may be given; holding ANY one of them is enough:
 *
 *     Route::middleware('system.permission:billing.view,billing.manage')
 *
 * A route with no ability is not affected by this middleware at all.
 */
class EnsureSystemPermission
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next, string ...$abilities): Response
    {
        // No ability declared — nothing to enforce.
        if ($abilities === []) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated');
        }

        // The platform owner, on either guard.
        if ($user instanceof User && $user->is_system_admin) {
            return $next($request);
        }

        if ($user instanceof AdminUser) {
            if ($user->isSuperAdmin()) {
                return $next($request);
            }

            foreach ($abilities as $ability) {
                if ($user->hasPermission($ability)) {
                    return $next($request);
                }
            }
        }

        abort(403, __('messages.system.admin_users.only_super_admin'));
    }

    /**
     * The ability names this middleware understands.
     *
     * Used by the route-file tests to catch a typo in a declared
     * ability — an unknown ability would silently deny everyone,
     * which is a confusing failure to debug.
     *
     * @return array<int, string>
     */
    public static function knownAbilities(): array
    {
        return SystemPermissions::names();
    }
}
