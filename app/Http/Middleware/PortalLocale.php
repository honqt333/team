<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Portal locale policy.
 *
 * The customer portal is a Saudi-arabic-first surface.
 * Even when the application default locale is `en`, the
 * portal mounts with Arabic as the baseline and lets the
 * user opt into English via a toggle in the login UI.
 *
 * The middleware is intentionally a thin layer over
 * SetLocale: it computes the desired locale for THIS
 * route group, then sets it on the application. It does
 * NOT touch the rest of the application — staff / system
 * routes are unaffected.
 *
 * Priority for the portal:
 *   1. Explicit `?lang=en` query (set by the language
 *      toggle in the UI; lets the user override once for
 *      the current page).
 *   2. The persistent `portal_lang` cookie (set by the
 *      toggle handler when the user actively switches).
 *   3. The session locale (if the user logged in earlier
 *      with a different locale).
 *   4. Arabic (the portal default).
 *
 * The cookie lives for one year; the user is not asked
 * every visit. The query string is the explicit
 * "change-now" affordance.
 */
class PortalLocale
{
    public const DEFAULT_LOCALE = 'ar';

    public const SUPPORTED_LOCALES = ['ar', 'en'];

    public const COOKIE_KEY = 'portal_lang';

    public const COOKIE_LIFETIME_MINUTES = 525600; // 1 year

    public function handle(Request $request, Closure $next): Response
    {
        // Only run on portal URLs. Mounting the middleware
        // globally lets us sit in the pipeline BEFORE
        // HandleInertiaRequests so the Inertia payload
        // already carries the right locale when the Vue
        // side boots.
        if (! $this->isPortalRequest($request)) {
            return $next($request);
        }

        $locale = $this->resolveLocale($request);

        if (! in_array($locale, self::SUPPORTED_LOCALES, true)) {
            $locale = self::DEFAULT_LOCALE;
        }

        App::setLocale($locale);

        // Stash on the request so the controller can read
        // it for the Vue Inertia payload.
        $request->attributes->set('portal_locale', $locale);

        return $next($request);
    }

    /**
     * Cheap path check. We don't need to read the route
     * — a substring match on the URL is enough to keep
     * the middleware no-op everywhere outside the portal.
     */
    private function isPortalRequest(Request $request): bool
    {
        return str_contains($request->path(), '/portal/')
            || str_ends_with($request->path(), '/portal');
    }

    private function resolveLocale(Request $request): string
    {
        // 1. Query string — explicit one-shot override.
        $fromQuery = $request->query('lang');

        if (is_string($fromQuery) && in_array($fromQuery, self::SUPPORTED_LOCALES, true)) {
            return $fromQuery;
        }

        // 2. Cookie — persistent user choice.
        $fromCookie = $request->cookie(self::COOKIE_KEY);

        if (is_string($fromCookie) && in_array($fromCookie, self::SUPPORTED_LOCALES, true)) {
            return $fromCookie;
        }

        // 3. Session — in case the customer has a session
        // already with a locale set.
        $fromSession = $request->session()->get('locale');

        if (is_string($fromSession) && in_array($fromSession, self::SUPPORTED_LOCALES, true)) {
            return $fromSession;
        }

        // 4. Arabic default.
        return self::DEFAULT_LOCALE;
    }
}
