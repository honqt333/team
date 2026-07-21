<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Generate or propagate a per-request correlation id and echo it back
 * to the client via the X-Request-Id response header.
 *
 * Why a separate middleware from SentryContext?
 *  - SentryContext does Sentry-specific work (configureScope, setUser,
 *    setTag). RequestId is purely about request-id plumbing: generate
 *    a UUID if the client didn't send one, attach it to the request
 *    attributes, and echo it back.
 *  - This lets us register RequestId independently of Sentry (e.g. in
 *    tests where Sentry SDK is not loaded, or in CLI tools that
 *    only need the id for log correlation).
 *  - The SentryContext middleware still emits its OWN correlation
 *    header (X-Correlation-Id) for backwards compatibility with
 *    existing log infrastructure. X-Request-Id is the new canonical
 *    header; the two are kept in sync.
 *
 * Header name: X-Request-Id
 *  - 8–128 chars
 *  - [A-Za-z0-9._-] only
 *  - Default generation: Str::uuid() (UUID v4)
 */
class RequestId
{
    public const HEADER = 'X-Request-Id';

    public const REQUEST_ATTRIBUTE = 'request_id';

    public function handle(Request $request, Closure $next): Response
    {
        $incoming = $request->headers->get(self::HEADER);
        $id = $this->isValid($incoming) ? $incoming : (string) Str::uuid();

        // Stash on the request for downstream consumers (controllers, jobs).
        $request->attributes->set(self::REQUEST_ATTRIBUTE, $id);

        /** @var Response $response */
        $response = $next($request);

        // Echo the id back so the caller can quote it in support tickets.
        $response->headers->set(self::HEADER, $id);

        return $response;
    }

    private function isValid(?string $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        return strlen($value) <= 128
            && preg_match('/^[A-Za-z0-9._-]+$/', $value) === 1;
    }
}
