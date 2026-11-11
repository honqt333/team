<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class PreventBackHistory
{
    /**
     * Handle an incoming request.
     *
     * Prevents both HTTP/bfcache browser caching and stale Inertia SPA
     * history snapshots after state-changing mutations (POST/PUT/PATCH/DELETE).
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Invalidate prior Inertia history snapshots after any mutating redirect
        // so browser Back never surfaces stale pre-mutation forms or balances.
        if (
            in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)
            && $response->isRedirection()
            && $request->hasSession()
        ) {
            Inertia::clearHistory();
        }

        // Prevent browser HTTP cache & BFCache retention on dynamic pages
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0, private');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', 'Sun, 02 Jan 1990 00:00:00 GMT');

        return $response;
    }
}
