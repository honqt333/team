<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Customer;
use App\Models\Tenant;
use App\Support\CustomerPortalSession;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Customer-portal session guard.
 *
 * The portal is mounted at /{tenant}/portal/*. The route
 * group has ResolveTenantFromUrl in front of this
 * middleware, so when we run, the request attributes
 * already carry `public_tenant` and `public_tenant_website`.
 *
 * What this middleware does
 * ------------------------
 *   1. Reads the customer_portal session key. If empty,
 *      the session is older than the hard ceiling, or the
 *      idle TTL is up, we 401.
 *   2. Looks up the customer in the DB. The lookup is
 *      tenant-scoped: a customer in tenant A cannot use
 *      a session minted in tenant B. This is the explicit
 *      cross-tenant check; nothing else in the system does
 *      it for the portal.
 *   3. Bumps the last_seen_at timestamp. This is how we
 *      implement "30 min idle, refreshed on activity".
 *
 * Two-tier timeout
 * ----------------
 * The session expires under EITHER condition:
 *   - IDLE_MINUTES (30 min) have passed since the last
 *     request, OR
 *   - ABSOLUTE_MINUTES (12 h) have passed since the
 *     session was first minted.
 *
 * The absolute ceiling is critical. A session ID that an
 * attacker captured before the legitimate user logged in
 * would otherwise be refreshable forever by sending a
 * request every 29 minutes. The hard cap bounds the
 * blast radius of a stolen session cookie to 12 hours.
 *
 * Why a custom guard
 * ------------------
 * Laravel's Auth::guard('customer_portal') would also work
 * — but it would re-define the user-provider contract,
 * which we deliberately avoid (see the comment in
 * config/auth.php). This middleware is the explicit,
 * readable form of the same idea.
 */
class EnsureCustomerPortalSession
{
    /**
     * 30-minute idle timeout. Refreshed on every request
     * that passes this middleware.
     */
    public const IDLE_MINUTES = 30;

    /**
     * 12-hour hard ceiling from session creation,
     * regardless of activity. The intent: a stolen
     * session cookie is useful for at most 12 hours
     * after the legitimate user signed in.
     */
    public const ABSOLUTE_MINUTES = 720; // 12 hours

    public function handle(Request $request, Closure $next): Response
    {
        $sessionKey = CustomerPortalSession::key();
        $payload = $request->session()->get($sessionKey);

        if (! is_array($payload) || empty($payload['customer_id']) || empty($payload['tenant_id'])) {
            return $this->unauthorized($request);
        }

        $tenantId = (int) $payload['tenant_id'];
        $customerId = (int) $payload['customer_id'];

        // Reject sessions minted for a different tenant.
        // The route's {tenant} slug is resolved to a tenant
        // by ResolveTenantFromUrl. If the session's tenant
        // does not match the route's tenant, the session
        // is invalid for this URL.
        /** @var Tenant|null $routeTenant */
        $routeTenant = $request->attributes->get('public_tenant');

        if (! $routeTenant || (int) $routeTenant->id !== $tenantId) {
            $request->session()->forget($sessionKey);

            return $this->unauthorized($request);
        }

        // Hard ceiling: even with active use, the session
        // expires 12 hours after the first mint. Sessions
        // minted before the ceiling existed have no
        // `created_at`; we treat those as "born now" so
        // they get a fresh 12-hour window the first time
        // they hit this middleware, and they will still be
        // bound by the IDLE_MINUTES window in the meantime.
        $createdAt = isset($payload['created_at'])
            ? Carbon::parse($payload['created_at'])
            : Carbon::now();

        if ($createdAt->lt(Carbon::now()->subMinutes(self::ABSOLUTE_MINUTES))) {
            $request->session()->forget($sessionKey);

            return $this->unauthorized($request);
        }

        // Idle timeout: 30 min from the last activity. The
        // last_seen_at is bumped below on every successful
        // pass, so the session is killed only after 30 min
        // of no requests.
        $lastSeen = isset($payload['last_seen_at'])
            ? Carbon::parse($payload['last_seen_at'])
            : null;

        if ($lastSeen === null || $lastSeen->lt(Carbon::now()->subMinutes(self::IDLE_MINUTES))) {
            $request->session()->forget($sessionKey);

            return $this->unauthorized($request);
        }

        // Look up the customer in this tenant. This is the
        // canonical isolation check: a session for customer
        // 1 in tenant A cannot be used to access tenant B,
        // and a session for a customer that has been
        // deleted in tenant A is rejected.
        $customer = Customer::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($customerId)
            ->first();

        if ($customer === null) {
            $request->session()->forget($sessionKey);

            return $this->unauthorized($request);
        }

        // Bind the resolved customer onto the request so
        // controllers do not have to re-query.
        $request->attributes->set('portal_customer', $customer);

        // Bump last_seen_at so the session window slides
        // forward on every request.
        $payload['last_seen_at'] = Carbon::now()->toIso8601String();
        $request->session()->put($sessionKey, $payload);

        return $next($request);
    }

    private function unauthorized(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => __('customer_portal.errors.unauthenticated')], 401);
        }

        // For Inertia requests, we redirect to the login
        // page. Inertia-aware: the server returns a 302
        // and Inertia's router follows it.
        $tenant = $request->attributes->get('public_tenant');
        $slug = $tenant instanceof Tenant ? $tenant->slug : '';

        return redirect()->to($slug ? '/'.$slug.'/portal/login' : '/');
    }
}
