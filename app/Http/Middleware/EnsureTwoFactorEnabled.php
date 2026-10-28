<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (! $user) {
            return $next($request);
        }

        $tenant = $user->tenant;

        if (! $tenant) {
            return $next($request);
        }

        // Check if 2FA is required by tenant and not enabled by user
        if ($tenant->two_factor_enabled &&
            $tenant->two_factor_enforcement === 'required' &&
            is_null($user->two_factor_confirmed_at)) {

            // Allow access to the 2FA routes themselves, otherwise the
            // user cannot finish the setup they are being sent to.
            //
            // Two name families are involved:
            //   app.security.2fa.*  setup / enable / disable / regenerate
            //   app.2fa.*           send-code / challenge / verify
            //
            // The send-code route belongs to the second family, and
            // without it the redirected user would land on the setup
            // screen but be unable to request the code that lets them
            // confirm 2FA.
            if ($request->routeIs('app.security.2fa.*', 'app.2fa.*')) {
                return $next($request);
            }

            return redirect()->route('app.security.2fa.setup')
                ->with('warning', __('auth.2fa.required'));
        }

        return $next($request);
    }
}
