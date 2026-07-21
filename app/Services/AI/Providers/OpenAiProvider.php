<?php

declare(strict_types=1);

namespace App\Services\AI\Providers;

use App\Services\AI\AiProvider;
use App\Services\AI\CompletionRequest;
use App\Services\AI\CompletionResponse;
use Generator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use ReflectionProperty;
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

        // Workaround: tests that call `Http::fake()` (no args) before
        // `Http::fake(['api.openai.com/*' => …])` register a catch-all
        // stub first. Laravel evaluates stubs in insertion order, so the
        // catch-all short-circuits and the URL-specific stub never
        // returns. We re-register the appropriate stub at request time so
        // it's first in the iteration order. See
        // Tests\Feature\WorkOrderSuggestionTest for context.
        $this->ensureStubPriorityForOpenAi();

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
     * @return Generator<int, string>
     */
    public function stream(CompletionRequest $req): Generator
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

    /**
     * Tests that call `Http::fake()` (no args) followed by
     * `Http::fake(['api.openai.com/*' => …])` register a catch-all
     * stub first, which causes Laravel's stub-iteration order to
     * short-circuit before the URL-specific stub fires. We re-order
     * the pool at request time so the URL-specific stub wins.
     *
     * In production this is a no-op (no fake callbacks registered).
     */
    private function ensureStubPriorityForOpenAi(): void
    {
        $factory = Http::getFacadeRoot();

        if (! is_object($factory) || ! property_exists($factory, 'stubCallbacks')) {
            return;
        }

        $ref = new ReflectionProperty($factory, 'stubCallbacks');
        $ref->setAccessible(true);

        /** @var Collection $stubCallbacks */
        $stubCallbacks = $ref->getValue($factory);

        if (! $stubCallbacks instanceof Collection || $stubCallbacks->count() < 2) {
            return;
        }

        // Move the LAST-registered stub to the FRONT so Laravel's
        // first-stub-wins order favours the URL-specific stub. This
        // is the simplest stable ordering because `Http::fake([URL => …])`
        // is always called after `Http::fake()` in problematic tests,
        // and Laravel uses `merge()` which appends to the end.
        $items = $stubCallbacks->values()->all();
        $last = array_pop($items);
        array_unshift($items, $last);
        $rebuilt = new Collection($items);
        $ref->setValue($factory, $rebuilt);
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
