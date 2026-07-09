# Integration Gate Report — AI Work Order Suggester

- **Date:** 2026-07-09 20:24 (Asia/Riyadh)
- **Branch verified:** `integration/ai-suggester-test`
  - Created from `feature/ai-work-order-suggester-frontend` @ `5b08b9c` (the AI feature commit)
  - Merged `--no-ff` of `feature/ai-work-order-suggester-backend` (tip `3355c0c`)
  - Resulting integration tip: `4172c15 integration: merge backend branch`
- **Verifier:** Mavis / verifier (`mvs_2e0d3200ecb34e18b291d8ecd0adc623`)
- **Spec under test:** `docs/features/ai-service-suggester/design.md`

---

## Step-by-step evidence

### Step 1 — Integration branch
**Method:** Created `integration/ai-suggester-test` from `feature/ai-work-order-suggester-frontend`
(where the AI feature commit `5b08b9c feat(ai): add work order service suggester with UI panel and tests`
had just been landed) and merged `feature/ai-work-order-suggester-backend` with `--no-ff`.

**Evidence:**
```
$ git log --oneline -4
4172c15 integration: merge backend branch
5b08b9c feat(ai): add work order service suggester with UI panel and tests
3c1a627 fix(center): bypass center_scoped global scope when querying/saving working hours...
3355c0c feat(finance): separate company revenues and expenses from center context
```

**Result: PASS** (branch created and contains both feature work)

> Note: at the moment the integration branch was first opened the AI-suggester work was
> *uncommitted* in the working dir of the frontend branch (the producer-agent had
> only staged but not committed). The verifier ignored those untracked files and
> waited; the producer subsequently committed them as `5b08b9c`. The integration
> branch therefore reflects the now-committed state.

---

### Step 2 — `npm run build`
**Method:** `npm run build` (vite production build)

**Evidence (tail):**
```
... build output: 30+ assets, including new WorkOrderSuggestionsPanel chunk
✓ built in 54.52s
```

**Result: PASS** (exit 0, all assets emitted including the new `WorkOrderSuggestionsPanel`)

---

### Step 3 — `npm run type-check`
**Method:** The script `vue-tsc --noEmit || true` swallows errors; I also ran `npx vue-tsc --noEmit`
directly to verify the underlying tool agrees.

**Evidence:**
```
$ npx vue-tsc --noEmit > /tmp/typecheck.log 2>&1 ; echo "EXIT=$?"; wc -l /tmp/typecheck.log
EXIT=0
       0 /tmp/typecheck.log
```
The package.json script also returned exit 0.

**Result: PASS** — but **caveat:** the `|| true` in the `type-check` npm script is a config smell.
It would mask any future type errors. The underlying `vue-tsc` is currently clean, so the
gate passes today, but the script should be `vue-tsc --noEmit` (without `|| true`) so CI
can fail on type regressions. Not a blocker for the gate; flagging for future hardening.

---

### Step 4 — `npm run test` (vitest)
**Method:** `npm run test`

**Evidence:**
```
 ✓ resources/js/Composables/__tests__/useWorkOrderSuggestions.test.js (4 tests) 248ms
 Test Files  1 passed (1)
      Tests  4 passed (4)
   Duration  5.20s
```

**Result: PASS** — all 4 vitest cases (mount+isLoading, debounce, 4xx/5xx error path, 200 contract)
are green. This is the only piece of the deliverable that exercises the API contract from the
frontend side without needing a running server.

---

### Step 5 — `php artisan test`
**Method:** `php artisan test` (full suite, in-memory SQLite from `phpunit.xml`).

**Evidence:**
```
  Tests:    9 failed, 252 passed (1011 assertions)
  Duration: 173.40s
```

The 9 failures are **all in `Tests\Feature\WorkOrderSuggestionTest`** (the spec §10 backend tests):

| # | Spec test (design.md §10) | Expected | Got |
|---|---|---|---|
| 1 | Unauthenticated → 401 | 401 | **404** |
| 2 | User without `tenant_id` → 403 fail-closed | 403 | **404** |
| 3 | Tenant A user → tenant B work order → 404 | 404 | 404 ✓ (passes for the wrong reason — see below) |
| 4 | No `OPENAI_API_KEY` → `provider=mock`, `cost_micro_cents=0` | 200 | **404** |
| 5 | With `OPENAI_API_KEY` → `provider=openai` | 200 | **404** |
| 6 | Hallucinated cross-tenant id dropped | 200 | **404** |
| 7 | Empty catalog → 200 + empty suggestions | 200 | **404** |
| 8 | Invalid complaint → 422 | 422 | **404** |
| 9 | Mock returns non-JSON → 502 | 502 | **404** |

Every failure is the same root cause — sample from test #4:
```
Expected response status code [200] but received 404.
Failed asserting that 404 is identical to 200.

  at tests/Feature/WorkOrderSuggestionTest.php:143
```

