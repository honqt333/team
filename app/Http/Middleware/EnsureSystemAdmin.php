<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\AdminUser;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSystemAdmin
{
    /**
     * Handle an incoming request.
     *
     * Only true system administrators may reach the /system/* panel.
     * The `super_admin` role that every tenant owner is auto-assigned
     * during registration is a tenant-scope role and must NOT grant
     * access here — otherwise any new signup could see and modify
     * every other tenant in the system (privilege escalation).
     *
     * Allowed identities:
     *   - AdminUser (the dedicated system-admin guard)
     *   - User with the explicit is_system_admin flag
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Automatically stop impersonation if returning to system routes
        if ($request->hasSession() && $request->session()->has('impersonating_from')) {
            $originalUserId = $request->session()->get('impersonating_from');
            $originalUser = User::find($originalUserId);

            if ($originalUser && ($originalUser->is_system_admin || $originalUser instanceof AdminUser)) {
                Auth::login($originalUser);
                $request->session()->forget('impersonating_from');
                $request->session()->forget('impersonating_tenant');
                $request->setUserResolver(fn () => $originalUser);
            }
        }

        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // 1. If user is AdminUser instance -> Allow
        if ($user instanceof AdminUser) {
            return $next($request);
        }

        // 2. User with the explicit is_system_admin flag -> Allow
        //    NOTE: do NOT accept the tenant-level `super_admin` role
        //    here. That role is granted to every new tenant owner
        //    (see RegisteredUserController::store) and only authorises
        //    actions within the tenant scope.
        if ($user instanceof User && $user->is_system_admin) {
            return $next($request);
        }

        abort(403, 'غير مصرح لك بالوصول للوحة النظام');
    }
}
