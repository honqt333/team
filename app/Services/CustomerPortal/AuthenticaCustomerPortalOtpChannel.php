<?php

declare(strict_types=1);

namespace App\Services\CustomerPortal;

use App\Contracts\CustomerPortalOtpChannel;
use App\Contracts\SmsSendResult;
use App\Models\Credits\SmsUsageLog;
use App\Models\Credits\TenantSmsBalance;
use App\Models\CustomerPortalOtp;
use App\Models\Integration\Integration;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use ReflectionClass;
use RuntimeException;
use Throwable;

/**
 * P1 — Customer portal OTP channel that talks directly
 * to the Authentica v2 API for tenant-by-tenant
 * delivery.
 *
 * Why this exists in addition to SmsService
 * ----------------------------------------
 * Authentica v2 (https://docs.authentica.sa/api/v2)
 * exposes only ONE outbound endpoint: /send-otp. There
 * is NO general "send free-form SMS" endpoint in the
 * official OpenAPI spec. The /send-otp endpoint
 * accepts a `method` (sms|whatsapp|email), a `phone`
 * (or `email`), an optional `template_id`, and — per
 * the public Authentica SDK and the v2 API
 * documentation — an OPTIONAL `otp` field that lets
 * the caller pass a pre-generated code so the API
 * delivers OUR 6-digit code verbatim through the
 * registered template.
 *
 * That is the only correct path to use Authentica for
 * the customer portal, because:
 *
 *   1. Our portal stores and verifies the OTP
 *      INTERNALLY (Hash::make + CustomerPortalOtp
 *      row). If we let Authentica generate the code
 *      (via AuthenticaService::sendOtp), the provider
 *      and our internal hash would disagree and the
 *      user could never verify.
 *
 *   2. /send-otp is the ONLY documented Authentica
 *      endpoint that can push a short message to a
 *      phone. There is no other documented path. The
 *      channel therefore does NOT go through
 *      SmsService: SmsService's
 *      `sendViaProvider()->sendViaAuthentica()` is
 *      intentionally absent because Authentica
 *      cannot deliver the rendered "Your booking is
 *      confirmed" or "Your 24h reminder" messages we
 *      need for appointment workflows.
 *
 *   3. The api_key stays in the integration row's
 *      `config`; the channel does not log it.
 *
 * Billing — atomic reservation, refund-on-failure
 * -----------------------------------------------
 * Authentica's /send-otp is a one-credit-per-call
 * operation on the PROVIDER side, and that is also
 * how we charge the tenant: each successful send
 * deducts exactly one credit from the tenant's local
 * TenantSmsBalance and writes one SmsUsageLog row
 * with message_type=otp and provider=authentica.
 *
 * The reservation is ATOMIC. We do NOT do
 * "check balance, then call Authentica, then deduct"
 * because two concurrent requests against a balance
 * of 1 would both pass the check and both call the
 * provider, driving the balance to -1 silently.
 *
 * The contract is:
 *
 *   1. Inside a DB transaction, lock the tenant's
 *      TenantSmsBalance row with `lockForUpdate()`.
 *      If the row does not exist yet, create it at
 *      zero and re-read it under the lock (so the
 *      row we lock is the row we charge).
 *
 *   2. If `balance < 1` under the lock:
 *        - invalidate the OTP,
 *        - return SmsSendResult::fail,
 *        - do NOT call Authentica.
 *
 *   3. Otherwise decrement the balance by 1 and
 *      increment total_used by 1. Commit.
 *
 *   4. Call Authentica OUTSIDE the lock. The lock is
 *      released at commit time; we no longer need it
 *      because the row state is already persisted.
 *
 *   5. On Authentica failure (HTTP non-2xx, success:
 *      false, malformed body, network exception):
 *        - refund the reserved credit in a separate
 *          transaction (TenantSmsBalance::getOrCreate
 *          + decrement of total_used),
 *        - do NOT write a SmsUsageLog,
 *        - invalidate the OTP as before.
 *
 *   6. On Authentica success: write one SmsUsageLog
 *      row with status='sent', provider='authentica',
 *      message_type='otp', credits_used=1, and the
 *      raw phone (matching the existing
 *      SmsUsageLog::phone_number convention — see
 *      database/migrations/.../create_sms_credits_
 *      table.php). The log row is the audit trail
 *      for the deduction.
 *
 * Crash safety
 * ------------
 * The only window where a "silent" deduction could
 * occur is: reserve succeeds → process dies →
 * provider call never happens. We close that window
 * with a `reserved` SmsUsageLog row written
 * immediately after the commit, inside the same
 * commit. If the process dies, the operator sees a
 * `reserved` row that was never promoted to `sent`
 * and can refund it. The SmsUsageLog row is the
 * audit anchor for the reservation.
 *
 * Failure semantics — mirror of SystemSmsOtpChannel
 * --------------------------------------------------
 * On ANY non-acceptance we treat the OTP as
 * un-delivered and:
 *
 *   - refund the reserved credit,
 *   - stamp `consumed_at = now()` on the OTP row, so
 *     the verify path rejects the code as
 *     `already_used` (the verify query filters
 *     `consumed_at IS NULL`),
 *   - return SmsSendResult::fail(...),
 *   - log a short reason — NEVER the phone, NEVER
 *     the code, NEVER the response body, NEVER the
 *     api_key. The response body can echo the phone
 *     back; we do not propagate that into the
 *     application log.
 *
 * "Non-acceptance" is intentionally strict: an HTTP
 * 2xx is NOT sufficient on its own. We additionally
 * require the response body to be valid JSON with
 * `success === true` (see isAcceptedResponse()). This
 * closes the Phase 12 P1 hole where a 200 OK with
 * `success: false` (or a malformed body) was treated
 * as a successful send.
 *
 * Privacy
 * -------
 * The channel does NOT log the phone, the code, the
 * api_key, or the provider's response body. It only
 * logs the short class name of the exception, the
 * otp id, the tenant id, and the customer id — the
 * same shape SystemSmsOtpChannel uses. The
 * SmsUsageLog row DOES carry the raw phone, which
 * mirrors the existing SmsService behavior (the
 * legacy SmsUsageLog table has `phone_number` as a
 * raw string column). The migration to a hashed
 * phone is out of scope for this fix; the operator
 * can review the column on their own timeline.
 */
