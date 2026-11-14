<?php

declare(strict_types=1);

use App\Http\Middleware\ConvertArabicNumerals;
use App\Http\Middleware\EnsureCenterContext;
use App\Http\Middleware\EnsureCustomerPortalSession;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\EnsureSystemAdmin;
use App\Http\Middleware\EnsureSystemPermission;
use App\Http\Middleware\EnsureTenantActive;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\PortalLocale;
use App\Http\Middleware\PreventBackHistory;
use App\Http\Middleware\RequestId;
use App\Http\Middleware\ResolveTenantFromUrl;
use App\Http\Middleware\SentryContext;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SetPermissionsTeam;
use App\Http\Middleware\TrackAiUsage;
use App\Http\Middleware\UsePortalSessionCookie;
use App\Providers\EventServiceProvider;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Inertia\Inertia;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        RouteServiceProvider::class,
        EventServiceProvider::class,
    ])
    // P0 — every event listener is declared EXPLICITLY in
    // App\Providers\EventServiceProvider::$listen.
    //
    // Laravel's Application::configure() registers the base
    // Illuminate\Foundation\Support\Providers\EventServiceProvider
    // through withEvents(), and that base instance has
    // shouldDiscoverEvents() === true. It therefore auto-discovers
    // every class under app/Listeners and registers it as
    // `Class@handle`.
    //
    // Because we ALSO register App\Providers\EventServiceProvider
    // above (which registers the same listeners as bare class strings
    // from its $listen map), the dispatcher ended up holding TWO
    // registrations for every listener. A bare class string resolves
    // to the same handle() method, so BOTH fired.
    //
    // The observable effect: every domain listener ran twice.
    //   - NotifyOwnerOnCreation       → 2 bell notifications per work order
    //   - NotifyAdminsOfReceipt       → 2 admin notifications per receipt
    //   - NotifyTenantOfReceiptDecision → 2 per receipt decision
    //   - LogActivityOnStatusChange   → 2 activity rows per status change
    //   - LinkPaymentToInvoice /
    //     UpdateInvoiceStatusOnPayment /
    //     HandleAutoInvoiceOnDoneWorkOrder → 2× payment side effects
    //   - LogSuccessfulLogin / LogFailedLogin → 2 log rows per login
    //
    // Disabling discovery keeps the single explicit registration. Do
    // NOT re-enable it: a new listener added to app/Listeners must be
    // declared in App\Providers\EventServiceProvider::$listen as well,
    // or it will never fire. tests/Feature/DuplicateListenerRegistrationTest.php
    // enforces both halves of that contract.
    ->withEvents(discover: false)
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // P1 — Customer portal session cookie isolation.
        //
        // The portal (/portal/*) and the admin app
        // (/app/*, /system/*) used to share the same
        // Laravel session cookie. The portal's own
        // session payload is namespaced under
        // CustomerPortalSession::key(), but the COOKIE
        // carrying it is the same one the admin app
        // uses. With both audiences active in the
        // same browser, a session->regenerate() in one
        // tab (e.g. on OTP verify) overwrites the
        // cookie the other tab is reading from,
        // bouncing the second tab's user back to
        // /login on their next request.
        //
        // The fix is to give the portal its own cookie
        // name (khidmh_customer_portal_session) for the
        // duration of a portal request. The middleware
        // runs at the START of the web group, BEFORE
        // StartSession, so it can rewrite
        // `session.cookie` in-process for that request
        // and StartSession then builds the session
        // store with the new name. Admin / system
        // requests are unchanged.
        //
        // Companion fix (NOT done here): the
        // verify endpoint also required
        // CustomerPortalOtpService::findCustomer() to
        // include `tenant_id` in the customer
        // projection. Without it, the session payload
        // minted on verify had tenant_id=null, and
        // EnsureCustomerPortalSession rejected the
        // next dashboard request (the cast of null to
        // int yields 0, which never matches the route
        // tenant). The cookie fix alone would not
        // have made the customer-portal dashboard
        // reachable. Both layers were required; this
        // middleware covers the wire-level layer.
        //
        // We register it with `prepend:` (NOT
        // `append:`) so it sits at the very start of
        // the web group. The default web group order
        // is: EncryptCookies → AddQueuedCookiesToResponse
        // → StartSession → ShareErrorsFromSession →
        // VerifyCsrfToken → SubstituteBindings. Our
        // middleware must run before StartSession
        // (which is the only middleware that reads
        // `session.cookie` at request time and builds
        // the Store with it). The `prependToPriorityList`
        // call below is a belt-and-braces guard for
        // the priority-sorted code path.
        $middleware->web(prepend: [
            UsePortalSessionCookie::class,
        ]);

        $middleware->web(append: [
            SetPermissionsTeam::class, // Must be FIRST - sets team before permissions are loaded
            SetLocale::class, // Must be before Inertia to pass correct locale to props
            PortalLocale::class, // Must be before Inertia too — only acts on /portal/* paths; sets the portal locale so the Inertia payload is right
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            ConvertArabicNumerals::class,
            PreventBackHistory::class,
        ]);

        // Observability: tag every request with correlation_id + tenant_id
        // so Sentry events and structured log records share the same context.
        // Registered on the global stack so it covers web + api routes.
        // RequestId runs first so every subsequent middleware (including
        // SentryContext) can read the id from $request->attributes.
        $middleware->append(RequestId::class);
        $middleware->append(SentryContext::class);

        // Exempt locale switching from CSRF — it's a safe, session-only action
        $middleware->validateCsrfTokens(except: [
            'locale',
        ]);

        // Health probes must NEVER appear in Sentry traces — they fire on every
        // load-balancer ping and would drown out real errors.
        $middleware->validateCsrfTokens(except: [
            'healthz',
            'readyz',
        ]);

        $middleware->alias([
            'tenant.active' => EnsureTenantActive::class,
            'center.context' => EnsureCenterContext::class,
            'system.admin' => EnsureSystemAdmin::class,
            'super_admin' => EnsureSuperAdmin::class,
            // Resolves {tenant} slug into a Tenant/TenantWebsite pair
            // for the public site route group. The middleware is
            // strict — it 404s on missing or unpublished sites.
            'public.tenant' => ResolveTenantFromUrl::class,
            // Phase 7 — ensures the customer portal session is
            // alive, matches the route's tenant, and resolves
            // the Customer onto the request. Behind this
            // middleware the controller can read
            // `portal_customer` from request attributes.
            'customer.portal.session' => EnsureCustomerPortalSession::class,
            // Phase 7 — sets Arabic as the portal default
            // and reads the user's persistent language
            // toggle from a dedicated cookie. Mounted
            // before the Inertia middleware so the locale
            // is in place by the time the Inertia props
            // are built.
            'customer.portal.locale' => PortalLocale::class,
            // Spatie Permission middleware
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            // System panel ability check. Declare the ability a route
            // needs: `system.permission:tenants.manage`. An account
            // must hold it (or be the platform owner) to pass. The
            // abilities come from App\Support\SystemPermissions, so a
            // route can only ask for something the registry knows.
            'system.permission' => EnsureSystemPermission::class,
        ]);

        // Ensure TrackAiUsage fails closed BEFORE SubstituteBindings so a
        // request without a tenant_id never triggers a 404 from route
        // model binding. See docs/features/ai-service-suggester/design.md §8.
        $middleware->prependToPriorityList(
            before: SubstituteBindings::class,
            prepend: TrackAiUsage::class,
        );

        // P1 — customer portal session cookie isolation.
        //
        // The portal (/portal/*) and the admin app
        // (/app/*, /system/*) used to share the same
        // Laravel session cookie. The portal's own
        // session payload is namespaced under
        // CustomerPortalSession::key(), but the COOKIE
        // carrying it is the same one the admin app
        // uses. With both audiences active in the
        // same browser, a session->regenerate() in one
        // tab (e.g. on OTP verify) overwrites the
        // cookie the other tab is reading from,
        // bouncing the second tab's user back to
        // /login on their next request.
        //
        // The fix is to give the portal its own cookie
        // name (khidmh_customer_portal_session) for the
        // duration of a portal request. The middleware
        // runs BEFORE StartSession in the priority
        // list, rewrites `session.cookie` in-process
        // for that request, and StartSession then
        // builds the session store with the new name.
        // Admin / system requests are unchanged.
        //
        // Why prependToPriorityList and not the
        // middleware group: StartSession reads the
        // cookie name in getSession() (line 159 of
        // StartSession.php) and builds the Store with
        // the config value at construction time. By
        // the time the priority-sorted list runs, the
        // Store has already been built. So the rewrite
        // MUST happen before StartSession executes.
        // A route-level middleware on /portal/* would
        // also be too late because route resolution
        // for the group happens after the global
        // web middleware stack has already started
        // running.
        $middleware->prependToPriorityList(
            before: StartSession::class,
            prepend: UsePortalSessionCookie::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Render the styled Inertia 403 page for a plain browser
        // navigation.
        //
        // The page existed but nothing rendered it: it was only ever
        // reachable by a direct URL guess, and a contract test kept
        // pinning its behaviour. It was consequently classified as
        // unreachable and removed — while the test kept failing.
        //
        // Wiring it up here makes the page live again:
        //
        //   - a document request that 403s now gets the SPA-styled
        //     page with the role-aware "Home" link, instead of the
        //     plain blade error view;
        //   - Inertia XHR requests and JSON API calls are left alone,
        //     because those expect a JSON error payload and the SPA
        //     has its own error handling.
        $exceptions->respond(function ($response) {
            $request = request();

            if ($response->getStatusCode() !== 403) {
                return $response;
            }

            if ($request->header('X-Inertia') || $request->expectsJson() || $request->ajax()) {
                return $response;
            }

            return Inertia::render('Errors/403')
                ->toResponse($request)
                ->setStatusCode(403);
        });
    })->create();
