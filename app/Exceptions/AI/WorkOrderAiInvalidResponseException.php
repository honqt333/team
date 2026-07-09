<?php

declare(strict_types=1);

namespace App\Exceptions\AI;

use RuntimeException;
use Throwable;

/**
 * Thrown when an AI provider returns a response that cannot be parsed as
 * the expected JSON shape for WorkOrder suggestion responses.
 *
 * The HTTP layer maps this to a 502 Bad Gateway response — see §8 of
 * docs/features/ai-service-suggester/design.md.
 */
class WorkOrderAiInvalidResponseException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        string $message = 'AI returned an invalid response.',
        private readonly string $provider = 'unknown',
        private readonly array $context = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function provider(): string
    {
        return $this->provider;
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return $this->context;
    }
}
