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
        return Inertia::render('Public/Portal/Login', [
            'tenant' => $this->tenantContext($request),
            'locale' => $this->localeContext($request),
            'phone' => old('phone', ''),
        ]);
    }

    public function requestOtp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
        ]);

        $tenant = $this->resolveTenant($request);
        $phone = PhoneNumber::normalize($validated['phone']);
        $generic = back()->with('status', __('customer_portal.auth.code_sent'));

        try {
            $this->otp->requestOtp(
                tenant: $tenant,
                phoneInput: $validated['phone'],
                ip: $request->ip(),
                userAgent: $request->userAgent(),
            );
        } catch (OtpRateLimitedException $e) {
            return back()
                ->withErrors(['phone' => $e->getMessage()])
                ->withInput();
        }

        // Always return the SAME generic message, regardless
        // of whether the phone was known. We do this by
        // returning before any branch, with the phone in the
        // session so the verify page can pre-fill it.
        $request->session()->put('cp_pending_phone', $phone ?? $validated['phone']);

        return $generic;
    }

    public function showVerify(Request $request): Response
    {
        return Inertia::render('Public/Portal/Verify', [
            'tenant' => $this->tenantContext($request),
            'locale' => $this->localeContext($request),
            'phone' => $request->session()->get('cp_pending_phone', ''),
        ]);
    }

    public function verifyOtp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
            'code' => ['required', 'string', 'size:6', 'regex:/^\d{6}$/'],
        ]);

        $tenant = $this->resolveTenant($request);
        $result = $this->otp->verify(
            tenant: $tenant,
            phoneInput: $validated['phone'],
            code: $validated['code'],
        );

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

        return redirect()->to('/'.$tenant->slug.'/portal/dashboard');
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
        $returnTo = $request->input('return_to');

        $url = '/'.ltrim($returnTo ?: ($tenantSlug.'/portal/login'), '/');
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
            'primary_color' => $website?->effective_primary_color ?? '#4f46e5',
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
