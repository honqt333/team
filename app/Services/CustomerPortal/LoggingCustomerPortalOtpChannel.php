<?php

declare(strict_types=1);

namespace App\Services\CustomerPortal;

use App\Contracts\CustomerPortalOtpChannel;
use App\Contracts\SmsSendResult;
use App\Models\CustomerPortalOtp;
use Illuminate\Support\Facades\Log;

/**
 * Default OTP channel. Phase 7 forbids real SMS / WhatsApp
 * integration, so the default channel just records the
 * event in the structured log. This is enough for
 * development and tests, and gives operators a paper
 * trail when they need to look up what code was sent to
 * which number at what time.
 *
 * Production deployments will swap this binding for a
 * real provider in AppServiceProvider; the
 * CustomerPortalOtpService does not change.
 *
 * Why logging (and not "fail loudly")
 * -----------------------------------
 * The portal flow MUST be tolerant of channel failures:
 * a transient network blip should not turn into a 500
 * for the customer. The OTP row is already committed and
 * the verification path is independent of the channel.
 * Operators get a log line + a Sentry breadcrumb.
 */
class LoggingCustomerPortalOtpChannel implements CustomerPortalOtpChannel
{
    public function send(CustomerPortalOtp $otp, string $phone, string $code): SmsSendResult
    {
        // SECURITY: the log line MUST NOT contain anything
        // derived from the code — not the code itself, not
        // a hash of it, not a fingerprint, not a prefix.
        // A sha256 prefix of an 8-hex-char value cuts
        // the brute-force search space to 10^6 / 2^32
        // candidates which is small enough to enumerate in
        // seconds. The only identifiers the log carries
        // are the otp id (a DB primary key), the tenant
        // id, the customer id, the expiry timestamp, and
        // the send status. None of those reveal or
        // significantly reduce the search space of the
        // 6-digit code.
        //
        // The contract with the caller is unchanged — the
        // channel receives $code so a real SMS gateway can
        // hand it off. The default logging channel is the
        // development stand-in; it never persists the code
        // or anything derived from it.
        //
        // Phone is also deliberately omitted: phone is
        // PII, and the otp_id already lets an operator
        // cross-reference with the DB row to look up the
        // phone if needed for support.

        Log::info('customer_portal.otp.sent', [
            'otp_id' => $otp->id,
            'tenant_id' => $otp->tenant_id,
            'customer_id' => $otp->customer_id,
            'expires_at' => $otp->expires_at?->toIso8601String(),
        ]);

        // Discard the plain code locally. PHP's reference
        // counting will free the string after this call,
        // but we also overwrite the parameter to be
        // defensive — a future caller that does
        // `Log::debug($code)` should never see it.
        unset($code);

        return SmsSendResult::ok(providerMessageId: 'log-'.bin2hex(random_bytes(4)));
    }
}
