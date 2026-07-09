<?php

namespace App\Services\AI\Providers;

use App\Services\AI\AiProvider;
use App\Services\AI\CompletionRequest;
use App\Services\AI\CompletionResponse;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiProvider implements AiProvider
{
    /**
     * Pricing is stored as micro-cents per token.
     * Example: $0.15 / 1M tokens = 15 cents / 1M = 15 micro-cents per token.
     */
    private const PRICING_MICRO_CENTS_PER_TOKEN = [
        'gpt-4o-mini' => ['input' => 15, 'output' => 60],
        'gpt-4o' => ['input' => 250, 'output' => 1000],
        'gpt-4.1-mini' => ['input' => 40, 'output' => 160],
        'gpt-4.1' => ['input' => 200, 'output' => 800],
        'default' => ['input' => 15, 'output' => 60],
    ];

    public function complete(CompletionRequest $req): CompletionResponse
    {
        $apiKey = $this->apiKey();

        if ($apiKey === '') {
            throw new RuntimeException('OPENAI_API_KEY is not configured. Use ProviderRegistry for MockProvider fallback.');
        }

        $response = Http::timeout(60)
            ->withToken($apiKey)
            ->acceptJson()
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $req->model,
                'messages' => $req->messages,
                'temperature' => $req->temperature,
                'max_tokens' => $req->maxTokens,
            ])
            ->throw()
            ->json();

        $content = (string) data_get($response, 'choices.0.message.content', '');
        $inputTokens = (int) data_get($response, 'usage.prompt_tokens', $this->estimateInputTokens($req));
        $outputTokens = (int) data_get($response, 'usage.completion_tokens', $this->estimateTextTokens($content));

        return new CompletionResponse(
            content: $content,
            inputTokens: $inputTokens,
            outputTokens: $outputTokens,
            cost: $this->calculateCost($req->model, $inputTokens, $outputTokens),
            provider: $this->name(),
            model: $req->model,
        );
    }

    /**
     * @return \Generator<int, string>
     */
    public function stream(CompletionRequest $req): \Generator
    {
        $response = $this->complete($req);

        foreach ($this->splitIntoChunks($response->content) as $chunk) {
            yield $chunk;
        }
    }

    public function name(): string
    {
        return 'openai';
    }

    public function estimateCost(CompletionRequest $req): int
    {
        return $this->calculateCost(
            $req->model,
            $this->estimateInputTokens($req),
            $req->maxTokens,
        );
    }

    private function apiKey(): string
    {
        return trim((string) config('services.openai.key', env('OPENAI_API_KEY', '')));
    }

    private function calculateCost(string $model, int $inputTokens, int $outputTokens): int
    {
        $pricing = self::PRICING_MICRO_CENTS_PER_TOKEN[$model] ?? self::PRICING_MICRO_CENTS_PER_TOKEN['default'];

        return ($inputTokens * $pricing['input']) + ($outputTokens * $pricing['output']);
    }

    private function estimateInputTokens(CompletionRequest $req): int
    {
        return $this->estimateTextTokens($req->promptText());
    }

    private function estimateTextTokens(string $text): int
    {
        $asciiText = preg_replace('/[^\x00-\x7F]/u', '', $text) ?? '';
        $asciiChars = strlen($asciiText);
        $unicodeChars = max(0, mb_strlen($text) - $asciiChars);

        return max(1, (int) ceil(($asciiChars / 4) + ($unicodeChars / 2)));
    }

    /**
     * @return array<int, string>
     */
    private function splitIntoChunks(string $content): array
    {
        return preg_split('/(\s+)/u', $content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [];
    }
}
