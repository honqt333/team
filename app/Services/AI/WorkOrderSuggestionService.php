<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Exceptions\AI\WorkOrderAiInvalidResponseException;
use App\Http\Requests\WorkOrder\WorkOrderSuggestionRequest;
use App\Models\Department;
use App\Models\Part;
use App\Models\Prompt;
use App\Models\Service;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\AI\Providers\MockProvider;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

/**
 * Orchestrator for the WorkOrder service/part suggester.
 *
 * End-to-end pipeline (see docs/features/ai-service-suggester/design.md §5/§6/§7):
 *
 *   1. Build a tenant + center scoped catalog of services and parts.
 *   2. Look up the `work_order.suggest` Prompt for the current tenant
 *      (or fall back to the hard-coded Arabic system prompt).
 *   3. Build the chat messages (system + user) and call the provider.
 *      If the provider resolves to the generic MockProvider (no real
 *      key configured, OR a `mock` provider is explicitly requested),
 *      delegate to the local `MockSuggester` instead — the generic
 *      `MockProvider::complete()` returns a reversed string which is
 *      wrong for this feature.
 *   4. Parse the response as JSON. Re-fetch every returned `item_id`
 *      from the DB scoped to the current tenant + center. Anything the
 *      AI hallucinated is dropped, and `meta.returned` is decremented.
 *   5. Tag the response on the request so the `TrackAiUsage` middleware
 *      can log it.
 *
 * Hard rule: NEVER trust an AI-returned `item_id`. Always re-fetch
 * against the DB scoped to the current tenant + center. Defense in depth
 * is described in §6 of the contract.
 */
class WorkOrderSuggestionService
{
    private const SYSTEM_PROMPT_FALLBACK = <<<'AR'
أنت فني خبير في ورشة سيارات. بناءً على شكوى العميل، اقترح 4 إلى 8 أصناف أو خدمات
من كتالوج المركز فقط. الرد يجب أن يكون JSON حصراً بدون أي نص إضافي.

صيغة الرد:
{
  "suggestions": [
    {
      "item_type": "service" | "part",
      "item_id": <int>,
      "name": "<string>",
      "reason": "<string>",
      "confidence": <float 0..1>,
      "qty": <int 1..4>
    }
  ]
}

قواعد:
- لا تخترع أصنافاً غير موجودة في الكتالوج المرفق
- إذا كانت الشكوى غير واضحة، اقترح فحصاً وقائياً بثقة منخفضة
- اطرح 4-8 اقتراحات بالضبط، مرتبة من الأعلى ثقة إلى الأدنى
AR;

    private const CATALOG_SERVICE_LIMIT = 60;

    private const CATALOG_PART_LIMIT = 20;

    public function __construct(
        private readonly ProviderRegistry $providers,
        private readonly MockSuggester $mock,
    ) {}

