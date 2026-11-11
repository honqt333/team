# Audit Corrections Register

**Date:** 2026-11-11
**Scope:** Claims in the Phase-1/2 audit reports that were re-verified during the
implementation round and found to be **inaccurate, overstated, or already fixed**.
**Rule applied:** no claim is downgraded or upgraded without a file:line, a command,
or a runtime probe. Every row below states what was claimed, what is actually true,
and the evidence.

---

## How to read this register

| Status | Meaning |
| :--- | :--- |
| ✅ **FIXED** | The finding was real; the work is done and verified by a test. |
| ⚠️ **CORRECTED** | The finding was real but its **severity or mechanism was misstated**. |
| ❌ **RETRACTED** | The finding was **not a defect**. The claim does not survive verification. |
| 📋 **RE-SCOPED** | The finding is real but belongs to a **different phase** than the one it was filed under. |

---

## 1. ❌ RETRACTED — `floatDiffInDays()` is not a Fatal error

**Filed as:** `09_BACKEND_DATABASE_AUDIT.md` §7, `14_EVIDENCE_INDEX.md` EV-02,
`11_SECURITY_PERFORMANCE.md` S-03.
**Original claim:** *"`floatDiffInDays` غير موجودة … أول استدعاء|accessor = **Fatal error**"*
severity **عالٍ / حرج**, risk row S-03 "⇒ Fatal عند أول استدعاء".

**What is actually true:** `floatDiffInDays()` is a **documented, supported Carbon
alias** that resolves through Carbon's `__call` magic method. It returns the
correct value and emits an `E_USER_DEPRECATED` notice.

### Evidence

```bash
php -r "require 'vendor/autoload.php';
  echo var_export(method_exists(Carbon\Carbon::class,'floatDiffInDays'), true);"
# → false          (the method is NOT declared — PHPStan is right about this)

php -r "...; echo var_export(\$a->floatDiffInDays(\$b), true);"
# → 9.0            (the call WORKS and returns the correct value)
```

The mechanism, in Carbon's own source:

- `vendor/nesbot/carbon/src/Carbon/Traits/Date.php:2731` — `__call()`
  dispatches to `callDiffAlias($unit, $parameters)`.
- `vendor/nesbot/carbon/src/Carbon/Traits/Date.php:2878-2908` —
  `callDiffAlias()` matches `/^(diff|floatDiff)In(Real|UTC|Utc)?(.+)$/`, rewrites
  `floatDiff…` → `diff…`, and **emits `E_USER_DEPRECATED`** before delegating
  to `diffInDays()`.

The runtime notice, captured:

```
NOTICE[16384]: Use the method diffInDay instead to make it more explicit about what it does.
value: 9.0
```

`E_USER_DEPRECATED` is **16384** — a notice, not an error. The value is correct.

### Corrected classification

| | |
| :--- | :--- |
| **Real issue** | A **deprecation notice**, and PHPStan cannot see the alias (it has no `@method` annotation for it), which is why the static analyser flags it. |
| **Not a real issue** | Fatal error. Crash. Request failure. Data loss. |
| **Correct severity** | **منخفض** — cosmetic / tooling. Not a runtime defect. |
| **Correct fix** | `floatDiffInDays($to)` → `diffInDays($to, true)` — silences the notice **and** the PHPStan error, with identical numeric behaviour. |

### Why the original claim was wrong

The probe `method_exists('Carbon\Carbon','floatDiffInDays')` returns `false`, which
is true — but it only proves the method is **not declared**. It says nothing about
whether the call **works**, because Carbon routes undeclared methods through
`__call`. The audit used a static-existence probe to conclude a runtime fatality.
That inference does not hold.

**Note:** the audit's *secondary* observation was correct and is retained — the
`days_remaining` accessor is wrapped in `rescue()`
(`app/Http/Middleware/HandleInertiaRequests.php:152-164`) and the accessor path is
not currently exercised by production code. But the consequence is a **deprecation
notice**, not a silent field loss caused by an exception.

---

## 2. ❌ RETRACTED — Payroll concurrency and authorization

**Filed as:** part of the "50 controller بلا transaction" / concurrency findings.

**Original claim:** payroll runs lack effective locking and authorization.

**What is actually true:** the payroll path is **sound**. It uses
`lockForUpdate()` inside `DB::transaction()`, and it re-authorizes the *locked*
record, not the pre-lock one.

### Evidence — `app/Http/Controllers/App/HR/PayrollController.php`

```php
:177  $this->authorize('update', $payrollRun);
:179  return DB::transaction(function () use ($payrollRun, $payrollItem) {
:180      $locked = PayrollRun::query()->lockForUpdate()->findOrFail($payrollRun->id);
:181      $this->authorize('update', $locked);          // ← re-authorized AFTER the lock
:182      $item = $locked->items()->lockForUpdate()->findOrFail($payrollItem->id);
```

