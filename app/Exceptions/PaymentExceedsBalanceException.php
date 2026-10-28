<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Raised when a payment would push an invoice past its outstanding
 * balance.
 *
 * The check is repeated inside the payment transaction, so this is the
 * error a collector sees when two collectors reach the same invoice at
 * the same time and only one of them may win.
 */
class PaymentExceedsBalanceException extends Exception {}