    /**
     * Build the full suggestion response payload.
     *
     * @return array{
     *     suggestions: array<int, array<string, mixed>>,
     *     meta: array<string, mixed>,
     *     usage: array<string, mixed>
     * }
     *
     * @throws WorkOrderAiInvalidResponseException When the provider returns non-JSON.
     */
    public function suggest(
        WorkOrder $workOrder,
        WorkOrderSuggestionRequest $request,
        User $user,
        int $tenantId,
        int $centerId,
    ): array {
        $locale = (string) app()->getLocale();
        $limit = (int) ($request->integer('limit') ?: 8);

        // 1. Build the catalog (center-scoped, defense-in-depth on tenant_id).
        $catalog = $this->buildCatalog($tenantId, $centerId);
        $catalogRows = $catalog['rows'];
        $totalCandidates = $catalog['total_candidates'];

        // 2. Pick the prompt: tenant-scoped DB row or fallback.
        $prompt = $this->resolvePrompt($tenantId);

        // 3. Build messages.
        $messages = $this->buildMessages(
            $prompt['content'],
            (string) $request->input('complaint'),
            (array) $request->input('vehicle', []),
            $catalogRows,
        );

        $model = $prompt['model'];
        $temperature = $prompt['temperature'];

        // 4. Call the provider.
        $provider = $this->providers->default();

        $completion = null;
        $rawSuggestions = [];

        if ($this->shouldUseLocalMock($provider)) {
            // Delegate to the local scorer — never call the generic
            // MockProvider::complete() because it returns a reversed
            // string which is wrong for this feature.
            $rawSuggestions = $this->mock->suggestForComplaint(
                (string) $request->input('complaint'),
                $catalogRows->map(fn (array $row) => [
                    'item_type' => (string) $row['item_type'],
                    'item_id' => (int) $row['item_id'],
                    'name_ar' => (string) $row['name_ar'],
                    'name_en' => (string) $row['name_en'],
                    'description_ar' => (string) ($row['description_ar'] ?? ''),
                    'description_en' => (string) ($row['description_en'] ?? ''),
                ])->values(),
                $limit,
                $locale,
            );
            // The local mock doesn't talk to a real provider. Build a
            // synthetic CompletionResponse purely so the middleware can
            // log it for parity with the live flow.
            $completion = new CompletionResponse(
                content: json_encode(['suggestions' => $rawSuggestions], JSON_UNESCAPED_UNICODE),
                inputTokens: 0,
                outputTokens: 0,
                cost: 0,
                provider: 'mock',
                model: $model,
            );
        } else {
            $completionRequest = new CompletionRequest(
                model: $model,
                messages: $messages,
                temperature: $temperature,
                maxTokens: 600,
                tenantId: $tenantId,
            );

            try {
                $completion = $provider->complete($completionRequest);
            } catch (Throwable $e) {
                Log::warning('ai.work_order_suggest.provider_failed', [
                    'tenant_id' => $tenantId,
                    'work_order_id' => $workOrder->id,
                    'provider' => $provider->name(),
                    'exception' => $e::class,
                    'message' => $e->getMessage(),
                ]);

                throw new RuntimeException(
                    __('work_orders.suggestions.errors.unavailable'),
                    previous: $e,
                );
            }

            // 5. Parse the response. Must be JSON we can interpret.
            $rawSuggestions = $this->decodeProviderResponse($completion->content, $provider->name());
        }

        // 6. Hydrate the suggestions — re-fetch every AI-returned id from
        //    the DB scoped to (tenant_id, center_id). Drop anything the AI
        //    hallucinated.
        $hydrated = $this->hydrateSuggestions(
            $rawSuggestions,
            $tenantId,
            $centerId,
            $catalogRows,
            $locale,
            (string) ($workOrder->currency_code ?: 'SAR'),
        );
        $suggestions = $hydrated['items'];
        $totalCandidates = $hydrated['total_candidates'];

        // 7. Tag the request for TrackAiUsage middleware.
        $request->attributes->set('ai_usage', $completion);

        $usage = [
            'tenant_id' => $tenantId,
            'input_tokens' => (int) $completion->inputTokens,
            'output_tokens' => (int) $completion->outputTokens,
            'total_tokens' => (int) $completion->totalTokens(),
            'cost_micro_cents' => (int) $completion->cost,
        ];

        return [
            'suggestions' => $suggestions,
            'meta' => [
                'provider' => (string) ($completion->provider ?? $provider->name()),
                'model' => (string) ($completion->model ?? $model),
                'work_order_id' => (int) $workOrder->id,
                'tenant_id' => $tenantId,
                'center_id' => $centerId,
                'total_candidates' => $totalCandidates,
                'returned' => count($suggestions),
            ],
            'usage' => $usage,
        ];
    }

