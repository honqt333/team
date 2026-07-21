<?php

declare(strict_types=1);

namespace App\Services\AI;

use InvalidArgumentException;

final readonly class CompletionRequest
{
    /**
     * @param array<int, array{role: string, content: string|array<mixed>}> $messages
     */
    public function __construct(
        public string $model,
        public array $messages,
        public float $temperature = 0.2,
        public int $maxTokens = 512,
        public int $tenantId = 0,
    ) {
        if ($this->tenantId <= 0) {
            throw new InvalidArgumentException('CompletionRequest requires a valid tenantId.');
        }

        if ($this->model === '') {
            throw new InvalidArgumentException('CompletionRequest requires a model.');
        }

        if ($this->messages === []) {
            throw new InvalidArgumentException('CompletionRequest requires at least one message.');
        }

        foreach ($this->messages as $message) {
            if (! is_array($message) || ! isset($message['role'], $message['content'])) {
                throw new InvalidArgumentException('Each completion message requires role and content.');
            }
        }

        if ($this->maxTokens < 1) {
            throw new InvalidArgumentException('CompletionRequest maxTokens must be greater than zero.');
        }
    }

    public function userText(): string
    {
        $messages = array_reverse($this->messages);

        foreach ($messages as $message) {
            if (($message['role'] ?? null) === 'user') {
                return $this->stringifyContent($message['content']);
            }
        }

        foreach ($messages as $message) {
            $content = $this->stringifyContent($message['content'] ?? '');

            if ($content !== '') {
                return $content;
            }
        }

        return '';
    }

    public function promptText(): string
    {
        return implode("\n", array_map(function (array $message): string {
            return ($message['role'] ?? 'user').': '.$this->stringifyContent($message['content'] ?? '');
        }, $this->messages));
    }

    /**
     * @return array{model: string, messages: array<int, array{role: string, content: string|array<mixed>}>, temperature: float, max_tokens: int, tenant_id: int}
     */
    public function toArray(): array
    {
        return [
            'model' => $this->model,
            'messages' => $this->messages,
            'temperature' => $this->temperature,
            'max_tokens' => $this->maxTokens,
            'tenant_id' => $this->tenantId,
        ];
    }

    private function stringifyContent(mixed $content): string
    {
        if (is_array($content)) {
            return (string) json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return (string) $content;
    }
}
