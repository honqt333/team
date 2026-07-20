# 🎯 Phase 2 — Carag V2 Master Recovery Plan (Part 2)
## Resilience & Hardening | شهر 2 | النتيجة: 95% → 100%

## 📋 السياق

أنت تعمل على **Carag V2** (Khidmh Pro) — نظام إدارة ورش سيارات SaaS مبني على Laravel 12 + Vue 3 + Inertia.js. **Phase 1 مكتمل ومُراجَع** (Score: 95/100, 292+29 tests passing).

**الـ Stack:**
- Laravel 12 + PHP 8.3
- Vue 3 + Composition API
- Inertia.js 2 + Vite
- MySQL 8.0 + SQLite (CI) + Redis 7
- Sanctum + Spatie Permission
- 110 Models, 121 Controllers, 293 Vue files, 462 routes

**الـ Reference:**
- 📄 `docs/audit/Carag-V2-Master-Recovery-Plan.md` (المرجع الأصلي)
- 📄 `docs/audit/Carag-V2-Phase-0-Review.md` (مراجعة Phase 0)
- 📄 `docs/audit/Carag-V2-Phase-1-Review.md` (مراجعة Phase 1)
- 📄 `docs/audit/Carag-V2-Phase-1-Fixes-Review.md` (إصلاحات Phase 1)
- 📄 `docs/EVENTS.md` (هيكل Events/Listeners)
- 📄 `~/Desktop/Carag-V2-Phase-1-Fixes-Review.md` (التقرير المُسلَّم)

**الـ Branch الحالي:** `feature/phase-1-cleanup-and-phase-2-setup`
**Target branch:** `feature/audit-master-plan-zxz80` (للـ merge لاحقاً)

**Target:** من **95% → 100%** (شهر واحد)
- 🟠 5 جولات من hardening
- 🟠 Coverage for remaining listeners / services
- 🟠 TypeScript migration kickoff
- 🟠 Performance baseline
- 🟠 Operational readiness (logging, monitoring, error tracking)

---

## 🎯 الأهداف

### Goal #1: 100% Test Coverage on Critical Path (Week 1-2)
**ما الذي يعنيه هذا:**
كل listener، service method، و policy method في الـ critical path (WorkOrder, Payment, Invoice) لازم يكون لها unit test.

**Critical path entities:**
- `App\Services\InvoiceService` (12 methods)
- `App\Services\PaymentService`
- `App\Services\WorkOrderPartsService`
- `App\Services\InventoryService`
- `App\Services\TenantSetupService` (sensitive — handles role seeding)
- `App\Policies\*` (16 policies)
- All `App\Listeners\*`

**Success criteria:**
- `php artisan test` reports ≥ 400 passing tests (was 292)
- Coverage report shows ≥ 80% on critical path

### Goal #2: TypeScript Migration Kickoff (Week 2-3)
**ما الذي يعنيه هذا:**
ابدأ تحويل الـ components من `.vue + JS` إلى `.vue + <script setup lang="ts">`. الهدف: 30% من الـ components الرئيسية تستخدم TS.

**Files to convert first (high traffic, high bug potential):**
- `resources/js/Composables/useConfirm.ts` (already TS)
- `resources/js/Composables/useWorkOrderItems.js` → `.ts`
- `resources/js/Composables/useWorkOrderStatus.js` → `.ts`
- `resources/js/Composables/useWorkOrderNotes.js` → `.ts`
- `resources/js/Composables/useTheme.ts` (already TS)
- `resources/js/Pages/WorkOrders/Show.vue` (the biggest page)