    /**
     * Build the catalog slice the prompt sees. Returns rows in a
     * normalized array shape ready to be projected into the prompt
     * text, plus a count of total candidates the model considered.
     *
     * @return array{rows: Collection<int, array<string, mixed>>, total_candidates: int}
     */
    private function buildCatalog(int $tenantId, int $centerId): array
    {
        $services = Service::query()
            ->where('is_active', true)
            ->where('center_id', $centerId) // explicit per contract §6
            ->orderByDesc('base_price')
            ->orderByDesc('updated_at')
            ->limit(self::CATALOG_SERVICE_LIMIT)
            ->get(['id', 'department_id', 'name_ar', 'name_en', 'base_price']);

        $partsQuery = Part::query()
            ->where('is_active', true)
            ->orderByDesc('default_sale_price')
            ->orderByDesc('updated_at')
            ->limit(self::CATALOG_PART_LIMIT)
            ->get(['id', 'name_ar', 'name_en', 'default_sale_price']);

        // The `parts` table does not have a `center_id` column in the
        // current schema; `TenantScoped` is the only valid filter.
        // We defensively attempt the contract's center filter when the
        // column exists (avoids SQL errors when the schema evolves).
        if (Schema::hasColumn('parts', 'center_id')) {
            $partsQuery = Part::query()
                ->where('is_active', true)
                ->where('center_id', $centerId)
                ->orderByDesc('default_sale_price')
                ->orderByDesc('updated_at')
                ->limit(self::CATALOG_PART_LIMIT)
                ->get(['id', 'name_ar', 'name_en', 'default_sale_price']);
        }

        $departmentIds = $services->pluck('department_id')->filter()->unique()->values();
        $departmentsById = $departmentIds->isEmpty()
            ? collect()
            : Department::query()
                ->whereIn('id', $departmentIds->all())
                ->get(['id', 'name_ar', 'name_en'])
                ->keyBy('id');

        $rows = collect();

        foreach ($services as $service) {
            /** @var Department|null $department */
            $department = $departmentsById->get($service->department_id);
            $rows->push([
                'item_type' => 'service',
                'item_id' => (int) $service->id,
                'department_id' => (int) ($service->department_id ?? 0),
                'department_name_ar' => $department ? (string) $department->name_ar : '',
                'department_name_en' => $department ? (string) $department->name_en : '',
                'name_ar' => (string) $service->name_ar,
                'name_en' => (string) ($service->name_en ?: $service->name_ar),
                'description_ar' => '',
                'description_en' => '',
                'price' => (float) $service->base_price,
                'is_active' => (bool) $service->is_active,
            ]);
        }

        foreach ($partsQuery as $part) {
            $rows->push([
                'item_type' => 'part',
                'item_id' => (int) $part->id,
                'department_id' => 0,
                'department_name_ar' => '',
                'department_name_en' => '',
                'name_ar' => (string) $part->name_ar,
                'name_en' => (string) ($part->name_en ?: $part->name_ar),
                'description_ar' => '',
                'description_en' => '',
                'price' => (float) $part->default_sale_price,
                'is_active' => (bool) $part->is_active,
            ]);
        }

        return [
            'rows' => $rows,
            'total_candidates' => (int) $services->count() + (int) $partsQuery->count(),
        ];
    }