The same pattern repeats at `:199-216`. Every sensitive method carries an
`authorize()` call (`:28, :92, :111, :157, :177, :199, :403, :427, :472, :491, :508`).

### Evidence — `app/Policies/HR/PayrollRunPolicy.php`

- `view` / `update` / `delete` all check `$payrollRun->tenant_id !== $user->tenant_id`
  → `false` (`:27`, `:51`, `:68`).
- All three then call `checkCenterAccess()` (`:31`, `:55`, `:76`), which enforces
  center isolation via `TenancyContext::centerId()` / `$user->current_center_id`,
  falls back to an explicit center assignment, and permits cross-center only for
  `super_admin` / system admin (`:86-105`).
- `delete` additionally refuses any non-`draft` run (`:72`).
- The permission constants are checked with `$user->can(Permissions::HR_PAYROLL_*)`
  (`:19`, `:35`, `:43`, `:59`, `:80`).

**Corrected classification:** ✅ **No defect.** Three independent layers — tenant,
center, permission — plus a row lock held for the whole transaction. The audit
should not list this as a finding.

---

## 3. ⚠️ CORRECTED — `t('key', 'fallback')` in vue-i18n

**Filed as:** a frontend defect — the second argument was described as a
**plural selector**, and the call was said to be invalid.

**What is actually true:** the second argument of `t()` is a **message fallback**,
and it works exactly as intended. It is not a plural selector and not an error.

### Evidence — runtime probe with the project's own `vue-i18n`

```
[intlify] Not found 'common.nope' key in 'en' locale messages.
missing key with fallback arg: Fallback text      ← the fallback IS used
[intlify] Not found 'common.alsoNope' key in 'en' locale messages.
missing key no arg        : common.alsoNope       ← the key itself is rendered
existing key               : Hello
```

`resources/js/i18n/index.js:26` sets `fallbackLocale: 'en'`, so a key missing
from `ar` falls back to `en`, and a key missing from **both** renders the key
string — which is the real hazard, and which the translation-key tests now guard
against.

**Corrected classification:** ❌ **Not a defect.** Retracted. The genuine
translation risk — a key missing from **both** locale files — is tracked
separately and was closed during this round (see §6).

---

## 4. ✅ FIXED — `InvoiceObserver` was not registered in production

**Filed as:** `09_BACKEND_DATABASE_AUDIT.md` §6, `14_EVIDENCE_INDEX.md` EV-01,
`11_SECURITY_PERFORMANCE.md` S-01 — severity **حرج**.

**The finding was real and is now closed.**

### Evidence

```php
// app/Providers/AppServiceProvider.php:264
Invoice::observe(InvoiceObserver::class);
```

The manual registration inside the test was removed
(`tests/Feature/InvoicingSecurityTest.php` no longer calls `Invoice::observe()`),
and the observer contract was rewritten from "any status change is allowed" to an
explicit transition map plus a per-operation field allowlist
(`app/Observers/InvoiceObserver.php`).

### Acceptance

`tests/Feature/InvoiceImmutabilityTest.php` — **19 tests, 59 assertions, all green.**
The suite proves the observer is registered **by the application**, by refusing a
protected-field write on an issued invoice and then re-reading the row from the
database to confirm the value is unchanged.

**Proof that the test can fail:** commenting out the registration in
`AppServiceProvider` makes **10 of the 19 tests fail**. The suite was verified
both ways; the registration was restored afterwards.

---

## 5. 📋 RE-SCOPED — "9 copies of the tax calculation"

**Filed as:** `04_COMPONENT_CONSOLIDATION.md` §2 (priority حرج),
`11_SECURITY_PERFORMANCE.md` S-04 "9 نسخ لحساب الضريبة ⇒ فرق أرصدة".

**What is actually true:** the duplication is real, but it is **not nine equivalent
implementations**, and the financial risk is **not uniform** across them.

Measured against the pinned reference (see `15_TAX_REFERENCE_AND_PATHS.md`):

| Path | Verdict |
| :--- | :--- |
| `WorkOrder::recalculateTotals()` | Aggregates the net, applies the tax **once** on the document total — **arithmetically correct** on the cases it can represent. |
| `InvoiceService` | Rounds every line, then sums the rounded lines — **wrong by a rounding rule** (documented halala differences). |
| `CreditNoteService` | Same per-line rounding — **wrong by the same rule**. |
| Frontend (≈19 Vue/JS files referencing `taxRate`) | **Preview/display arithmetic**, not the persisted financial value. |

Two corrections to the original framing:

