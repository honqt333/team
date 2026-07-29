<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\CustomerPortalOtp;

/**
 * Contract for sending a one-time password to a customer
 * on the public portal.
 *
 * Why an interface
 * ----------------
 * Phase 7 explicitly forbids any real SMS / WhatsApp
 * integration. We need a swappable channel so:
 *
 *   1. Tests can plug in a "capture" channel that just
 *      records the OTP + phone, then assert against it.
 *   2. Production can plug in a real provider later
 *      (Moyasar SMS gateway, Unifonic, etc.) without
 *      touching the service or the controllers.
 *
 * The contract is intentionally tiny. Implementations
 * MUST NOT throw on transient delivery failure (network
 * timeout, etc.) — the OTP row is already committed and
 * the user will be told "we sent you a code" regardless.
 * Permanent failures (invalid phone) should be reported
 * via the returned SmsResult so the caller can decide
 * what to do (typically: log and return generic "we sent
 * a code if your number is registered" to the user).
 */
interface CustomerPortalOtpChannel
{
    /**
     * @param CustomerPortalOtp $otp the persisted OTP row
     *                               (code_hash is NOT included;
     *                               the channel receives the
     *                               plain code as a separate
     *                               argument)
     * @param string $phone the destination phone, in
     *                      normalized form
     * @param string $code the 6-digit code (plain text,
     *                     provided by the service ONLY
     *                     at this call site)
     */
    public function send(CustomerPortalOtp $otp, string $phone, string $code): SmsSendResult;
}
