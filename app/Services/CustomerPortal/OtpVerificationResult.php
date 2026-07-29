<?php

declare(strict_types=1);

namespace App\Services\CustomerPortal;

use App\Models\Customer;
use App\Models\CustomerPortalOtp;

/**
 * Result of CustomerPortalOtpService::verify().
 *
 * One of two top-level outcomes: success or failure. On
 * failure, $reason is one of the REASON_* constants and
 * the controller renders a single, generic "code is not
 * valid" message — the reason is for logging, not for
 * the UI.
 */
class OtpVerificationResult
{
    public const REASON_BAD_CODE = 'bad_code';

    public const REASON_EXPIRED = 'expired';

    public const REASON_TOO_MANY_ATTEMPTS = 'too_many_attempts';

    public const REASON_NO_ACTIVE_OTP = 'no_active_otp';

    public const REASON_MALFORMED = 'malformed';

    public const REASON_NO_CUSTOMER = 'no_customer';

    public const REASON_ALREADY_USED = 'already_used';

    public function __construct(
        public readonly bool $success,
        public readonly ?string $reason = null,
        public readonly ?Customer $customer = null,
        public readonly ?CustomerPortalOtp $otp = null,
    ) {}

    public static function success(Customer $customer, CustomerPortalOtp $otp): self
    {
        return new self(true, null, $customer, $otp);
    }

    public static function failed(string $reason): self
    {
        return new self(false, $reason);
    }
}