class AuthenticaCustomerPortalOtpChannel implements CustomerPortalOtpChannel
{
    /**
     * Authentica v2 base URL. Per the OpenAPI spec
     * (https://docs.authentica.sa/openapi.yaml) the
     * only production host is api.authentica.sa. Tests
     * use Http::fake() so this URL is never actually
     * called from a unit test.
     */
    protected string $baseUrl = 'https://api.authentica.sa/api/v2';

    /**
     * P1 — failure sentinel for the case where the
     * HTTP transport was successful (2xx) but the
     * response BODY did not satisfy the documented
     * Authentica v2 contract: either the JSON was
     * missing/unparseable, the `success` field was
     * absent, or `success` was explicitly `false`.
     *
     * Per the user spec, we treat this as a hard
     * failure (invalidate the OTP, return
     * SmsSendResult::fail) and we log only this short
     * sentinel — NEVER the response body, the phone,
     * the code, or the api_key. The body can echo
     * PII back to us and we must not propagate it.
     */
    public const REJECTED_REASON = 'authentica_response_rejected';

    /**
     * Default Authentica template id when the
     * operator has not configured one in the
     * integration row. Mirrors the v2 OpenAPI
     * default of 1.
     */
    private const DEFAULT_TEMPLATE_ID = 1;

    public function __construct(
        private readonly Integration $integration,
    ) {
        // Defensive: the binding decision lives in
        // AppServiceProvider::resolveOtpChannel() and
        // only routes here when the provider is
        // 'authentica' AND the integration has an
        // api_key. If a caller wires the wrong
        // integration by hand, surface that as a hard
        // failure rather than a silent 401 from
        // Authentica.
        if ($this->integration->provider !== 'authentica') {
            throw new RuntimeException(
                'AuthenticaCustomerPortalOtpChannel requires an authentica integration, got: '
                .$this->integration->provider
            );
        }
    }

