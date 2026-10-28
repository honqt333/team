<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Raised when a payment gateway callback does not match the local
 * payment record it claims to settle.
 *
 * This is a refusal, not a crash: the caller marks the payment as
 * failed and shows the reason. It must never result in an activated
 * subscription.
 */
class PaymentMismatchException extends Exception {}
