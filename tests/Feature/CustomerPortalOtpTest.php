<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\CustomerPortalOtpChannel;
use App\Contracts\SmsSendResult;
use App\Models\Customer;
use App\Models\CustomerPortalOtp;
use App\Models\Tenant;
use App\Models\TenantWebsite;
use App\Services\CustomerPortal\CustomerPortalOtpService;
use App\Services\CustomerPortal\LoggingCustomerPortalOtpChannel;
use App\Services\CustomerPortal\OtpRateLimitedException;
use App\Services\CustomerPortal\OtpRequestResult;
use App\Services\CustomerPortal\OtpVerificationResult;
use Carbon\Carbon;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Phase 7 — Customer Portal OTP service tests.
 *
 * The service is the single source of truth for issuing
 * and verifying customer OTPs. These tests exercise the
 * service directly (no HTTP) so the assertions can target
 * the exact behavior we care about: hash-only storage,
 * rate-limit semantics, no phone enumeration, and the
 * 30-minute verify window.
 */
class CustomerPortalOtpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The rate-limiter writes to the application cache
        // (the `database` driver in this project). Without
        // an explicit reset, the buckets from one test bleed
        // into the next and every "5-call burst" test sees
        // the prior test's leftover counts. Cache::flush()
        // wipes the entire cache store — it is safe inside
        // a test because each test owns its cache.
        Cache::flush();
    }

    private function makeService(): CustomerPortalOtpService
    {
        return app(CustomerPortalOtpService::class);
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

    // ─────────────────────────────────────────────────────────────────
    // requestOtp
    // ─────────────────────────────────────────────────────────────────

    public function test_request_issues_otp_for_registered_customer(): void
    {
        $tenant = $this->publishedTenant();
        $customer = $this->customerIn($tenant);

        $result = $this->makeService()->requestOtp(
            tenant: $tenant,
            phoneInput: '0501234567',
            ip: '127.0.0.1',
            userAgent: 'PHPUnit',
        );

        $this->assertTrue($result->isIssued());
        $this->assertNotNull($result->otp);
        $this->assertSame($customer->id, $result->otp->customer_id);
        $this->assertSame('+966501234567', $result->otp->phone_normalized);
    }

    public function test_request_returns_no_customer_for_unknown_phone(): void
    {
        $tenant = $this->publishedTenant();

        $result = $this->makeService()->requestOtp(
            tenant: $tenant,
            phoneInput: '0509999999',
            ip: '127.0.0.1',
        );

        $this->assertFalse($result->isIssued());
        $this->assertSame(OtpRequestResult::NO_CUSTOMER, $result->outcome);
    }

    public function test_request_stores_hash_only_no_plaintext_code(): void
    {
        $tenant = $this->publishedTenant();
        $this->customerIn($tenant);

        $result = $this->makeService()->requestOtp(
            tenant: $tenant,
            phoneInput: '0501234567',
        );

        $otp = $result->otp;
        $this->assertNotEmpty($otp->code_hash);
        // code_hash is a bcrypt-style hash, not a plain 6-digit.
        $this->assertDoesNotMatchRegularExpression('/^\d{6}$/', $otp->code_hash);
        $this->assertGreaterThan(20, strlen($otp->code_hash));
    }

    public function test_request_creates_10_minute_expiry(): void
    {
        $tenant = $this->publishedTenant();
        $this->customerIn($tenant);

        $result = $this->makeService()->requestOtp(
            tenant: $tenant,
            phoneInput: '0501234567',
        );

        $expiresAt = $result->otp->expires_at;
        $this->assertNotNull($expiresAt);
        // The expiry must be within a small tolerance of
        // 10 minutes from now. diffInSeconds returns a
        // signed value (negative if expires_at is before
        // now), so we take the absolute value.
        $diff = abs((int) $expiresAt->diffInSeconds(Carbon::now()));
        $this->assertGreaterThan(595, $diff); // 9:55
        $this->assertLessThan(605, $diff);   // 10:05
    }

    public function test_request_throttles_resends_within_60s(): void
    {
        $tenant = $this->publishedTenant();
        $this->customerIn($tenant);

        $service = $this->makeService();
        $first = $service->requestOtp($tenant, '0501234567');
        $second = $service->requestOtp($tenant, '0501234567');

        $this->assertTrue($first->isIssued());
        $this->assertTrue($second->isThrottled());
    }

    public function test_request_allows_resend_after_cooldown(): void
    {
        $tenant = $this->publishedTenant();
        $this->customerIn($tenant);

        $service = $this->makeService();
        $first = $service->requestOtp($tenant, '0501234567');

        // Move time forward past the cooldown.
        Carbon::setTestNow(Carbon::now()->addSeconds(61));

        $second = $service->requestOtp($tenant, '0501234567');

        $this->assertTrue($first->isIssued());
        $this->assertTrue($second->isIssued());

        Carbon::setTestNow();
    }

    public function test_request_throttles_after_max_attempts_per_phone(): void
    {
        $tenant = $this->publishedTenant();
        $this->customerIn($tenant);

        $service = $this->makeService();

        // Per-phone cap is 5 within 15 minutes. We make 5
        // successful requests, each separated by the
        // 60-second cooldown so the resend throttle is
        // not the reason the test exits.
        $base = Carbon::now();

        for ($i = 0; $i < 5; $i++) {
            Carbon::setTestNow($base->copy()->addSeconds(61 * ($i + 1)));
            $service->requestOtp($tenant, '0501234567');
        }

        // The 6th request, again after the cooldown, must
        // hit the per-phone cap (5 attempts in 15 min).
        Carbon::setTestNow($base->copy()->addSeconds(61 * 6));
        $thrown = false;

        try {
            $service->requestOtp($tenant, '0501234567');
        } catch (OtpRateLimitedException $e) {
            $thrown = true;
        } finally {
            Carbon::setTestNow();
        }
        $this->assertTrue($thrown, 'Expected OtpRateLimitedException after exhausting per-phone rate limit');
    }

    public function test_unknown_phone_consumes_rate_limit_slot(): void
    {
        $tenant = $this->publishedTenant();

        $service = $this->makeService();

        // All five calls reuse the same phone literal so
        // the per-phone bucket receives five hits on one
        // key. The 6th call must hit the cap.
        for ($i = 0; $i < 5; $i++) {
            $service->requestOtp($tenant, '0500000000', ip: '10.0.0.1');
        }
        $thrown = false;

        try {
            $service->requestOtp($tenant, '0500000000', ip: '10.0.0.1');
        } catch (OtpRateLimitedException $e) {
            $thrown = true;
        }
        $this->assertTrue($thrown);
    }

    public function test_request_does_not_enumerate_customers_across_tenants(): void
    {
        $tenantA = $this->publishedTenant('a');
        $tenantB = $this->publishedTenant('b');
        $this->customerIn($tenantA, '+966501234567');

        $result = $this->makeService()->requestOtp(
            tenant: $tenantB,
            phoneInput: '0501234567',
        );

        // The phone exists in tenant A only. From tenant B's
        // perspective, it MUST be no-customer. The result
        // shape is identical to a real "no customer" call.
        $this->assertSame(OtpRequestResult::NO_CUSTOMER, $result->outcome);
    }

    // ─────────────────────────────────────────────────────────────────
    // P1-2: requesting a fresh OTP must invalidate any
    // outstanding code so a previously-sent (and possibly
    // intercepted) code cannot be reused.
    // ─────────────────────────────────────────────────────────────────

    public function test_new_request_invalidates_previous_usable_otp(): void
    {
        $tenant = $this->publishedTenant();
        $this->customerIn($tenant);

        // First request — capture its code.
        $firstCode = $this->captureCode(function () use ($tenant) {
            $this->makeService()->requestOtp($tenant, '0501234567');
        });

        // Wait past the cooldown so the second request is
        // not throttled.
        Carbon::setTestNow(Carbon::now()->addSeconds(61));
        $secondCode = $this->captureCode(function () use ($tenant) {
            $this->makeService()->requestOtp($tenant, '0501234567');
        });

        // The second code is what the user typed in. The
        // verify call MUST succeed for the second code
        // (proves the new row is usable)…
        $verify = $this->makeService()->verify($tenant, '0501234567', $secondCode);
        $this->assertTrue($verify->success);

        // …and the first code MUST be rejected (proves the
        // first row was invalidated by the second request).
        $verifyFirst = $this->makeService()->verify($tenant, '0501234567', $firstCode);
        $this->assertFalse($verifyFirst->success);

        Carbon::setTestNow();
    }

    public function test_previous_otp_is_marked_consumed_atomically(): void
    {
        // Direct DB check: after a new request, the
        // previous row's consumed_at must be set.
        $tenant = $this->publishedTenant();
        $this->customerIn($tenant);

        $this->captureCode(function () use ($tenant) {
            $this->makeService()->requestOtp($tenant, '0501234567');
        });

        $previous = CustomerPortalOtp::query()
            ->where('tenant_id', $tenant->id)
            ->first();
        $this->assertNull($previous->consumed_at);

        Carbon::setTestNow(Carbon::now()->addSeconds(61));
        $this->captureCode(function () use ($tenant) {
            $this->makeService()->requestOtp($tenant, '0501234567');
        });
        Carbon::setTestNow();

        $previous->refresh();
        $this->assertNotNull($previous->consumed_at);
    }

    public function test_concurrent_requests_yield_exactly_one_usable_otp(): void
    {
        // The atomicity contract: two simultaneous
        // requestOtp calls for the same customer must
        // produce exactly one usable OTP. The
        // customer-row lock serializes the two
        // transactions; the second one either sees
        // the first's fresh last_sent_at and returns
        // throttled (no new row), or arrives after the
        // first's cooldown has elapsed and creates a
        // second row but invalidates the first.
        //
        // In this test we collapse "simultaneous" to
        // "back-to-back without an intervening
        // addSeconds(61)" — the lock has the same
        // effect either way: the second request waits
        // on the first's lock, then re-reads the
        // cooldown and either proceeds or throttles.
        $tenant = $this->publishedTenant();
        $this->customerIn($tenant);

        $firstCode = $this->captureCode(function () use ($tenant) {
            // Resolve the service inside the closure so
            // it picks up the fake channel that
            // captureCode() just bound in the container.
            app(CustomerPortalOtpService::class)
                ->requestOtp($tenant, '0501234567');
        });

        // Second request, still inside the cooldown
        // window (no addSeconds): must be throttled, no
        // new row created. Resolve fresh here too, so
        // we are not depending on which channel is
        // currently bound — we only assert the
        // outcome.
        $result = app(CustomerPortalOtpService::class)
            ->requestOtp($tenant, '0501234567');
        $this->assertTrue($result->isThrottled(), 'Second concurrent request must be throttled');

        // There must be exactly one OTP row, and it
        // must NOT be pre-consumed.
        $rows = CustomerPortalOtp::query()
            ->where('tenant_id', $tenant->id)
            ->get();
        $this->assertCount(1, $rows, 'exactly one OTP row should exist after two concurrent requests');
        $this->assertNull($rows->first()->consumed_at, 'the surviving row must NOT be pre-consumed');

        // The first code is the one the user has. The
        // second request did not invalidate it (it
        // was throttled before the invalidation step).
        $verify = app(CustomerPortalOtpService::class)
            ->verify($tenant, '0501234567', $firstCode);
        $this->assertTrue($verify->success, 'the original code must still verify');
    }

    // ─────────────────────────────────────────────────────────────────
    // P1-1: the logging channel must never record the plain
    // code. Operators may tail the log; the only thing they
    // should see is the otp id and a short fingerprint.
    // ─────────────────────────────────────────────────────────────────

    public function test_logging_channel_does_not_record_plain_code(): void
    {
        Log::spy();

        $tenant = $this->publishedTenant();
        $customer = $this->customerIn($tenant);

        $this->makeService()->requestOtp($tenant, '0501234567');

        // We expect at least one log line that mentions
        // 'customer_portal.otp.sent'. None of them may
        // contain the 6-digit code itself, nor any
        // fingerprint or hash derived from it, nor the
        // phone (PII). The structured payload must
        // include only the otp id (a DB primary key),
        // tenant id, customer id, and expires_at.
        Log::shouldHaveReceived('info')
            ->withArgs(function ($message, $context) {
                if ($message !== 'customer_portal.otp.sent') {
                    return true; // not the line we care about
                }
                $json = json_encode($context);
                $allowedKeys = ['otp_id', 'tenant_id', 'customer_id', 'expires_at'];
                $actualKeys = is_array($context) ? array_keys($context) : [];

                foreach ($actualKeys as $key) {
                    $this->assertContains($key, $allowedKeys, "Log line carries forbidden key '{$key}': {$json}");
                }
                // Phone raw is PII — must not be logged.
                $this->assertStringNotContainsString('0501234567', $json, 'phone leaked into log');
                $this->assertStringNotContainsString('+966501234567', $json, 'phone leaked into log');
                // No fingerprint / hash of the code. The
                // only identifiers in the log should be
                // the otp id, tenant id, customer id,
                // expires_at — all of which are useless
                // to an attacker trying to brute force
                // a 6-digit code.
                $this->assertStringNotContainsString('code', $json, 'log mentions code in any form');
                $this->assertStringNotContainsString('fingerprint', $json, 'log carries a code fingerprint');

                return true;
            })
            ->atLeast()->once();
    }

    public function test_unknown_phone_log_carries_hash_not_phone(): void
    {
        Log::spy();

        $tenant = $this->publishedTenant();
        // No customer — the service logs unknown_phone
        // and returns noCustomer.
        $this->makeService()->requestOtp($tenant, '0501234567');

        Log::shouldHaveReceived('info')
            ->withArgs(function ($message, $context) {
                if ($message !== 'customer_portal.otp.request.unknown_phone') {
                    return true;
                }
                $json = json_encode($context);
                $this->assertStringNotContainsString('0501234567', $json, 'raw phone leaked into unknown_phone log');
                $this->assertStringNotContainsString('+966501234567', $json, 'raw phone leaked into unknown_phone log');
                // The hash of the normalized phone is
                // sha256('+966501234567'). It is OK
                // for the log to carry this hash; the
                // operator can join it against the
                // customers table to look up the row.
                $this->assertArrayHasKey('phone_hash', $context);

                return true;
            })
            ->atLeast()->once();
    }

    // ─────────────────────────────────────────────────────────────────
    // P1-5: verify attempts are throttled per (tenant, phone)
    // in addition to the route-level IP throttle.
    // ─────────────────────────────────────────────────────────────────

    public function test_verify_throttles_per_tenant_phone(): void
    {
        $tenant = $this->publishedTenant();
        $this->customerIn($tenant);

        $this->captureCode(function () use ($tenant) {
            $this->makeService()->requestOtp($tenant, '0501234567');
        });

        // 10 wrong attempts must succeed (RATE_LIMIT_VERIFY_PER_PHONE = 10).
        // The OTP itself is limited to 5 attempts, so the
        // 6th wrong verify will hit the OTP cap, not the
        // service cap. The 11th would hit the service cap
        // only if we issued a fresh OTP, which we don't.
        // To exercise the service cap, we issue 11 fresh
        // OTPs (each reset by the per-OTP 5-attempt cap
        // and the 60s cooldown).
        $thrown = false;

        for ($i = 0; $i < 12; $i++) {
            Carbon::setTestNow(Carbon::now()->addSeconds(61));
            $code = $this->captureCode(function () use ($tenant) {
                $this->makeService()->requestOtp($tenant, '0501234567');
            });

            // Now hit the verify cap with wrong codes.
            for ($j = 0; $j < 12; $j++) {
                try {
                    $this->makeService()->verify($tenant, '0501234567', '000000');
                } catch (OtpRateLimitedException $e) {
                    Carbon::setTestNow();
                    $thrown = true;
                    break 2;
                }
            }
        }
        Carbon::setTestNow();
        $this->assertTrue($thrown, 'Expected OtpRateLimitedException from per-(tenant,phone) verify bucket');
    }

    // ─────────────────────────────────────────────────────────────────
    // verify
    // ─────────────────────────────────────────────────────────────────

    public function test_verify_succeeds_with_correct_code(): void
    {
        $tenant = $this->publishedTenant();
        $this->customerIn($tenant);

        // We use a fake channel to capture the plaintext code.
        $code = $this->captureCode(function () use ($tenant) {
            $this->makeService()->requestOtp($tenant, '0501234567');
        });

        $result = $this->makeService()->verify($tenant, '0501234567', $code);

        $this->assertTrue($result->success);
        $this->assertNotNull($result->customer);
    }

    public function test_verify_rejects_wrong_code(): void
    {
        $tenant = $this->publishedTenant();
        $this->customerIn($tenant);

        $this->captureCode(function () use ($tenant) {
            $this->makeService()->requestOtp($tenant, '0501234567');
        });

        $result = $this->makeService()->verify($tenant, '0501234567', '000000');

        $this->assertFalse($result->success);
        $this->assertSame(OtpVerificationResult::REASON_BAD_CODE, $result->reason);
    }

    public function test_verify_rejects_expired_code(): void
    {
        $tenant = $this->publishedTenant();
        $this->customerIn($tenant);

        $code = $this->captureCode(function () use ($tenant) {
            $this->makeService()->requestOtp($tenant, '0501234567');
        });

        // Move past the 10-minute TTL. The verify path
        // filters out expired OTPs at the "latestUsableOtp"
        // step, so the surface reason is REASON_NO_ACTIVE_OTP
        // — the user-facing message is the same, but the
        // logging reason is the more specific one.
        Carbon::setTestNow(Carbon::now()->addMinutes(11));

        $result = $this->makeService()->verify($tenant, '0501234567', $code);

        $this->assertFalse($result->success);
        $this->assertSame(OtpVerificationResult::REASON_NO_ACTIVE_OTP, $result->reason);

        Carbon::setTestNow();
    }

    public function test_verify_increments_attempts_on_wrong_code(): void
    {
        $tenant = $this->publishedTenant();
        $this->customerIn($tenant);

        $otp = CustomerPortalOtp::factory()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => Customer::query()->where('tenant_id', $tenant->id)->first()->id,
            'phone_normalized' => '+966501234567',
            'code_hash' => Hash::make('123456'),
            'expires_at' => Carbon::now()->addMinutes(10),
            'attempts' => 0,
            'max_attempts' => 5,
        ]);

        $this->makeService()->verify($tenant, '0501234567', '000000');
        $this->assertSame(1, $otp->fresh()->attempts);
    }

    public function test_verify_rejects_after_max_attempts(): void
    {
        $tenant = $this->publishedTenant();
        $customer = $this->customerIn($tenant);

        // Seed an OTP already at the cap.
        CustomerPortalOtp::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'phone_normalized' => '+966501234567',
            'code_hash' => Hash::make('123456'),
            'expires_at' => Carbon::now()->addMinutes(10),
            'attempts' => 5,
            'max_attempts' => 5,
        ]);

        $result = $this->makeService()->verify($tenant, '0501234567', '123456');

        $this->assertFalse($result->success);
        $this->assertSame(OtpVerificationResult::REASON_TOO_MANY_ATTEMPTS, $result->reason);
    }

    public function test_verify_cannot_reuse_consumed_code(): void
    {
        $tenant = $this->publishedTenant();
        $this->customerIn($tenant);

        $code = $this->captureCode(function () use ($tenant) {
            $this->makeService()->requestOtp($tenant, '0501234567');
        });

        $first = $this->makeService()->verify($tenant, '0501234567', $code);
        $this->assertTrue($first->success);

        $second = $this->makeService()->verify($tenant, '0501234567', $code);
        $this->assertFalse($second->success);
        $this->assertSame(OtpVerificationResult::REASON_NO_ACTIVE_OTP, $second->reason);
    }

    public function test_verify_rejects_phone_in_different_tenant(): void
    {
        $tenantA = $this->publishedTenant('a');
        $tenantB = $this->publishedTenant('b');
        $this->customerIn($tenantA, '+966501234567');

        $code = $this->captureCode(function () use ($tenantA) {
            $this->makeService()->requestOtp($tenantA, '0501234567');
        });

        $result = $this->makeService()->verify($tenantB, '0501234567', $code);

        $this->assertFalse($result->success);
        $this->assertSame(OtpVerificationResult::REASON_NO_CUSTOMER, $result->reason);
    }

    public function test_verify_rejects_malformed_phone(): void
    {
        $tenant = $this->publishedTenant();
        $this->customerIn($tenant);

        $result = $this->makeService()->verify($tenant, 'not-a-phone', '123456');

        $this->assertFalse($result->success);
        $this->assertSame(OtpVerificationResult::REASON_MALFORMED, $result->reason);
    }

    // ─────────────────────────────────────────────────────────────────
    // Test helpers
    // ─────────────────────────────────────────────────────────────────

    /**
     * Run the given closure with a fake OTP channel that
     * captures the plaintext code into memory, then return
     * the captured code. The original binding is restored
     * after the closure.
     *
     * Implementation note: we use a static array on the
     * anonymous class rather than a constructor-passed
     * reference. PHP's anonymous class constructor
     * property type `private array &$ref` does NOT actually
     * bind by reference when instantiated; the captured
     * code would be set on a copy. Using a static keeps
     * the value visible to the outer test method.
     */
    private function captureCode(Closure $fn): string
    {
        // Reset the static holder so the previous test's
        // value doesn't bleed through.
        self::$capturedCodeStatic = '';

        $fake = new class implements CustomerPortalOtpChannel
        {
            public function send(CustomerPortalOtp $otp, string $phone, string $code): SmsSendResult
            {
                CustomerPortalOtpTest::$capturedCodeStatic = $code;

                return SmsSendResult::ok('fake');
            }
        };

        $this->app->instance(CustomerPortalOtpChannel::class, $fake);

        try {
            // Forget the singleton so the service
            // re-resolves and picks up the fake channel.
            // The CustomerPortalOtpService is bound as a
            // singleton in AppServiceProvider, so
            // without this forget the constructor would
            // have been called once with the production
            // logging channel and would keep using it
            // even after we replace the binding.
            //
            // We also forget it AGAIN after the closure
            // runs, so the next test gets a fresh
            // service. The instance-level state
            // (`$pendingCode`) does not need to survive
            // across closure runs — the test asserts the
            // code is captured, not that it persists.
            $this->app->forgetInstance(CustomerPortalOtpService::class);
            $fn();
        } finally {
            $this->app->forgetInstance(CustomerPortalOtpService::class);
            $this->app->instance(
                CustomerPortalOtpChannel::class,
                app(LoggingCustomerPortalOtpChannel::class)
            );
        }

        if (self::$capturedCodeStatic === '') {
            $this->fail('Channel was not called within the closure');
        }

        return self::$capturedCodeStatic;
    }

    public static string $capturedCodeStatic = '';
}