    public function send(CustomerPortalOtp $otp, string $phone, string $code): SmsSendResult
    {
        // Defense in depth: $code is the plain 6-digit
        // OTP. We do NOT log it. We unset the local
        // reference after the API call so a future
        // Log::debug($code) that lands in this method
        // would not see it.
        $apiKey = (string) ($this->integration->config['api_key'] ?? '');
        $templateId = (int) ($this->integration->config['template_id'] ?? self::DEFAULT_TEMPLATE_ID);

        if ($apiKey === '') {
            // Missing config is a misconfiguration, not
            // a transient failure. We treat it the same
            // way SystemSmsOtpChannel treats a missing
            // provider: invalidate the row + return
            // generic failure.
            $this->invalidateOtp($otp, 'no_api_key');

            Log::warning('customer_portal.otp.authentica_no_api_key', [
                'otp_id' => $otp->id,
                'tenant_id' => $otp->tenant_id,
                'customer_id' => $otp->customer_id,
            ]);

            return SmsSendResult::fail('sms_send_failed', 'sms_send_failed');
        }

        // ─── Reservation ───────────────────────────────────────
        // Atomically lock the tenant's balance row, check
        // it has at least 1 credit, and decrement it.
        // Anything below this point that wants to talk
        // to the provider MUST come after a successful
        // reservation. The lock is released at commit
        // time; we deliberately do not hold it across
        // the HTTP call.
        $reservation = $this->reserveCredit($otp);

        if ($reservation === null) {
            // The reservation helper has already
            // invalidated the OTP. We did not touch the
            // provider, we did not touch the balance,
            // and no SmsUsageLog row was written.
            return SmsSendResult::fail('sms_send_failed', 'sms_send_failed');
        }

        $reservedAt = $reservation['reserved_at'];
        $reservedLogId = $reservation['usage_log_id'];

        $startTime = microtime(true);

        try {
            // The Authentica v2 /send-otp endpoint. The
            // body shape below is the union of the
            // public OpenAPI spec (method, phone,
            // template_id) and the documented `otp`
            // field that lets us pass our OWN code so
            // the internal hash stays authoritative.
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-Authorization' => $apiKey,
            ])->post("{$this->baseUrl}/send-otp", [
                'method' => 'sms',
                'phone' => $phone,
                'template_id' => $templateId,
                'otp' => $code,
            ]);

            // P1 — must not treat a 2xx with
            // success:false (or a 2xx with no
            // `success` field at all) as a successful
            // send. Authentica's v2 contract says the
            // caller should look at `success === true`
            // inside the JSON body. The previous
            // implementation accepted anything that
            // returned HTTP 2xx, which means a
            // provider-side "OTP rejected" reply
            // (success:false) would have left a code
            // that never reached the customer marked
            // as deliverable.
            $accepted = $this->isAcceptedResponse($response);

            if (! $accepted) {
                // The provider rejected the call. We
                // already deducted 1 credit; refund it
                // and invalidate the OTP. The
                // `reserved` SmsUsageLog row is the
                // audit trail for the reservation —
                // we mark it `failed` so the operator
                // sees the lifecycle.
                $this->refundReservation($otp, $reservedLogId, self::REJECTED_REASON);
                $this->invalidateOtp($otp, self::REJECTED_REASON);

                // SECURITY: log only short
                // identifiers. NEVER the response body
                // (it can echo the phone or the
                // api_key), NEVER the phone, NEVER the
                // code, NEVER the api_key.
                Log::warning('customer_portal.otp.authentica_channel_rejected', [
                    'otp_id' => $otp->id,
                    'tenant_id' => $otp->tenant_id,
                    'customer_id' => $otp->customer_id,
                    'reason' => self::REJECTED_REASON,
                    'response_time_ms' => (int) ((microtime(true) - $startTime) * 1000),
                ]);

                return SmsSendResult::fail('sms_send_failed', 'sms_send_failed');
            }

            // Unset the local copy of $code. Authentica
            // has the message now; we no longer need the
            // variable.
            unset($code);

            // Promote the reserved row to `sent` and
            // attach the provider_message_id (which is
            // null for Authentica — the v2 contract
            // does not echo an id back). The SmsUsageLog
            // row IS the audit trail that connects the
            // deduction to the successful send.
            $this->markReservationSent($reservedLogId, $reservedAt);

            // Authentica's success body is
            // {"success": true, "data": null|{...}, "message": "..."}.
            // We do NOT echo the body into the
            // application log; only a short
            // confirmation. The response body can
            // include the phone and the user-visible
            // message, both of which are PII.
            Log::info('customer_portal.otp.authentica_sent', [
                'otp_id' => $otp->id,
                'tenant_id' => $otp->tenant_id,
                'customer_id' => $otp->customer_id,
                'response_time_ms' => (int) ((microtime(true) - $startTime) * 1000),
            ]);

