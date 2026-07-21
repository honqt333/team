<?php

declare(strict_types=1);

namespace App\Services\AI;

use Generator;

interface AiProvider
{
    public function complete(CompletionRequest $req): CompletionResponse;

    /**
     * @return Generator<int, string>
     */
    public function stream(CompletionRequest $req): Generator;

    public function name(): string;

    /**
     * Estimate request cost in micro-cents before the provider call is made.
     */
    public function estimateCost(CompletionRequest $req): int;
}
