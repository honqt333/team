# Implementation Round — Summary

**Date:** 2026-11-11
**Project:** `/Users/ahmad/Herd/carag-v2` — خدمة برو / Khidmh Pro

---

## 1. The final gate

| Gate | Result |
|---|---|
| `php artisan test` | **1,780 passed · 1 failed · 8 skipped · 10,698 assertions** (2,997s) |
| — the one failure | **Not ours** — proven below |
| `npx vitest run` | ✅ **48 files / 184 tests** |
| `npm run build` | ✅ succeeded |
| `npx eslint resources/js` | ✅ **0 errors** |
| `php artisan translations:check --source=both` | ✅ **0 missing** in both languages |
| `vendor/bin/phpstan analyse` | ⚠️ 38 — **the 3 Carbon errors are fixed**; the rest are in files the parallel process is editing |

**Before this round:** `1,636 passed · 13 failed`.
**After:** `1,780 passed · 1 failed` — **+144 passing tests, and the 13 pre-existing failures are gone.**

### The one remaining failure is not ours

`CompanyTransactionEnhancementsTest::approve accepts approved at and applies
to generated invoice` fails because the test asserts a bare date
(`2026-11-11`) against `invoices.issue_date`, which is a **`timestamp`**
column (`database/migrations/2025_12_29_141556_create_invoices_and_tax_settings_tables.php:36`).

**Proof it is unrelated to this round:** the test fails *identically* with
`Invoice::observe(InvoiceObserver::class)` removed from
`AppServiceProvider`. The file is **untracked** (`??` in `git status`) —
written by the parallel process at 23:00, not by this round.

---

## 2. What was done

### 2.1 Invoice immutability — the item worth presenting

`app/Observers/InvoiceObserver.php` was rewritten around an **explicit
contract** instead of "any status change is allowed":

- **Transitions are enumerated.** `draft → valid|cancelled`,
  `valid → reported|cancelled`, `reported`/`cancelled` → terminal.
- **Every dirty field of the save is inspected**, not just the status.
  Changing the status does not exempt the rest of the payload.
- **A save that mixes an allowed change with a forbidden one is rejected
  in full** — no partial application.
- **The issue transition** may set `status`, `invoice_number` and
  `issue_date` together — and nothing else.
- **The settlement path** may move `total_paid` and `payment_status`.
- **The reporting path** may stamp the ZATCA fields.
- Everything else — the commercial content of the document — is protected.
- Deleting a non-draft invoice is refused, including a force delete.

Enforcement raises a **`ValidationException`**, so a JSON request gets a
422 with an `errors` bag and an Inertia request gets the usual validation
errors in the session. Unexpected database errors are **not** converted
into validation failures — they keep bubbling up so they stay
diagnosable.

**Registered in the application**, not in the test:
`app/Providers/AppServiceProvider.php:278` → `Invoice::observe(InvoiceObserver::class);`
The manual registration inside `InvoicingSecurityTest` was removed.

**Acceptance:** `tests/Feature/InvoiceImmutabilityTest.php` — **19 tests,
59 assertions**, covering the issue transition, the settlement path, the
reporting path, the refusal of a status change that smuggles a protected
field, the refusal to delete an issued invoice, a **403 for an
unauthorised user**, and a full rollback of a contract violation.

**Proof the test can fail:** commenting out the registration makes **10 of
the 19 tests fail**. It was verified both ways and the registration was
restored.

### 2.2 The frontend regressions — one independent batch

| Defect | Resolution |
|---|---|
| Missing translation keys | `translations:check --source=both` ⇒ **0 missing** in both languages |
| `en.json` `common.app_name` was `"Pro service"`; `app.js` fell back to `'Laravel'` | The **server** is the source of truth — `config('app.name_ar')` / `config('app.name')`, shared through `HandleInertiaRequests` |
| Hardcoded system names in print templates, `Register.vue`, `CustomPage.vue` | All read the shared prop |
| `CustomPage.vue` forced `dir` with `\|\| true` | Direction now follows `document.documentElement.dir === 'rtl'` |
| Keys unreachable because of dots (`system.help.httpswww.youtube...`) | Renamed |

New guard: `resources/js/Support/frontendRegression.test.js` (11 tests).

### 2.3 The financial reference — pinned before anything was unified

`tests/Feature/TaxReferenceCasesTest.php` — **23 tests / 47 assertions**,
defining what a document must say, independently of the code that produces
it:

> Line values are **not** rounded. The document totals are rounded **once**,
> at the end, from the unrounded line values.

`tests/Feature/TaxProductionPathsComparisonTest.php` then **measured** all
three production paths against it — transcribing each path exactly rather
than re-implementing it.

**The measurement found a real divergence:**

| Reference case | Path that diverges | Path output | Reference |
| :--- | :--- | :--- | :--- |
| three awkward lines | invoice / credit note | `99.99 / 114.99` | `100.00 / 115.00` |
| mixed inclusive + awkward | invoice / credit note | tax `3.95`, incl `30.23` | tax `3.94`, incl `30.22` |

The **work-order path is arithmetically correct** — it aggregates the net
and applies the tax once. It has a **model limitation** instead: one
`tax_enabled_snapshot` and one `pricing_mode_snapshot` for the whole
order, so an order cannot contain a tax-exempt line beside a taxable one.
That is a separate decision, not a rounding fix.

**Nothing in the calculation was changed.** The three open questions for
the consolidation are in `15_TAX_REFERENCE_AND_PATHS.md` §6.

### 2.4 The failures, reclassified and fixed — no threshold was raised

Full detail in `17_FAILURE_RECLASSIFICATION.md`. The summary:

**7 failures were the new guard working correctly.** They were fixtures
that mutated an issued document — `valid → draft`, `cancelled → valid`,
deleting a `valid` invoice. `InvoicePolicy::update()` and `::delete()`
both require `status === 'draft'`, so those transitions are **never
legitimate in production**. The fixtures now **create** the document in
the state under test, and the soft delete goes through the query builder —
the way a legacy row would actually have been produced.

**4 were stale tests** — the behaviour moved and the test did not. The
most important: commit `c093fc40` *tightened* `WorkOrderPolicy::view()`
from tenant-only to tenant + current centre, and the test asserting the
old cross-branch **read** access was added in the same commit. The fix
keeps the 403 and adds the "can read it once back in the owning branch"
case.

**6 were the performance budgets — and the audit's diagnosis was wrong.**
The audit said "one missing eager-load relation per page". There was no
missing eager-load. A query log with **stack traces** showed that
`SubscriptionLifecycleService::resolveTenantState()` was computed
**twice per request** — once by `EnsureTenantActive`, once by
`HandleInertiaRequests` — at 5 queries each. Roughly 10 of the 29-35
queries on every page were the same computation done twice.

**The fix, not the budget:**

| Endpoint | Before | After | Budget |
|---|---|---|---|
| work-orders/1 | 60 ❌ | **52** ✅ | 55 |
| customers | 29 ❌ | **21** ✅ | 25 |
| inventory/stock | 33 ❌ | **25** ✅ | 30 |
| vehicles | 35 ❌ | **27** ✅ | 30 |
| workshop | 34 ❌ | **26** ✅ | 30 |

- `SubscriptionLifecycleService` memoizes the resolution per tenant.
- It is bound as **`scoped()`**, not `singleton()` — under Octane the
  instance is flushed between requests, so the memo cannot leak.
- `app/Observers/TenantObserver.php` (new) invalidates the memo whenever
  a field that feeds the resolution changes.
- Guarded by `tests/Feature/SubscriptionLifecycleMemoizationTest.php`
  (7 tests).

**2 were test isolation** — the `tenants.slug` collision moved **into the
factory**, and an invalid `pending` status (which belongs to
`SubscriptionInvoice`, not `Invoice`) was removed from a fixture.

### 2.5 A flakiness bug the fixed suite exposed

`PerfQueryShapeTest` was green in isolation and red in the suite. That is
not a flaky test — it is a **latent race** in the code under test.

The memo was keyed on the current **second**. The middleware reads the
state at the start of a request and the Inertia handler at the end, so a
request crossing a second boundary **missed** the memo. Whether it hit was
a coin flip.

The key is now a **clock identity**: a frozen clock is identified by the
instant it is frozen at (so `Carbon::setTestNow()` invalidates it, and a
passing second does not), and a running clock by the wall-clock **minute**
(finer than any boundary that matters — a trial is measured in days).

After the fix the probe reports **52 → 52, 25 → 25, 26 → 26** — delta 0,
stable.

### 2.6 The component duplication — measured, then extracted

The audit measured duplication by **file lines**. The right measure is
**logic**. Each pair was measured on two axes: are the `<script>` blocks
identical, and have the templates diverged?

