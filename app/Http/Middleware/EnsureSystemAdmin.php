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
        
        // Check if user is a system admin
        // System admins are stored without tenant_id OR have special is_system_admin flag
        if (!$user->is_system_admin) {
            abort(403, 'غير مصرح لك بالوصول للوحة النظام');
        }
        
        return $next($request);
    }
}
