<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Sentry\SentrySdk;
use Sentry\State\Scope;
use Symfony\Component\HttpFoundation\Response;

/**
 * Apply per-request observability context:
 *  - Generates / propagates a stable correlation_id (X-Correlation-Id header)
 *  - Tags the Sentry scope with tenant_id, user_id, route, correlation_id
 *  - Echoes correlation_id back to the client in the response headers
 *  - Pushed into the Laravel log context via Log::shareContext() so structured
 *    JSON log records automatically inherit the same correlation_id without
 *    callers having to pass it explicitly.
 *
 * Behaviour:
 *  - Production / non-local env: wired to Sentry
 *  - Local env / no Sentry SDK: still applies Log context + response header,
 *    and skips Sentry::configureScope() if the SDK isn't available
 *  - Generation is one-shot per request (cached on the Request itself so
 *    sub-requests and queue dispatches from the same parent share the id)
 */
class SentryContext
{
    public const CORRELATION_HEADER = 'X-Correlation-Id';

    public function handle(Request $request, Closure $next): Response
    {
        // Use incoming header if present and valid (UUID-ish or alphanumeric);
        // otherwise mint a fresh UUID v4 (Str::uuid()).
        $incoming = $request->headers->get(self::CORRELATION_HEADER);
        $correlationId = $this->isValidCorrelationId($incoming)
            ? $incoming
            : (string) Str::uuid();

        // Stash on the request for any downstream consumer (controllers, jobs).
        $request->attributes->set('correlation_id', $correlationId);

        // Share into Laravel's log context — picked up by App\Logging\JsonFormatter
        // automatically, no need for callers to pass it on every Log:: call.
        Log::shareContext([
            'correlation_id' => $correlationId,
            'route' => $request->path(),
            'method' => $request->method(),
        ]);

        $this->applyTenantAndUserContext($request, $correlationId);

        /** @var Response $response */
        $response = $next($request);

        // Echo the id back so the caller can quote it in support tickets
        // and trace it across the entire stack (LB → app → jobs).
        $response->headers->set(self::CORRELATION_HEADER, $correlationId);

        return $response;
    }

    /**
     * Tag Sentry with tenant_id / user_id / correlation_id so every event /
     * breadcrumb picked up during this request is annotated.
     *
     * No-op if the Sentry SDK isn't installed (local dev / test env), so
     * this middleware is safe to register unconditionally.
     */
    private function applyTenantAndUserContext(Request $request, string $correlationId): void
    {
        if (! class_exists(SentrySdk::class)) {
            return;
        }

        $user = $request->user();
        $tenantId = null;

        if ($user !== null) {
            // User model has a direct tenant relation; fall back to the
            // raw tenant_id attribute so we work even on a partial user.
            $tenantId = $user->tenant_id ?? optional($user->tenant)->id;
        }

        \Sentry\configureScope(function (Scope $scope) use ($request, $user, $tenantId, $correlationId): void {
            $scope->setTag('correlation_id', $correlationId);
            $scope->setTag('route', $request->method().' '.$request->path());
            if ($tenantId !== null) {
                $scope->setTag('tenant_id', (string) $tenantId);
                $scope->setUser([
                    'id' => (string) ($user?->getAuthIdentifier() ?? ''),
                    'tenant_id' => (string) $tenantId,
                ]);
            } elseif ($user !== null) {
                $scope->setUser([
                    'id' => (string) $user->getAuthIdentifier(),
                ]);
            }
        });
    }

    private function isValidCorrelationId(?string $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        // 8–128 chars; allow hex/UUID/dashed/underscore — typical CLI / LB IDs.
        return strlen($value) <= 128
            && preg_match('/^[A-Za-z0-9._-]+$/', $value) === 1;
    }
}
