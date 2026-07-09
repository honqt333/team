<?php

namespace App\Services\AI\Providers;

use App\Services\AI\AiProvider;
use App\Services\AI\CompletionRequest;
use App\Services\AI\CompletionResponse;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AnthropicProvider implements AiProvider
{
    /**
     * Pricing is stored as micro-cents per token.
     */
    private const PRICING_MICRO_CENTS_PER_TOKEN = [
        'claude-3-5-sonnet-20241022' => ['input' => 300, 'output' => 1500],
        'claude-3-5-sonnet-latest' => ['input' => 300, 'output' => 1500],
        'claude-3-5-haiku-latest' => ['input' => 80, 'output' => 400],
        'claude-3-opus-20240229' => ['input' => 1500, 'output' => 7500],
        'default' => ['input' => 300, 'output' => 1500],
    ];

    public function complete(CompletionRequest $req): CompletionResponse
    {
        $apiKey = $this->apiKey();

        if ($apiKey === '') {
            throw new RuntimeException('ANTHROPIC_API_KEY is not configured. Use ProviderRegistry for MockProvider fallback.');
        }

        $response = Http::timeout(60)
            ->withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
            ])
            ->acceptJson()
            ->post('https://api.anthropic.com/v1/messages', $this->payload($req))
            ->throw()
            ->json();

        $content = $this->extractContent($response);
        $inputTokens = (int) data_get($response, 'usage.input_tokens', $this->estimateInputTokens($req));
        $outputTokens = (int) data_get($response, 'usage.output_tokens', $this->estimateTextTokens($content));

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
        return 'anthropic';
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
        return trim((string) config('services.anthropic.key', env('ANTHROPIC_API_KEY', '')));
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(CompletionRequest $req): array
    {
        $systemMessages = [];
        $messages = [];

        foreach ($req->messages as $message) {
            $role = (string) ($message['role'] ?? 'user');
            $content = $this->stringifyContent($message['content'] ?? '');

            if ($role === 'system') {
                $systemMessages[] = $content;

                continue;
            }

            $messages[] = [
                'role' => $role === 'assistant' ? 'assistant' : 'user',
                'content' => $content,
            ];
        }

        if ($messages === []) {
            $messages[] = [
                'role' => 'user',
                'content' => $req->promptText(),
            ];
        }

        $payload = [
            'model' => $req->model,
            'messages' => $messages,
            'temperature' => $req->temperature,
            'max_tokens' => $req->maxTokens,
        ];

        if ($systemMessages !== []) {
            $payload['system'] = implode("\n", $systemMessages);
        }

        return $payload;
    }

    private function extractContent(array $response): string
    {
        $parts = [];

        foreach ((array) data_get($response, 'content', []) as $part) {
            if (($part['type'] ?? null) === 'text') {
                $parts[] = (string) ($part['text'] ?? '');
            }
        }

        return trim(implode("\n", $parts));
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

    private function stringifyContent(mixed $content): string
    {
        if (is_array($content)) {
            return (string) json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return (string) $content;
    }

    /**
     * @return array<int, string>
     */
    private function splitIntoChunks(string $content): array
    {
        return preg_split('/(\s+)/u', $content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [];
    }
}
