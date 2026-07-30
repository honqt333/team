<?php

declare(strict_types=1);

namespace App\Services\CustomerPortal;

use App\Contracts\CustomerPortalOtpChannel;
use App\Contracts\SmsSendResult;
use App\Models\CustomerPortalOtp;
use App\Services\Messaging\SmsService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use ReflectionClass;

/**
 * Production OTP delivery channel.
 *
 * Replaces the Phase 7 LoggingCustomerPortalOtpChannel
 * default in non-test environments. Wraps the existing
 * App\Services\Messaging\SmsService so we keep the
 * same provider / sender-id / balance logic the system
 * admin has already configured.
 *
 * The binding decision lives in AppServiceProvider —
 * `APP_ENV !== testing` selects this class, `testing`
 * selects the in-memory capture channel. The OTP
 * service and the controllers do not know which
 * implementation is active.
 *
 * Privacy
 * -------
 * The SmsService is responsible for the full OTP body
 * (the 6-digit code) being handed to the provider. We
 * deliberately do NOT log the body, the phone, or any
 * prefix of either. The SmsService itself writes a
 * SmsUsageLog row carrying the raw phone and message
 * length, which is acceptable for billing + audit but
 * the values are NOT echoed into the application log
 * here.
 *
 * Failure semantics — P1 critical
 * -------------------------------
 * The contract says implementations "MUST NOT throw
 * on transient delivery failure", but it does NOT say
 * "the OTP row must remain valid after a failure".
 * Phase 10 P1 closes that gap: a failure here is
 * treated as a hard failure for THIS row.
 *
 * Why a hard failure:
 *   - If the SmsService has no provider configured
 *     (no Integration row), there is no path by
 *     which this code can reach the customer's phone.
 *     The row must not be verifiable.
 *   - If the SmsService throws, the provider rejected
 *     the request (bad gateway response, missing
 *     credits, malformed phone). Same story.
 *   - If the SmsService returns a non-success
 *     result, the explicit `success => false` is the
 *     contract for "I am not going to send this".
 *     We honor that as a hard failure too.
 *
 * In every case above, the channel:
 *   1. Marks the OTP row as `consumed_at = now()`
 *      inside an atomic DB update. The verify path
 *      filters `consumed_at IS NULL` and will reject
 *      the code as `already_used`. (We do not
 *      introduce a new "send_failed" status on the
 *      OTP row because the verify path already treats
 *      `consumed_at` as the canonical "this code is
 *      dead" marker — see CustomerPortalOtpService::
 *      verify().)
 *   2. Returns SmsSendResult::fail(...) so the
 *      caller logs the failure (without leaking
 *      reason details to the customer).
 *   3. The HTTP response stays generic — the user
 *      sees the same "if your number is registered,
 *      we sent you a code" message regardless of
 *      success or failure. They can retry, which
 *      triggers a new OTP row.
 *
 * The user can request a fresh OTP, which the
 * CustomerPortalOtpService::requestOtp() handler
 * invalidates the dead row and creates a new one.
 * Net effect: a code that never made it to the
 * customer's phone is never usable.
 *
 * We deliberately do NOT log the exception's
 * message — the SmsService's exception body
 * (provider's HTTP response) can carry the phone.
 * We log only the short class name.
 */
class SystemSmsOtpChannel implements CustomerPortalOtpChannel
{
    public function __construct(
        private readonly SmsService $sms,
    ) {}

