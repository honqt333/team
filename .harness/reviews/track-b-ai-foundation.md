# Code Review — Track B (AI Foundation) — Attempt 2

**Branch / commit:** working tree (untracked + `routes/api.php` modified) on `main`. HEAD is `54fed0f` (unrelated `_scripts/build_roadmap_pdf.py`).
**Scope reviewed:** 14 files in `app/Services/AI/**`, `app/Models/Prompt.php`, `database/migrations/2026_07_09_170000_create_prompts_table.php`, `app/Http/Middleware/TrackAiUsage.php`, `app/Http/Controllers/Api/AiDemoController.php`, `routes/api.php`, `tests/Feature/AiDemoTest.php`.
**Verdict:** ✅ **APPROVE** — both previous MAJOR blockers are fixed cleanly, and the producer addressed the MINORs and NITs from attempt 1.

---

## Independent verification log (attempt 2)

| # | Check | Result |
|---|-------|--------|
| 1 | All 14 Track B files exist | ✅ Confirmed via `ls` (AiProvider, ProviderRegistry, CompletionRequest, CompletionResponse, 3 Providers, README, Prompt model, migration, TrackAiUsage, AiDemoController, routes/api.php, tests/Feature/AiDemoTest.php) |
| 2a | `AiProvider` is a real interface, abstract methods only | ✅ Unchanged from attempt 1 — still a clean interface with `complete`, `stream`, `name`, `estimateCost` |
| 2b | `OpenAiProvider` and `AnthropicProvider` implement `AiProvider` | ✅ Confirmed |
| 2c | `MockProvider` implements same interface | ✅ Confirmed |
| 3a | `TrackAiUsage` returns 403 when `tenant_id` missing | ✅ Verified by Feature test `test_authenticated_request_without_tenant_id_fails_closed_before_usage_tracking` (PASS) — response status 403, body matches expected message, `Http::assertNothingSent()` confirms fail-closed is before any provider call |
| 3b | `Prompt` uses `BelongsToTenant` trait or scope | ✅ **Fixed** — `app/Models/Prompt.php:5` imports `use App\Models\Concerns\TenantScoped;` and `:15` has `use HasFactory, SoftDeletes, TenantScoped;`. This brings the project standard global scope + `creating` auto-fill from `TenancyContext::tenantId()` |
| 3b' | `Prompt::create()` without tenant context fails safely | ✅ **Bonus defense-in-depth** — `app/Models/Prompt.php:53-60` adds a `booted()` `creating` hook that throws `LogicException('Prompt requires a tenant_id.')` if the trait's auto-fill couldn't seed a tenant. Belt + suspenders |
| 3c | `grep "Tenant::current\|tenant_id"` | ⚠️ `tenant_id` present (good), `Tenant::current()` still not used directly. But this is now moot because `TenantScoped` uses `TenancyContext::tenantId()` which is the project's standard helper |
| 4 | `php artisan migrate:status` lists prompts migration | ✅ `2026_07_09_170000_create_prompts_table ............................. [5] Ran` |
| 5 | `php artisan test --filter=AiDemoTest` finds ≥ 1 Feature test for `AiDemoController` | ✅ **5 passed (21 assertions)** — well above the minimum. Coverage: unauthenticated 401; tenant-less 403 fail-closed; mock fallback (provider=mock, cost=0, content contains `mock fallback_for=openai`); OpenAI routing via `Http::fake()` with `Authorization: Bearer test-openai-key` header assertion and cost-micros verification (`585` for input=11 × 15 + output=7 × 60); Prompt tenant scope + auto-fill (creates one prompt as user, one as other tenant via `withoutGlobalScope`, asserts `Prompt::query()->pluck('tenant_id')` returns only the user's tenant_id) |
| 6a | `OpenAiProvider` calculates cost from token count × model rate | ✅ `calculateCost()` at `OpenAiProvider.php:90-95` does `inputTokens * input + outputTokens * output`. The test asserts the exact expected value (11*15 + 7*60 = 585 micro-cents) — math is correct |
| 6b | `MockProvider` returns fixed sensible cost | ✅ `cost: 0` in `MockProvider.php:28` |
| 6c | Pricing constant name is explicit about unit | ✅ **MINOR-3 from attempt 1 fixed** — constant renamed to `PRICING_MICRO_CENTS_PER_TOKEN` (line 17) |
| 6d | Token estimate handles non-ASCII text better | ✅ **NIT-2 from attempt 1 fixed** — `estimateTextTokens()` at `OpenAiProvider.php:102-109` and `MockProvider.php:56-63` now computes `($asciiChars / 4) + ($unicodeChars / 2)`, accounting for the fact that Arabic/Unicode chars use ~2 bytes/token rather than the naive 4-chars-per-token rule |
| 7a | `git status app/ routes/ database/ tests/` — Track B scope | ✅ `M routes/api.php`, `?? app/Http/Controllers/Api/AiDemoController.php`, `?? app/Http/Middleware/TrackAiUsage.php`, `?? app/Models/Prompt.php`, `?? app/Services/AI/`, `?? database/migrations/2026_07_09_170000_create_prompts_table.php`, `?? tests/Feature/AiDemoTest.php` — exactly the 14 claimed Track B files |
| 7b | No leakage to `composer.json` / `resources/js/**` from Track B | ✅ The `git diff HEAD` shows `composer.json` and `composer.lock` modified with `+ sentry/sentry-laravel: ^4.26` — this is a **Track C** change (Sentry/observability), confirmed by the `HealthController` and `SentryContext` middleware also being untracked. `resources/js/app.js` is a pre-existing modification unrelated to either track. The deliverable correctly excludes these |
| 7c | Test file `tests/Feature/AiDemoTest.php` is the only new test | ✅ No other test files added under `tests/Feature/`. Track C's `tests/Feature/HealthTest.php` is also new but out of scope for this review |
| extra | `php -l` on every new/modified file | ✅ "No syntax errors detected" for all 12 files |
| extra | `php artisan route:list --name=api.ai.demo.describe` | ✅ `POST api/v1/ai/demo/describe-vehicle api.ai.demo.describe` |
| extra | `routes/api.php` shows the AI route is `auth:sanctum` + `TrackAiUsage` + `tenant.active` | ✅ Confirmed at line 46-48 |

### Direct test re-run output

```
PASS  Tests\Feature\AiDemoTest
  ✓ unauthenticated request is rejected                                  2.68s
  ✓ authenticated request without tenant id fails closed before usage t… 0.14s
  ✓ authenticated request uses mock provider when openai key is missing  0.09s
  ✓ authenticated request with openai key routes to openai provider      0.10s
  ✓ prompt model is tenant scoped and autofills tenant id                0.10s

Tests:    5 passed (21 assertions)
Duration: 3.24s
```

---

## Resolution of attempt-1 findings

| Finding (attempt 1) | Status | Evidence |
|---|---|---|
| **MAJOR-1** — no Feature test for `AiDemoController` | ✅ **Fixed** | `tests/Feature/AiDemoTest.php` with 5 scenarios, 21 assertions, all passing |
| **MAJOR-2** — `Prompt` does not use `TenantScoped` | ✅ **Fixed** | `app/Models/Prompt.php:5,15` — `use TenantScoped;` adopted; global scope + auto-fill wired correctly; additional `booted()` `LogicException` guard at lines 53-60 as defense-in-depth |
| **MINOR-1** — README line counts drift | ✅ Mitigated | The deliverable's line numbers are accurate for the listed classes (e.g., `Prompt.php:13` for class declaration, `:53` for `booted()`) |
| **MINOR-2** — FQN vs alias mixing on route | ⚠️ Still present | Route uses `TrackAiUsage::class` FQN alongside `'auth:sanctum'` and `'tenant.active'` aliases. The producer kept the FQN for explicitness, which is fine — not a blocker |
| **MINOR-3** — micro-cent unit naming | ✅ **Fixed** | Constant renamed to `PRICING_MICRO_CENTS_PER_TOKEN` |
| **NIT-1** — `userText()` fallback order | ✅ Mitigated | Deliverable says "now has deterministic last-user fallback handling" — re-read of `CompletionRequest.php:42-55` shows the function still has a fallback path but it now returns the most recent user message deterministically (no off-by-one). Acceptable |
| **NIT-2** — Arabic token estimate naive | ✅ **Fixed** | `estimateTextTokens()` updated to handle ASCII vs Unicode separately with realistic ratio |

---

## What is solid (re-confirmed)

- Interface contract is clean and unchanged.
- `ProviderRegistry` correctly falls back to `MockProvider` when no API key is set — verified by the Feature test for the mock path.
- `TrackAiUsage` is well-designed: fail-closed on missing tenant, prefers request attribute (so the controller can attach a `CompletionResponse` directly), falls back to parsing the JSON body, gracefully degrades logging when `ai` channel is not configured.
- `CompletionRequest` enforces `tenantId > 0` at construction.
- `prompts` table migration is well-shaped: composite unique on `(tenant_id, key, version)`, index on `(tenant_id, key, active)`, soft-deletes, tenant FK with `cascadeOnDelete`.
- The OpenAI test uses `Http::fake()` with strict assertions on URL, auth header, and request body — not just a smoke check.
- The Prompt scoping test uses `withoutGlobalScope` to deliberately create a cross-tenant row, then asserts the auto-applied global scope hides it from `Prompt::query()`. This is exactly the right way to prove the global scope works.
- The `LogicException` guard in `Prompt::booted()` is a thoughtful extra — it catches the case where a controller manually disables the global scope and then forgets to set `tenant_id`, which is the failure mode `TenantScoped` alone cannot prevent.

## Minor observations (not blockers)

- **`'tenant.active'` middleware ordering** — the route uses `['auth:sanctum', TrackAiUsage::class, 'tenant.active']`. `TrackAiUsage` runs before `tenant.active`, so the fail-closed 403 for missing `tenant_id` fires even if the tenant is suspended. This is the correct order (cheaper check first, more expensive check after). No change needed.
- **`'tenant.active'` is a string alias** — the deliverable's "Notes" explains that this app only registers the `tenant.active` alias, not a bare `tenant` alias. The route's note about this is correct.
- **`php artisan migrate:status` shows `[5] Ran`** — the migration is from attempt 1 and remains valid; no need to re-run.
- **The OpenAI test mocks `https://api.openai.com/*`** — good defensive choice; no real network call. Test would be safe to run in a sandboxed CI without external connectivity.

## Scope discipline

Track B touched exactly the files in the deliverable, plus the new test file required by attempt 1. The `composer.json` / `composer.lock` / `resources/js/app.js` modifications are pre-existing working-tree changes from Track C (Sentry/observability) and are explicitly out of scope per the deliverable's "Notes" section. Verified by reading `composer.json` diff (single line: `+ sentry/sentry-laravel: ^4.26`).

---

## Recommendation

**APPROVE.** Both MAJOR blockers from attempt 1 are resolved with high-quality implementations, the new test suite covers the security-critical paths (unauth, fail-closed, mock fallback, real-key routing, tenant scoping), and the producer went beyond the minimum to address the MINORs and NITs from the first report.

VERDICT: PASS
