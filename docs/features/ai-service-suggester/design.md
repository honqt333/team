# AI Service Suggester — Design Contract

> **Date:** 2026-07-09
> **Status:** Active contract for backend + frontend parallel tracks
> **Owner:** Mavis (root)

## 1. Goal

When a mechanic opens a `WorkOrder` and writes the **customer complaint** in free text, surface a ranked list of suggested services / parts from the tenant's own catalog (center-scoped, multi-tenant safe), so the mechanic can add them with one click.

## 2. Endpoint

```
POST /api/v1/work-orders/{workOrder}/suggestions
```

**Middleware chain** (in this exact order):

```php
Route::post('/v1/work-orders/{workOrder}/suggestions',
    [WorkOrderSuggestionController::class, 'suggest'])
    ->middleware([
        'auth:sanctum',
        'tenant.active',
        TrackAiUsage::class,
    ])
    ->name('api.work_orders.suggestions');
```

**Authorization**: `authorize('update', $workOrder)` — same gate as editing the work order.

## 3. Request Schema

```jsonc
POST /api/v1/work-orders/123/suggestions
Content-Type: application/json
{
  "complaint": "صوت طقطقة في الفرامل الأمامية عند الكبح",  // required, string 5..2000
  "vehicle": {                                              // optional, used as context only
    "make": "Toyota",
    "model": "Camry",
    "year": 2021,
    "plate_number": "ABC 1234",
    "odometer": 88000
  },
  "limit": 8                                                // optional, default 8, max 20
}
```

**Validation** (FormRequest or controller-level):

| Field | Rule |
| --- | --- |
| `complaint` | `required\|string\|min:5\|max:2000` |
| `vehicle` | `nullable\|array` |
| `vehicle.make` | `nullable\|string\|max:80` |
| `vehicle.model` | `nullable\|string\|max:80` |
| `vehicle.year` | `nullable\|integer\|min:1900\|max:2100` |
| `vehicle.plate_number` | `nullable\|string\|max:30` |
| `vehicle.odometer` | `nullable\|integer\|min:0\|max:9999999` |
| `limit` | `nullable\|integer\|min:1\|max:20` |

## 4. Response Schema

```jsonc
200 OK
{
  "suggestions": [
    {
      "department_id": 12,                              // int, required
      "department_name": "فرامل",                        // string, required
      "item_type": "service",                           // "service" | "part"
      "item_id": 45,                                    // int, required
      "name": "فحص نظام الفرامل",                       // string, required
      "name_en": "Brake system inspection",             // string, optional
      "qty": 1,                                         // int, default 1
      "estimated_unit_price_cents": 15000,              // int (cents), required
      "currency": "SAR",                                // string, default "SAR"
      "confidence": 0.92,                               // float 0..1, required
      "reason": "شكوى العميل من صوت — فحص وقائي",       // string, max 240
      "is_active": true                                 // bool
    }
  ],
  "meta": {
    "provider": "mock",                                 // "mock" | "openai" | "anthropic"
    "model": "gpt-4o-mini",
    "work_order_id": 123,
    "tenant_id": 7,
    "center_id": 3,
    "total_candidates": 18,                             // how many catalog items were considered
    "returned": 4
  },
  "usage": {
    "tenant_id": 7,
    "input_tokens": 142,
    "output_tokens": 88,
    "total_tokens": 230,
    "cost_micro_cents": 0
  }
}
```

**422** on validation failure, **403** on auth/authorization, **429** on throttle (use `throttle:30,1` like the existing AI route).

## 5. AI Prompt Contract

The service looks up the `Prompt` model with `key = 'work_order.suggest'` and `active = true` for the current tenant. If missing, it falls back to a hard-coded Arabic system prompt baked into the service.

**System prompt (Arabic, hard-coded fallback):**

```
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
```

**User message structure:**

```
شكوى العميل: <complaint>
نوع المركبة: <make> <model> <year>
اللوحة: <plate_number>
قراءة العداد: <odometer> كم

كتالوج المركز المتاح:
[1] (service, id=45) فحص نظام الفرامل — قسم: فرامل — 150.00 SAR
[2] (part,    id=302) تيل فرامل أمامي — قسم: فرامل — 180.00 SAR
[3] (service, id=89) تغيير زيت المحرك — قسم: المحرك — 80.00 SAR
[4] ...
```