Test #3 passes (`assertNotFound` happens to be satisfied by Laravel's "no such route" 404),
but that is **a false positive**: the assertion is satisfied by the missing route, not by
the actual tenant-isolation security boundary. Once the route exists it will exercise the
real `WorkOrder::resolveRouteBinding()` + `TenantScoped` global scope and we will get a
*real* 404 (with `Http::assertNothingSent()` proving no AI call was made).

**Result: FAIL** — 8 of 9 spec tests are red. The pre-existing `ExampleTest` passes
(`Tests\Unit\ExampleTest > that true is true ✓ 0.03s`), so the task's "only
`ExampleTest` failure is acceptable" rule is violated by a wide margin.

---

### Step 6 — Live API smoke (POST /api/v1/work-orders/{id}/suggestions, happy path)
**Method:** `php -S 127.0.0.1:18080 -t public public/index.php` (built-in PHP server)
then `curl -X POST` against the new endpoint.

**Evidence:**
```
$ curl -sI -X POST "http://127.0.0.1:18080/api/v1/work-orders/1/suggestions" \
       -H "Content-Type: application/json" -d '{"complaint":"brake noise"}' | head -1
HTTP/1.1 404 Not Found

$ curl -s -o /tmp/suggest.out -w "HTTP=%{http_code}\n" -X POST \
       -H "Content-Type: application/json" -d '{"complaint":"brake noise"}' \
       "http://127.0.0.1:18080/api/v1/work-orders/1/suggestions"
HTTP=404
```

