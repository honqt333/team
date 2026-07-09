<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantActive
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'User not authenticated');
        }

        $tenant = $user->tenant;

        // Check if tenant exists and is not soft-deleted
        if (! $tenant || $tenant->trashed()) {
            abort(403, 'Tenant not found or inactive');
        }

        // Check tenant status if column exists
        if (Schema::hasColumn('tenants', 'status')) {
            if (in_array($tenant->status, ['expired', 'suspended', 'readonly'])) {
                // Allow logout route to proceed with write operations
                if ($request->routeIs('logout')) {
                    return $next($request);
                }

                // Block write/modifying operations
                if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
                    $message = $tenant->status === 'suspended'
                        ? 'تم تعليق حسابك مؤقتاً. يرجى تجديد الاشتراك لتتمكن من إجراء العمليات.'
                        : 'انتهت صلاحية اشتراكك. يرجى تجديد الاشتراك لتتمكن من إجراء العمليات.';

                    abort(403, $message);
                }
            }
        }

        return $next($request);
    }
}
