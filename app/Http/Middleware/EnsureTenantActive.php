<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(403, 'User not authenticated');
        }

        $tenant = $user->tenant;

        // Check if tenant exists and is not soft-deleted
        if (!$tenant || $tenant->trashed()) {
            abort(403, 'Tenant not found or inactive');
        }

        // Check readonly status if column exists
        if (\Illuminate\Support\Facades\Schema::hasColumn('tenants', 'status')) {
            if ($tenant->status === 'readonly') {
                // Block write operations
                if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
                    abort(403, 'Tenant is in read-only mode');
                }
            }
        }

        return $next($request);
    }
}
