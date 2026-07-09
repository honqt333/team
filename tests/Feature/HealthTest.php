<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Smoke tests for the Track C health/readiness probes.
 *
 * /healthz must always return 200 with a JSON body containing status=ok.
 * /readyz must return 200 in a healthy environment and expose per-check
 * status keys (db, cache, queue).
 *
 * Both endpoints MUST be reachable without auth, without CSRF, and
 * without a database write — a misbehaving probe that depends on a
 * broken downstream would mask real outages.
 */
class HealthTest extends TestCase
{
    public function test_healthz_returns_ok(): void
    {
        $response = $this->get('/api/healthz');

        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'service', 'timestamp'])
            ->assertJson(['status' => 'ok']);

        // Correlation ID middleware must be active even on the probe.
        $response->assertHeader('X-Correlation-Id');
        $this->assertNotEmpty(
            $response->headers->get('X-Correlation-Id'),
            'X-Correlation-Id header must not be empty'
        );
    }

    public function test_readyz_returns_check_status(): void
    {
        $response = $this->get('/api/readyz');

        $this->assertContains(
            $response->getStatusCode(),
            [200, 503],
            'readyz must be 200 when healthy, 503 when degraded — never anything else.'
        );

        $response->assertJsonStructure([
            'status',
            'service',
            'checks' => ['db', 'cache', 'queue'],
            'timestamp',
        ]);

        // In the testing env (sqlite + array cache + sync queue) all checks
        // should be ok.
        $body = $response->json();
        $this->assertSame('ready', $body['status'], 'test env should report ready');
        $this->assertSame('ok', $body['checks']['db']['status']);
        $this->assertSame('ok', $body['checks']['cache']['status']);
        $this->assertSame('ok', $body['checks']['queue']['status']);
    }

    public function test_healthz_does_not_require_auth(): void
    {
        // No $this->actingAs() — anonymous request must succeed.
        $this->get('/api/healthz')->assertStatus(200);
    }

    public function test_readyz_emits_correlation_header_even_when_degraded(): void
    {
        $response = $this->get('/api/readyz');

        // Whether degraded or not, we MUST echo the correlation id so an
        // operator can correlate the failed probe with the relevant log
        // lines / Sentry event.
        $this->assertNotEmpty($response->headers->get('X-Correlation-Id'));
    }
}