**Catalog input is hard-bounded to 80 items max** (highest-priced first, ties broken by most-recently-updated) to stay well under token limits on small-context models.

## 6. Catalog Query (Tenant-Scoped, Center-Scoped)

In the controller, after auth and tenant context, build the candidate catalog:

```php
$services = Service::query()
    ->where('is_active', true)
    ->where('center_id', $centerId)        // explicit, even though TenantScoped
    ->orderByDesc('base_price')
    ->limit(60)
    ->get(['id', 'department_id', 'name_ar', 'name_en', 'base_price', 'min_price']);

$parts = Part::query()
    ->where('is_active', true)
    ->where('center_id', $centerId)
    ->orderByDesc('default_sale_price')
    ->limit(20)
    ->get(['id', 'name_ar', 'name_en', 'default_sale_price']);
```

> **Hard rule:** Never trust an AI-returned `item_id` blindly. After parsing, every returned `item_id` MUST be re-fetched from the DB scoped to the current tenant + center. If an AI-hallucinated id is missing, drop it and reduce `meta.returned` accordingly. The `confidence` field is AI-provided, not user-trusted for security decisions.

## 7. MockProvider Behavior

Until `OPENAI_API_KEY` or `ANTHROPIC_API_KEY` is set, `ProviderRegistry::default()` returns `MockProvider`. For the suggester, the **default mock must be domain-realistic**, not a string-reverser. Approach:

- Define a tiny local function `mockSuggestForComplaint(string $complaint, Collection $catalog): array` that:
  - Scores each catalog item by simple keyword overlap between `name_ar + name_en + description_ar + description_en` and the complaint (lowercased Arabic + English tokens).
  - Returns the top N (limit or 4, whichever is larger) with deterministic confidences derived from the score.
- This makes the demo "feel right" out of the box, without requiring a key. The mock is only used when no real key is configured.

## 8. Failure Modes

| Failure | Behavior |
| --- | --- |
| No `tenant_id` on user | 403, fail-closed (existing `TrackAiUsage` middleware) |
| No `center_id` on user | 403 with `message: 'Suggester requires a center_id.'` |
| `WorkOrder` not in tenant | 404 (route model binding + TenantScoped global scope) |
| Catalog empty (no services/parts) | 200 with `suggestions: []`, `meta.total_candidates: 0` |
| AI provider returns non-JSON | 502 with `message: 'AI returned an invalid response.'`, log the raw content for debugging |
| AI returns `item_id` not in catalog | Drop it silently, decrement `meta.returned` |
| Provider exception / timeout | 502 with `message: 'AI service unavailable.'`, log the exception class + message |
| Validation error | 422 with Laravel's standard validation payload |

## 9. Frontend Contract (Vue 3 / Inertia)

**Component**: `resources/js/Components/WorkOrders/WorkOrderSuggestionsPanel.vue`

**Props**:

| Prop | Type | Required |
| --- | --- | --- |
| `workOrder` | `Object` (Inertia prop) | yes |
| `endpoint` | `String` (defaults to `/api/v1/work-orders/{id}/suggestions`) | no |
| `compact` | `Boolean` (default `false`) | no |

**Emits**:

| Event | Payload |
| --- | --- |
| `add` | `{ itemType: 'service'\|'part', itemId: number, name: string, qty: number, departmentId: number, unitPrice: number }` |
| `error` | `{ message: string }` |

**Position in `Show.vue`**: directly after `WorkOrderComplaintAssessment`, before the tabs container. The panel reads the complaint from `workOrder.complaint` (or a `v-model` from the parent) and calls the API on mount + when the complaint changes (debounced 600ms).

**Composable**: `resources/js/Composables/useWorkOrderSuggestions.js`

Exports:

```js
const {
  suggestions,         // Ref<Array<Suggestion>>
  isLoading,           // Ref<boolean>
  error,               // Ref<string | null>
  meta,                // Ref<Meta | null>
  refresh,             // () => Promise<void>
  isStale,             // ComputedRef<boolean>
} = useWorkOrderSuggestions({ workOrder, endpoint, debounceMs: 600 });
```

**Styling rules** (Track A):

- Use the `App*` design system components (`AppButton`, `AppSelect`, `AppTextarea`).
- All copy comes from i18n (`__('...')` / `$t('...')`) — bilingual `ar` + `en` keys.
- RTL-safe layout.
- Suggestion card shows: confidence badge (high ≥0.7, medium ≥0.4, low <0.4), item name, department, price, reason, "Add" button.