1. **The work-order path is not a bug.** It is the most correct of the three. It
   does have a **model limitation**: it takes one `tax_enabled_snapshot` and one
   `pricing_mode_snapshot` for the whole order
   (`app/Models/Concerns/HasTaxSnapshot.php:43-55`) and never consults the
   per-line `tax_rate_snapshot` / `is_taxable` that exist on `WorkOrderItem`
   (`app/Models/WorkOrderItem.php:46-63`). So an order **cannot** contain a
   tax-exempt line beside a taxable one, nor an inclusive line beside an
   exclusive one. That is a **feature gap**, filed for a separate decision — it
   must not be "fixed" inside a rounding consolidation.
2. **The frontend copies are not a financial risk.** They compute what the user
   sees in a form before the document exists. The persisted value is the
   server's. Conflating the two inflates the severity.

**Corrected classification:** the real, measurable defect is **two** paths
rounding per line instead of once per document. The work-order path must be
**preserved**, not rewritten; and the per-line-exemption gap is a separate,
explicitly-approved change.

---

## 6. ✅ FIXED — Frontend regressions (translation, app name, direction)

| Claimed defect | Resolution |
| :--- | :--- |
| Missing translation keys → the key string rendered on screen | **Closed.** `php artisan translations:check --source=both` reports **zero** missing keys in either language. |
| `en.json` `common.app_name` was `"Pro service"`; `resources/js/app.js` fell back to `'Laravel'` | **Closed.** Source of truth is now the **server** — `HandleInertiaRequests` shares `app_name` from `config('app.name_ar')` / `config('app.name')`. |
| Hardcoded system names in print templates, `Register.vue`, `CustomPage.vue` | **Closed.** All read the shared prop. |
| `resources/js/Pages/Public/CustomPage.vue` forced `dir` via `|| true` | **Closed.** Direction now follows `document.documentElement.dir === 'rtl'`. |
| Unreachable keys caused by dots (`system.bank_transfer_receipts.e.g.*`, `system.help.httpswww.youtube...`) | **Closed.** Renamed. |

**Acceptance:** `npx vitest run` → **45 files / 159 tests, all green**
(was 44 / 147). `npm run build` → succeeded. New guard:
`resources/js/Support/frontendRegression.test.js` (11 tests).

---

## 7. ✅ FIXED — Tax reference behaviour pinned

The audit asserted the paths "calculate tax the same way". They do not.

A reference rule is now pinned and independently verified
(`tests/Feature/TaxReferenceCasesTest.php`, **23 tests / 47 assertions**), and every
production path is measured against it
(`tests/Feature/TaxProductionPathsComparisonTest.php`, **7 passed + 3 skipped**,
the skips carrying the divergent values in their skip messages).

Full detail and the three measured divergences:
`15_TAX_REFERENCE_AND_PATHS.md`.

---

## 8. 📋 OPEN — The remaining PHPUnit failures

The full-suite failure list is **not yet reclassified**. Per the decision to
classify before touching any test or threshold, the outstanding items are:

| Item | Classification | Action |
| :--- | :--- | :--- |
| `InvoicePdfCompanyInfoTest` | **Isolation** — depends on a `payment_settings` row that a sibling test creates by convention | Make the row explicit in the test's own setup |
| Performance budget tests — 5 queries over budget | **N+1 with a query budget** | Fix the query, not the budget |
| `Feedback`, `GeneralSettingsAllowlist`, `TenantPublicSite`, `WorkOrdersCrud` | **Stale** — written against a contract that has since changed | Re-point at the current contract, or delete if the behaviour is gone |

**No threshold has been raised to make anything pass.** The reclassification
round is still to be done; this register records it as open rather than
resolved.

---

## 9. Summary — the net effect on the risk table

`11_SECURITY_PERFORMANCE.md` §2 should be read with this register. Two rows change:

| # | Risk | Was | Now | Why |
| :--- | :--- | :--- | :--- | :--- |
| S-01 | `InvoiceObserver` not registered | حرج / مؤكد | ✅ **Closed** | Registered + 19 acceptance tests |
| S-03 | `floatDiffInDays` ⇒ Fatal | عالٍ / مؤكد | 📉 **منخفض** | Carbon alias; deprecation notice, correct value |
| S-04 | 9 copies of tax calc ⇒ balance differences | عالٍ / مؤكد | 📋 **Re-scoped** | 2 paths round per line; work order is correct; frontend is preview-only |
| — | Payroll locking / authorization | (implied defect) | ❌ **No finding** | `lockForUpdate` in transaction + tenant/center/permission checks |
| — | vue-i18n `t()` fallback | (implied defect) | ❌ **No finding** | Second argument is a message fallback; verified at runtime |

The audit was right to flag the observer (S-01) and the translation gaps, and it
was right to insist that the financial behaviour be pinned before consolidation.
It overstated three findings — the Carbon fatality, the payroll defect, and the
i18n fallback — and it overstated the tax duplication by counting paths that are
not equivalent.
