<?php

declare(strict_types=1);

namespace App\Services\CustomerPortal;

use App\Contracts\SmsSendResult;
use App\Models\CustomerPortalOtp;
use Carbon\Carbon;

/**
 * Result of CustomerPortalOtpService::requestOtp().
 *
 * Three outcomes, deliberately limited:
 *   - ISSUED: a real OTP was created and the channel was
 *     called.
 *   - NO_CUSTOMER: the phone is not registered. The
 *     rate-limit slot was consumed but no OTP was sent.
 *     The caller MUST render the same generic message
 *     as for ISSUED.
 *   - THROTTLED: a recent OTP exists and we declined to
 *     send another. The caller can show "we already sent
 *     you a code, please wait".
 */
class OtpRequestResult
{
    public const ISSUED = 'issued';

    public const NO_CUSTOMER = 'no_customer';

    public const THROTTLED = 'throttled';

    public function __construct(
        public readonly string $outcome,
        public readonly ?CustomerPortalOtp $otp = null,
        public readonly ?SmsSendResult $sendResult = null,
        public readonly ?Carbon $nextSendAt = null,
    ) {}

    public static function issued(CustomerPortalOtp $otp, SmsSendResult $result): self
    {
        return new self(self::ISSUED, $otp, $result);
    }

    public static function noCustomer(): self
    {
        return new self(self::NO_CUSTOMER);
    }

    public static function throttled(Carbon $nextSendAt): self
    {
        return new self(self::THROTTLED, null, null, $nextSendAt);
    }

    public function isIssued(): bool
    {
        return $this->outcome === self::ISSUED;
    }

    public function isThrottled(): bool
    {
        return $this->outcome === self::THROTTLED;
    }
}