    public function send(CustomerPortalOtp $otp, string $phone, string $code): SmsSendResult
    {
        // The body. We hand the SmsService the OTP code
        // and let the provider replace the configured
        // sender id. We never log $code, never log
        // $phone, and never store $code after this
        // call returns. The SmsService writes its own
        // billing/audit log; this layer is intentionally
        // silent on success and writes a single
        // diagnostic line on failure.
        $body = __('customer_portal.otp.body', ['code' => $code]);

        // $code is held by the caller (the OTP service)
        // and discarded right after this call. We unset
        // the local copy here too — defense in depth so
        // any future caller that does Log::debug($code)
        // sees nothing.
        unset($code);

        // Defensive: if no SMS provider is configured,
        // this code can never reach the customer. Mark
        // the row consumed and return failure.
        if (! $this->sms->isReady()) {
            $this->invalidateOtp($otp, 'no_provider');
            Log::info('customer_portal.otp.no_provider_configured', [
                'otp_id' => $otp->id,
                'tenant_id' => $otp->tenant_id,
                'customer_id' => $otp->customer_id,
            ]);

            return SmsSendResult::fail('sms_send_failed', 'sms_send_failed');
        }

        try {
            $result = $this->sms->send($phone, $body, $otp->tenant_id);
        } catch (Exception $e) {
            $this->invalidateOtp($otp, 'send_threw');
            // SECURITY: log the otp id, the tenant id,
            // the customer id, and a short reason class.
            // NEVER the phone, NEVER the body, NEVER a
            // prefix of either. The SmsService's
            // exception message can include the
            // provider's HTTP body (which in turn can
            // include the phone). We must NOT echo it.
            // We log only the short class name of the
            // exception — the operator can look up the
            // full message in the IntegrationLog if
            // they need it.
            Log::warning('customer_portal.otp.system_channel_failed', [
                'otp_id' => $otp->id,
                'tenant_id' => $otp->tenant_id,
                'customer_id' => $otp->customer_id,
                'reason_class' => (new ReflectionClass($e))->getShortName(),
            ]);

            return SmsSendResult::fail('sms_send_failed', 'sms_send_failed');
        }

        // The SmsService returns
        //   ['success' => bool, 'message_id' => ?string, 'provider' => ?string]
        // A non-success result is the SmsService's
        // explicit "I did not send this" signal — same
        // outcome as a throw: the code never reached
        // the customer, the row must be invalidated.
        $success = $result['success'] ?? false;

        if (! $success) {
            $this->invalidateOtp($otp, 'send_returned_failure');
            Log::info('customer_portal.otp.system_channel_returned_failure', [
                'otp_id' => $otp->id,
                'tenant_id' => $otp->tenant_id,
                'customer_id' => $otp->customer_id,
            ]);

            return SmsSendResult::fail('sms_send_failed', 'sms_send_failed');
        }

        return SmsSendResult::ok(
            providerMessageId: $result['message_id'] !== null && $result['message_id'] !== ''
                ? (string) $result['message_id']
                : null,
        );
    }

    /**
     * P1: the OTP row must not remain valid if the
     * delivery was not confirmed by the provider.
     *
     * The verify path filters `consumed_at IS NULL`
     * (see CustomerPortalOtpService::verify()). By
     * stamping `consumed_at = now()` we make the
     * row immediately unverifiable. The next
     * `requestOtp()` call from the user will
     * invalidate this row (the request handler
     * already does that as part of its "invalidate
     * previous outstanding OTPs" step) and create
     * a fresh one with a new code.
     *
     * The update is conditional on `consumed_at IS
     * NULL` so we never re-stamp a row that was
     * already used by a verify. We do not
     * short-circuit the result; the user can always
     * get a new code on the next request.
     *
     * SECURITY: the `consumed_at` is the only
     * column we write. We do NOT write the failure
     * reason to the row; that lives in the
     * application log only, with no phone, no
     * provider body, no PII.
     */
    private function invalidateOtp(CustomerPortalOtp $otp, string $reason): void
    {
        $otp->newQuery()
            ->where('id', $otp->id)
            ->whereNull('consumed_at')
            ->update([
                'consumed_at' => Carbon::now(),
            ]);

        Log::info('customer_portal.otp.invalidated_after_send_failure', [
            'otp_id' => $otp->id,
            'tenant_id' => $otp->tenant_id,
            'customer_id' => $otp->customer_id,
            'reason' => $reason,
        ]);
    }
}
