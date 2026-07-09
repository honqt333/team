# Carag AI Layer

Backend-only AI foundation for provider abstraction, deterministic tests, and per-tenant usage tracking.

## Files and responsibilities

- `AiProvider` defines the common provider contract: `complete`, `stream`, `name`, and `estimateCost`.
- `CompletionRequest` carries `model`, `messages`, `temperature`, `maxTokens`, and a required `tenantId`.
- `CompletionResponse` carries `content`, token counts, `cost` in micro-cents, `provider`, and `model`.
- `ProviderRegistry` chooses OpenAI, Anthropic, or Mock. It fails safe to `MockProvider` when a requested provider has no API key.
- `Providers/MockProvider` is deterministic and reverses the last user message, which makes smoke tests possible without external calls.
- `App\Models\Prompt` uses `TenantScoped`, SoftDeletes, and `forTenant()` / `active()` scopes so prompt reads and creates stay tenant-safe.

## Add a provider

1. Create a class under `app/Services/AI/Providers` that implements `AiProvider`.
2. Read credentials from `config('services.<provider>.key')` and throw if the concrete provider is used without a key.
3. Keep a provider-local pricing table in micro-cents per token.
4. Return a populated `CompletionResponse` from `complete()`.
5. Add the provider name to `ProviderRegistry::for()` and, if appropriate, `ProviderRegistry::default()`.

## Usage tracking and tenant billing

Attach `TrackAiUsage` to AI endpoints. The middleware fails closed before cost can be tracked when `tenant_id` is missing from the authenticated user. Controllers should put the provider response on the request attributes:

```php
$request->attributes->set('ai_usage', $completionResponse);
```

The middleware logs an `ai.usage` event with:

- `tenant_id`
- `provider`
- `model`
- `input_tokens`
- `output_tokens`
- `total_tokens`
- `cost_micro_cents`
- route and request metadata

For billing, consume these structured log events per tenant and aggregate `cost_micro_cents`. If a database-backed ledger is added later, write from the same middleware after the log line so all AI usage remains centralized.

> Note: if the app does not define a `logging.channels.ai` channel yet, the middleware writes to `storage/logs/ai.log` with a built logger to keep this backend slice self-contained.

## Demo endpoint

Route name: `api.ai.demo.describe`

Path when served by Laravel API routes:

```text
POST /api/v1/ai/demo/describe-vehicle
```

Example local request:

```bash
php artisan serve

curl -X POST http://127.0.0.1:8000/api/v1/ai/demo/describe-vehicle \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <SANCTUM_TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{
    "make": "Toyota",
    "model": "Camry",
    "year": 2021,
    "plate_number": "ABC 1234",
    "color": "White",
    "odometer": 88000,
    "condition": "Needs inspection before quote"
  }'
```

## Test with MockProvider and no API key

Remove `OPENAI_API_KEY` and `ANTHROPIC_API_KEY` from the environment, then run the demo endpoint. `ProviderRegistry::for('openai')` returns `MockProvider`, so the response should contain:

```json
{
  "provider": "mock",
  "usage": {
    "cost_micro_cents": 0
  }
}
```

The description starts with `-- mock fallback_for=openai input_tokens=... --` and then the reversed user prompt. This proves the abstraction works without a production provider call.

Automated coverage for this behavior lives in `tests/Feature/AiDemoTest.php` and covers unauthenticated requests, tenant fail-closed behavior, mock fallback, and OpenAI routing through `Http::fake()`.

## Prompt seed smoke command

After migrating, seed one prompt manually for a local tenant:

```bash
php artisan migrate
php artisan tinker --execute="App\\Models\\Prompt::updateOrCreate(['tenant_id' => 1, 'key' => 'vehicle.describe', 'version' => 1], ['content' => 'Describe the vehicle for a service advisor.', 'model' => 'gpt-4o-mini', 'temperature' => 0.3, 'active' => true]);"
```