**Prerequisites:**
- Add `tsconfig.json` (already exists)
- Add `@vue/tsconfig` to `tsconfig.json extends
- Add `vue-tsc` for type checking
- Add CI step: `npx vue-tsc --noEmit`

**Success criteria:**
- 30% of Composables are .ts
- `vue-tsc --noEmit` runs in CI without errors

### Goal #3: Performance Baseline (Week 3)
**ما الذي يعنيه هذا:**
- Add `barryvdh/laravel-debugbar` (dev only) for query/log profiling
- Identify N+1 query patterns in:
  - `WorkOrdersCrudTest` paths (List, Show)
  - `CustomerController::index` (filter + sort)
  - `InventoryBalanceController` (joins)
- Add `php artisan test --profile` results to docs

**Tools:**
- Laravel Telescope (or just Debugbar)
- `EXPLAIN` analysis for slow queries

**Success criteria:**
- All `index` endpoints respond in < 500ms (SQLite, 1000 records)
- `WorkOrders::Show` page loads in < 800ms
- Documented baseline in `docs/PERFORMANCE.md`

### Goal #4: Error Tracking & Logging (Week 3-4)
**ما الذي يعنيه هذا:**
- Integrate Sentry (or alternative: `spatie/laravel-flare`)
- Add request ID middleware (`X-Request-Id` for tracing)
- Add structured logging: `Log::info('event', ['key' => 'value'])`
- Add a "recent errors" admin page (read from `failed_jobs` + `logs`)

**Files to add:**
- `app/Http/Middleware/RequestId.php`
- `config/logging.php` (new channel: `daily-detailed`)
- `app/Http/Controllers/App/System/ErrorLogController.php` (admin-only)

**Success criteria:**
- Every request has an `X-Request-Id` header
- Sentry captures at least 1 test exception in dev
- Failed jobs show in admin UI

### Goal #5: TypeScript Strict Mode + tsconfig Cleanup (Week 4)
**ما الذي يعناه هذا:**
- Enable `strict: true` in `tsconfig.json`
- Add `noImplicitAny`, `strictNullChecks` to config
- Fix all resulting errors in the converted files

**Success criteria:**
- `tsc --noEmit` passes with `strict: true`
- No `any` types in the 30% migrated files

---

## 📅 Timeline

### Week 1-2: Coverage Sprint
- Day 1-2: Unit tests for `InvoiceService` (all 12 methods)
- Day 3: Unit tests for `PaymentService`
- Day 4: Unit tests for `WorkOrderPartsService`
- Day 5: Unit tests for `InventoryService` (extending current 12)
- Day 6-7: Unit tests for all 16 policies
- Day 8-9: Unit tests for remaining listeners
- Day 10: Integration test: full WO → invoice → payment → refund flow

### Week 2-3: TypeScript Kickoff
- Day 11: Add `vue-tsc` + update `tsconfig.json`
- Day 12-14: Convert 5 Composables
- Day 15-16: Convert `Pages/WorkOrders/Show.vue` script to TS
- Day 17: Add CI step for `vue-tsc --noEmit`

### Week 3: Performance
- Day 18: Add Debugbar, profile list endpoints
- Day 19-20: Fix N+1 queries
- Day 21: Document baseline in `docs/PERFORMANCE.md`

### Week 3-4: Error Tracking
- Day 22: Add Sentry SDK + env var
- Day 23: Add RequestId middleware
- Day 24: Update logging channels
- Day 25: Build admin Error Log UI

### Week 4: Strict TypeScript
- Day 26-27: Enable strict mode, fix errors
- Day 28: Run final test suite

---

## 🔨 Critical Implementation Notes

### CI integration for `vue-tsc`
Add to `.github/workflows/tests.yml` (or similar):
```yaml
- name: TypeScript type check
  run: npx vue-tsc --noEmit
```

### Performance testing approach
Don't add a "performance test" suite — that's flaky. Instead:
- Manual benchmark: time the dev server with a stopwatch
- Document the baseline
- Re-benchmark after each Phase 2 PR that touches a list endpoint

### Sentry in tests
- DO NOT send test exceptions to real Sentry
- Use Sentry's `dry-run` mode in `phpunit.xml`
- Or use a `null` Sentry transport in test env

---

## 🚦 معايير النجاح

```bash
# 1. Test count
php artisan test
# Expected: 400+ passing (was 292)

# 2. Frontend tests
npm run test
# Expected: 50+ passing (was 29)

# 3. TypeScript check
npx vue-tsc --noEmit
# Expected: 0 errors

# 4. Performance baseline documented
cat docs/PERFORMANCE.md
# Expected: numbers + methodology

# 5. Sentry integration
grep "SENTRY_LARAVEL_DSN" .env.example
# Expected: variable present

# 6. Strict TypeScript
grep '"strict": true' tsconfig.json
# Expected: present

# 7. Score estimate
# 95% (Phase 1) + 2% (coverage) + 1.5% (TS) + 1% (perf) + 0.5% (ops) = 100%
```

**Final Score Target: 100/100** ⬆️

---

## 📤 التقرير النهائي

عند الانتهاء، أنشئ تقرير في `docs/audit/phase-2-completion.md` + نسخة على Desktop:

```markdown
# Phase 2 Completion Report

## Status: ✅ COMPLETE / ⚠️ PARTIAL / ❌ INCOMPLETE

## Summary
- [x] 400+ tests passing (was 292)
- [x] 50+ frontend tests passing (was 29)
- [x] TypeScript: 30% of Composables + Show.vue
- [x] vue-tsc runs in CI
- [x] Performance baseline documented
- [x] Sentry integrated (dry-run in tests)
- [x] RequestId middleware active
- [x] Admin Error Log UI
- [x] strict TypeScript enabled

## Score: 100/100 (from 95)

## Commits
- abc123 phase-2(d1): ...
- ...

## Phase 3 Recommendations
- Migrate remaining .js → .ts (50% target)
- Add Playwright e2e tests
- Setup CI/CD for staging
- ...
```

---

## ⚠️ Risks

1. **Time estimate is aggressive.** 28 working days is tight for 100%.
   Fallback: cut Goal #5 (strict TS) to Phase 3, ship at 99/100.
2. **N+1 fix scope creep.** Performance work tends to expand. Cap at
   2 days. Anything bigger → defer to Phase 3.
3. **Sentry rate limits.** Use sampling in prod (0.1 = 10%) to avoid
   surprise bills.

---

## 🎯 Phase 3 Preview (للتفكير فقط، لا تلتزم)

- Full TypeScript migration (100% of Composables)
- Playwright e2e tests
- CI/CD pipeline (GitHub Actions → staging)
- Multi-currency support
- API rate limiting
- API documentation (OpenAPI/Swagger)
- Mobile app (PWA or React Native wrapper)

---