The 404 is the application-level HTML 404 page (Laravel's `errors/404.blade.php`):
```
HTTP/1.1 404 Not Found
Content-Type: text/html; charset=utf-8
X-Correlation-Id: 7615a2e3-cce3-4892-9b94-c26f7b9481be
```

Smoke status: route is unregistered → endpoint is unreachable.

**Root cause evidence:**
```
$ grep -n "Suggestion\|suggest\|api/v1" routes/api.php
# (no matches; the file only has /api/healthz, /api/readyz, /v1/attendance/*, /v1/ai/demo/describe-vehicle)

$ php artisan route:list 2>&1 | grep -i "suggest\|api/v1/work"
# (empty — nothing matches)
```

**Result: FAIL** — endpoint unreachable; `meta.provider`, suggestion array, and all
downstream assertions are unobservable.

---

### Step 7 — Cross-tenant probe
**Method:** Spec §10 test #3 (and the design intent behind it) requires a tenant-A user
to get 404 when querying tenant-B's work order.

**Evidence:** The same 404 we saw above — but it can be reached only via Laravel's
"route not found" path, not via route-model-binding + TenantScoped global scope. We
cannot distinguish "the route was never registered" from "the route correctly
scopes the work-order to its tenant". Once the route is registered we should
re-execute the test and confirm `Http::assertNothingSent()` fires (no AI call is
made) and the response status is 404.

**Result: BLOCKED** — unable to exercise the real security boundary. The test currently
asserts the right status for the wrong reason.

---

### Step 8 — Empty-catalog probe (200, `suggestions: []`, `meta.total_candidates: 0`)
**Method:** Spec §10 test #7 (matches my probe description in the task brief) requires
the endpoint to return 200 + an empty suggestion list + `total_candidates: 0` when
no services/parts exist.

**Evidence:** Same 404 HTML page. The empty-catalog graceful handling in
`MockSuggester` and `WorkOrderSuggestionService::suggest()` is unreachable while the
route is missing.

**Result: BLOCKED** — endpoint unreachable.

---

## Adversarial probes I ran (beyond the spec §10 script)

### AP-1: Lang keys referenced by controller don't exist
**Method:** The controller says
```php
'message' => __('work_orders.suggestions.errors.tenant_required'),
```
Search for the keys.
```
$ grep -c "suggest\|Suggestion" lang/ar/work_orders.php lang/en/work_orders.php
0
0
```
**Result:** even if the route were registered, hitting the "no tenant_id" branch would
return Laravel's "raw key as message" string (e.g. `work_orders.suggestions.errors.tenant_required`),
not a human-readable Arabic/English sentence. This would fail any user-facing test that
asserts on the message text and would also break the task definition's "messages
should be translatable" expectation. **Producer gap.**
*(Note: the in-process phpunit tests that exercise this branch currently can't reach it
because the route 404s first, so this is a latent bug uncovered by reading the code.)*

### AP-2: Commit `5b08b9c` includes build cache + planner artifacts
**Method:** `git show --stat 5b08b9c | grep -E '\.opencode|\.mavis/plans'`
```
.opencode/tmp/.7bd7bcf5f5bfddfc-00000000.dylib  | Bin 0 -> 915007 bytes
.opencode/tmp/.7bd7bcf5fcbef5bc-00000000.dylib  | Bin 0 -> 915007 bytes
.opencode/tmp/.7bd7bdf5beb6fddc-00000000.dylib  | Bin 0 -> 915007 bytes
.opencode/tmp/.7bd7bdf7bd36d7bc-00000000.dylib  | Bin 0 -> 915007 bytes
.opencode/tmp/.7bd7bdf7f7fff5fc-00000000.dylib  | Bin 0 -> 915007 bytes
.opencode/tmp/.7bd7bdfdfdffd7bc-00000000.dylib  | Bin 0 -> 915007 bytes
.../.mavis/plans/ai-service-suggester.yaml     | 418
.mavis/plans/decision-cycle-1.json             |  46
```
**Result:** the `.gitignore` does not exclude `.opencode/` or `.mavis/plans/`, so a
~7 MB blob of dylib + node-compile-cache files and the planner's internal YAML/JSON
were committed to the feature branch. This bloats history and exposes internal tooling
artifacts. Not a release blocker, but flagged for cleanup.

### AP-3: `npm run type-check` masks errors
Already noted in Step 3 — `vue-tsc --noEmit || true` in `package.json`. Underlying
`vue-tsc` is currently clean so today it's a smell, not a fail. Will silently swallow
regressions until the script is fixed.

### AP-4: Authorization `meta` leakage? — checked the controller
```php
return response()->json([
    'message' => __('work_orders.suggestions.errors.tenant_required'),
], 403);
```
This is a hardcoded tenant-scope fail-closed. The 403 message passes through `__()`
and (per AP-1) will surface the raw key when the lang file is missing. No `meta`
leakage; the suggester response payload only includes tenant_id, center_id, etc.
that the user already owns. ✓

---

## What the producer must fix before this can PASS

1. **MISSING — high priority:** Register the route in `routes/api.php` exactly as
   design.md §2 prescribes:
   ```php
   use App\Http\Controllers\Api\WorkOrderSuggestionController;
   use App\Http\Middleware\TrackAiUsage;

   Route::post('/v1/work-orders/{workOrder}/suggestions',
       [WorkOrderSuggestionController::class, 'suggest'])
       ->middleware(['auth:sanctum', 'tenant.active', TrackAiUsage::class])
       ->name('api.work_orders.suggestions');
   ```
   This single fix should flip 8 of the 9 failing tests (the 9th, test #3, will
   continue to pass — but now for the **right reason**, behind real auth + binding).

2. **MISSING — blocker for graceful UX, low-priority for tests:** Add
   `work_orders.suggestions.errors.tenant_required` and `...center_required` to
   `lang/ar/work_orders.php` and `lang/en/work_orders.php`. Today the controller
   would emit the raw key as the 403 message.

3. **Hygiene:** Add `.opencode/`, `.mavis/plans/` to `.gitignore`; the next CI run
   will fail the size-pole otherwise. Not a release blocker.

4. **Hardening (post-gate):** Drop `|| true` from the `npm run type-check` script
   so the gate fails on real type regressions. Today the underlying `vue-tsc` is
   clean so this can land in any follow-up.

After items #1 and #2 are fixed, the integration gate should be re-run with the
same probe script. Cross-tenant (#3) and empty-catalog (#7) tests will then exercise
the real implementations.

---

## Summary (TL;DR)

| Step | Command / probe | Result |
|---|---|---|
| 1  | Create integration branch | PASS |
| 2  | `npm run build` (exit 0) | PASS |
| 3  | `npm run type-check` (vue-tsc clean) | PASS (script smells) |
| 4  | `npm run test` (4 vitest) | PASS (4/4) |
| 5  | `php artisan test` (only ExampleTest pre-existing fail allowed) | **FAIL** — 8 WorkOrderSuggestionTest tests fail with 404 (route missing); test #3 passes for the wrong reason |
| 6  | Live API smoke: `POST /api/v1/work-orders/1/suggestions` | **FAIL** — 404 (route not registered) |
| 7  | Cross-tenant probe | **BLOCKED** — cannot exercise; status is 404 but for the wrong reason |
| 8  | Empty-catalog probe | **BLOCKED** — cannot exercise; endpoint unreachable |

The frontend (build, type-check, vitest) is fully integrated. The backend is not
wired up: `routes/api.php` has no entry for `POST /api/v1/work-orders/{workOrder}/suggestions`,
the lang keys referenced by the error paths don't exist, and `5b08b9c` accidentally
committed ~7 MB of untracked build cache and planner artifacts.

---

VERDICT: FAIL — Route `POST /api/v1/work-orders/{workOrder}/suggestions` is not registered in `routes/api.php` (design.md §2); 8 of 9 spec §10 tests fail with 404; live endpoint returns 404; lang keys `work_orders.suggestions.errors.{tenant_required,center_required}` referenced by `WorkOrderSuggestionController` are missing from `lang/{ar,en}/work_orders.php`.
