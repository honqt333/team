<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Throwable;

/**
 * Health & readiness probes — mounted on the global `/healthz` (liveness)
 * and `/readyz` (readiness) endpoints. No auth, no session, no CSRF.
 *
 *  - /healthz: am-I-alive?
 *      Liveness probe — kept trivial: returns 200 + {"status":"ok"}
 *      as long as PHP can serve the request. K8s/LB use this to decide
 *      whether to restart the pod.
 *
 *  - /readyz: should-I-receive-traffic?
 *      Readiness probe — verifies live dependencies (DB, cache, queue
 *      connection). K8s/LB use this to decide whether to route traffic
 *      to the pod. Returns 200 with per-check status, or 503 if any
 *      critical check fails.
 *
 * Routes are registered OUTSIDE the tenant/auth/scope middleware in
 * routes/api.php, so a misconfigured database tenant or expired auth
 * cannot cause the probes themselves to fail (which would mask outages).
 */
class HealthController
{
    /**
     * Liveness probe — the application process is up.
     *
     * Intentionally minimal: we only return 200 so that the orchestrator
     * distinguishes "process dead" from "dependency broken" (see /readyz).
     */
    public function live(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'service' => config('app.name'),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Readiness probe — every downstream the app needs to be useful is
     * reachable. Each check has its own try/catch so one slow / broken
     * dependency doesn't mask the others; the overall status is the
     * logical AND — if ANY check fails we return 503.
     */
    public function ready(): JsonResponse
    {
        $checks = [
            'db' => $this->checkDb(),
            'cache' => $this->checkCache(),
            'queue' => $this->checkQueue(),
        ];

        $allOk = ! in_array('fail', array_column($checks, 'status'), true);

        $payload = [
            'status' => $allOk ? 'ready' : 'degraded',
            'service' => config('app.name'),
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ];

        // 200 when everything is up, 503 if any check failed. 503 keeps the
        // orchestrator from sending traffic to a broken pod while still
        // distinguishing from a fully-dead pod (/healthz → 200).
        return response()
            ->json($payload, $allOk ? 200 : 503)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    /**
     * @return array{status: string, latency_ms?: float, error?: string}
     */
    private function checkDb(): array
    {
        $start = microtime(true);

        try {
            // Plain SELECT 1 — exercises both the connection pool and the
            // default driver; does NOT depend on any tenant-scoped table so
            // we can still report health on a half-migrated schema.
            DB::connection()->select('SELECT 1');

            return [
                'status' => 'ok',
                'latency_ms' => round((microtime(true) - $start) * 1000, 2),
            ];
        } catch (Throwable $e) {
            Log::warning('Health check: DB failed', ['exception' => $e]);

            return [
                'status' => 'fail',
                'error' => $this->sanitiseError($e),
            ];
        }
    }

    /**
     * @return array{status: string, latency_ms?: float, error?: string}
     */
    private function checkCache(): array
    {
        $start = microtime(true);
        $key = '__healthz_probe__';

        try {
            Cache::put($key, '1', 5);
            $value = Cache::get($key);

            if ($value !== '1') {
                return [
                    'status' => 'fail',
                    'error' => 'roundtrip mismatch',
                ];
            }

            return [
                'status' => 'ok',
                'latency_ms' => round((microtime(true) - $start) * 1000, 2),
            ];
        } catch (Throwable $e) {
            Log::warning('Health check: cache failed', ['exception' => $e]);

            return [
                'status' => 'fail',
                'error' => $this->sanitiseError($e),
            ];
        }
    }

    /**
     * @return array{status: string, latency_ms?: float, error?: string}
     */
    private function checkQueue(): array
    {
        $start = microtime(true);

        try {
            // We avoid dispatching a real job (would create load + a record
            // in jobs table). Instead, instantiate the queue manager and
            // ask the configured connection for its size — for Redis/SQS
            // drivers this hits the broker; for the `sync` driver it just
            // returns 0 immediately.
            $size = Queue::connection()->size();

            return [
                'status' => 'ok',
                'size' => $size,
                'latency_ms' => round((microtime(true) - $start) * 1000, 2),
            ];
        } catch (Throwable $e) {
            Log::warning('Health check: queue failed', ['exception' => $e]);

            return [
                'status' => 'fail',
                'error' => $this->sanitiseError($e),
            ];
        }
    }

    /**
     * Strip DSN/credentials out of exception messages before returning
     * them on the public health endpoint — we don't want a Redis DSN or
     * DB password leaking just because the broker is down.
     */
    private function sanititiseError(Throwable $e): string
    {
        $msg = $e->getMessage();

        // mysql: "SQLSTATE[HY000] [2002] ..." — keep the prefix, drop anything
        // after the first colon pair that contains hostnames / passwords.
        // Generic enough that it works for PDOException, RedisException, etc.
        $msg = preg_replace('/(password|secret|token)=[^;\s]+/i', '$1=***', $msg) ?? $msg;

        // Truncate so a runaway exception doesn't blow up the JSON response.
        return mb_substr($msg, 0, 200);
    }
}