## 10. Tests (Required Coverage)

### Backend (`tests/Feature/WorkOrderSuggestionTest.php`)

| # | Test | Proves |
| --- | --- | --- |
| 1 | Unauthenticated → 401 | middleware chain order is correct |
| 2 | User without `tenant_id` → 403 fail-closed | `TrackAiUsage` short-circuits before AI call |
| 3 | User in tenant A queries tenant B's work order → 404 | route model binding + global scope |
| 4 | No `OPENAI_API_KEY` set → response `provider=mock`, `cost_micro_cents=0` | MockProvider wiring |
| 5 | With `OPENAI_API_KEY` + `Http::fake` → `provider=openai`, response has OpenAI's content | OpenAIProvider wiring |
| 6 | Mock returns no `item_id` from another tenant → that item is dropped, `meta.returned` < `meta.total_candidates` | AI-hallucination defense |
| 7 | Empty catalog → 200 with `suggestions: []` | graceful empty state |
| 8 | Invalid `complaint` (<5 chars) → 422 | validation |
| 9 | Mock provider returns non-JSON → 502 | failure handling |

### Frontend (`resources/js/Composables/__tests__/useWorkOrderSuggestions.test.js`)

| # | Test | Proves |
| --- | --- | --- |
| 1 | Fetches on mount, sets `isLoading=true` then `false` | happy path |
| 2 | Debounces when `workOrder.complaint` changes | debounce 600ms |
| 3 | On 4xx/5xx, sets `error` and clears `suggestions` | error path |
| 4 | On 200, exposes `suggestions` + `meta` | contract conformance |

## 11. Out of Scope (Phase 2 follow-ups)

- Streaming the suggestions (the AI interface supports it, but the panel does a single round-trip).
- Persisting "applied" suggestions to `WorkOrderActivity` for analytics.
- Cache layer (Redis) for repeated identical complaints — Phase 3 candidate.
- Cross-center catalog (currently each suggestion is bounded to the user's `current_center_id`).
- Re-ranking with tenant historical data.

## 12. Files Expected

| Layer | Path | Purpose |
| --- | --- | --- |
| Backend | `app/Services/AI/WorkOrderSuggestionService.php` | New service — catalog build + prompt + provider call + response assembly |
| Backend | `app/Http/Controllers/Api/WorkOrderSuggestionController.php` | New controller — thin, delegates to service |
| Backend | `app/Http/Requests/WorkOrder/WorkOrderSuggestionRequest.php` | Form request with rules from §3 |
| Backend | `app/Services/AI/MockSuggester.php` | Domain-realistic mock (not the generic MockProvider) |
| Backend | `routes/api.php` | New route line |
| Backend | `database/seeders/WorkOrderSuggestionPromptSeeder.php` | Seeds the default `work_order.suggest` prompt |
| Backend | `tests/Feature/WorkOrderSuggestionTest.php` | 9 tests from §10 |
| Frontend | `resources/js/Components/WorkOrders/WorkOrderSuggestionsPanel.vue` | Panel component |
| Frontend | `resources/js/Composables/useWorkOrderSuggestions.js` | Composable |
| Frontend | `resources/js/Composables/__tests__/useWorkOrderSuggestions.test.js` | Composable test |
| Frontend | `resources/js/Pages/WorkOrders/Show.vue` | Wire panel between ComplaintAssessment and Tabs |
| Frontend | `resources/js/lang/ar.json`, `en.json` | i18n keys (panel + composable) |

## 13. Definition of Done

- [ ] `php artisan test` — all new tests pass + no existing test regressed (excluding pre-existing `ExampleTest` failure)
- [ ] `npm run build` — exit 0
- [ ] `npm run type-check` — exit 0
- [ ] `npm run test` — vitest pass
- [ ] Live API smoke: `curl -X POST` against the new endpoint with a real user returns 200 with `provider=mock` and at least 1 suggestion when the catalog has items
- [ ] Tenant isolation: a tenant A user calling the endpoint cannot surface tenant B's catalog
- [ ] Pre-commit hook accepts the commit (the devX toolchain we just fixed)
- [ ] `git log` shows the work as a clean feature branch with focused commits
