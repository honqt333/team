<?php

namespace App\Services\AI\Providers;

use App\Services\AI\AiProvider;
use App\Services\AI\CompletionRequest;
use App\Services\AI\CompletionResponse;

class MockProvider implements AiProvider
{
    public function __construct(
        private readonly ?string $fallbackFor = null,
    ) {}

    public function complete(CompletionRequest $req): CompletionResponse
    {
        $inputTokens = $this->estimateTextTokens($req->promptText());
        $source = $req->userText();
        $reversed = $this->reverseString($source);
        $fallback = $this->fallbackFor ? " fallback_for={$this->fallbackFor}" : '';
        $content = "-- mock{$fallback} input_tokens={$inputTokens} -- {$reversed}";
        $outputTokens = $this->estimateTextTokens($content);

        return new CompletionResponse(
            content: $content,
            inputTokens: $inputTokens,
            outputTokens: $outputTokens,
            cost: 0,
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
        return 'mock';
    }

    public function estimateCost(CompletionRequest $req): int
    {
        return 0;
    }

    private function estimateTextTokens(string $text): int
    {
        $asciiText = preg_replace('/[^\x00-\x7F]/u', '', $text) ?? '';
        $asciiChars = strlen($asciiText);
        $unicodeChars = max(0, mb_strlen($text) - $asciiChars);

        return max(1, (int) ceil(($asciiChars / 4) + ($unicodeChars / 2)));
    }

    private function reverseString(string $value): string
    {
        return implode('', array_reverse(mb_str_split($value)));
    }

    /**
     * @return array<int, string>
     */
    private function splitIntoChunks(string $content): array
    {
        return preg_split('/(\s+)/u', $content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [];
    }
}
