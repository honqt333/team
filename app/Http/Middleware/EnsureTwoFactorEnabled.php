<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        if (!$user) {
            return $next($request);
        }

        $tenant = $user->tenant;
        if (!$tenant) {
            return $next($request);
        }

        // Check if 2FA is required by tenant and not enabled by user
        if ($tenant->two_factor_enabled && 
            $tenant->two_factor_enforcement === 'required' && 
            is_null($user->two_factor_confirmed_at)) {
            
            // Allow access to 2FA setup routes to prevent infinite loop
            if ($request->routeIs('app.security.2fa.*')) {
                return $next($request);
            }

            return redirect()->route('app.security.2fa.setup')
                ->with('warning', 'المصادقة الثنائية إلزامية في هذا الحساب. يرجى تفعيلها للمتابعة.');
        }

        return $next($request);
    }
}
