<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Middleware\PortalLocale;
use App\Models\Tenant;
use App\Models\TenantWebsite;
use App\Services\CustomerPortal\CustomerPortalOtpService;
use App\Services\CustomerPortal\OtpRateLimitedException;
use App\Support\CustomerPortalSession;
use App\Support\PhoneNumber;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public-side authentication for the customer portal.
 *
 * Mounted at /{tenant}/portal/* (route group below the
 * ResolveTenantFromUrl middleware). Endpoints:
 *
 *   GET  /{tenant}/portal/login         — phone form
 *   POST /{tenant}/portal/login         — request OTP
 *   GET  /{tenant}/portal/verify        — code form
 *   POST /{tenant}/portal/verify        — verify code
 *   POST /{tenant}/portal/logout       — sign out
 *
 * Critical: ALL public-facing messages are generic. We
 * never tell the user whether their phone is in the
 * system, whether the code was wrong, whether the code
 * expired, or whether they have used up their attempts.
 * The same "we sent you a code if your number is
 * registered" message handles every failure mode.
 */
class CustomerPortalAuthController extends Controller
{
    public function __construct(
        private readonly CustomerPortalOtpService $otp,
    ) {}

    public function showLogin(Request $request): Response
    {
        // We accept a `return_to` query param so deep
        // links (e.g. "Book an appointment" CTA from
        // the public site) can drop the customer on the
        // login page and then resume the original
        // destination after a successful OTP verify.
        // The value is stored in the session because
        // the POST /login → GET /verify → POST /verify
        // round-trip does not preserve a custom query
        // string cleanly, and the redirect after verify
        // is server-driven.
        $returnTo = $this->sanitizeReturnTo(
            $request->query('return_to'),
            $this->resolveTenantSlug($request)
        );

        if ($returnTo !== null) {
            $request->session()->put('cp_return_to', $returnTo);
        }

        return Inertia::render('Public/Portal/Login', [
            'tenant' => $this->tenantContext($request),
            'locale' => $this->localeContext($request),
            'phone' => old('phone', ''),
            'return_to' => $returnTo,
            // The flash status (e.g. the "we sent you a
            // code" banner from the verify page on a
            // back-navigation, or a future rate-limit
            // / cooldowned message) is read out of the
            // session and exposed as a top-level prop.
            // The Vue page reads it as `status`, not
            // `page.props.flash.status`, because Inertia
            // does not auto-merge Laravel's session
            // flash into props. The prop is only set
            // when the session has a status; absent
            // that, the page renders without the
            // banner.
            'status' => $request->session()->get('status'),
        ]);
    }

