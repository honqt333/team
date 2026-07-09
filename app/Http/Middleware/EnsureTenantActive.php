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
            $isExpired = $tenant->status === 'expired' ||
                ($tenant->status === 'trial' && $tenant->trial_ends_at && $tenant->trial_ends_at->isPast());

            // 1. Block suspended tenants
            if ($tenant->status === 'suspended') {
                if ($request->routeIs('logout') ||
                    ($request->routeIs('app.settings.company') && $request->get('tab') === 'subscription')) {
                    return $next($request);
                }
                abort(403, 'تم إيقاف حسابك مؤقتاً لعدم وجود اشتراك نشط. يرجى التواصل مع الإدارة للتفعيل.');
            }

            // 2. Block write/modifying operations for expired or readonly tenants
            if ($isExpired || $tenant->status === 'readonly') {
                if ($request->routeIs('logout') ||
                    ($request->routeIs('app.settings.company') && $request->get('tab') === 'subscription')) {
                    return $next($request);
                }

                if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
                    abort(403, 'انتهت صلاحية اشتراكك. يرجى تجديد الاشتراك لتتمكن من إجراء العمليات.');
                }
            }
        }

        return $next($request);
    }
}
