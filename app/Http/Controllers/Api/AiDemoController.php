<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Services\AI\CompletionRequest;
use App\Services\AI\ProviderRegistry;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiDemoController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ProviderRegistry $providers,
    ) {}

    public function describe(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Vehicle::class);

        $tenantId = (int) ($request->user()?->tenant_id ?? 0);

        if ($tenantId <= 0) {
            return response()->json([
                'message' => 'AI demo requires an authenticated tenant user.',
            ], 403);
        }

        $validated = $request->validate([
            'make' => ['nullable', 'string', 'max:80'],
            'model' => ['nullable', 'string', 'max:80'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:'.((int) date('Y') + 1)],
            'plate_number' => ['nullable', 'string', 'max:30'],
            'color' => ['nullable', 'string', 'max:40'],
            'odometer' => ['nullable', 'integer', 'min:0', 'max:9999999'],
            'condition' => ['nullable', 'string', 'max:500'],
            'temperature' => ['nullable', 'numeric', 'min:0', 'max:2'],
            'max_tokens' => ['nullable', 'integer', 'min:32', 'max:1000'],
            'ai_model' => ['nullable', 'string', 'max:100'],
        ]);

        $provider = $this->providers->for('openai');
        $completionRequest = new CompletionRequest(
            model: (string) ($validated['ai_model'] ?? 'gpt-4o-mini'),
            messages: [
                [
                    'role' => 'system',
                    'content' => 'أنت مساعد خدمات سيارات في Carag. أجب بالعربية بنبرة مهنية مختصرة ولا تخترع معلومات غير موجودة.',
                ],
                [
                    'role' => 'user',
                    'content' => "اكتب وصفاً موجزاً لمركبة لاستخدامه في مركز صيانة.\n\nبيانات المركبة:\n".$this->vehicleSummary($validated),
                ],
            ],
            temperature: (float) ($validated['temperature'] ?? 0.3),
            maxTokens: (int) ($validated['max_tokens'] ?? 220),
            tenantId: $tenantId,
        );

        $completion = $provider->complete($completionRequest);
        $request->attributes->set('ai_usage', $completion);

        return response()->json([
            'description' => $completion->content,
            'provider' => $completion->provider,
            'model' => $completion->model,
            'usage' => [
                'tenant_id' => $tenantId,
                'input_tokens' => $completion->inputTokens,
                'output_tokens' => $completion->outputTokens,
                'total_tokens' => $completion->totalTokens(),
                'cost_micro_cents' => $completion->cost,
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $vehicle
     */
    private function vehicleSummary(array $vehicle): string
    {
        $labels = [
            'make' => 'الشركة',
            'model' => 'الموديل',
            'year' => 'السنة',
            'plate_number' => 'اللوحة',
            'color' => 'اللون',
            'odometer' => 'قراءة العداد',
            'condition' => 'ملاحظات الحالة',
        ];

        $lines = [];

        foreach ($labels as $key => $label) {
            if (! array_key_exists($key, $vehicle) || $vehicle[$key] === null || $vehicle[$key] === '') {
                continue;
            }

            $lines[] = $label.': '.$vehicle[$key];
        }

        return $lines === [] ? 'لا توجد بيانات مركبة إضافية.' : implode("\n", $lines);
    }
}
