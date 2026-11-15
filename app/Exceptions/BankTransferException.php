<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class BankTransferException extends RuntimeException
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        string $message,
        public readonly int $statusCode = 422,
        public readonly array $context = []
    ) {
        parent::__construct($message, $statusCode);
    }
}
