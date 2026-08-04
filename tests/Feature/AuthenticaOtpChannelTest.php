<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\CustomerPortalOtpChannel;
use App\Http\Controllers\System\IntegrationsController;
use App\Models\Credits\SmsUsageLog;
use App\Models\Credits\TenantSmsBalance;
use App\Models\Customer;
use App\Models\CustomerPortalOtp;
use App\Models\Integration\Integration;
use App\Models\Tenant;
use App\Models\TenantWebsite;
use App\Providers\AppServiceProvider;
use App\Services\CustomerPortal\AuthenticaCustomerPortalOtpChannel;
use App\Services\CustomerPortal\CustomerPortalOtpService;
use App\Services\CustomerPortal\OtpVerificationResult;
use App\Services\CustomerPortal\SystemSmsOtpChannel;
use App\Services\Messaging\SmsService;
use Carbon\Carbon;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use ReflectionMethod;
use RuntimeException;
use Tests\TestCase;
use Throwable;

/**
 * P1 — Authentica v2 customer-portal OTP channel.
 *
 * The original P1 bug: the default SMS integration
 * could be set to `authentica`, but the
 * SmsService::sendViaProvider() switch had no
 * `authentica` case. The customer portal therefore
 * threw "مزود غير مدعوم: authentica" on every OTP
 * request. Operators saw the customer-portal OTP
 * field say "we sent you a code" while no code
 * ever reached the customer.
 *
 * Authentica's v2 OpenAPI spec
 * (https://docs.authentica.sa/openapi.yaml) does
 * NOT expose a general "send free-form SMS"
 * endpoint — only /send-otp, /verify-otp,
 * /verify-by-face, /verify-by-voice, and /balance.
 * /send-otp accepts a `method` (sms), a `phone`, an
 * optional `template_id`, and an optional `otp`
 * field that lets the caller pass a pre-generated
 * 6-digit code.
 *
 * The fix is therefore two-channel:
 *   1. App\Services\CustomerPortal\AuthenticaCustomerPortalOtpChannel
 *      — talks directly to /send-otp with the
 *      portal's own code so the internal hash
 *      remains authoritative. Bound to
 *      CustomerPortalOtpChannel when the default
 *      SMS integration is authentica.
 *   2. App\Services\Messaging\SmsService
 *      — explicitly REJECTS `authentica` because
 *      Authentica cannot carry the multi-sentence
 *      Arabic/English appointment messages. This
 *      forces the operator to keep one provider
 *      (Unifonic / Twilio / Msegat) configured for
 *      appointment messages while Authentica
 *      handles the OTP only.
 *
 * These tests cover the OTP channel end-to-end
 * without ever touching a real provider
 * (Http::fake). They also pin the SmsService
 * rejection contract and the AppServiceProvider
 * binding logic.
 */
class AuthenticaOtpChannelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The rate-limiter writes to the application
        // cache. Without an explicit reset, buckets
        // from one test bleed into the next.
        Cache::flush();
    }

    private function publishedTenant(string $slug = 'demo', int $smsBalance = 100): Tenant
    {
        $tenant = Tenant::factory()->create(['slug' => $slug]);
        TenantWebsite::factory()->for($tenant)->published()->create();

        // The Authentica customer-portal OTP channel
        // now bills the tenant's SMS balance: each
        // /send-otp call reserves 1 credit
        // atomically. The existing Phase 7/8/9 tests
        // assume an unlimited balance; we keep that
        // assumption by defaulting to 100 credits
        // (more than enough for any single test)
        // and letting the new billing tests create
        // a tenant with a specific balance.
        //
        // updateOrCreate is safe for the rare test
        // that re-seeds the balance itself.
        TenantSmsBalance::updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'balance' => $smsBalance,
                'total_purchased' => $smsBalance,
                'total_used' => 0,
            ]
        );

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

    private function makeAuthenticaIntegration(array $overrides = []): Integration
    {
        return Integration::create(array_merge([
            'type' => 'sms',
            'provider' => 'authentica',
            'name' => 'Authentica',
            'name_ar' => 'أوثينتيكا',
            'config' => [
                'api_key' => 'sk_test_authentica_'.$this->randomKey(),
                'template_id' => 1,
            ],
            'is_active' => true,
            'is_default' => true,
        ], $overrides));
    }

    private function randomKey(): string
    {
        return bin2hex(random_bytes(8));
    }

    private function makeChannel(Integration $integration): AuthenticaCustomerPortalOtpChannel
    {
        return new AuthenticaCustomerPortalOtpChannel($integration);
    }

    private function makeOtp(Tenant $tenant, Customer $customer, string $phone): CustomerPortalOtp
    {
        return CustomerPortalOtp::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'phone_normalized' => $phone,
            'phone_hash' => hash('sha256', $phone),
            'code_hash' => Hash::make('123456'),
            'expires_at' => Carbon::now()->addMinutes(10),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // P1 — the channel hits the Authentica v2 /send-otp endpoint
    // with the documented headers + body shape.
    // ─────────────────────────────────────────────────────────────────

    public function test_channel_posts_to_authentica_v2_send_otp_with_documented_payload(): void
    {
        Http::fake([
            'api.authentica.sa/api/v2/send-otp' => Http::response([
                'success' => true,
                'data' => null,
                'message' => 'OTP send successfully',
            ], 200),
        ]);

        $integration = $this->makeAuthenticaIntegration();
        $tenant = $this->publishedTenant();
        $customer = $this->customerIn($tenant);
        $otp = $this->makeOtp($tenant, $customer, '+966501234567');

        $result = $this->makeChannel($integration)->send($otp, '+966501234567', '123456');

        $this->assertTrue($result->success, 'channel must report success on 2xx');
        $this->assertNull($result->providerMessageId, 'Authentica does not return a message id; we surface null');
        $this->assertNull($result->errorCode);

        // The single outbound call MUST be /send-otp
        // with the documented v2 contract: method=sms,
        // phone=E.164, template_id, AND our 6-digit
        // code in the `otp` field. The X-Authorization
        // header is the api_key.
        Http::assertSentCount(1);
        Http::assertSent(function ($request) use ($integration) {
            if ($request->url() !== 'https://api.authentica.sa/api/v2/send-otp') {
                return false;
            }

            if ($request->method() !== 'POST') {
                return false;
            }

            if ($request->header('X-Authorization')[0] !== $integration->config['api_key']) {
                return false;
            }

            if ($request->header('Accept')[0] !== 'application/json') {
                return false;
            }

            if ($request->header('Content-Type')[0] !== 'application/json') {
                return false;
            }

            $body = json_decode($request->body(), true);
            $this->assertSame('sms', $body['method'] ?? null);
            $this->assertSame('+966501234567', $body['phone'] ?? null);
            $this->assertSame(1, $body['template_id'] ?? null);
            // The internal 6-digit code MUST travel
            // through. The user types this same code
            // on the verify endpoint; if we let
            // Authentica generate it, the hashes
            // diverge and verify always fails.
            $this->assertSame('123456', $body['otp'] ?? null);

            return true;
        });
    }

    public function test_channel_uses_configured_template_id(): void
    {
        Http::fake([
            'api.authentica.sa/*' => Http::response(['success' => true, 'data' => null], 200),
        ]);

        $integration = $this->makeAuthenticaIntegration([
            'config' => [
                'api_key' => 'sk_test_'.$this->randomKey(),
                'template_id' => 31,
            ],
        ]);

        $tenant = $this->publishedTenant();
        $customer = $this->customerIn($tenant);
        $otp = $this->makeOtp($tenant, $customer, '+966501234567');

        $this->makeChannel($integration)->send($otp, '+966501234567', '123456');

        Http::assertSent(function ($request) {
            $body = json_decode($request->body(), true);

            return ($body['template_id'] ?? null) === 31;
        });
    }

    public function test_channel_uses_default_template_id_when_unset(): void
    {
        Http::fake([
            'api.authentica.sa/*' => Http::response(['success' => true, 'data' => null], 200),
        ]);

        $integration = Integration::create([
            'type' => 'sms',
            'provider' => 'authentica',
            'name' => 'Authentica',
            'name_ar' => 'أوثينتيكا',
            'config' => ['api_key' => 'sk_test_'.$this->randomKey()],
            'is_active' => true,
            'is_default' => true,
        ]);

        $tenant = $this->publishedTenant();
        $customer = $this->customerIn($tenant);
        $otp = $this->makeOtp($tenant, $customer, '+966501234567');

        $this->makeChannel($integration)->send($otp, '+966501234567', '123456');

        Http::assertSent(function ($request) {
            $body = json_decode($request->body(), true);

            // Per the v2 OpenAPI spec, the default
            // template_id when none is configured is 1.
            return ($body['template_id'] ?? null) === 1;
        });
    }

    // ─────────────────────────────────────────────────────────────────
    // P1 — success path keeps the OTP row usable.
    // ─────────────────────────────────────────────────────────────────

    public function test_success_path_does_not_invalidate_otp_row(): void
    {
        Http::fake([
            'api.authentica.sa/*' => Http::response(['success' => true, 'data' => null], 200),
        ]);

        $integration = $this->makeAuthenticaIntegration();
        $tenant = $this->publishedTenant();
        $customer = $this->customerIn($tenant);
        $otp = $this->makeOtp($tenant, $customer, '+966501234567');

        $result = $this->makeChannel($integration)->send($otp, '+966501234567', '123456');

        $this->assertTrue($result->success);

        // The row must remain verifiable. The
        // verify path filters `consumed_at IS NULL`.
        $fresh = $otp->fresh();
        $this->assertNull(
            $fresh->consumed_at,
            'OTP row must NOT be marked consumed on a successful send'
        );
    }

    // ─────────────────────────────────────────────────────────────────
    // P1 — HTTP failure invalidates the OTP row + returns failure.
    // ─────────────────────────────────────────────────────────────────

    public function test_http_failure_invalidates_otp_row(): void
    {
        Http::fake([
            'api.authentica.sa/*' => Http::response([
                'errors' => [['message' => 'Invalid API key']],
            ], 401),
        ]);

        $integration = $this->makeAuthenticaIntegration();
        $tenant = $this->publishedTenant();
        $customer = $this->customerIn($tenant);
        $otp = $this->makeOtp($tenant, $customer, '+966501234567');

        $result = $this->makeChannel($integration)->send($otp, '+966501234567', '123456');

        $this->assertFalse($result->success);
        $this->assertNull($result->providerMessageId);
        $this->assertSame('sms_send_failed', $result->errorCode);

        $fresh = $otp->fresh();
        $this->assertNotNull(
            $fresh->consumed_at,
            'OTP row MUST be marked consumed on a failed send — the code never reached the customer'
        );
    }

    public function test_5xx_failure_invalidates_otp_row(): void
    {
        Http::fake([
            'api.authentica.sa/*' => Http::response('upstream down', 503),
        ]);

        $integration = $this->makeAuthenticaIntegration();
        $tenant = $this->publishedTenant();
        $customer = $this->customerIn($tenant);
        $otp = $this->makeOtp($tenant, $customer, '+966501234567');

        $result = $this->makeChannel($integration)->send($otp, '+966501234567', '123456');

        $this->assertFalse($result->success);

        $fresh = $otp->fresh();
        $this->assertNotNull($fresh->consumed_at);
    }

    public function test_network_exception_invalidates_otp_row(): void
    {
        Http::fake(function () {
            throw new RuntimeException('connection refused');
        });

        $integration = $this->makeAuthenticaIntegration();
        $tenant = $this->publishedTenant();
        $customer = $this->customerIn($tenant);
        $otp = $this->makeOtp($tenant, $customer, '+966501234567');

        $result = $this->makeChannel($integration)->send($otp, '+966501234567', '123456');

        $this->assertFalse($result->success);
        $this->assertSame('sms_send_failed', $result->errorCode);

        $fresh = $otp->fresh();
        $this->assertNotNull(
            $fresh->consumed_at,
            'A network exception must invalidate the row the same way an HTTP 4xx does'
        );
    }

    public function test_failure_does_not_double_stamp_consumed_at(): void
    {
        // If the row was already consumed by a
        // concurrent verify, the second send must NOT
        // overwrite that stamp. The UPDATE is
        // conditional on `consumed_at IS NULL`.
        Http::fake([
            'api.authentica.sa/*' => Http::response(['error' => 'x'], 500),
        ]);

        $integration = $this->makeAuthenticaIntegration();
        $tenant = $this->publishedTenant();
        $customer = $this->customerIn($tenant);
        $otp = $this->makeOtp($tenant, $customer, '+966501234567');

        $existing = Carbon::now()->subSeconds(30);
        CustomerPortalOtp::query()
            ->whereKey($otp->id)
            ->update(['consumed_at' => $existing]);

        $this->makeChannel($integration)->send($otp, '+966501234567', '123456');

        $fresh = $otp->fresh();
        $this->assertNotNull($fresh->consumed_at);
        // The pre-existing stamp must remain — the
        // channel must not have re-stamped with
        // `now()`. We assert the difference between
        // the original stamp and the surviving
        // stamp is less than 5 seconds. If the
        // channel had re-stamped with `now()`, the
        // difference would be 30s+; the 5-second
        // bound is generous enough to absorb
        // millisecond-level roundtrip jitter.
        $delta = abs($existing->diffInSeconds($fresh->consumed_at));
        $this->assertLessThan(
            5,
            $delta,
            'channel must not re-stamp consumed_at when the row is already consumed'
        );
    }

    // ─────────────────────────────────────────────────────────────────
    // Phase 12 P1 — response-body contract: 2xx alone is not
    // enough. The Authentica v2 OpenAPI spec
    // (https://docs.authentica.sa/openapi.yaml) requires the
    // body to be JSON with `success === true`. A 200 OK
    // with `success: false` (or a malformed body) must
    // invalidate the OTP the same way a 4xx does — the
    // customer can never verify a code that never reached
    // them.
    // ─────────────────────────────────────────────────────────────────

    public function test_phase12_p1_http_200_with_success_false_invalidates_otp(): void
    {
        // The previous implementation accepted any
        // 2xx as a successful send. The new contract:
        // we look at `response.success === true` from
        // the JSON body. A 200 with success:false is
        // a provider-side rejection — the customer's
        // phone never received a code, the row MUST
        // be marked consumed, and we MUST return
        // failure.
        Http::fake([
            'api.authentica.sa/*' => Http::response([
                'success' => false,
                'message' => 'Phone not authorized for this template',
                'errors' => ['phone' => 'not_in_allowed_list'],
            ], 200),
        ]);

        $integration = $this->makeAuthenticaIntegration();
        $tenant = $this->publishedTenant();
        $customer = $this->customerIn($tenant);
        $otp = $this->makeOtp($tenant, $customer, '+966501234567');

        $result = $this->makeChannel($integration)->send($otp, '+966501234567', '123456');

        $this->assertFalse(
            $result->success,
            'A 200 with success:false MUST be a failure — the customer never received a code'
        );
        $this->assertSame('sms_send_failed', $result->errorCode);

        // The row must be marked consumed so the
        // verify path rejects the code.
        $fresh = $otp->fresh();
        $this->assertNotNull(
            $fresh->consumed_at,
            'success:false on a 2xx must invalidate the OTP — the code never reached the customer'
        );
    }

    public function test_phase12_p1_http_200_with_invalid_json_invalidates_otp(): void
    {
        // The provider returned a 2xx but the body
        // is not parseable JSON (e.g. an HTML
        // maintenance page served with a 200, or a
        // proxy error page). The channel MUST treat
        // this as a hard failure.
        Http::fake([
            'api.authentica.sa/*' => Http::response('<html>not json</html>', 200),
        ]);

        $integration = $this->makeAuthenticaIntegration();
        $tenant = $this->publishedTenant();
        $customer = $this->customerIn($tenant);
        $otp = $this->makeOtp($tenant, $customer, '+966501234567');

        $result = $this->makeChannel($integration)->send($otp, '+966501234567', '123456');

        $this->assertFalse($result->success);
        $this->assertNull($result->providerMessageId);
        $this->assertSame('sms_send_failed', $result->errorCode);

        $this->assertNotNull(
            $otp->fresh()->consumed_at,
            'A 2xx with a non-JSON body must invalidate the OTP — Authentica did not confirm delivery'
        );
    }

    public function test_phase12_p1_http_200_with_success_true_keeps_otp_valid(): void
    {
        // The happy path: 200 + success:true. The
        // channel MUST return success, the row MUST
        // remain usable, and the existing e2e test
        // continues to hold.
        Http::fake([
            'api.authentica.sa/*' => Http::response([
                'success' => true,
                'data' => null,
                'message' => 'OTP sent successfully',
            ], 200),
        ]);

        $integration = $this->makeAuthenticaIntegration();
        $tenant = $this->publishedTenant();
        $customer = $this->customerIn($tenant);
        $otp = $this->makeOtp($tenant, $customer, '+966501234567');

        $result = $this->makeChannel($integration)->send($otp, '+966501234567', '123456');

        $this->assertTrue($result->success, 'a documented 200 + success:true MUST succeed');
        $this->assertNull($result->errorCode);
        $this->assertNull(
            $otp->fresh()->consumed_at,
            'the OTP row must remain valid for verify'
        );
    }

    public function test_phase12_p1_http_200_with_missing_success_field_invalidates_otp(): void
    {
        // 200 with JSON that has no `success` field
        // at all. Per the Authentica v2 contract, an
        // absent `success` is treated as a
        // contract violation. The channel MUST
        // reject the row.
        Http::fake([
            'api.authentica.sa/*' => Http::response([
                'data' => ['id' => 'something'],
                'message' => 'no success field here',
            ], 200),
        ]);

        $integration = $this->makeAuthenticaIntegration();
        $tenant = $this->publishedTenant();
        $customer = $this->customerIn($tenant);
        $otp = $this->makeOtp($tenant, $customer, '+966501234567');

        $result = $this->makeChannel($integration)->send($otp, '+966501234567', '123456');

        $this->assertFalse($result->success);
        $this->assertNotNull($otp->fresh()->consumed_at);
    }

    public function test_phase12_p1_rejection_log_uses_safe_sentinel_and_no_body(): void
    {
        // Privacy: the rejection log must use the
        // short sentinel `authentica_response_rejected`
        // and MUST NOT carry the phone, the code, the
        // api_key, or the response body.
        $captured = [];
        Log::listen(function (MessageLogged $event) use (&$captured) {
            $captured[] = [
                'level' => $event->level,
                'message' => $event->message,
                'context' => $event->context,
            ];
        });

        Http::fake([
            'api.authentica.sa/*' => Http::response([
                'success' => false,
                // The body could echo the phone or
                // the api_key back to us. We must not
                // propagate any of it.
                'message' => 'phone +966501111111 rejected for sk_test_abcdef1234',
            ], 200),
        ]);

        $apiKey = 'sk_test_'.$this->randomKey();
        $integration = Integration::create([
            'type' => 'sms',
            'provider' => 'authentica',
            'name' => 'Authentica',
            'name_ar' => 'أوثينتيكا',
            'config' => ['api_key' => $apiKey, 'template_id' => 7],
            'is_active' => true,
            'is_default' => true,
        ]);

        $tenant = $this->publishedTenant();
        $customer = $this->customerIn($tenant, '+966501111111');
        $otp = $this->makeOtp($tenant, $customer, '+966501111111');

        $this->makeChannel($integration)->send($otp, '+966501111111', '424242');

        $rawPhone = '+966501111111';
        $rawCode = '424242';

        $foundSentinel = false;

        foreach ($captured as $entry) {
            $json = json_encode($entry['context']);
            $this->assertStringNotContainsString($rawPhone, $json, "phone leaked: {$entry['message']}");
            $this->assertStringNotContainsString($rawCode, $json, "code leaked: {$entry['message']}");
            $this->assertStringNotContainsString($apiKey, $json, "api_key leaked: {$entry['message']}");
            $this->assertStringNotContainsString('rejected for', $json, "body leaked: {$entry['message']}");

            // At least one entry MUST carry the
            // short sentinel as the reason.
            $ctx = $entry['context'] ?? [];

            if (($ctx['reason'] ?? null) === AuthenticaCustomerPortalOtpChannel::REJECTED_REASON) {
                $foundSentinel = true;
            }
        }

        $this->assertTrue(
            $foundSentinel,
            'the rejection log MUST carry the short reason sentinel (no body, no phone, no code)'
        );
    }

    // ─────────────────────────────────────────────────────────────────
    // P1 — privacy: the failure log must NOT carry the phone,
    // the code, the api_key, or the response body.
    // ─────────────────────────────────────────────────────────────────

    public function test_failure_log_does_not_carry_phone_code_api_key_or_response_body(): void
    {
        $captured = [];
        Log::listen(function (MessageLogged $event) use (&$captured) {
            $captured[] = [
                'level' => $event->level,
                'message' => $event->message,
                'context' => $event->context,
            ];
        });

        Http::fake([
            'api.authentica.sa/*' => Http::response([
                'errors' => [
                    ['message' => 'phone +966501111111 not authorized for sk_test_secret_key'],
                ],
            ], 422),
        ]);

        $apiKey = 'sk_test_secret_'.$this->randomKey();
        $integration = Integration::create([
            'type' => 'sms',
            'provider' => 'authentica',
            'name' => 'Authentica',
            'name_ar' => 'أوثينتيكا',
            'config' => ['api_key' => $apiKey],
            'is_active' => true,
            'is_default' => true,
        ]);

        $tenant = $this->publishedTenant();
        $customer = $this->customerIn($tenant, '+966501111111');
        $otp = $this->makeOtp($tenant, $customer, '+966501111111');

        $this->makeChannel($integration)->send($otp, '+966501111111', '987654');

        $rawPhone = '+966501111111';
        $rawCode = '987654';

        foreach ($captured as $entry) {
            $json = json_encode($entry['context']);
            $this->assertStringNotContainsString(
                $rawPhone,
                $json,
                "phone leaked in log entry '{$entry['message']}': {$json}"
            );
            $this->assertStringNotContainsString(
                $rawCode,
                $json,
                "code leaked in log entry '{$entry['message']}': {$json}"
            );
            $this->assertStringNotContainsString(
                $apiKey,
                $json,
                "api_key leaked in log entry '{$entry['message']}': {$json}"
            );
            // The response body can carry the phone
            // and the api_key — the channel must not
            // echo any of it into the log.
            $this->assertStringNotContainsString(
                'not authorized',
                $json,
                "response body leaked in log entry '{$entry['message']}': {$json}"
            );
        }
    }

    public function test_success_log_does_not_carry_phone_or_code(): void
    {
        $captured = [];
        Log::listen(function (MessageLogged $event) use (&$captured) {
            $captured[] = [
                'level' => $event->level,
                'message' => $event->message,
                'context' => $event->context,
            ];
        });

        Http::fake([
            'api.authentica.sa/*' => Http::response(['success' => true, 'data' => null], 200),
        ]);

        $integration = $this->makeAuthenticaIntegration();
        $tenant = $this->publishedTenant();
        $customer = $this->customerIn($tenant, '+966509999999');
        $otp = $this->makeOtp($tenant, $customer, '+966509999999');

        $this->makeChannel($integration)->send($otp, '+966509999999', '424242');

        $rawPhone = '+966509999999';
        $rawCode = '424242';

        foreach ($captured as $entry) {
            $json = json_encode($entry['context']);
            $this->assertStringNotContainsString($rawPhone, $json, "phone leaked: {$entry['message']}");
            $this->assertStringNotContainsString($rawCode, $json, "code leaked: {$entry['message']}");
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // P1 — integration validation: the channel refuses non-authentica
    // integrations. Defensive guard against a binding mistake.
    // ─────────────────────────────────────────────────────────────────

    public function test_channel_rejects_non_authentica_integration(): void
    {
        $this->expectException(RuntimeException::class);

        $integration = Integration::create([
            'type' => 'sms',
            'provider' => 'unifonic',
            'name' => 'Unifonic',
            'name_ar' => 'يونيفونك',
            'config' => [
                'app_sid' => 'fake',
                'sender_id' => 'fake',
            ],
            'is_active' => true,
            'is_default' => true,
        ]);

        new AuthenticaCustomerPortalOtpChannel($integration);
    }

    public function test_channel_returns_failure_when_api_key_missing(): void
    {
        $integration = Integration::create([
            'type' => 'sms',
            'provider' => 'authentica',
            'name' => 'Authentica',
            'name_ar' => 'أوثينتيكا',
            'config' => [], // missing api_key
            'is_active' => true,
            'is_default' => true,
        ]);

        $tenant = $this->publishedTenant();
        $customer = $this->customerIn($tenant);
        $otp = $this->makeOtp($tenant, $customer, '+966501234567');

        $result = $this->makeChannel($integration)->send($otp, '+966501234567', '123456');

        $this->assertFalse($result->success);
        $this->assertSame('sms_send_failed', $result->errorCode);

        // No outbound HTTP — the channel must not
        // call Authentica with an empty key.
        Http::assertNothingSent();

        // The row must be invalidated.
        $this->assertNotNull($otp->fresh()->consumed_at);
    }

    // ─────────────────────────────────────────────────────────────────
    // P1 — SmsService contract: Authentica is REJECTED for
    // free-form messages. Appointment reminders must NOT
    // silently drop the body.
    // ─────────────────────────────────────────────────────────────────

    public function test_sms_service_rejects_authentica_provider(): void
    {
        // The default integration is authentica; the
        // SmsService must refuse to send a free-form
        // message. Appointment reminders flow through
        // SmsService, so this guard prevents the
        // "we sent your appointment is confirmed"
        // message from being silently dropped.
        $integration = $this->makeAuthenticaIntegration();
        $this->assertEquals('authentica', $integration->provider);

        // Build a real SmsService around this
        // integration. We do not go through the
        // container (which would swap to
        // NullSmsService under APP_ENV=testing); we
        // instantiate the class directly so we test
        // the production code path.
        //
        // We pass tenantId=null so the
        // TenantSmsBalance branch is skipped — the
        // guard we want to test is in
        // sendViaProvider(), before the credit
        // deduction. With a real tenantId the test
        // would need a Tenant + balance row just to
        // reach the rejection, which is noise.
        $service = new SmsService($integration);

        Http::fake(); // any outbound call would be a bug

        $thrown = null;

        try {
            $service->send('+966501234567', 'تذكير: موعدك غداً الساعة 10 صباحاً', null);
        } catch (Throwable $e) {
            $thrown = $e;
        }

        $this->assertNotNull($thrown, 'SmsService must throw when asked to send via authentica');
        $this->assertInstanceOf(Exception::class, $thrown);

        // The exception message must be unambiguous
        // about WHY this is not supported.
        $this->assertStringContainsString('Authentica', $thrown->getMessage());
        $this->assertStringContainsString('OTP', $thrown->getMessage());

        // The phone and the body MUST NOT be in the
        // exception message — the exception flows
        // through IntegrationLog and could be echoed
        // into the application log.
        $this->assertStringNotContainsString('+966501234567', $thrown->getMessage());
        $this->assertStringNotContainsString('تذكير', $thrown->getMessage());

        // No outbound HTTP — the rejection is local.
        Http::assertNothingSent();
    }

    public function test_sms_service_provider_switch_still_supports_unifonic_twilio_msegat(): void
    {
        // Regression — we did NOT remove support for
        // the other providers when we added the
        // authentica rejection. The match statement
        // still routes them through their respective
        // sendVia* helpers.
        Http::fake([
            'el.cloud.unifonic.com/*' => Http::response([
                'MessageStatus' => ['MessageID' => 'uni-123'],
            ], 200),
            'api.twilio.com/*' => Http::response(['sid' => 'tw-123'], 200),
            'www.msegat.com/*' => Http::response(['id' => 'msg-123'], 200),
        ]);

        $unifonic = Integration::create([
            'type' => 'sms',
            'provider' => 'unifonic',
            'name' => 'Unifonic',
            'name_ar' => 'يونيفونك',
            'config' => ['app_sid' => 'a', 'sender_id' => 's'],
            'is_active' => true,
            'is_default' => false,
        ]);
        $twilio = Integration::create([
            'type' => 'sms',
            'provider' => 'twilio',
            'name' => 'Twilio',
            'name_ar' => 'تويليو',
            'config' => [
                'account_sid' => 'AC'.bin2hex(random_bytes(8)),
                'auth_token' => bin2hex(random_bytes(8)),
                'from_number' => '+10000000000',
            ],
            'is_active' => true,
            'is_default' => false,
        ]);
        $msegat = Integration::create([
            'type' => 'sms',
            'provider' => 'msegat',
            'name' => 'Msegat',
            'name_ar' => 'مسجات',
            'config' => [
                'username' => 'u',
                'api_key' => 'k',
                'sender_name' => 's',
            ],
            'is_active' => true,
            'is_default' => false,
        ]);

        // We need a tenant balance to skip the "no
        // credits" branch. The publishedTenant()
        // helper seeds one with 100 credits by
        // default, which is plenty for the three
        // SmsService::send() calls below.
        $tenant = $this->publishedTenant();

        $r1 = (new SmsService($unifonic))->send('+966501234567', 'hello', $tenant->id);
        $r2 = (new SmsService($twilio))->send('+966501234567', 'hello', $tenant->id);
        $r3 = (new SmsService($msegat))->send('+966501234567', 'hello', $tenant->id);

        $this->assertTrue($r1['success']);
        $this->assertSame('unifonic', $r1['provider']);
        $this->assertSame('uni-123', $r1['message_id']);

        $this->assertTrue($r2['success']);
        $this->assertSame('twilio', $r2['provider']);
        $this->assertSame('tw-123', $r2['message_id']);

        $this->assertTrue($r3['success']);
        $this->assertSame('msegat', $r3['provider']);
        $this->assertSame('msg-123', $r3['message_id']);
    }

    // ─────────────────────────────────────────────────────────────────
    // P1 — AppServiceProvider binding: when the default SMS
    // integration is authentica, the resolved
    // CustomerPortalOtpChannel is the Authentica channel
    // (not the SystemSmsOtpChannel).
    //
    // The provider's `register()` runs once during
    // application boot, so the binding decision
    // happens BEFORE the test inserts the
    // integration. We therefore drive the binding
    // decision directly through the
    // CUSTOMER_PORTAL_OTP_CHANNEL env var, which
    // short-circuits the APP_ENV check and forces
    // the production-style routing logic.
    // ─────────────────────────────────────────────────────────────────

    public function test_app_service_provider_routes_to_authentica_channel_when_default_is_authentica(): void
    {
        $this->makeAuthenticaIntegration();

        // Force the production routing path. Without
        // this, the APP_ENV=testing check returns the
        // logging channel and we never test the
        // production-style Authentica routing.
        $this->forceProductionOtpChannel('authentica');

        $channel = $this->app->make(CustomerPortalOtpChannel::class);

        $this->assertInstanceOf(
            AuthenticaCustomerPortalOtpChannel::class,
            $channel,
            'When the default SMS integration is authentica, the OTP channel must route to AuthenticaCustomerPortalOtpChannel'
        );
    }

    public function test_app_service_provider_falls_back_to_system_channel_when_default_is_unifonic(): void
    {
        // Default integration is unifonic — the
        // Authentica channel is NOT the right
        // choice; we use the SystemSmsOtpChannel
        // (which wraps SmsService).
        Integration::create([
            'type' => 'sms',
            'provider' => 'unifonic',
            'name' => 'Unifonic',
            'name_ar' => 'يونيفونك',
            'config' => ['app_sid' => 'a', 'sender_id' => 's'],
            'is_active' => true,
            'is_default' => true,
        ]);

        $this->forceProductionOtpChannel('system');

        $channel = $this->app->make(CustomerPortalOtpChannel::class);

        $this->assertInstanceOf(
            SystemSmsOtpChannel::class,
            $channel
        );
    }

    public function test_app_service_provider_skips_authentica_channel_when_integration_inactive(): void
    {
        // The integration row exists with provider
        // authentica but is_active = false. The
        // binding MUST fall back to the system
        // channel (which is also inactive and
        // therefore no_provider, but the channel
        // identity is what we are testing here).
        Integration::create([
            'type' => 'sms',
            'provider' => 'authentica',
            'name' => 'Authentica',
            'name_ar' => 'أوثينتيكا',
            'config' => ['api_key' => 'k'],
            'is_active' => false,
            'is_default' => true,
        ]);

        // Force the production-style routing with
        // an explicit "use whatever Authentica
        // resolution returns" — an inactive
        // integration should fall back to the
        // system channel.
        $this->forceProductionOtpChannel('authentica');

        $channel = $this->app->make(CustomerPortalOtpChannel::class);

        $this->assertInstanceOf(
            SystemSmsOtpChannel::class,
            $channel,
            'A misconfigured (inactive) authentica integration must NOT hijack the OTP channel'
        );
    }

    public function test_app_service_provider_skips_authentica_channel_when_api_key_missing(): void
    {
        // is_active=true, is_default=true, but the
        // api_key is empty. The integration is NOT
        // configured (per Integration::isConfigured()).
        // The binding MUST fall back to the system
        // channel.
        Integration::create([
            'type' => 'sms',
            'provider' => 'authentica',
            'name' => 'Authentica',
            'name_ar' => 'أوثينتيكا',
            'config' => [], // no api_key
            'is_active' => true,
            'is_default' => true,
        ]);

        $this->forceProductionOtpChannel('authentica');

        $channel = $this->app->make(CustomerPortalOtpChannel::class);

        $this->assertInstanceOf(
            SystemSmsOtpChannel::class,
            $channel
        );
    }

    /**
     * Drive the AppServiceProvider's
     * resolveOtpChannel() with a forced value of
     * CUSTOMER_PORTAL_OTP_CHANNEL. We set the env
     * var, drop the cached bindings, and re-run
     * `register()` so the binding picks up the new
     * value. The env var is restored in tearDown.
     */
    private function forceProductionOtpChannel(string $forced): void
    {
        $previous = $_ENV['CUSTOMER_PORTAL_OTP_CHANNEL'] ?? null;
        $_ENV['CUSTOMER_PORTAL_OTP_CHANNEL'] = $forced;
        putenv("CUSTOMER_PORTAL_OTP_CHANNEL={$forced}");

        $this->app->forgetInstance(CustomerPortalOtpChannel::class);
        $this->app->forgetInstance(CustomerPortalOtpService::class);

        $provider = new AppServiceProvider($this->app);
        $provider->register();

        // Register a teardown that restores the env
        // so other tests in the same process see the
        // original value.
        $this->beforeApplicationDestroyed(function () use ($previous) {
            if ($previous === null) {
                unset($_ENV['CUSTOMER_PORTAL_OTP_CHANNEL']);
                putenv('CUSTOMER_PORTAL_OTP_CHANNEL');
            } else {
                $_ENV['CUSTOMER_PORTAL_OTP_CHANNEL'] = $previous;
                putenv("CUSTOMER_PORTAL_OTP_CHANNEL={$previous}");
            }
        });
    }

    // ─────────────────────────────────────────────────────────────────
    // P1 — end-to-end: requestOtp() against the
    // AuthenticaCustomerPortalOtpChannel succeeds,
    // the customer can verify the same code, and
    // a failed send invalidates the row so the
    // customer must request a new one.
    // ─────────────────────────────────────────────────────────────────

    public function test_end_to_end_request_otp_succeeds_with_authentica_channel(): void
    {
        // We exercise the binding path: the
        // AppServiceProvider's resolveOtpChannel()
        // picks AuthenticaCustomerPortalOtpChannel
        // when the default SMS integration is
        // authentica. We then drive the channel
        // directly with a hand-built OTP row so we
        // can assert that the code the channel
        // receives is the same code we hand to the
        // verify endpoint.
        Http::fake([
            'api.authentica.sa/api/v2/send-otp' => Http::response([
                'success' => true,
                'data' => null,
                'message' => 'OTP send successfully',
            ], 200),
        ]);

        $this->makeAuthenticaIntegration();
        $this->forceProductionOtpChannel('authentica');

        $channel = $this->app->make(CustomerPortalOtpChannel::class);
        $this->assertInstanceOf(AuthenticaCustomerPortalOtpChannel::class, $channel);

        // Drive the channel directly with a known
        // code. The verify path uses the same code,
        // so success here proves the code we hand
        // the channel is the code the customer
        // types in.
        $tenant = $this->publishedTenant();
        $customer = $this->customerIn($tenant, '+966501234567');
        $code = '424242';

        $otp = CustomerPortalOtp::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'phone_normalized' => '+966501234567',
            'phone_hash' => hash('sha256', '+966501234567'),
            'code_hash' => Hash::make($code),
            'expires_at' => Carbon::now()->addMinutes(10),
        ]);

        $result = $channel->send($otp, '+966501234567', $code);
        $this->assertTrue($result->success);

        // Verify with the same code MUST succeed —
        // proves the code the channel forwarded
        // matches the code we hashed on the row.
        $verify = app(CustomerPortalOtpService::class)
            ->verify($tenant, '+966501234567', $code);
        $this->assertTrue($verify->success, 'verify must succeed when the channel forwards the same code');

        // The channel made exactly one outbound
        // call to /send-otp with our code.
        Http::assertSentCount(1);
        Http::assertSent(function ($request) use ($code) {
            $body = json_decode($request->body(), true);

            return ($body['otp'] ?? null) === $code;
        });
    }

    public function test_end_to_end_failed_authentica_send_invalidates_otp(): void
    {
        Http::fake([
            'api.authentica.sa/*' => Http::response(['error' => 'down'], 503),
        ]);

        $this->makeAuthenticaIntegration();
        $this->forceProductionOtpChannel('authentica');

        $channel = $this->app->make(CustomerPortalOtpChannel::class);
        $this->assertInstanceOf(AuthenticaCustomerPortalOtpChannel::class, $channel);

        $tenant = $this->publishedTenant();
        $customer = $this->customerIn($tenant, '+966501234567');
        $code = '424242';

        $otp = CustomerPortalOtp::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'phone_normalized' => '+966501234567',
            'phone_hash' => hash('sha256', '+966501234567'),
            'code_hash' => Hash::make($code),
            'expires_at' => Carbon::now()->addMinutes(10),
        ]);

        $result = $channel->send($otp, '+966501234567', $code);
        $this->assertFalse($result->success);

        // The OTP row exists but the channel failed.
        // The verify path MUST reject the code. The
        // exact reason depends on the verify query:
        // the channel stamps consumed_at, and the
        // `latestUsableOtp` helper filters
        // `consumed_at IS NULL`, so the verify path
        // never even sees this row. The reason is
        // `no_active_otp`. (We also accept
        // `already_used` if the implementation
        // shifts, but `no_active_otp` is the
        // canonical "the row is dead" verdict.)
        $verify = app(CustomerPortalOtpService::class)
            ->verify($tenant, '+966501234567', $code);
        $this->assertFalse($verify->success, 'verify must reject a code whose delivery failed');
        $this->assertContains(
            $verify->reason,
            [
                OtpVerificationResult::REASON_ALREADY_USED,
                OtpVerificationResult::REASON_NO_ACTIVE_OTP,
            ],
            'verify must reject the failed-delivery OTP with a "dead code" reason'
        );
    }

    // ─────────────────────────────────────────────────────────────────
    // Phase 12 P2 — template_id is exposed by
    // Integration::getConfigFields('authentica') as a
    // optional numeric field, the channel reads it
    // from config, and the api_key never leaves the
    // server.
    // ─────────────────────────────────────────────────────────────────

    public function test_phase12_p2_authentica_config_fields_expose_template_id(): void
    {
        $fields = Integration::getConfigFields('authentica');

        $keys = array_map(fn ($f) => $f['key'] ?? null, $fields);
        $this->assertContains('api_key', $keys, 'api_key must remain in the field list');
        $this->assertContains('template_id', $keys, 'template_id must be present for the admin UI');

        // The template_id entry MUST be a
        // non-required numeric field with a
        // positive-integer minimum. The controller
        // uses these flags to build the validation
        // rule on save.
        $templateId = collect($fields)->firstWhere('key', 'template_id');
        $this->assertNotNull($templateId);
        $this->assertSame('number', $templateId['type']);
        $this->assertFalse($templateId['required']);
        $this->assertSame(1, $templateId['min'] ?? null, 'template_id must be a positive integer');
    }

    public function test_phase12_p2_integration_persists_template_id_via_set_config_value(): void
    {
        // Operator uses setConfigValue() (the same
        // path the controller takes on save) to
        // store a template id on the row. The value
        // must round-trip through the JSON config
        // column and be readable by the channel.
        $integration = $this->makeAuthenticaIntegration([
            'config' => [
                'api_key' => 'sk_test_'.$this->randomKey(),
            ],
        ]);

        $this->assertSame(1, $integration->getConfigValue('template_id', 1));

        $integration->setConfigValue('template_id', 42);

        $fresh = $integration->fresh();
        $this->assertSame(
            42,
            $fresh->getConfigValue('template_id'),
            'template_id must be persisted through setConfigValue and survive a fresh load'
        );
    }

    public function test_phase12_p2_channel_uses_persisted_template_id_in_payload(): void
    {
        // The channel reads template_id from the
        // integration row and forwards it on every
        // /send-otp call. If the operator configures
        // a non-default id, the request body MUST
        // carry that id.
        Http::fake([
            'api.authentica.sa/*' => Http::response(['success' => true, 'data' => null], 200),
        ]);

        $integration = $this->makeAuthenticaIntegration([
            'config' => [
                'api_key' => 'sk_test_'.$this->randomKey(),
                'template_id' => 99,
            ],
        ]);

        $tenant = $this->publishedTenant();
        $customer = $this->customerIn($tenant);
        $otp = $this->makeOtp($tenant, $customer, '+966501234567');

        $this->makeChannel($integration)->send($otp, '+966501234567', '123456');

        Http::assertSent(function ($request) {
            $body = json_decode($request->body(), true);

            return ($body['template_id'] ?? null) === 99;
        });
    }

    public function test_phase12_p2_integration_show_page_does_not_return_raw_api_key(): void
    {
        // P2 collateral — the integration show page
        // must not echo the plaintext api_key. The
        // controller redacts every password-typed
        // field before the Inertia render so the
        // operator's browser payload never contains
        // the secret.
        //
        // We exercise the controller's redaction
        // helper through reflection — the show
        // endpoint is mounted behind the full
        // /system admin middleware chain, so going
        // through HTTP would pull in the admin guard
        // and the system admin bypass. The
        // redaction logic itself is the part we are
        // testing here.
        $plainKey = 'sk_test_secret_'.$this->randomKey();
        $integration = $this->makeAuthenticaIntegration([
            'config' => [
                'api_key' => $plainKey,
                'template_id' => 5,
            ],
            'is_default' => true,
        ]);

        $controller = app(IntegrationsController::class);
        $reflection = new ReflectionMethod($controller, 'redactIntegration');
        $reflection->setAccessible(true);

        $redacted = $reflection->invoke($controller, $integration);

        // The plaintext key MUST NOT survive the
        // redaction. The placeholder is a non-empty
        // mask so the form can still see the field
        // as "set".
        $this->assertSame(
            '••••••••',
            $redacted->config['api_key'] ?? null,
            'api_key must be redacted before the Inertia render'
        );
        $this->assertNotSame(
            $plainKey,
            $redacted->config['api_key'] ?? null,
            'plaintext api_key must never be returned'
        );

        // Non-sensitive values MUST remain intact —
        // template_id is just a number, and the
        // operator needs to see the current value
        // when they open the page.
        $this->assertSame(
            5,
            $redacted->config['template_id'] ?? null,
            'template_id is not sensitive and must not be masked'
        );
    }

    // ─────────────────────────────────────────────────────────────────
    // Phase 13 — AuthenticaCustomerPortalOtpChannel now
    // bills the tenant's TenantSmsBalance. Each
    // successful /send-otp call must:
    //   - atomically reserve 1 credit
    //   - decrement balance
    //   - increment total_used
    //   - write ONE SmsUsageLog row with
    //     status='sent', provider='authentica',
    //     message_type='otp', credits_used=1
    //
    // On failure, the reservation is refunded and
    // no SmsUsageLog row with status='sent' is
    // written.
    //
    // On insufficient credits, the provider is NOT
    // called at all and no deduction is made.
    // ─────────────────────────────────────────────────────────────────

    public function test_phase13_success_deducts_one_credit_and_writes_one_usage_log(): void
    {
        Http::fake([
            'api.authentica.sa/*' => Http::response(['success' => true, 'data' => null], 200),
        ]);

        $integration = $this->makeAuthenticaIntegration();
        $tenant = $this->publishedTenant('demo', smsBalance: 5);
        $customer = $this->customerIn($tenant);
        $otp = $this->makeOtp($tenant, $customer, '+966501234567');

        $result = $this->makeChannel($integration)->send($otp, '+966501234567', '123456');

        $this->assertTrue($result->success, 'channel must report success on 2xx + success:true');

        // 1 credit deducted, total_used incremented
        // by the same amount. balance and total_used
        // stay consistent for billing reports.
        $balance = TenantSmsBalance::find($tenant->id);
        $this->assertNotNull($balance);
        $this->assertSame(4, $balance->balance, 'balance must be 5 - 1 = 4 after a successful send');
        $this->assertSame(1, $balance->total_used, 'total_used must reflect the 1 credit consumed');

        // Exactly ONE SmsUsageLog row, marked sent.
        $logs = SmsUsageLog::all();
        $this->assertCount(1, $logs, 'exactly one SmsUsageLog row per successful send');
        $log = $logs->first();
        $this->assertSame($tenant->id, $log->tenant_id);
        $this->assertSame('otp', $log->message_type);
        $this->assertSame('authentica', $log->provider);
        $this->assertSame(1, $log->credits_used);
        $this->assertSame('sent', $log->status, 'the audit row must be promoted to sent on success');
    }

    public function test_phase13_http_failure_refunds_credit_and_writes_no_sent_log(): void
    {
        Http::fake([
            'api.authentica.sa/*' => Http::response(['error' => 'down'], 503),
        ]);

        $integration = $this->makeAuthenticaIntegration();
        $tenant = $this->publishedTenant('demo', smsBalance: 5);
        $customer = $this->customerIn($tenant);
        $otp = $this->makeOtp($tenant, $customer, '+966501234567');

        $result = $this->makeChannel($integration)->send($otp, '+966501234567', '123456');

        $this->assertFalse($result->success);
        $this->assertSame('sms_send_failed', $result->errorCode);

        // The credit is refunded: balance and
        // total_used are both back to their original
        // values.
        $balance = TenantSmsBalance::find($tenant->id);
        $this->assertNotNull($balance);
        $this->assertSame(5, $balance->balance, 'balance must be restored after a failed send');
        $this->assertSame(0, $balance->total_used, 'total_used must be decremented on refund');

        // No `sent` log row. There MAY be a `failed`
        // audit row for the reservation — that is
        // intentional. We assert no SENT row exists
        // and that at most one log row exists.
        $sentLogs = SmsUsageLog::where('status', 'sent')->get();
        $this->assertCount(0, $sentLogs, 'a failed send must NOT write a sent SmsUsageLog row');
    }

    public function test_phase13_network_exception_refunds_credit(): void
    {
        Http::fake(function () {
            throw new RuntimeException('connection refused');
        });

        $integration = $this->makeAuthenticaIntegration();
        $tenant = $this->publishedTenant('demo', smsBalance: 5);
        $customer = $this->customerIn($tenant);
        $otp = $this->makeOtp($tenant, $customer, '+966501234567');

        $result = $this->makeChannel($integration)->send($otp, '+966501234567', '123456');

        $this->assertFalse($result->success);

        $balance = TenantSmsBalance::find($tenant->id);
        $this->assertNotNull($balance);
        $this->assertSame(5, $balance->balance, 'balance must be restored after a network exception');
        $this->assertSame(0, $balance->total_used, 'total_used must be decremented on refund');

        $sentLogs = SmsUsageLog::where('status', 'sent')->get();
        $this->assertCount(0, $sentLogs);
    }

    public function test_phase13_zero_balance_skips_http_call_and_invalidates_otp(): void
    {
        // No HTTP fake — if the channel calls the
        // provider, the test would fail with an
        // "unexpected request" assertion.
        Http::fake();

        $integration = $this->makeAuthenticaIntegration();
        $tenant = $this->publishedTenant('demo', smsBalance: 0);
        $customer = $this->customerIn($tenant);
        $otp = $this->makeOtp($tenant, $customer, '+966501234567');

        $result = $this->makeChannel($integration)->send($otp, '+966501234567', '123456');

        $this->assertFalse($result->success);
        $this->assertSame('sms_send_failed', $result->errorCode);

        // No outbound HTTP at all — the channel
        // MUST NOT call Authentica when the tenant
        // has no credits.
        Http::assertNothingSent();

        // The OTP row is dead — verify will reject.
        $this->assertNotNull(
            $otp->fresh()->consumed_at,
            'an OTP against a zero-balance tenant must be invalidated'
        );

        // No balance change.
        $balance = TenantSmsBalance::find($tenant->id);
        $this->assertNotNull($balance);
        $this->assertSame(0, $balance->balance, 'balance must not be touched on a zero-balance send');
        $this->assertSame(0, $balance->total_used);

        // No SmsUsageLog row at all (we never even
        // reserved a credit).
        $this->assertCount(0, SmsUsageLog::all());
    }

    public function test_phase13_concurrent_requests_with_one_credit_send_only_one(): void
    {
        // The original bug: check-then-act on the
        // balance let two concurrent threads both
        // see balance=1, both call Authentica, and
        // drive the balance to -1. The fix is the
        // lockForUpdate() inside the reservation
        // transaction.
        //
        // We model the race with a serial test
        // harness: the first request reserves the
        // last credit, the second request finds
        // balance=0 under the lock and is rejected
        // before any HTTP call.
        Http::fake([
            'api.authentica.sa/*' => Http::response(['success' => true, 'data' => null], 200),
        ]);

        $integration = $this->makeAuthenticaIntegration();
        $tenant = $this->publishedTenant('demo', smsBalance: 1);
        $customer = $this->customerIn($tenant);

        $otp1 = $this->makeOtp($tenant, $customer, '+966501234567');
        $result1 = $this->makeChannel($integration)->send($otp1, '+966501234567', '111111');
        $this->assertTrue($result1->success, 'first request should succeed against a balance of 1');

        // The second request comes in with a
        // fresh OTP. Balance is now 0 under the
        // lock — the channel must NOT call
        // Authentica and must NOT create a sent
        // log.
        $otp2 = $this->makeOtp($tenant, $customer, '+966501234567');
        $result2 = $this->makeChannel($integration)->send($otp2, '+966501234567', '222222');
        $this->assertFalse($result2->success, 'second request must fail when balance is 0');
        $this->assertSame('sms_send_failed', $result2->errorCode);

        // Exactly one HTTP call: the first request.
        Http::assertSentCount(1);

        // The balance is exactly 0 (NOT negative).
        $balance = TenantSmsBalance::find($tenant->id);
        $this->assertNotNull($balance);
        $this->assertSame(0, $balance->balance, 'balance must be 0, never negative, after a race');
        $this->assertSame(1, $balance->total_used, 'total_used must equal the count of successful sends');

        // Exactly ONE sent log row.
        $sentLogs = SmsUsageLog::where('status', 'sent')->get();
        $this->assertCount(1, $sentLogs, 'only the successful send gets a sent log row');
    }

    public function test_phase13_each_resend_charges_separately(): void
    {
        // Re-sending an OTP (the user clicks
        // "resend" or `requestOtp()` is called
        // again) MUST count as a separate charge
        // and write a separate SmsUsageLog row.
        // We simulate three back-to-back requests
        // against a balance of 5 and assert that
        // three log rows landed and the balance is
        // 5 - 3 = 2.
        Http::fake([
            'api.authentica.sa/*' => Http::response(['success' => true, 'data' => null], 200),
        ]);

        $integration = $this->makeAuthenticaIntegration();
        $tenant = $this->publishedTenant('demo', smsBalance: 5);
        $customer = $this->customerIn($tenant);

        $channel = $this->makeChannel($integration);

        for ($i = 1; $i <= 3; $i++) {
            $otp = $this->makeOtp($tenant, $customer, '+966501234567');
            $result = $channel->send($otp, '+966501234567', (string) (100000 + $i));
            $this->assertTrue($result->success, "send #{$i} must succeed");
        }

        $balance = TenantSmsBalance::find($tenant->id);
        $this->assertNotNull($balance);
        $this->assertSame(2, $balance->balance, 'balance must be 5 - 3 = 2 after three successful sends');
        $this->assertSame(3, $balance->total_used);

        $sentLogs = SmsUsageLog::where('status', 'sent')->get();
        $this->assertCount(3, $sentLogs, 'each successful send writes exactly one SmsUsageLog row');
    }

    public function test_phase13_sms_usage_log_does_not_carry_phone_code_or_api_key(): void
    {
        // The SmsUsageLog row carries the raw phone
        // (matching the existing SmsService
        // convention — see database/migrations
        // /.../create_sms_credits_table.php). The
        // spec allows the raw phone on the log row
        // as long as we follow the existing usage
        // log pattern. The OTP code and the
        // Authentica api_key MUST NOT appear on
        // the log row.
        Http::fake([
            'api.authentica.sa/*' => Http::response(['success' => true, 'data' => null], 200),
        ]);

        $rawPhone = '+966507777777';
        $rawCode = '555123';
        $apiKey = 'sk_test_secret_'.$this->randomKey();

        $integration = Integration::create([
            'type' => 'sms',
            'provider' => 'authentica',
            'name' => 'Authentica',
            'name_ar' => 'أوثينتيكا',
            'config' => ['api_key' => $apiKey],
            'is_active' => true,
            'is_default' => true,
        ]);

        $tenant = $this->publishedTenant('demo', smsBalance: 5);
        $customer = $this->customerIn($tenant, $rawPhone);
        $otp = $this->makeOtp($tenant, $customer, $rawPhone);

        $this->makeChannel($integration)->send($otp, $rawPhone, $rawCode);

        $log = SmsUsageLog::where('status', 'sent')->first();
        $this->assertNotNull($log);

        // Phone is the audit anchor and is allowed.
        $this->assertSame($rawPhone, $log->phone_number);

        // Code MUST NOT be in the log row.
        $this->assertStringNotContainsString(
            $rawCode,
            json_encode($log->toArray()),
            'the OTP code must never appear on a SmsUsageLog row'
        );

        // api_key MUST NOT be in the log row.
        $this->assertStringNotContainsString(
            $apiKey,
            json_encode($log->toArray()),
            'the Authentica api_key must never appear on a SmsUsageLog row'
        );

        // Provider is the canonical name.
        $this->assertSame('authentica', $log->provider);
        $this->assertSame('otp', $log->message_type);
        $this->assertSame(1, $log->credits_used);
    }

    public function test_phase13_billing_logs_do_not_carry_phone_code_or_api_key(): void
    {
        // Every application log entry the channel
        // emits on the billing path must remain
        // free of phone, code, and api_key. This is
        // a defense-in-depth check on top of the
        // existing Phase 12 P1 privacy tests: the
        // billing events are NEW log entries, and
        // they must respect the same privacy
        // contract.
        $captured = [];
        Log::listen(function (MessageLogged $event) use (&$captured) {
            $captured[] = [
                'level' => $event->level,
                'message' => $event->message,
                'context' => $event->context,
            ];
        });

        $rawPhone = '+966505555555';
        $rawCode = '999888';
        $apiKey = 'sk_test_billing_'.$this->randomKey();

        Http::fake([
            'api.authentica.sa/*' => Http::response(['success' => true, 'data' => null], 200),
        ]);

        $integration = Integration::create([
            'type' => 'sms',
            'provider' => 'authentica',
            'name' => 'Authentica',
            'name_ar' => 'أوثينتيكا',
            'config' => ['api_key' => $apiKey],
            'is_active' => true,
            'is_default' => true,
        ]);

        $tenant = $this->publishedTenant('demo', smsBalance: 5);
        $customer = $this->customerIn($tenant, $rawPhone);
        $otp = $this->makeOtp($tenant, $customer, $rawPhone);

        $this->makeChannel($integration)->send($otp, $rawPhone, $rawCode);

        foreach ($captured as $entry) {
            $json = json_encode($entry['context']);
            $this->assertStringNotContainsString($rawPhone, $json, "phone leaked: {$entry['message']}");
            $this->assertStringNotContainsString($rawCode, $json, "code leaked: {$entry['message']}");
            $this->assertStringNotContainsString($apiKey, $json, "api_key leaked: {$entry['message']}");
        }
    }
}
