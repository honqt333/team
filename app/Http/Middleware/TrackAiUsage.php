<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\AI\CompletionResponse;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

class TrackAiUsage
{
    /**
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $request->user()?->tenant_id;

        if (! $tenantId) {
            return response()->json([
                'message' => 'AI usage tracking requires a tenant_id.',
            ], 403);
        }

        $request->attributes->set('ai.tenant_id', (int) $tenantId);

        $response = $next($request);
        $usage = $this->usageFromRequest($request) ?? $this->usageFromResponse($response);

        if ($usage !== null) {
            $this->logUsage($request, (int) $tenantId, $usage);
        }

        return $response;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function usageFromRequest(Request $request): ?array
    {
        $usage = $request->attributes->get('ai_usage');

        if ($usage instanceof CompletionResponse) {
            return [
                'provider' => $usage->provider,
                'model' => $usage->model,
                'input_tokens' => $usage->inputTokens,
                'output_tokens' => $usage->outputTokens,
                'cost_micro_cents' => $usage->cost,
            ];
        }

        return is_array($usage) ? $usage : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function usageFromResponse(Response $response): ?array
    {
        if (! $response instanceof JsonResponse) {
            return null;
        }

        $payload = $response->getData(true);

        if (! is_array($payload) || ! isset($payload['usage']) || ! is_array($payload['usage'])) {
            return null;
        }

        return array_merge([
            'provider' => $payload['provider'] ?? null,
            'model' => $payload['model'] ?? null,
        ], $payload['usage']);
    }

    /**
     * @param array<string, mixed> $usage
     */
    private function logUsage(Request $request, int $tenantId, array $usage): void
    {
        $inputTokens = max(0, (int) ($usage['input_tokens'] ?? $usage['inputTokens'] ?? 0));
        $outputTokens = max(0, (int) ($usage['output_tokens'] ?? $usage['outputTokens'] ?? 0));
        $costMicroCents = max(0, (int) ($usage['cost_micro_cents'] ?? $usage['cost'] ?? 0));

        $this->aiLogger()->info('ai.usage', [
            'tenant_id' => $tenantId,
            'user_id' => $request->user()?->id,
            'provider' => (string) ($usage['provider'] ?? 'unknown'),
            'model' => (string) ($usage['model'] ?? 'unknown'),
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'total_tokens' => $inputTokens + $outputTokens,
            'cost_micro_cents' => $costMicroCents,
            'route' => $request->route()?->getName(),
            'path' => $request->path(),
            'method' => $request->method(),
        ]);
    }

    private function aiLogger(): LoggerInterface
    {
        $channels = config('logging.channels', []);

        if (is_array($channels) && array_key_exists('ai', $channels)) {
            return Log::channel('ai');
        }

        return Log::build([
            'driver' => 'single',
            'path' => storage_path('logs/ai.log'),
            'level' => config('logging.channels.single.level', 'debug'),
            'replace_placeholders' => true,
        ]);
    }
}
