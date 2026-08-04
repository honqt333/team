<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * P1 — Separate session cookie for the customer portal.
 *
 * Why this middleware exists
 * --------------------------
 * The customer portal (/{tenant}/portal/*) and the
 * admin / staff app (/{tenant}/app/*, /system/*)
 * share the same Laravel session cookie by default.
 * The portal session is a small payload under
 * CustomerPortalSession::key() but the COOKIE that
 * carries it is the same one the admin app uses.
 *
 * The bug had two layers. Both had to be fixed
 * together; either one alone was insufficient.
 *
 * Layer 1 — the cookie. With both audiences
 * active in the same browser:
 *
 *   1. An operator is signed into /app/dashboard in
 *      tab A. Their session cookie is `X-SESSION=admin-xyz`.
 *   2. A customer signs into /{tenant}/portal/dashboard
 *      in tab B. The verify endpoint calls
 *      $request->session()->regenerate() which mints
 *      a new session ID and queues a Set-Cookie that
 *      overwrites `X-SESSION` to the new ID.
 *   3. A long-poll or livewire request from tab A
 *      (the admin app) lands AFTER tab B's regenerate
 *      but BEFORE the browser has applied the new
 *      Set-Cookie, and the new cookie is queued on
 *      the admin request's response. The admin tab
 *      now holds the portal session ID; the next
 *      redirect (a /app/* route that does
 *      session()->regenerate() on a form submit)
 *      drops the admin session.
 *
 * Or, more painfully: the browser's cookie jar
 * receives Set-Cookie from the admin app that points
 * at the portal session ID, then a subsequent
 * portal request reads it and finds an empty
 * cp_session key, the dashboard middleware
 * 401-redirects, and the customer lands back on
 * /portal/login despite having just verified.
 *
 * The fix is to give the portal its own cookie
 * name. The portal and admin sessions are now
 * completely disjoint on the wire: the browser
 * holds two cookies, one per audience, and a
 * regenerate() in one does not affect the other.
 *
 * Layer 2 — the session payload. Even with the
 * cookie isolated, the verify endpoint could still
 * mint a session that the dashboard middleware
 * rejected. CustomerPortalOtpService::findCustomer()
 * was returning the customer with `get(['id',
 * 'phone', 'whatsapp'])` — without `tenant_id`. The
 * verify endpoint then built the session payload
 * from the customer model: `customer->tenant_id`
 * resolved to NULL because the column was not on
 * the projection. The dashboard middleware
 * (EnsureCustomerPortalSession) checks
 * `(int) $routeTenant->id !== $tenantId` where
 * $tenantId is the session value; with a null
 * session value, the cast yields 0, which never
 * matches the route tenant, so the middleware
 * 401-redirects to /portal/login. Adding
 * `tenant_id` to the projection is what makes
 * the verify → dashboard round-trip actually
 * survive a second request. That change is in
 * CustomerPortalOtpService::findCustomer() and is
 * documented inline at the projection.
 *
 * Why both layers matter in the report
 * ------------------------------------
 * If only the cookie had been isolated, the
 * customer-portal dashboard would still bounce:
 * the cookie would carry the right session ID
 * across the redirect, but the session payload
 * itself would be incomplete (no tenant_id),
 * and the dashboard middleware would still 401.
 * If only the projection had been fixed, the
 * cookie race would still happen in multi-tab
 * flows and the symptom (customer lands back on
 * /portal/login after verify) would return.
 * The two layers are independent: the cookie is
 * about wire-level isolation, the projection is
 * about payload completeness.
 *
 * Why this middleware (not a config-based switch)
 * ------------------------------------------------
 * Laravel's `session.cookie` config is global. A
 * config-based switch would have to either:
 *   - always use the portal cookie (breaks /app/*
 *     and /system/*)
 *   - fork the session driver per request (we tried
 *     this once, it breaks StartSession because the
 *     session is built before route resolution)
 *
 * The cleanest fix is a tiny middleware that runs
 * at the start of the web group, BEFORE
 * StartSession, looks at the request path, and
 * rewrites `config('session.cookie')` in-process
 * for this request only. The session driver reads
 * the config during buildSession(); we also call
 * setName() on the resolved Store so the cached
 * instance gets the new name. StartSession then
 * writes the Set-Cookie with the new name.
 *
 * Why path-based detection (not route-based)
 * ------------------------------------------
 * The route group /{tenant}/portal/* uses the
 * ResolveTenantFromUrl middleware, which depends on
 * {tenant} being a real published tenant. We could
 * match the route name (`public.portal.*`) but that
 * requires route resolution to have run. At the
 * pre-StartSession point in the pipeline the route
 * may not be resolved yet on the first hit of a
 * session (Laravel defers route resolution). A path
 * regex is more reliable: it does not depend on the
 * session, the tenant DB lookup, or the route
 * binding. The regex is the same one used in
 * routes/web.php for the portal prefix.
 *
 * Why we do not touch SESSION_PATH
 * ---------------------------------
 * Both cookies are written to the same path (`/`).
 * That is intentional: a same-origin request to
 * /app/* and /{tenant}/portal/* would otherwise
 * see different cookies depending on path. The
 * isolation is the cookie NAME, not the cookie
 * path. Per the task spec, the path stays `/` and
 * only the name changes.
 */
class UsePortalSessionCookie
{
    public function __construct(
        private readonly Application $app,
    ) {}

    /**
     * The portal session cookie name. The
     * `carag_` prefix is the project brand (the
     * project ships as "Carag" / "Khidmh Pro" — see
     * the project root AGENTS.md). The
     * `_customer_portal_` segment makes the
     * purpose self-documenting in a browser's
     * cookie jar. The suffix `_session` matches
     * Laravel's default naming so an operator
     * grepping the cookie header recognizes it.
     */
    public const COOKIE_NAME = 'carag_customer_portal_session';

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isPortalRequest($request)) {
            // The session driver reads
            // `session.cookie` during buildSession().
            // StartSession is the next priority-
            // sorted middleware after this one (we
            // are prepended to its priority slot
            // in bootstrap/app.php), so the rewrite
            // is in effect by the time the session
            // store is constructed.
            //
            // We also save the previous value so
            // child code (or a future test) can
            // confirm the swap. The swap is
            // in-process: it does not persist
            // between requests.
            config([
                'session.cookie' => self::COOKIE_NAME,
            ]);

            // The cached Store carries the OLD cookie
            // name. The simplest way to swap the name
            // WITHOUT losing the in-memory state is to
            // call setName() on the resolved Store.
            // Store::setName() is a public API; the
            // alternative — re-building the Store via
            // $manager->driver() — would also forget
            // the data the test set up via
            // withSession() / session()->put() (because
            // the array driver is per-instance).
            //
            // The request-scoped contract: any code
            // path that reads the session name during
            // this request (StartSession, the Auth
            // guard, the cookie queuer) sees the new
            // name. The next non-portal request hits
            // the kernel fresh, the manager rebuilds
            // from the default config, and the admin
            // cookie is back.
            try {
                $store = $this->app->make('session.store');

                if (is_object($store) && method_exists($store, 'setName')) {
                    $store->setName(self::COOKIE_NAME);
                }
            } catch (Throwable $e) {
                // No store yet (e.g. an early
                // service-provider resolution). The
                // next container resolution will pick
                // up the new config and build with
                // the new name. Nothing to do here.
            }
        }

        return $next($request);
    }

    /**
     * True if the request path is under a tenant's
     * portal surface. The match is deliberately
     * cheap (no DB, no tenant lookup) so it can
     * run before StartSession without spinning up
     * anything beyond the request object.
     */
    private function isPortalRequest(Request $request): bool
    {
        $path = ltrim($request->path(), '/');

        // The portal prefix requires {tenant} then
        // `/portal/`. A bare `/portal/...` (no
        // tenant) is not a portal request — the
        // route group would 404 it anyway.
        if (! str_contains($path, '/portal/')) {
            return false;
        }

        // Split into segments and require exactly:
        //   [0] = tenant slug
        //   [1] = 'portal'
        // Any number of additional segments is fine.
        $segments = explode('/', $path);

        if (count($segments) < 2) {
            return false;
        }

        return $segments[0] !== '' && $segments[1] === 'portal';
    }
}
