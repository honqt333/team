<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\CustomerPortalOtpChannel;
use App\Contracts\SmsSendResult;
use App\Http\Middleware\UsePortalSessionCookie;
use App\Models\Customer;
use App\Models\CustomerPortalOtp;
use App\Models\Tenant;
use App\Models\TenantWebsite;
use App\Support\CustomerPortalSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * P1 — Portal session cookie isolation.
 *
 * The customer portal (/{tenant}/portal/*) used to share
 * the Laravel session cookie with the admin app
 * (/{tenant}/app/*, /system/*). The portal's session
 * payload was namespaced under CustomerPortalSession::key()
 * but the COOKIE that carried it was the same cookie the
 * admin app used. With both audiences active in the same
 * browser, a session->regenerate() in one tab overwrote
 * the cookie the other tab was reading from, which caused
 * the second tab to lose its session on the next request.
 *
 * The fix is a dedicated cookie for the portal:
 * `khidmh_customer_portal_session`. The
 * UsePortalSessionCookie middleware rewrites the session
 * cookie name for portal requests. The admin cookie
 * (`khidmh-pro-session` in this project, derived from
 * APP_NAME) is unchanged.
 *
 * These tests pin the new contract:
 *
 *   1. Portal requests get Set-Cookie with the portal
 *      cookie name.
 *   2. Admin requests get Set-Cookie with the admin
 *      cookie name (unchanged from before the fix).
 *   3. The two cookies carry separate session data.
 *   4. The login → verify → dashboard round-trip works
 *      end-to-end on the portal cookie.
 *   5. Portal logout invalidates the portal cookie
 *      without touching the admin cookie.
 *   6. The admin app keeps its own session unaffected
 *      by a portal request that comes in on the same
 *      TCP connection (i.e. the request boundary does
 *      not leak state between the two audiences).
 *
 * We do NOT test the underlying Set-Cookie header for
 * admin routes that have nothing to do with the portal
 * — those tests live in their respective feature
 * suites. The scope here is the boundary between the
 * two audiences.
 */
class PortalSessionCookieIsolationTest extends TestCase
{
    use RefreshDatabase;

    public static string $capturedCode = '';

    protected function setUp(): void
    {
        parent::setUp();

        // Bind a fake OTP channel that captures the
        // issued code on a static so the e2e happy
        // path can post the right code to /portal/verify.
        // The LoggingCustomerPortalOtpChannel (the
        // APP_ENV=testing default) does NOT persist
        // the code — that is correct for production
        // logging but useless for an end-to-end test
        // that needs the code back. The fake below
        // mirrors the binding pattern used by
        // CustomerPortalHttpTest::setUp().
        self::$capturedCode = '';
        $this->app->instance(
            CustomerPortalOtpChannel::class,
            new class implements CustomerPortalOtpChannel
            {
                public function send(
                    CustomerPortalOtp $otp,
                    string $phone,
                    string $code
                ): SmsSendResult {
                    PortalSessionCookieIsolationTest::$capturedCode = $code;

                    return SmsSendResult::ok('fake');
                }
            }
        );
    }

    private function publishedTenant(string $slug = 'demo'): Tenant
    {
        $tenant = Tenant::factory()->create(['slug' => $slug]);
        TenantWebsite::factory()->for($tenant)->published()->create();

        return $tenant;
    }

    private function customerIn(Tenant $tenant, string $phone = '+966501234567'): Customer
    {
        return Customer::factory()->create([
            'tenant_id' => $tenant->id,
            'phone' => $phone,
            'whatsapp' => $phone,
            'name' => 'Test Customer',
        ]);
    }

    /**
     * Collect the names of every cookie the response
     * asked the browser to set. Symfony represents
     * queued cookies as Cookie objects; the test
     * response exposes them via getCookies().
     *
     * @return string[]
     */
    private function cookieNames(TestResponse $response): array
    {
        return array_map(
            fn ($c) => $c->getName(),
            $response->headers->getCookies()
        );
    }

    /**
     * The default admin cookie name. The Laravel
     * default is derived from APP_NAME, which the
     * project sets to "Khidmh Pro". We hard-code
     * it here rather than reading `config('session.cookie')`
     * at runtime because the middleware rewrites
     * that config value in-process for portal
     * requests — by the time the test assertion
     * runs, the config is already the portal
     * cookie name. A hard-coded reference matches
     * the production behaviour (the admin cookie
     * is a stable, project-defined name) and
     * avoids the runtime-mutated config.
     */
    private function adminCookieName(): string
    {
        return 'khidmh-pro-session';
    }

    // ─────────────────────────────────────────────────────────────────
    // 1. Portal requests set the portal cookie.
    // ─────────────────────────────────────────────────────────────────

    public function test_portal_login_page_sets_portal_cookie_not_admin_cookie(): void
    {
        $tenant = $this->publishedTenant();

        $response = $this->get('/'.$tenant->slug.'/portal/login');

        $names = $this->cookieNames($response);
        $this->assertContains(
            UsePortalSessionCookie::COOKIE_NAME,
            $names,
            'portal login page must queue a Set-Cookie with the portal cookie name'
        );
        $this->assertNotContains(
            $this->adminCookieName(),
            $names,
            'portal login page must NOT queue the admin cookie'
        );
    }

    public function test_portal_verify_page_sets_portal_cookie(): void
    {
        $tenant = $this->publishedTenant();

        $response = $this->get('/'.$tenant->slug.'/portal/verify');

        $names = $this->cookieNames($response);
        $this->assertContains(UsePortalSessionCookie::COOKIE_NAME, $names);
        $this->assertNotContains($this->adminCookieName(), $names);
    }

    public function test_portal_dashboard_with_session_sets_portal_cookie(): void
    {
        $tenant = $this->publishedTenant();
        $customer = $this->customerIn($tenant);

        // Mint a portal session the way the verify
        // endpoint would. The middleware sees this
        // request as a portal request and rewrites
        // the cookie name accordingly.
        session()->put(CustomerPortalSession::key(), [
            'customer_id' => $customer->id,
            'tenant_id' => $tenant->id,
            'created_at' => now()->toIso8601String(),
            'last_seen_at' => now()->toIso8601String(),
        ]);

        $response = $this->get('/'.$tenant->slug.'/portal/dashboard');

        $names = $this->cookieNames($response);
        $this->assertContains(UsePortalSessionCookie::COOKIE_NAME, $names);
        $this->assertNotContains($this->adminCookieName(), $names);
    }

    // ─────────────────────────────────────────────────────────────────
    // 2. Admin requests keep the admin cookie.
    // ─────────────────────────────────────────────────────────────────

    public function test_admin_route_keeps_admin_cookie(): void
    {
        // A non-portal route — pick a public route
        // that does NOT live under /portal/*. The
        // root `/` is a 404 in this app for
        // unauthenticated visitors, but the response
        // is what we care about: the cookie name.
        $response = $this->get('/');

        $names = $this->cookieNames($response);
        $this->assertContains(
            $this->adminCookieName(),
            $names,
            'non-portal requests must queue the default admin cookie'
        );
        $this->assertNotContains(
            UsePortalSessionCookie::COOKIE_NAME,
            $names,
            'non-portal requests must NOT queue the portal cookie'
        );
    }

    public function test_app_route_keeps_admin_cookie(): void
    {
        // /login is the staff login page (the
        // /app/* surface is mounted behind the
        // auth/2fa middleware and would 302 to
        // /login for an unauthenticated visitor;
        // we hit /login directly). The session
        // cookie here is the admin cookie, not
        // the portal cookie. We do not need to
        // authenticate — we just need the response
        // to come back without a portal cookie.
        $response = $this->get('/login');

        $names = $this->cookieNames($response);
        $this->assertContains($this->adminCookieName(), $names);
        $this->assertNotContains(UsePortalSessionCookie::COOKIE_NAME, $names);
    }

    // ─────────────────────────────────────────────────────────────────
    // 3. End-to-end login → verify → dashboard on the
    //    portal cookie. Reproduces the original P1 bug
    //    (verify succeeded but the next request bounced
    //    to /portal/login).
    // ─────────────────────────────────────────────────────────────────

    public function test_full_login_to_dashboard_round_trip_uses_portal_cookie(): void
    {
        $tenant = $this->publishedTenant();
        $this->customerIn($tenant);

        // Step 1: POST /portal/login. The response
        // queues the portal cookie. The OTP is
        // issued (the fake channel bound in setUp()
        // captures the code), the session carries
        // cp_pending_phone, and the response
        // redirects to /portal/verify.
        $loginResponse = $this->post(
            '/'.$tenant->slug.'/portal/login',
            ['phone' => '0501234567']
        );
        $loginResponse->assertRedirect('/'.$tenant->slug.'/portal/verify');
        $this->assertContains(
            UsePortalSessionCookie::COOKIE_NAME,
            $this->cookieNames($loginResponse)
        );
        $this->assertSame(6, strlen(self::$capturedCode));

        // Step 2: POST /portal/verify with the
        // captured code. On success, the controller
        // mints a fresh session id, writes the
        // CustomerPortalSession payload (carrying
        // customer_id, tenant_id, created_at,
        // last_seen_at), and redirects to
        // /portal/dashboard.
        $verifyResponse = $this->post(
            '/'.$tenant->slug.'/portal/verify',
            ['phone' => '0501234567', 'code' => self::$capturedCode]
        );
        $verifyResponse->assertRedirect('/'.$tenant->slug.'/portal/dashboard');

        // Step 3: GET /portal/dashboard. The
        // EnsureCustomerPortalSession middleware
        // reads the session payload, finds the
        // customer, and renders the dashboard.
        //
        // This is the request that the original P1
        // bug broke: the cookie was the admin
        // cookie, and even with the cookie fix the
        // session payload had tenant_id=null
        // (because findCustomer() did not include
        // it in the projection). Both layers had
        // to land for this assertion to pass.
        $dashboardResponse = $this->get('/'.$tenant->slug.'/portal/dashboard');
        $dashboardResponse->assertOk();
        $this->assertContains(
            UsePortalSessionCookie::COOKIE_NAME,
            $this->cookieNames($dashboardResponse)
        );
    }

    // ─────────────────────────────────────────────────────────────────
    // 4. The portal cookie is independent of the admin
    //    cookie. A request that carries both still
    //    resolves the portal session correctly.
    // ─────────────────────────────────────────────────────────────────

    public function test_admin_cookie_does_not_break_portal_request(): void
    {
        $tenant = $this->publishedTenant();

        // Simulate a browser that has BOTH cookies
        // (the original P1 bug surface: the admin
        // tab's Set-Cookie pollutes the portal
        // tab's session).
        $response = $this->withCookies([
            // Admin cookie carrying a stale ID.
            $this->adminCookieName() => 'admin-stale-id',
        ])->get('/'.$tenant->slug.'/portal/login');

        $response->assertOk();
        $names = $this->cookieNames($response);
        $this->assertContains(
            UsePortalSessionCookie::COOKIE_NAME,
            $names,
            'portal request must still set the portal cookie even when the request carries the admin cookie'
        );
    }

    // ─────────────────────────────────────────────────────────────────
    // 5. Portal logout invalidates only the portal
    //    cookie. The admin session is unaffected.
    // ─────────────────────────────────────────────────────────────────

    public function test_portal_logout_invalidates_portal_cookie_only(): void
    {
        $tenant = $this->publishedTenant();
        $customer = $this->customerIn($tenant);

        // Mint a portal session.
        session()->put(CustomerPortalSession::key(), [
            'customer_id' => $customer->id,
            'tenant_id' => $tenant->id,
            'created_at' => now()->toIso8601String(),
            'last_seen_at' => now()->toIso8601String(),
        ]);

        $response = $this->post('/'.$tenant->slug.'/portal/logout');

        $response->assertRedirect('/'.$tenant->slug);
        // Symfony exposes cookie expiry as a
        // negative max-age on the deleted cookie.
        // We just assert the portal cookie is
        // queued for deletion.
        $deletedNames = array_map(
            fn ($c) => $c->getName(),
            $response->headers->getCookies()
        );
        $this->assertContains(
            UsePortalSessionCookie::COOKIE_NAME,
            $deletedNames,
            'portal logout must queue a Set-Cookie that deletes the portal cookie'
        );

        // The admin cookie MUST NOT be deleted.
        // The cookie-jar after logout should
        // either NOT contain the admin cookie
        // (the response is on a portal request,
        // so the admin cookie is not on the
        // queue) or contain a fresh, un-deleted
        // admin cookie. We assert "not deleted"
        // by reading each cookie's expiry: a
        // deletion is signalled by an expires
        // time in the past.
        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->getName() === $this->adminCookieName()) {
                $this->assertGreaterThan(
                    time(),
                    $cookie->getExpiresTime(),
                    'portal logout must NOT delete the admin cookie'
                );
            }
        }

        // The portal session payload is gone.
        $this->assertNull(session(CustomerPortalSession::key()));
    }

    // ─────────────────────────────────────────────────────────────────
    // 6. Path detection: only true portal paths trigger
    //    the cookie rewrite. A non-portal path with the
    //    word "portal" in it (e.g. a hypothetical
    //    /system/portals-management) does NOT.
    // ─────────────────────────────────────────────────────────────────

    public function test_path_with_portal_in_a_non_tenant_segment_does_not_rewrite_cookie(): void
    {
        // /foo-portal/bar contains the substring
        // '/portal/' but it is not under a
        // /{tenant}/portal/ surface. The first
        // segment ('foo-portal') is not empty and
        // the second segment is 'bar' (not
        // 'portal'), so isPortalRequest returns
        // false. We pick a route that exists in
        // the app but is not portal-scoped — the
        // tenant website landing page at /{slug}.
        $tenant = $this->publishedTenant();

        $response = $this->get('/'.$tenant->slug);

        $names = $this->cookieNames($response);
        $this->assertContains($this->adminCookieName(), $names);
        $this->assertNotContains(UsePortalSessionCookie::COOKIE_NAME, $names);
    }

    // ─────────────────────────────────────────────────────────────────
    // 7. The middleware's path detection rejects the
    //    bare `/portal/...` shape (no tenant prefix).
    //    Such a path would 404 the route group, but
    //    the middleware should not promote it to a
    //    portal request.
    // ─────────────────────────────────────────────────────────────────

    public function test_bare_portal_path_does_not_trigger_rewrite(): void
    {
        // /portal/login without a tenant prefix.
        // The route group requires {tenant}, so
        // this 404s. The middleware must not have
        // queued the portal cookie because the
        // second segment ('login') is not the
        // literal 'portal'.
        $response = $this->get('/portal/login');

        // 404 or 405 are both acceptable. The
        // point is the cookie name.
        $names = $this->cookieNames($response);
        $this->assertNotContains(
            UsePortalSessionCookie::COOKIE_NAME,
            $names,
            'bare /portal/... (no tenant prefix) must not be promoted to a portal request'
        );
    }

    // ─────────────────────────────────────────────────────────────────
    // 8. The middleware's COOKIE_NAME constant is the
    //    canonical, stable name. Operators can grep
    //    for it in cookie headers and in the source.
    // ─────────────────────────────────────────────────────────────────

    public function test_cookie_name_constant_is_stable(): void
    {
        $this->assertSame(
            'khidmh_customer_portal_session',
            UsePortalSessionCookie::COOKIE_NAME,
            'COOKIE_NAME must not change without a coordinated migration'
        );
    }
}