            return SmsSendResult::ok(null);
        } catch (Throwable $e) {
            // Network exception, DNS failure, timeout,
            // TLS error — anything that prevents the
            // call from completing. The customer's
            // phone never received a code, so the row
            // is dead. Refund the credit we reserved.
            $this->refundReservation($otp, $reservedLogId, 'authentica_threw');
            $this->invalidateOtp($otp, 'authentica_threw');

            // SECURITY: log only the short class name.
            // Authentica's exception body can include
            // the phone or the api_key. We do NOT echo
            // $e->getMessage().
            Log::warning('customer_portal.otp.authentica_channel_threw', [
                'otp_id' => $otp->id,
                'tenant_id' => $otp->tenant_id,
                'customer_id' => $otp->customer_id,
                'reason_class' => (new ReflectionClass($e))->getShortName(),
            ]);

            return SmsSendResult::fail('sms_send_failed', 'sms_send_failed');
        }
    }

    /**
     * Atomically reserve one credit from the tenant's
     * SMS balance. Returns the new usage log id and
     * the timestamp the row was created at, or null
     * if the tenant has insufficient credits.
     *
     * Inside a single transaction we:
     *   1. SELECT … FOR UPDATE on the tenant's
     *      TenantSmsBalance row. If the row does not
     *      exist, INSERT a zero-balance row and
     *      re-read it under the lock so we charge
     *      the row we just created.
     *   2. Check `balance >= 1`. If not, return
     *      null (caller invalidates the OTP and
     *      returns failure).
     *   3. Decrement `balance` by 1, increment
     *      `total_used` by 1. Insert a SmsUsageLog
     *      row with status='reserved' so the
     *      reservation is auditable even if the
     *      process dies before the HTTP call.
     *   4. Commit.
     *
     * The two concurrent-request case (both threads
     * see balance=1, both pass the check, both call
     * the provider) is impossible here because both
     * threads serialize on the row lock. Only one
     * thread can hold `balance=1`; the other sees
     * `balance=0` after the first commits and is
     * rejected.
     */
    private function reserveCredit(CustomerPortalOtp $otp): ?array
    {
        try {
            $result = DB::transaction(function () use ($otp) {
                // 1. Lock or create the balance row.
                //    `lockForUpdate()` requires the row
                //    to exist in the same transaction,
                //    so we ensure that with
                //    `getOrCreate` first (outside the
                //    lock), then re-read under the
                //    lock. This is the documented
                //    Laravel pattern for
                //    "lock-or-create".
                TenantSmsBalance::getOrCreate($otp->tenant_id);

                /** @var TenantSmsBalance|null $balance */
                $balance = TenantSmsBalance::query()
                    ->where('tenant_id', $otp->tenant_id)
                    ->lockForUpdate()
                    ->first();

                if ($balance === null) {
                    // Defensive: the row should exist
                    // because we just created it. If it
                    // does not, refuse the reservation
                    // rather than racing the create.
                    return null;
                }

                if ($balance->balance < 1) {
                    return null;
                }

                $balance->decrement('balance', 1);
                $balance->increment('total_used', 1);

                // Write the audit row inside the same
                // transaction so the deduction and the
                // audit are atomic. We mark the row
                // `reserved`; the success path promotes
                // it to `sent`, the failure path
                // promotes it to `failed` and refunds
                // the credit. The status column is the
                // lifecycle of the reservation.
                $reservedAt = Carbon::now();
                $log = SmsUsageLog::create([
                    'tenant_id' => $otp->tenant_id,
                    'phone_number' => $otp->phone_normalized,
                    'message_type' => 'otp',
                    'credits_used' => 1,
                    'provider' => 'authentica',
                    'provider_message_id' => null,
                    'status' => 'reserved',
                    'error_message' => null,
                ]);

                return [
                    'usage_log_id' => $log->id,
                    'reserved_at' => $reservedAt,
                ];
            });
        } catch (Throwable $e) {
            // The transaction itself blew up. We did
            // not touch Authentica and the balance is
            // untouched (Laravel rolls back the whole
            // transaction on Throwable). Invalidate
            // the OTP and bail.
            Log::warning('customer_portal.otp.authentica_reservation_failed', [
                'otp_id' => $otp->id,
                'tenant_id' => $otp->tenant_id,
                'customer_id' => $otp->customer_id,
                'reason_class' => (new ReflectionClass($e))->getShortName(),
            ]);

            $this->invalidateOtp($otp, 'reservation_failed');

            return null;
        }

        if ($result === null) {
            // Insufficient credits. The row is locked
            // and decremented only when we have
            // balance; this branch is the "no
            // credits" branch.
            $this->invalidateOtp($otp, 'insufficient_credits');
            Log::info('customer_portal.otp.authentica_insufficient_credits', [
                'otp_id' => $otp->id,
                'tenant_id' => $otp->tenant_id,
                'customer_id' => $otp->customer_id,
            ]);

            return null;
        }

        return $result;
    }

    /**
     * Refund a reserved credit after a failed
     * Authentica call. Runs in a fresh transaction
     * because the reservation transaction is
     * already committed. The refund re-locks the
     * balance row and rolls back the deduction;
     * total_used is decremented in lockstep so the
     * "purchased / used / balance" triple stays
     * consistent for billing reports.
     *
     * The SmsUsageLog row created at reservation
     * time is marked `failed` with the short
     * reason — the operator can grep for it later
     * to reconcile provider-side vs. system-side
     * counts.
     */
    private function refundReservation(CustomerPortalOtp $otp, int $usageLogId, string $reason): void
    {
        try {
            DB::transaction(function () use ($otp, $usageLogId, $reason) {
                /** @var TenantSmsBalance|null $balance */
                $balance = TenantSmsBalance::query()
                    ->where('tenant_id', $otp->tenant_id)
                    ->lockForUpdate()
                    ->first();

                if ($balance !== null) {
                    $balance->increment('balance', 1);
                    $balance->decrement('total_used', 1);
                }

                SmsUsageLog::query()
                    ->where('id', $usageLogId)
                    ->where('tenant_id', $otp->tenant_id)
                    ->where('status', 'reserved')
                    ->update([
                        'status' => 'failed',
                        'error_message' => $reason,
                        'updated_at' => Carbon::now(),
                    ]);
            });
        } catch (Throwable $e) {
            // The refund itself failed. We log the
            // short class name only — never the
            // exception body. The audit log row is
            // still `reserved`; the operator can
            // reconcile by comparing Authentica's
            // dashboard to the local SmsUsageLog.
            Log::warning('customer_portal.otp.authentica_refund_failed', [
                'otp_id' => $otp->id,
                'tenant_id' => $otp->tenant_id,
                'customer_id' => $otp->customer_id,
                'usage_log_id' => $usageLogId,
                'reason' => $reason,
                'reason_class' => (new ReflectionClass($e))->getShortName(),
            ]);
        }
    }

    /**
     * Promote the `reserved` SmsUsageLog row to
     * `sent` after Authentica acknowledged the
     * delivery. We do NOT touch the balance again —
     * the deduction already happened at reservation
     * time and the row's `credits_used` reflects the
     * actual charge.
     */
    private function markReservationSent(int $usageLogId, Carbon $reservedAt): void
    {
        SmsUsageLog::query()
            ->where('id', $usageLogId)
            ->where('status', 'reserved')
            ->update([
                'status' => 'sent',
                'error_message' => null,
                'updated_at' => Carbon::now(),
            ]);
    }

    /**
     * P1 — accept the Authentica v2 response ONLY when
     *   1. the HTTP transport returned 2xx, AND
     *   2. the response body parses as JSON, AND
     *   3. the JSON has a `success` field that is
     *      exactly boolean true.
     *
     * Anything else (non-2xx, empty body, malformed
     * JSON, missing `success`, or `success: false`) is
     * treated as a hard failure. The caller is
     * responsible for invalidating the OTP row and
     * surfacing the failure — this helper just
     * answers the yes/no question.
     *
     * SECURITY: this helper does NOT log anything. The
     * response body can echo the phone or the api_key
     * back to us and we must not propagate it. Logging
     * is the caller's job, and it logs only the short
     * reason sentinel.
     */
    private function isAcceptedResponse(Response $response): bool
    {
        if (! $response->successful()) {
            return false;
        }

        // `json()` returns null on a non-JSON body or
        // an empty body. Authentica's documented
        // success body is JSON; any other shape is a
        // contract violation.
        $body = $response->json();

        if (! is_array($body)) {
            return false;
        }

        // The `success` field MUST exist and MUST be
        // exactly boolean true. `1`, `"true"`, or
        // `null` are all rejected — the contract is
        // explicit.
        $success = $body['success'] ?? null;

        return $success === true;
    }

    /**
     * Mark the OTP row as consumed so the verify path
     * rejects the code. The UPDATE is conditional on
     * `consumed_at IS NULL` so a row that was already
     * marked-consumed (e.g. by a concurrent verify
     * racing with the failed send) is not re-stamped.
     *
     * The verify path filters `consumed_at IS NULL`
     * (see CustomerPortalOtpService::verify()). By
     * stamping `consumed_at = now()` we make the
     * row immediately unverifiable. The next
     * `requestOtp()` call from the user will
     * invalidate this row and create a fresh one.
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