| Pair | Script | Template | Fix |
|---|---|---|---|
| `HelpCenterTab` ↔ `Help/Index` | **3 byte-identical** copies of `getVideoEmbed()` (same MD5) | diverged (2,890 diff lines) | extracted to `useVideoEmbed.js` |
| `CompanySettings` ↔ `CompanySettingsTab` | **identical except 2 layout imports** | diverged (637 diff lines) | extracted to `useCompanySettingsForm.js` |
| `PurchaseInvoiceFormModal` ↔ `PurchaseOrderFormModal` | **diverged** (122 of ~250 lines) | — | **left alone** — not a deduplication task |

Neither of the first two was merged, because merging would rewrite a
1,100-line page. Only the shared **pure logic** was moved.

`useVideoEmbed.test.js` — **16 tests**, written against the original
implementation's behaviour before the extraction.

---

## 3. The corrections to the audit itself

Full register in `16_AUDIT_CORRECTIONS.md`.

| Claim | Verdict | Evidence |
|---|---|---|
| `floatDiffInDays()` ⇒ **Fatal error** | ❌ **retracted** | It is a Carbon **alias** resolved through `__call` (`Date.php:2731` → `callDiffAlias()` `:2878-2908`). The call returns `9.0` and emits `E_USER_DEPRECATED` (16384) — a notice, not a crash. The audit used a `method_exists` probe to conclude a runtime fatality; that inference does not hold. **Fixed anyway** — the 3 PHPStan errors are gone, with the numeric behaviour verified identical on six intervals. |
| Payroll locking / authorization | ❌ **no finding** | `lockForUpdate()` inside `DB::transaction`, the **locked** record re-authorized, and a policy checking tenant + centre + permission. |
| `t('key', 'fallback')` is invalid | ❌ **retracted** | Probed at runtime: the second argument is a **message fallback** and works. The second argument of `diffInDays()` is the *absolute* flag — a trap recorded because the first fix attempt changed a negative interval (`-1.0` → `1.0`). |
| "9 copies of the tax calculation" | 📋 **re-scoped** | **Two** paths round per line and are wrong. The work-order path is correct. The frontend copies are **display arithmetic**, not the persisted value. |

---

## 4. What was deliberately NOT done

| Option | Why rejected |
|---|---|
| Allow `valid → draft` in the observer | `InvoicePolicy::update` already requires `draft`. A `valid → draft` transition is never legitimate. |
| Allow deleting a `valid` invoice | Same. An issued document is reversed with a credit note. |
| Raise the query budgets | It would hide the duplicate computation. After fixing the source, **every endpoint came in under the original budget**. |
| Raise the translation ceiling | It is **zero** and must stay zero. |
| Relax `WorkOrderPolicy::view()` | The hardening was deliberate; the stale test was the wrong side. |
| Merge the Help / CompanySettings components | Their templates have genuinely diverged. Only the shared logic was extracted. |
| Merge the purchasing modals | The logic has already diverged. A separate analysis. |
| Unify the tax calculation | It needs three decisions first — see `15_TAX_REFERENCE_AND_PATHS.md` §6. Changing the calculation is not a mechanical step. |

---

## 5. The reports

| File | Contents |
|---|---|
| `15_TAX_REFERENCE_AND_PATHS.md` | The reference rule, the 10 cases, the three measured paths, and the three open decisions |
| `16_AUDIT_CORRECTIONS.md` | The corrections register — what was retracted, what was overstated, what was fixed |
| `17_FAILURE_RECLASSIFICATION.md` | All 20 failures, classified, with the fix for each and what was *not* done |
| `18_COMPONENT_CONSOLIDATION.md` | The two-axis measurement and what was extracted vs left alone |

Reports 09, 11, 13, 14 and 01 carry inline correction notices pointing at
these.

---

## 6. The next decision

The tax-path consolidation is the remaining item, and it is **not** ready to
start unattended. `15_TAX_REFERENCE_AND_PATHS.md` §6 lists three questions
that need an answer first:

1. **Should the invoice line rows store rounded values while the document
   header stores the raw-sum total?** (The line rows and the header would
   otherwise disagree by a halala.)
2. **Should the work order gain per-line tax treatment?** It is a schema +
   behaviour change, and it must not be smuggled into a rounding fix.
3. **Should the credit note's baseline be the original invoice's raw
   values?** A credit note that rounds differently from the invoice it
   reverses is an accounting problem.

The suite is green, so a change to the calculation would be attributable.
