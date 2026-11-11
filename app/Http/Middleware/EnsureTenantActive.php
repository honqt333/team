<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Billing\SubscriptionLifecycleService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantActive
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
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

        if (Schema::hasColumn('tenants', 'status')) {
            $lifecycle = $tenant->getSubscriptionLifecycle();

            // 1. Suspended tenants: block everything except logout and billing/renewal routes
            if ($lifecycle['state'] === SubscriptionLifecycleService::STATE_SUSPENDED) {
                if ($this->isWhitelistedBillingOrAuthRoute($request)) {
                    return $next($request);
                }

                abort(403, __('messages.subscription.account_suspended'));
            }

            // 2. Expired (post grace period) or readonly: allow GET reads & billing renewal, block write mutations
            if (! $lifecycle['can_operate']) {
                if ($this->isWhitelistedBillingOrAuthRoute($request)) {
                    return $next($request);
                }

                if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
                    abort(403, __('messages.subscription.expired_readonly'));
                }
            }
        }

        return $next($request);
    }

    private function isWhitelistedBillingOrAuthRoute(Request $request): bool
    {
        return $request->routeIs('logout')
            || $request->routeIs('app.billing.*')
            || $request->routeIs('app.subscription.*')
            || ($request->routeIs('app.settings.company') && $request->get('tab') === 'subscription');
    }
}
