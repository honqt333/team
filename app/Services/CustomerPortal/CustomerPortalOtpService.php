<?php

declare(strict_types=1);

namespace App\Services\CustomerPortal;

use App\Contracts\CustomerPortalOtpChannel;
use App\Contracts\SmsSendResult;
use App\Models\Customer;
use App\Models\CustomerPortalOtp;
use App\Models\Tenant;
use App\Support\PhoneNumber;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Customer-portal OTP service.
 *
 * Responsibilities:
 *
 *   1. Take a phone number + tenant context, find the
 *      customer (must already exist), and issue an OTP.
 *   2. Rate-limit by (tenant, phone) and by IP — both
 *      buckets must allow the request.
 *   3. Hash the OTP (Hash::make) and persist it. The
 *      plain-text code leaves the service only through
 *      the channel call; it is never logged here and
 *      never returned to the controller.
 *   4. On verify, compare the hash, increment the attempt
 *      counter, and return a structured verdict.
 *
 * The service is intentionally chatty about errors so
 * the controller can render a single, generic message
 * ("If your number is registered, we sent you a code").
 * We never tell the caller whether the phone is in the
 * system.
 */
class CustomerPortalOtpService
{
    /**
     * Per-tenant + phone rate limit: at most 5 OTPs in
     * 15 minutes. Wide enough for a forgetful user, tight
     * enough to make brute force infeasible.
     */
    private const RATE_LIMIT_PER_PHONE = 5;

    private const RATE_LIMIT_PER_PHONE_DECAY = 900; // 15 min

    /**
     * Per-IP rate limit: at most 20 OTPs in 15 minutes.
     * Stops a single IP from trying many phones. The
     * Laravel built-in RateLimiter gives us this for
     * free; the key is just the IP.
     */
    private const RATE_LIMIT_PER_IP = 20;

    private const RATE_LIMIT_PER_IP_DECAY = 900; // 15 min

    /**
     * Per-(tenant, phone) limit on verify attempts. Sits
     * between the route-level IP throttle (which only
     * blocks IP rotation within a small window) and the
     * OTP's own max_attempts (which is per-row, not per-
     * tenant+phone). 10 per 10 min lets a legitimate user
     * retry a few times (e.g. 2-3 OTPs of mistypes) while
     * making brute force infeasible across a 10-min window.
     */
    private const RATE_LIMIT_VERIFY_PER_PHONE = 10;

    private const RATE_LIMIT_VERIFY_PER_PHONE_DECAY = 600; // 10 min

    public function __construct(
        private readonly CustomerPortalOtpChannel $channel,
    ) {
        // The plain-text code is generated inside a
        // DB::transaction and must be carried across the
        // commit boundary so we can hand it to the
        // channel AFTER the lock is released. We hold
        // it on the instance rather than a closure
        // variable because the transaction closure
        // returns a value and the code is generated
        // inside it.
        $this->pendingCode = null;
    }

    /**
     * Plain-text code held across the transaction
     * boundary. Reset to null as soon as the channel
     * has consumed it. We never log this, never return
     * it, and never store it.
     */
    private ?string $pendingCode = null;

