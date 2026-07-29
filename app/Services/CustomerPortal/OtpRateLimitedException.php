<?php

declare(strict_types=1);

namespace App\Services\CustomerPortal;

use RuntimeException;

class OtpRateLimitedException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $retryAfterSeconds,
    ) {
        parent::__construct($message);
    }
}
