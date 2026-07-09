<?php

namespace App\Services\AI;

use App\Services\AI\Providers\AnthropicProvider;
use App\Services\AI\Providers\MockProvider;
use App\Services\AI\Providers\OpenAiProvider;
use InvalidArgumentException;

class ProviderRegistry
{
    public function for(?string $name = null): AiProvider
    {
        if ($name === null || $name === '') {
            return $this->default();
        }

        return match (strtolower($name)) {
            'openai', 'open_ai' => $this->hasOpenAiKey()
                ? new OpenAiProvider
                : new MockProvider('openai'),
            'anthropic', 'claude' => $this->hasAnthropicKey()
                ? new AnthropicProvider
                : new MockProvider('anthropic'),
            'mock', 'fake', 'testing' => new MockProvider,
            default => throw new InvalidArgumentException("Unknown AI provider [{$name}]."),
        };
    }

    public function default(): AiProvider
    {
        if ($this->hasOpenAiKey()) {
            return new OpenAiProvider;
        }

        if ($this->hasAnthropicKey()) {
            return new AnthropicProvider;
        }

        return new MockProvider;
    }

    private function hasOpenAiKey(): bool
    {
        return trim((string) config('services.openai.key', env('OPENAI_API_KEY', ''))) !== '';
    }

    private function hasAnthropicKey(): bool
    {
        return trim((string) config('services.anthropic.key', env('ANTHROPIC_API_KEY', ''))) !== '';
    }
}
