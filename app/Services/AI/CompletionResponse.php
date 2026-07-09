<?php

namespace App\Services\AI;

final readonly class CompletionResponse
{
    public function __construct(
        public string $content,
        public int $inputTokens,
        public int $outputTokens,
        public int $cost,
        public string $provider,
        public string $model,
    ) {}

    public function totalTokens(): int
    {
        return $this->inputTokens + $this->outputTokens;
    }

    /**
     * @return array{content: string, usage: array{input_tokens: int, output_tokens: int, total_tokens: int, cost_micro_cents: int}, provider: string, model: string}
     */
    public function toArray(): array
    {
        return [
            'content' => $this->content,
            'usage' => [
                'input_tokens' => $this->inputTokens,
                'output_tokens' => $this->outputTokens,
                'total_tokens' => $this->totalTokens(),
                'cost_micro_cents' => $this->cost,
            ],
            'provider' => $this->provider,
            'model' => $this->model,
        ];
    }
}
