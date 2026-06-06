<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSystemAdmin
{
    /**
     * Handle an incoming request.
     * Only allow users with system_admin role to access system panel.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // 1. If user is AdminUser instance -> Allow
        if ($user instanceof \App\Models\AdminUser) {
            return $next($request);
        }

        // 2. If user is User instance -> Allow if either:
        //    a) The dedicated is_system_admin flag is set, OR
        //    b) The user has the super_admin role (system-wide admin via spatie/permission)
        //    Without this second check, the super_admin user cannot reach the
        //    system panel even though Gate::before lets them bypass other policies.
        if ($user instanceof User) {
            if ($user->is_system_admin) {
                return $next($request);
            }

            if (method_exists($user, 'hasRole') && $user->hasRole('super_admin')) {
                return $next($request);
            }

            abort(403, 'غير مصرح لك بالوصول للوحة النظام');
        }

        return $next($request);
    }
}