    /**
     * @return array{content: string, model: string, temperature: float}
     */
    private function resolvePrompt(int $tenantId): array
    {
        $prompt = Prompt::query()
            ->where('key', 'work_order.suggest')
            ->where('active', true)
            ->where('tenant_id', $tenantId)
            ->first();

        if ($prompt !== null) {
            return [
                'content' => (string) $prompt->content,
                'model' => (string) $prompt->model,
                'temperature' => (float) $prompt->temperature,
            ];
        }

        return [
            'content' => self::SYSTEM_PROMPT_FALLBACK,
            'model' => 'gpt-4o-mini',
            'temperature' => 0.2,
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $catalogRows
     * @return array<int, array{role: string, content: string}>
     */
    private function buildMessages(
        string $systemContent,
        string $complaint,
        array $vehicle,
        Collection $catalogRows,
    ): array {
        $vehicleLine = trim(sprintf(
            '%s %s %s',
            (string) ($vehicle['make'] ?? ''),
            (string) ($vehicle['model'] ?? ''),
            isset($vehicle['year']) ? (string) $vehicle['year'] : '',
        ));
        $plateLine = isset($vehicle['plate_number']) ? (string) $vehicle['plate_number'] : '';
        $odometerLine = isset($vehicle['odometer']) ? ((string) $vehicle['odometer']).' كم' : '';

        $userBody = "شكوى العميل: {$complaint}\n";
        $userBody .= 'نوع المركبة: '.($vehicleLine !== '' ? $vehicleLine : 'غير محدد')."\n";
        $userBody .= 'اللوحة: '.($plateLine !== '' ? $plateLine : 'غير محدد')."\n";
        $userBody .= 'قراءة العداد: '.($odometerLine !== '' ? $odometerLine : 'غير محدد')."\n\n";
        $userBody .= "كتالوج المركز المتاح:\n";

        $counter = 0;
        foreach ($catalogRows as $row) {
            $counter++;
            $dept = (string) ($row['department_name_ar'] ?: $row['department_name_en'] ?: '');
            $price = number_format((float) ($row['price'] ?? 0), 2, '.', '');
            $userBody .= sprintf(
                "[%d] (%s, id=%d) %s — قسم: %s — %s SAR\n",
                $counter,
                (string) $row['item_type'],
                (int) $row['item_id'],
                (string) ($row['name_ar'] ?: $row['name_en']),
                $dept !== '' ? $dept : '—',
                $price,
            );
        }

        return [
            ['role' => 'system', 'content' => $systemContent],
            ['role' => 'user', 'content' => $userBody],
        ];
    }

    /**
     * Decode the provider response into the canonical suggestion shape.
     *
     * Tolerant: the AI might wrap the JSON in ```json ... ``` fences or
     * prefix it with a short apology line. We strip the fences and
     * extract the first balanced JSON object.
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws WorkOrderAiInvalidResponseException
     */
    private function decodeProviderResponse(string $content, string $providerName): array
    {
        $raw = trim($content);
        $candidate = $this->extractFirstJson($raw);

        if ($candidate === null) {
            Log::warning('ai.work_order_suggest.invalid_json', [
                'provider' => $providerName,
                'raw_excerpt' => mb_substr($raw, 0, 400),
            ]);
            throw new WorkOrderAiInvalidResponseException(
                __('work_orders.suggestions.errors.invalid_response'),
                $providerName,
                ['raw_excerpt' => mb_substr($raw, 0, 400)],
            );
        }

        try {
            $decoded = json_decode($candidate, true, 32, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            throw new WorkOrderAiInvalidResponseException(
                __('work_orders.suggestions.errors.invalid_response'),
                $providerName,
                ['json_error' => $e->getMessage()],
                previous: $e,
            );
        }

        if (! is_array($decoded) || ! isset($decoded['suggestions']) || ! is_array($decoded['suggestions'])) {
            throw new WorkOrderAiInvalidResponseException(
                __('work_orders.suggestions.errors.invalid_response'),
                $providerName,
                ['decoded_shape' => is_array($decoded) ? array_keys($decoded) : gettype($decoded)],
            );
        }

        return $decoded['suggestions'];
    }

    /**
     * Extract the first JSON object from a possibly wrapped string.
     * Handles ```json fences and trailing prose.
     */
    private function extractFirstJson(string $text): ?string
    {
        // Strip ```json fences, if present.
        $stripped = preg_replace('/```(?:json)?\s*(.*?)\s*```/si', '$1', $text) ?? $text;
        $stripped = trim($stripped);

        // Find the first '{' that looks like a JSON object start.
        $start = strpos($stripped, '{');
        if ($start === false) {
            return null;
        }

        $depth = 0;
        $inString = false;
        $escape = false;
        $length = strlen($stripped);
        for ($i = $start; $i < $length; $i++) {
            $char = $stripped[$i];
            if ($escape) {
                $escape = false;

                continue;
            }
            if ($inString) {
                if ($char === '\\') {
                    $escape = true;

                    continue;
                }
                if ($char === '"') {
                    $inString = false;
                }

                continue;
            }
            if ($char === '"') {
                $inString = true;

                continue;
            }
            if ($char === '{') {
                $depth++;

                continue;
            }
            if ($char === '}') {
                $depth--;
                if ($depth === 0) {
                    return substr($stripped, $start, $i - $start + 1);
                }
            }
        }

        return null;
    }

    /**
     * Re-fetch every AI-returned item_id from the DB scoped to the
     * current tenant + center. Items not present in the catalog rows
     * (i.e. AI hallucinations) are dropped. The returned suggestions
     * are aligned with the local `name` selection based on the current
     * locale.
     *
     * @param  array<int, array<string, mixed>>  $rawSuggestions
     * @param  Collection<int, array<string, mixed>>  $catalogRows
     * @return array{items: array<int, array<string, mixed>>, total_candidates: int}
     */
    private function hydrateSuggestions(
        array $rawSuggestions,
        int $tenantId,
        int $centerId,
        Collection $catalogRows,
        string $locale,
        string $currency,
    ): array {
        $serviceIds = [];
        $partIds = [];
        $seen = [];
        $deduped = [];

        foreach ($rawSuggestions as $raw) {
            $type = (string) ($raw['item_type'] ?? '');
            $id = (int) ($raw['item_id'] ?? 0);
            if ($id <= 0 || ! in_array($type, ['service', 'part'], true)) {
                continue;
            }
            $key = $type.':'.$id;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $deduped[] = $raw;

            if ($type === 'service') {
                $serviceIds[] = $id;
            } else {
                $partIds[] = $id;
            }
        }

        // Defense-in-depth: re-fetch every candidate by tenant + center
        // (Services) or tenant (Parts, which lack a center_id column in
        // the current schema). Anything that doesn't match is an AI
        // hallucination and is dropped.
        $resolvedServices = $this->refetchServices($serviceIds, $tenantId, $centerId);
        $resolvedParts = $this->refetchParts($partIds, $tenantId, $centerId);

        $result = [];
        foreach ($deduped as $raw) {
            $type = (string) $raw['item_type'];
            $id = (int) $raw['item_id'];
            $key = $type.':'.$id;

            if ($type === 'service') {
                /** @var Service|null $service */
                $service = $resolvedServices[$id] ?? null;
                if ($service === null) {
                    continue; // hallucinated — drop
                }
                $result[] = $this->presentService($raw, $service, $locale, $currency);

                continue;
            }

            /** @var Part|null $part */
            $part = $resolvedParts[$id] ?? null;
            if ($part === null) {
                continue; // hallucinated — drop
            }
            $result[] = $this->presentPart($raw, $part, $locale, $currency);
        }

        return [
            'items' => $result,
            // `total_candidates` reports how many items the AI returned
            // *before* the defense-in-depth pass. Comparison against
            // `returned` then exposes how many hallucinated ids were
            // dropped.
            'total_candidates' => count($deduped),
        ];
    }

    /**
     * Re-fetch services by id, scoped to (tenant_id, center_id).
     *
     * @param  array<int, int>  $ids
     * @return array<int, Service>
     */
    private function refetchServices(array $ids, int $tenantId, int $centerId): array
    {
        if ($ids === []) {
            return [];
        }

        return Service::query()
            ->whereIn('id', array_values(array_unique($ids)))
            ->where('tenant_id', $tenantId)
            ->where('center_id', $centerId)
            ->where('is_active', true)
            ->get(['id', 'department_id', 'name_ar', 'name_en', 'base_price', 'is_active'])
            ->keyBy('id')
            ->all();
    }

    /**
     * Re-fetch parts by id, scoped to tenant_id.
     *
     * The `parts` table does not currently have a `center_id` column,
     * so the safe default is `tenant_id` only. When the column is added
     * in a future migration we will additionally filter by `center_id`
     * — detected defensively with `Schema::hasColumn()` so this code
     * does not need to change.
     *
     * @param  array<int, int>  $ids
     * @return array<int, Part>
     */
    private function refetchParts(array $ids, int $tenantId, int $centerId): array
    {
        if ($ids === []) {
            return [];
        }

        $query = Part::query()
            ->whereIn('id', array_values(array_unique($ids)))
            ->where('tenant_id', $tenantId)
            ->where('is_active', true);

        if (Schema::hasColumn('parts', 'center_id')) {
            $query->where('center_id', $centerId);
        }

        return $query
            ->get(['id', 'name_ar', 'name_en', 'default_sale_price', 'is_active'])
            ->keyBy('id')
            ->all();
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    private function presentService(
        array $raw,
        Service $service,
        string $locale,
        string $currency,
    ): array {
        $departmentName = $this->resolveDepartmentName($service->department_id, $locale);
        $priceCents = (int) round(((float) $service->base_price) * 100);

        $name = $locale === 'en'
            ? ((string) ($service->name_en ?: $service->name_ar))
            : ((string) ($service->name_ar ?: $service->name_en));

        $nameEn = (string) ($service->name_en ?: '');

        $reason = (string) ($raw['reason'] ?? '');

        return [
            'department_id' => (int) ($service->department_id ?? 0),
            'department_name' => $departmentName,
            'item_type' => 'service',
            'item_id' => (int) $service->id,
            'name' => $name,
            'name_en' => $nameEn !== '' ? $nameEn : null,
            'qty' => $this->safeQty($raw['qty'] ?? 1),
            'estimated_unit_price_cents' => $priceCents,
            'currency' => $currency,
            'confidence' => $this->safeConfidence((float) ($raw['confidence'] ?? 0.5)),
            'reason' => mb_substr($reason, 0, 240),
            'is_active' => (bool) $service->is_active,
        ];
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    private function presentPart(
        array $raw,
        Part $part,
        string $locale,
        string $currency,
    ): array {
        $name = $locale === 'en'
            ? ((string) ($part->name_en ?: $part->name_ar))
            : ((string) ($part->name_ar ?: $part->name_en));
        $nameEn = (string) ($part->name_en ?: '');
        $priceCents = (int) round(((float) $part->default_sale_price) * 100);

        $reason = (string) ($raw['reason'] ?? '');

        return [
            'department_id' => 0,
            'department_name' => '',
            'item_type' => 'part',
            'item_id' => (int) $part->id,
            'name' => $name,
            'name_en' => $nameEn !== '' ? $nameEn : null,
            'qty' => $this->safeQty($raw['qty'] ?? 1),
            'estimated_unit_price_cents' => $priceCents,
            'currency' => $currency,
            'confidence' => $this->safeConfidence((float) ($raw['confidence'] ?? 0.5)),
            'reason' => mb_substr($reason, 0, 240),
            'is_active' => (bool) $part->is_active,
        ];
    }

    private function resolveDepartmentName(?int $departmentId, string $locale): string
    {
        if ($departmentId === null || $departmentId <= 0) {
            return '';
        }

        /** @var Department|null $department */
        $department = Department::query()->whereKey($departmentId)->first(['name_ar', 'name_en']);

        if ($department === null) {
            return '';
        }

        if ($locale === 'en') {
            return (string) ((string) ($department->name_en ?: $department->name_ar) ?: '');
        }

        return (string) ((string) ($department->name_ar ?: $department->name_en) ?: '');
    }

    private function safeQty(mixed $value): int
    {
        $qty = (int) $value;
        if ($qty < 1) {
            return 1;
        }
        if ($qty > 4) {
            return 4;
        }

        return $qty;
    }

    private function safeConfidence(float $value): float
    {
        if ($value < 0.0) {
            return 0.0;
        }
        if ($value > 1.0) {
            return 1.0;
        }

        return round($value, 4);
    }

    /**
     * Decide whether to delegate to the local MockSuggester.
     *
     * The generic `MockProvider` returns a reversed string which is not
     * useful for this feature, so we ALWAYS use the local scorer when
     * the resolved provider is `MockProvider`.
     */
    private function shouldUseLocalMock(AiProvider $provider): bool
    {
        return $provider instanceof MockProvider
            && $provider->name() === 'mock';
    }
}
