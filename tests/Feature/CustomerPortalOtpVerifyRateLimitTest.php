<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\CustomerPortalOtpChannel;
use App\Contracts\SmsSendResult;
use App\Models\Customer;
use App\Models\CustomerPortalOtp;
use App\Models\Tenant;
use App\Models\TenantWebsite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Tests for the customer-portal OTP verify rate limit
 * (audit finding #16).
 *
 * THE BUG
 *
 * CustomerPortalOtpService throws OtpRateLimitedException when a
 * (tenant, phone) pair exceeds 10 verify attempts in 10 minutes.
 *
 * The request side handled it:
 *
 *     try { $this->otp->requestOtp(...); }
 *     catch (OtpRateLimitedException $e) { return back()->withErrors(...); }
 *
 * The verify side did not:
 *
 *     $result = $this->otp->verify(...);   // no try/catch
 *
 * So a customer who exhausted the attempt limit — the exact person
 * the protection is meant to slow down — got a 500 error page
 * instead of a "try again in a few minutes" message. The protection
 * itself worked; only the failure handling was missing.
 *
 * THE FIX
 *
 * verifyOtp() catches the exception and answers with the same generic
 * message the request side uses, plus the retry delay.
 */
class CustomerPortalOtpVerifyRateLimitTest extends TestCase
{
    use RefreshDatabase;

    private const PHONE = '0501234567';

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        self::$capturedCodeStatic = '';

        $this->app->instance(
            CustomerPortalOtpChannel::class,
            new class implements CustomerPortalOtpChannel
            {
                public function send(CustomerPortalOtp $otp, string $phone, string $code): SmsSendResult
                {
                    CustomerPortalOtpVerifyRateLimitTest::$capturedCodeStatic = $code;

                    return SmsSendResult::ok('fake');
                }
            }
        );
    }

    public static string $capturedCodeStatic = '';

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

    /**
     * The headline case: exhausting the verify limit must not produce
     * a server error.
     */
    public function test_exhausting_the_verify_limit_does_not_return_a_server_error(): void
    {
        $tenant = $this->publishedTenant();
        $this->customerIn($tenant);

        // Request a code so the verify endpoint has something to work
        // against.
        $this->post('/'.$tenant->slug.'/portal/login', ['phone' => self::PHONE])
            ->assertRedirect('/'.$tenant->slug.'/portal/verify');

        // 12 wrong codes. The service allows 10 verify attempts per
        // (tenant, phone); the 11th raises OtpRateLimitedException.
        for ($i = 0; $i < 12; $i++) {
            $response = $this->post('/'.$tenant->slug.'/portal/verify', [
                'phone' => self::PHONE,
                'code' => '000000',
            ]);

            $this->assertNotSame(
                500,
                $response->getStatusCode(),
                "The verify endpoint must not answer 500 (attempt {$i})."
            );
        }
    }

    /**
     * When the limit is reached the customer is told to wait, with
     * the generic message — and the retry delay is passed along.
     */
    public function test_the_rate_limit_answers_with_a_friendly_error_and_the_retry_delay(): void
    {
        $tenant = $this->publishedTenant();
        $this->customerIn($tenant);

        $this->post('/'.$tenant->slug.'/portal/login', ['phone' => self::PHONE]);

        for ($i = 0; $i < 10; $i++) {
            $this->post('/'.$tenant->slug.'/portal/verify', [
                'phone' => self::PHONE,
                'code' => '000000',
            ]);
        }

        // The 11th attempt crosses the limit.
        $response = $this->post('/'.$tenant->slug.'/portal/verify', [
            'phone' => self::PHONE,
            'code' => '000000',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('code');
        $response->assertSessionHas('otp_retry_after');

        $response->assertSessionHasErrors([
            'code' => __('customer_portal.errors.too_many_attempts'),
        ]);
    }

    /**
     * A wrong code that has NOT reached the limit keeps the ordinary
     * "invalid code" message — the rate-limit handling must not
     * swallow the normal failure path.
     */
    public function test_a_wrong_code_below_the_limit_still_reports_an_invalid_code(): void
    {
        $tenant = $this->publishedTenant();
        $this->customerIn($tenant);

        $this->post('/'.$tenant->slug.'/portal/login', ['phone' => self::PHONE]);

        $response = $this->post('/'.$tenant->slug.'/portal/verify', [
            'phone' => self::PHONE,
            'code' => '000000',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('code');

        $response->assertSessionHasErrors([
            'code' => __('customer_portal.auth.code_invalid'),
        ]);
    }
}
