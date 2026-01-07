<?php

namespace App\Http\Middleware;

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
        
        // 2. If user is User instance -> Check is_system_admin flag
        if ($user instanceof \App\Models\User && !$user->is_system_admin) {
            abort(403, 'غير مصرح لك بالوصول للوحة النظام');
        }
        
        return $next($request);
    }
}