    public function requestOtp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
        ]);

        $tenant = $this->resolveTenant($request);
        $phone = PhoneNumber::normalize($validated['phone']);

        try {
            $this->otp->requestOtp(
                tenant: $tenant,
                phoneInput: $validated['phone'],
                ip: $request->ip(),
                userAgent: $request->userAgent(),
            );
        } catch (OtpRateLimitedException $e) {
            // Rate limit. Keep the user on the login
            // page with the error so they can read the
            // cooldown. We do NOT redirect to /verify
            // here — the customer is being told to wait,
            // not told "we sent you a code".
            return back()
                ->withErrors(['phone' => $e->getMessage()])
                ->withInput();
        }

        // Always return the SAME generic message,
        // regardless of whether the phone was known —
        // this is the anti-enumeration guarantee. The
        // phone is stored in the session so the verify
        // page can pre-fill it without the customer
        // having to re-type.
        //
        // The redirect target is the verify page, NOT
        // the login page. Before this fix the controller
        // used `back()->with('status', ...)` which
        // bounced the customer back to /portal/login —
        // the OTP landed in their SMS but the verify
        // form was never shown. The redirect must be
        // explicit and tenant-scoped (the route name
        // lives under the {tenant} prefix, so we pass
        // the slug).
        $request->session()->put('cp_pending_phone', $phone ?? $validated['phone']);

        return redirect()
            ->route('public.portal.verify', ['tenant' => $tenant->slug])
            ->with('status', __('customer_portal.auth.code_sent'));
    }

    public function showVerify(Request $request): Response
    {
        return Inertia::render('Public/Portal/Verify', [
            'tenant' => $this->tenantContext($request),
            'locale' => $this->localeContext($request),
            'phone' => $request->session()->get('cp_pending_phone', ''),
            // The "we sent you a code if your number is
            // registered" flash from the login POST is
            // read out of the session and exposed as a
            // top-level prop. Inertia does not
            // auto-merge Laravel's session flash into
            // props, so the Vue page reads `status`,
            // not `page.props.flash.status`.
            'status' => $request->session()->get('status'),
        ]);
    }

    public function verifyOtp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
            'code' => ['required', 'string', 'size:6', 'regex:/^\d{6}$/'],
        ]);

        $tenant = $this->resolveTenant($request);

        try {
            $result = $this->otp->verify(
                tenant: $tenant,
                phoneInput: $validated['phone'],
                code: $validated['code'],
            );
        } catch (OtpRateLimitedException $e) {
            // Rate limit on the VERIFY side.
            //
            // The request side (requestOtp) already handles this
            // exception, but the verify side did not — so a customer
            // who exhausted the attempt limit got a 500 error page
            // instead of being told to wait. The protection worked;
            // only the user-facing handling was missing.
            //
            // The message is the same generic one the request side
            // uses, and the retry delay is passed along so the screen
            // can tell the customer how long to wait.
            return back()
                ->withErrors(['code' => $e->getMessage()])
                ->withInput()
                ->with('otp_retry_after', $e->retryAfterSeconds);
        }

        if (! $result->success) {
            // Same generic error for every failure mode.
            return back()
                ->withErrors(['code' => __('customer_portal.auth.code_invalid')])
                ->withInput();
        }

        // SECURITY: regenerate the session ID on successful
        // authentication. Without this, a session ID that
        // an attacker captured BEFORE the user signed in
        // (e.g. via a stolen cookie from a public kiosk)
        // would still be valid after the user logs in, and
        // the attacker would inherit the authenticated
        // session. regenerate() mints a new ID and copies
        // the (small) payload forward, leaving the old
        // session ID orphaned in the storage.
        $request->session()->regenerate();

        // Mint the portal session. The middleware resolves
        // and validates on every request. `created_at`
        // anchors the 12-hour hard ceiling in the
        // middleware; the field is set ONCE here, never
        // updated on subsequent requests.
        $nowIso = Carbon::now()->toIso8601String();
        $request->session()->put(CustomerPortalSession::key(), [
            'customer_id' => $result->customer->id,
            'tenant_id' => $result->customer->tenant_id,
            'created_at' => $nowIso,
            'last_seen_at' => $nowIso,
        ]);

        // Clear the "pending phone" — the user is now
        // signed in and the verify page should not auto-
        // reflow.
        $request->session()->forget('cp_pending_phone');

        // Bind the customer to the request for this turn
        // so the redirect's controller can render
        // immediately.
        $request->attributes->set('portal_customer', $result->customer);

        // Resume the original destination if the user
        // came from a deep link (e.g. "Book an
        // appointment" CTA). The session-stored value was
        // validated at the login step; we re-sanitize here
        // because session data is user-controlled in
        // pathological cases.
        $returnTo = $this->sanitizeReturnTo(
            $request->session()->pull('cp_return_to'),
            $tenant->slug
        );

        return redirect()->to(
            $returnTo !== null
                ? '/'.$tenant->slug.$returnTo
                : '/'.$tenant->slug.'/portal/dashboard'
        );
    }

    public function logout(Request $request): RedirectResponse
    {
        $tenant = $request->attributes->get('public_tenant');
        $slug = $tenant instanceof Tenant ? $tenant->slug : '';

        // SECURITY: invalidate the entire session, not just
        // forget the portal key. A "forget" only drops the
        // single key — other data (flash messages, etc.)
        // would still be addressable by the same session ID.
        // invalidate() drops the row in the session store,
        // flushes the cookie, and mints a new ID. Without
        // this, a captured session ID could keep its
        // post-logout flash data accessible, and a future
        // session ID rotation wouldn't kick in.
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->to($slug ? '/'.$slug : '/');
    }

    /**
     * Persist a user-selected locale for the portal.
     *
     * The PortalLocale middleware reads three sources in
     * order — query, cookie, session. The query string
     * is the one-shot "switch now" affordance; the
     * cookie is what persists the choice across visits.
     *
     * The cookie name matches what the middleware reads
     * — the middleware reads only the base
     * PortalLocale::COOKIE_KEY. Per-tenant scoping
     * (cookie name including the slug) would be the
     * right call when the same user has accounts in
     * multiple tenants with different language
     * preferences; for now the portal is single-tenant
     * per session and the simple key is enough.
     */
    public function setLocale(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'lang' => ['required', 'string', 'in:ar,en'],
        ]);

        $cookie = cookie(
            name: PortalLocale::COOKIE_KEY,
            value: $validated['lang'],
            minutes: PortalLocale::COOKIE_LIFETIME_MINUTES,
            path: '/',
            domain: null,
            secure: $request->isSecure(),
            httpOnly: false, // readable by JS so the toggle can show the active state
            sameSite: 'lax',
        );

        // We also set ?lang=… in the redirect so the very
        // next request resolves to the new locale even
        // before the cookie round-trips.
        $tenant = $request->attributes->get('public_tenant');
        $tenantSlug = $tenant instanceof Tenant ? $tenant->slug : '';

        // Honor a return_to deep link if it survives
        // sanitization. Without this, a customer who
        // clicks the language toggle while on a deep
        // link (e.g. from the booking CTA) would be
        // bumped back to the login screen.
        $returnTo = $this->sanitizeReturnTo(
            $request->input('return_to'),
            $tenantSlug
        );

        $url = '/'.ltrim($returnTo !== null ? $returnTo : ($tenantSlug.'/portal/login'), '/');
        $url .= (str_contains($url, '?') ? '&' : '?').'lang='.$validated['lang'];

        return redirect()->to($url)->withCookie($cookie);
    }

    private function resolveTenant(Request $request): Tenant
    {
        $tenant = $request->attributes->get('public_tenant');

        if (! $tenant instanceof Tenant) {
            abort(404);
        }

        return $tenant;
    }

    /**
     * Strip + validate a `return_to` value. Allowed
     * shapes are paths RELATIVE to the tenant's portal:
     *
     *   /portal/dashboard
     *   /portal/appointments/new
     *   /portal/appointments/123
     *
     * Reject anything that:
     *   - is not a string,
     *   - does not start with `/portal/`,
     *   - contains `..` or a scheme (open-redirect
     *     protection),
     *   - contains a newline (header-injection guard).
     *
     * The function returns the canonical path (with a
     * leading `/`) or null if the input is unsafe.
     */
    private function sanitizeReturnTo(mixed $value, ?string $tenantSlug): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        if (strlen($value) > 255) {
            return null;
        }

        if (preg_match('/[\r\n\0]/', $value)) {
            return null;
        }

        // Must be an absolute path under /portal/.
        if (! str_starts_with($value, '/portal/')) {
            return null;
        }

        // Reject any attempt to climb out of /portal/.
        if (str_contains($value, '..')) {
            return null;
        }

        // Reject schemes (defense-in-depth; the leading
        // slash already rules out most cases).
        if (preg_match('#^[a-z][a-z0-9+.\-]*://#i', $value) === 1) {
            return null;
        }

        return $value;
    }

    private function resolveTenantSlug(Request $request): ?string
    {
        $tenant = $request->attributes->get('public_tenant');

        return $tenant instanceof Tenant ? $tenant->slug : null;
    }

    private function tenantContext(Request $request): array
    {
        $tenant = $this->resolveTenant($request);

        // The Tenant model does not expose a website()
        // relation (it is not a 1:1 in code, only by
        // convention). We look up the row directly. The
        // middleware guarantees the website exists
        // (otherwise the resolve step would 404), so
        // $website is non-null here.
        $website = TenantWebsite::where('tenant_id', $tenant->id)->first();

        // Logo + cover. The portal prefers the tenant's
        // own logo (a real brand mark gives the customer
        // trust that they are in their own center's
        // portal). Falls back to the small monogram tile
        // when no logo has been uploaded.
        $logoUrl = $website?->logo_url;
        $coverUrl = $website?->cover_url;

        return [
            'id' => $tenant->id,
            'slug' => $tenant->slug,
            'site_name' => $website?->display_name ?? $tenant->name,
            'tagline' => $website?->tagline,
            'logo_url' => $logoUrl,
            'cover_url' => $coverUrl,
            'has_logo' => ! empty($logoUrl),
            'primary_color' => $website?->effective_primary_color ?? '#2563eb',
            'secondary_color' => $website?->effective_secondary_color ?? '#0ea5e9',
        ];
    }

    private function localeContext(Request $request): array
    {
        $current = $request->attributes->get('portal_locale') ?? PortalLocale::DEFAULT_LOCALE;

        return [
            'current' => $current,
            'is_rtl' => $current === 'ar',
        ];
    }
}