    /**
     * Issue a new OTP for a (tenant, phone) pair.
     *
     * Returns the SmsSendResult. The caller (controller)
     * does not branch on the result — the public message
     * is generic. The result is recorded for the audit
     * log and for the test-fake channel.
     *
     * If the phone is not registered as a customer of
     * the tenant, we still consume a rate-limit slot
     * (so the endpoint is not a free oracle for "is this
     * phone in the system") and return a synthetic
     * success result. The OTP is never sent. This keeps
     * the success message identical regardless of
     * enrollment.
     */
    public function requestOtp(
        Tenant $tenant,
        string $phoneInput,
        ?string $ip = null,
        ?string $userAgent = null,
    ): OtpRequestResult {
        $phone = PhoneNumber::normalize($phoneInput);

        if ($phone === null) {
            // Even malformed input consumes a rate slot so
            // attackers can't probe with garbage.
            $this->hitPhoneLimit($tenant, $phoneInput ?? 'invalid');
            $this->hitIpLimit($ip);

            return OtpRequestResult::noCustomer();
        }

        // Rate limit FIRST so the lookup is not a side
        // channel for "is this phone in the system".
        $this->hitPhoneLimit($tenant, $phone);
        $this->hitIpLimit($ip);

        $customer = $this->findCustomer($tenant, $phone);

        if ($customer === null) {
            // Privacy: the phone itself is PII and we do
            // NOT log it. phone_hash is sha256 of the
            // normalized phone — useless to an attacker
            // without the DB row, but the support team
            // can still cross-reference a log line with
            // the customer_portal_otps.phone_hash column.
            Log::info('customer_portal.otp.request.unknown_phone', [
                'tenant_id' => $tenant->id,
                'phone_hash' => hash('sha256', $phone),
                'ip' => $ip,
            ]);

            return OtpRequestResult::noCustomer();
        }

        // ATOMICITY. The four steps below MUST run inside
        // a single transaction with the customer row
        // locked. Without the lock, two concurrent
        // requests for the same customer can both pass
        // the cooldown check, both invalidate the
        // previous OTP, and both create a new one —
        // leaving the customer with two valid codes
        // and the rate-limit layer none the wiser.
        //
        // The lock is on the customer row because:
        //   1. The customer_id is the only stable cross-
        //      request anchor for this customer.
        //   2. Locking the otp rows would let two
        //      concurrent requests lock different rows
        //      and both proceed.
        //   3. lockForUpdate on a row that does not
        //      exist yet (the otp) is a no-op; the
        //      customer row is guaranteed to exist.
        //
        // We recheck the cooldown UNDER the lock so the
        // second request waits for the first to commit,
        // then sees the freshly-stamped last_sent_at
        // and returns throttled.
        $otp = null;
        $throttled = null;

        DB::transaction(function () use (
            $tenant, $customer, $phone, $ip, $userAgent,
            &$otp, &$throttled
        ) {
            // Re-read the customer row with a write lock.
            // Any concurrent requestOtp for the same
            // customer will block here until we commit
            // or roll back.
            $locked = Customer::query()
                ->whereKey($customer->id)
                ->where('tenant_id', $tenant->id)
                ->lockForUpdate()
                ->first();

            // If the row vanished between the lookup and
            // the lock, abort. The unlocked find was
            // good enough for the rate limit / phone
            // bucket; the lock is the source of truth.
            if ($locked === null) {
                $throttled = 'gone';

                return;
            }

            // Recheck cooldown under the lock. A second
            // request that was waiting for the lock
            // will now see the first one's last_sent_at.
            $recent = $this->latestUsableOtp($tenant, $phone);

            if ($recent !== null && $recent->last_sent_at !== null
                && $recent->last_sent_at->diffInSeconds(Carbon::now()) < CustomerPortalOtp::RESEND_COOLDOWN_SECONDS
            ) {
                $throttled = $recent->last_sent_at->copy()->addSeconds(CustomerPortalOtp::RESEND_COOLDOWN_SECONDS);

                return;
            }

            // Invalidate every outstanding OTP for this
            // (tenant, phone) pair, atomically with the
            // lock held. The verify path filters
            // `consumed_at IS NULL` so the rows are
            // immediately rejected.
            $invalidated = CustomerPortalOtp::query()
                ->where('tenant_id', $tenant->id)
                ->where('phone_normalized', $phone)
                ->whereNull('consumed_at')
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', Carbon::now());
                })
                ->update(['consumed_at' => Carbon::now()]);

            if ($invalidated > 0) {
                // Privacy: log only the phone_hash, not
                // the phone. Operators can join the
                // phone_hash against the otps table.
                Log::info('customer_portal.otp.previous_invalidated', [
                    'tenant_id' => $tenant->id,
                    'phone_hash' => hash('sha256', $phone),
                    'invalidated_count' => $invalidated,
                ]);
            }

            // Create the new OTP. The lock is still held;
            // commit happens when the closure returns.
            $code = $this->generateCode();
            $now = Carbon::now();

            $otp = CustomerPortalOtp::create([
                'tenant_id' => $tenant->id,
                'customer_id' => $customer->id,
                'phone_normalized' => $phone,
                'phone_hash' => hash('sha256', $phone),
                'code_hash' => Hash::make($code),
                'attempts' => 0,
                'max_attempts' => CustomerPortalOtp::DEFAULT_MAX_ATTEMPTS,
                'expires_at' => $now->copy()->addMinutes(CustomerPortalOtp::TTL_MINUTES),
                'ip' => $ip,
                'user_agent' => $userAgent !== null ? substr($userAgent, 0, 255) : null,
                'last_sent_at' => $now,
            ]);

            // Stash the code on a closure variable so
            // we can hand it to the channel AFTER the
            // commit. Doing the send inside the
            // transaction would risk an external call
            // (SMS gateway) holding a DB lock.
            $this->pendingCode = $code;
        });

        // The transaction has committed (or rolled back).
        // The lock is released. We can now talk to the
        // channel without holding a DB row.
        if ($throttled === 'gone') {
            // Customer row vanished. Treat as no-customer
            // — the rate limit and bucket consumption
            // already happened.
            return OtpRequestResult::noCustomer();
        }

        if ($throttled !== null) {
            // The cooldown recheck under the lock fired.
            // We do not send; the previous OTP (if any)
            // remains the only usable code.
            return OtpRequestResult::throttled($throttled);
        }

        if ($otp === null) {
            // Defensive: should not happen — every branch
            // above either returns or sets $otp. If we
            // get here, the DB transaction did not
            // produce a row. Treat as a hard failure so
            // the caller surfaces a generic error.
            return OtpRequestResult::noCustomer();
        }

        // Send the code AFTER commit. The DB row is
        // already visible to any concurrent reader, so
        // there is no observable window where the
        // caller has a "successful" response but the
        // row is not there. The channel may fail (a
        // real SMS gateway is down) — we log and
        // return success anyway because the row is
        // durable and the user can retry.
        $result = $this->channel->send($otp, $phone, $this->pendingCode ?? '');

        // Drop the reference to the plain code as
        // soon as the channel is done with it.
        $this->pendingCode = null;

        if (! $result->success) {
            // success to the caller — the row is
            // persisted and the user can retry.
            Log::warning('customer_portal.otp.channel_failed', [
                'otp_id' => $otp->id,
                'error_code' => $result->errorCode,
                'error_message' => $result->errorMessage,
            ]);
        }

        return OtpRequestResult::issued($otp, $result);
    }

    /**
     * Verify a code against the latest usable OTP for the
     * (tenant, phone) pair.
     *
     * Returns OtpVerificationResult. The caller decides
     * what to render. We never reveal whether the
     * customer exists.
     */
    public function verify(
        Tenant $tenant,
        string $phoneInput,
        string $code,
    ): OtpVerificationResult {
        $phone = PhoneNumber::normalize($phoneInput);

        if ($phone === null) {
            return OtpVerificationResult::failed(OtpVerificationResult::REASON_MALFORMED);
        }

        // Per-(tenant, phone) rate limit on verify attempts.
        // The route layer (throttle:customer-portal-verify)
        // already throttles per IP, but an attacker can
        // rotate IPs on a single (tenant, phone) and burn
        // through the OTP's attempt budget one wrong guess
        // at a time. This bucket is keyed on the
        // normalized phone, so brute force against a
        // specific account is capped regardless of IP.
        //
        // 10 attempts per 10 min: the OTP's own
        // max_attempts is 5, so a legitimate user who
        // mistypes a few times has plenty of headroom
        // (they would hit the OTP cap at 5 first), while
        // a brute-force attacker has to stop and wait
        // after 10 failed attempts.
        $this->hitVerifyLimit($tenant, $phone);

        $customer = $this->findCustomer($tenant, $phone);

        if ($customer === null) {
            return OtpVerificationResult::failed(OtpVerificationResult::REASON_NO_CUSTOMER);
        }

        $otp = $this->latestUsableOtp($tenant, $phone);

        if ($otp === null) {
            return OtpVerificationResult::failed(OtpVerificationResult::REASON_NO_ACTIVE_OTP);
        }

        if ($otp->expires_at !== null && $otp->expires_at->isPast()) {
            return OtpVerificationResult::failed(OtpVerificationResult::REASON_EXPIRED);
        }

        if ($otp->attempts >= $otp->max_attempts) {
            return OtpVerificationResult::failed(OtpVerificationResult::REASON_TOO_MANY_ATTEMPTS);
        }

        if (! Hash::check($code, $otp->code_hash)) {
            // Increment attempts and re-check; this is
            // what makes brute force infeasible.
            $otp->increment('attempts');

            if ($otp->fresh()->attempts >= $otp->max_attempts) {
                return OtpVerificationResult::failed(OtpVerificationResult::REASON_TOO_MANY_ATTEMPTS);
            }

            return OtpVerificationResult::failed(OtpVerificationResult::REASON_BAD_CODE);
        }

        // Mark consumed. We do this atomically with the
        // check so two parallel verify requests cannot
        // both succeed.
        $updated = DB::table('customer_portal_otps')
            ->where('id', $otp->id)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => Carbon::now()]);

        if ($updated === 0) {
            // Another concurrent request won. From the
            // caller's perspective, the code is already
            // used.
            return OtpVerificationResult::failed(OtpVerificationResult::REASON_ALREADY_USED);
        }

        return OtpVerificationResult::success($customer, $otp);
    }

    /**
     * Per-tenant + phone rate limit. The key includes the
     * tenant id so two tenants on the same IP cannot
     * affect each other's bucket.
     */
    private function hitPhoneLimit(Tenant $tenant, string $phone): void
    {
        $key = sprintf('customer_portal:phone:%d:%s', $tenant->id, sha1($phone));

        if (RateLimiter::tooManyAttempts($key, self::RATE_LIMIT_PER_PHONE)) {
            $seconds = RateLimiter::availableIn($key);

            throw new OtpRateLimitedException(
                __('customer_portal.errors.too_many_attempts'),
                $seconds
            );
        }
        RateLimiter::hit($key, self::RATE_LIMIT_PER_PHONE_DECAY);
    }

    /**
     * Per-IP rate limit. The key is the IP, the cap is
     * 20 in 15 minutes.
     */
    private function hitIpLimit(?string $ip): void
    {
        if ($ip === null || $ip === '') {
            return;
        }

        $key = 'customer_portal:ip:'.sha1($ip);

        if (RateLimiter::tooManyAttempts($key, self::RATE_LIMIT_PER_IP)) {
            $seconds = RateLimiter::availableIn($key);

            throw new OtpRateLimitedException(
                __('customer_portal.errors.too_many_attempts'),
                $seconds
            );
        }
        RateLimiter::hit($key, self::RATE_LIMIT_PER_IP_DECAY);
    }

    /**
     * Per-(tenant, phone) limit on verify attempts. Hits
     * a different bucket from hitPhoneLimit (which is
     * the request-side counter); this is the verify-side
     * counter. The two together mean a single (tenant,
     * phone) gets:
     *
     *   - hitPhoneLimit: 5 OTPs in 15 min
     *   - hitVerifyLimit: 10 verify attempts in 10 min
     *   - per-OTP:        5 wrong codes before the row
     *                     becomes unusable
     *
     * The first cuts brute force on the request endpoint;
     * the second cuts brute force on the verify endpoint;
     * the third ensures each OTP's own budget is bounded.
     */
    private function hitVerifyLimit(Tenant $tenant, string $phone): void
    {
        $key = sprintf('customer_portal:verify:%d:%s', $tenant->id, sha1($phone));

        if (RateLimiter::tooManyAttempts($key, self::RATE_LIMIT_VERIFY_PER_PHONE)) {
            $seconds = RateLimiter::availableIn($key);

            throw new OtpRateLimitedException(
                __('customer_portal.errors.too_many_attempts'),
                $seconds
            );
        }
        RateLimiter::hit($key, self::RATE_LIMIT_VERIFY_PER_PHONE_DECAY);
    }

    /**
     * Find a customer in this tenant by normalized phone
     * number. We compare against the stored phone using
     * the same normalization, so a user typing
     * "0501234567" or "501234567" or "+966501234567"
     * all match.
     */
    private function findCustomer(Tenant $tenant, string $normalizedPhone): ?Customer
    {
        $digitsOnly = PhoneNumber::lookupKey($normalizedPhone);

        if ($digitsOnly === null) {
            return null;
        }

        // We pull a small candidate set and then compare
        // with normalize() in PHP. The candidates filter
        // is `phone LIKE %digits%` which is a poor index
        // match in production but a fine placeholder for
        // a per-tenant scale. A real deployment would
        // add a stored lookup column or a hash column.
        $candidates = Customer::query()
            ->where('tenant_id', $tenant->id)
            ->whereNotNull('phone')
            ->get(['id', 'phone', 'whatsapp']);

        foreach ($candidates as $candidate) {
            foreach (['phone', 'whatsapp'] as $field) {
                $stored = $candidate->{$field};

                if (! $stored) {
                    continue;
                }

                if (PhoneNumber::lookupKey($stored) === $digitsOnly) {
                    return $candidate;
                }
            }
        }

        return null;
    }

    /**
     * Latest non-consumed, non-expired OTP for the pair.
     * Used for both "request" and "verify".
     */
    private function latestUsableOtp(Tenant $tenant, string $phone): ?CustomerPortalOtp
    {
        return CustomerPortalOtp::query()
            ->where('tenant_id', $tenant->id)
            ->where('phone_normalized', $phone)
            ->whereNull('consumed_at')
            ->where(function (Builder $q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', Carbon::now());
            })
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Cryptographically random 6-digit code, zero-padded.
     */
    private function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
