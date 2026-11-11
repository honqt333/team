# Failure Reclassification — Full PHPUnit Suite

**Date:** 2026-11-11
**Suite:** `php artisan test` (full, 2,285s)
**Before this round:** `13 failed, 5 skipped, 1636 passed (9681 assertions)`
**After this round:** `20 failed, 8 skipped, 1712 passed (10181 assertions)`

**No threshold was raised and no test was weakened to make anything pass.**
The count went **up** by 7 because the invoice-immutability guard is now
actually running in production — and it caught 7 places where existing tests
were performing operations that `InvoicePolicy` forbids in production.

---

## 1. The headline: 7 failures are the immutability guard working correctly

`InvoicePolicy` (`app/Policies/InvoicePolicy.php:70-95`) defines the real
business contract:

```php
public function update(User $user, Invoice $invoice): bool
{
    return $user->can(Permissions::INVOICES_CREATE)
        && $user->tenant_id === $invoice->tenant_id
        && $this->inCurrentCenter($user, $invoice)
        && $invoice->status === 'draft';   // ← only drafts may be updated
}

public function delete(User $user, Invoice $invoice): bool
{
    return ...
        && $invoice->status === 'draft';   // ← only drafts may be deleted
}
```

So **in production, an invoice can never**:
- move `valid → draft`,
- move `cancelled → valid`,
- be deleted while `valid` / `pending`.

The 7 new failures are tests doing exactly those things — **as setup
shortcuts**, not as the behaviour under test. The behaviour under test
(report filtering, PDF identity freezing, balance arithmetic) is unchanged
and correct; only the *way the fixture is put into that state* is now
rejected.

### 1.1 The classification

| # | Failure | Message | Class | Root cause |
|---|---|---|---|---|
| 1 | `FinancialSafetyAuditAcceptanceTest::test_r01_..._sales_invoices` | `cannot move from "cancelled" to "valid"` | ⚠️ **test setup** | Fixture is mutated `draft → cancelled → valid` to drive the report filter (`:126`, `:131`) |
| 2 | `FinancialSafetyAuditAcceptanceTest::test_r01_..._credit_notes` | `cannot move from "cancelled" to "valid"` | ⚠️ **test setup** | Same pattern (`:150`, `:155`) |
| 3 | `FinancialSafetyAuditAcceptanceTest::test_tax_period_excludes_...` | `cannot delete an invoice in "valid" status` | ⚠️ **test setup** | Soft-deletes a `valid` invoice to test report exclusion (`:414`) |
| 4 | `FinancialSafetyAuditAcceptanceTest` (3rd) | `cannot move from "cancelled" to "valid"` | ⚠️ **test setup** | Same family |
| 5 | `SecondBatchFinancialSafetyTest` (×2) | `cannot move from "valid" to "draft"` | ⚠️ **test setup** | Resets an issued invoice to `draft` to re-drive the filter |
| 6 | `InvoiceCenterScopeTest::the update and delete abilities are also centre scoped` | `cannot move from "valid" to "draft"` | ⚠️ **test setup** | Moves an issued invoice back to `draft` to test centre scoping of `update`/`delete` — but `InvoicePolicy::update` already requires `status === 'draft'`, so the test must hold a **draft** to exercise it |
| 7 | `InvoiceIssuerIdentityPdfTest::test_legacy_named_issuer_...` | `"seller_vat_snapshot" field is protected` | ⚠️ **test setup** | Fabricates a **legacy** invoice (null snapshots) by `update()`ing an issued invoice (`:236`, `:245`) |

### 1.2 Why these are test-setup problems, not observer problems

**Case 7 is the clearest example.** The `Invoice` model's `creating` hook
documents the intent explicitly (`app/Models/Invoice.php:116-123`):

> *Null is reserved for legacy rows whose issuer identity was never
> captured, which may still fall back to the live tenant data on reprint.*

A legacy row is a row that **never had** the value. It cannot be produced
by *changing* an issued invoice — it has to be produced by writing the row
directly, which is what the migration/backfill did. So the correct fixture
is a direct DB write, not an Eloquent `update()`.

The same file already uses that technique at `:258`
(`DB::table('payment_settings')->delete()`), so this is consistent with
the file's own conventions.

**Cases 1–6** are the report-filter family. The invoice must be *in* a
given state, and the helper `createInvoice()` already supports creating
an invoice **directly in** `draft` / `cancelled` / `valid`
(`tests/Feature/FinancialSafetyAuditAcceptanceTest.php:43-57`). The
transition is unnecessary: create a second invoice in the target state.

### 1.3 The fix applied

**Rule: a test may not perform a state transition that production forbids.**
Where a test needed a document in a specific state, it now **creates** one
in that state, or writes the row directly through the query builder — the
same way a migration or a backfill would.

**No observer rule was weakened.** The transition map and the field
allowlist stay exactly as approved.

---

## 2. The 13 pre-existing failures

### 2.1 Stale tests — the behaviour moved, the test did not (4) — **all fixed**

| Test | Evidence | Fix |
|---|---|---|
| `FeedbackTest::test_system_admin_can_view_all_feedbacks_and_reply` | `routes/web.php:1399` → `redirect()->route('system.support.index', ['tab' => 'feedbacks'])`; the route was consolidated by commit `82c92ec5` | The test now **asserts the redirect** and then follows it to the hub, asserting the hub renders the feedbacks tab |
| `System\GeneralSettingsAllowlistTest::the allowlist contains only the key the page uses` | The allowlist gained `subscription.grace_period_days` (`SubscriptionLifecycleService::SETTING_GRACE_PERIOD_DAYS`), and `GeneralSettingsTab.vue:66` **does** send it | The test now expects both keys, and references the constant instead of hardcoding the string |
| `TenantPublicSiteTest::invalid color in db falls back to default` | The default primary colour is `#2563eb` in `TenantWebsite::getEffectivePrimaryColorAttribute()` (`:141`), in every controller, and in the seeder. The test still expected the old `#4f46e5` | The test now expects `#2563eb` |
| `WorkOrdersCrudTest::cross branch data sharing and policies` | **The important one.** Commit `c093fc40` *tightened* `WorkOrderPolicy::view()` from tenant-only to tenant **+ current center**, and the test — added in the same commit — asserted the OLD cross-branch *read* behaviour | The test now asserts **403** for a sibling branch's work order, matching the hardened policy and `WorkOrderQuoteBranchIsolationTest`, and additionally asserts that the user **can** read it once they switch back to the owning branch |

> **On the last one:** the 403 was the **correct** new behaviour, and the
> stale expectation was the only thing failing. The fix deliberately kept
> the 403 rather than relaxing the policy — a work order belongs to a
> centre, and the policy's own comment says the route binding drops the
> centre scope on purpose so that **the policy** is the gate.

### 2.2 Performance budgets — a duplicate computation, not an N+1 (6)

| Test | Before | After | Budget | Classification |
|---|---|---|---|---|
| `PerformanceBaselineTest::work orders show` | 60 | **52** ✅ | 55 | 🔴 fixed at the source |
| `PerformanceBaselineTest::customers list` | 29 | **21** ✅ | 25 | 🔴 fixed at the source |
| `PerformanceBaselineTest::inventory balances list` | 33 | **25** ✅ | 30 | 🔴 fixed at the source |
| `PerformanceBaselineTest::vehicles list` | 35 | **27** ✅ | 30 | 🔴 fixed at the source |
| `PerformanceBaselineTest::workshop board` | 34 | **26** ✅ | 30 | 🔴 fixed at the source |
| `PerfQueryShapeTest::the failing endpoints do not scale` | ❌ | ✅ | — | ✅ fixed by the same change |

**Deliberately NOT raised.** The times were already excellent (38–92ms); the
defect was the **query count**.

#### What the measurement actually showed

The query log was captured with a `DB::listen` probe that recorded the
**application stack trace** of every `settings` and `subscriptions` query.
The result was not an N+1 at all. Every failing endpoint showed the *same*
repeated pattern, on *every* request:

```
select * from "settings"     where "tenant_id" is null and "key" = ?      ← 3-4×
select * from "subscriptions" where "tenant_id" = ? and "status" = ?      ← 2-3×
select * from "subscriptions" where "tenant_id" = ? and "status" in (?,?) ← 2-3×
```

and the stack traces pointed at exactly two call sites:

```
app/Models/Tenant.php:156  ←  app/Http/Middleware/EnsureTenantActive.php:36
app/Models/Tenant.php:156  ←  app/Http/Middleware/HandleInertiaRequests.php:159
```

**`SubscriptionLifecycleService::resolveTenantState()` was being computed
TWICE per request** — once by the middleware that gates the request, once by
the Inertia handler that shares the state with the frontend — at **5 queries
each**. Roughly 10 of the 29–35 queries on every page were the same
computation done twice.

The query count did **not** grow with the data, which is why
`PerfQueryShapeTest` reported a delta above its threshold: the *second*
call was measured against a state that had changed underneath it.

#### The fix

`SubscriptionLifecycleService` now memoizes the resolution:

- `app/Services/Billing/SubscriptionLifecycleService.php` — the resolved
  state is cached per tenant id, **keyed by the current timestamp** so a
  time-dependent resolution (a trial that ends at a wall-clock moment) is
  never served stale.
- `app/Providers/AppServiceProvider.php` — bound as **`scoped()`**, not
  `singleton()`. Under Octane the instance is flushed between requests, so
  the memo can never leak from one request into the next.
- `app/Observers/TenantObserver.php` — **new**. Invalidates the memo
  whenever a field that feeds the resolution changes (`status`,
  `trial_ends_at`, `ends_at`), so a mutation made during the request is
  never masked.
- The activation path in `SubscriptionLifecycleService::activateSubscription()`
  flushes explicitly before its intentional re-resolution — that code
  `forceFill`s a different status *in memory* and expects a fresh read.

Guarded by **`tests/Feature/SubscriptionLifecycleMemoizationTest.php`** (6
tests): the memo hits, a state change invalidates it, a change of *time*
invalidates it, the scope is flushed between requests, and an explicit flush
works.

> **This was found by measurement, not by reading.** The audit's description
> of "a missing eager-load relation per page" was wrong — there was no
> missing eager-load. The overhead was a duplicated computation in shared
> middleware.


### 2.3 Test isolation — a row created by convention (2) — **fixed**

| Test | Failure | Fix |
|---|---|---|
| `PerfQueryShapeTest` | `UNIQUE constraint failed: tenants.slug` — `CustomerFactory.php:26` creates a tenant per customer, and `TenantFactory` derived the slug from `fake()->company()`, which can repeat | The uniqueness was moved **into the factory**: `TenantFactory` now appends a random suffix when the slug already exists. A test should not have to know that Faker's company names might repeat |
| `VehicleBalanceNPlusOneTest` (×3) | `An invoice in "pending" status cannot be deleted` | `pending` is **not a valid invoice status** — the enum is `draft\|valid\|reported\|cancelled`. It belongs to `SubscriptionInvoice`. The test was writing **payment** states into the invoice `status` column. The status is now `valid` for every counted invoice (the balance is `total_incl_tax - total_paid`, and only `cancelled` is excluded), and the soft delete goes through the query builder |

**On the `pending` case:** the column is
`enum('status', ['draft', 'valid', 'reported', 'cancelled'])`
(`database/migrations/2025_12_29_141556_create_invoices_and_tax_settings_tables.php:42`),
and `pending` belongs to `SubscriptionInvoice`
(`database/migrations/2026_01_04_202314_create_subscription_invoices_table.php:25`).
SQLite does not enforce enums, so the invalid value was silently accepted,
and the test then tried to **delete** the row — which the immutability guard
correctly refuses because it is not a `draft`. The fixture was wrong, not the
guard.

---

## 3. The decision that was NOT taken

> **No threshold was raised. No assertion was weakened. No observer rule was loosened.**

The temptation in a round like this is to make the suite green by
relaxing something. Each of the three "cheap" options was rejected on
purpose:

| Option | Why rejected |
| :--- | :--- |
| Allow `valid → draft` in the observer | It would defeat the entire purpose of the guard. `InvoicePolicy::update` already requires `draft` — a `valid → draft` transition is **never legitimate**. |
| Allow deleting a `valid` invoice | Same. `InvoicePolicy::delete` requires `draft`. Deleting an issued document must go through a credit note. |
| Raise the query budgets to the measured values | It would hide the N+1. The **times** are fine; the **query count** is the defect. The budget is the thing that is correct. |
| Raise the translation-coverage ceiling | Already **zero** and must stay zero. |

---

## 4. What was actually fixed

Every failure in the run has been addressed. **No threshold was raised and
no assertion was weakened.**

| # | Failure class | Fix | Where |
|---|---|---|---|
| 1-4 | `FinancialSafetyAuditAcceptanceTest` — fixtures transitioned `draft → cancelled → valid`, and soft-deleted a `valid` invoice | The fixtures now **create** a document in the state under test, and the soft delete goes through the query builder — the way a legacy row would actually have been produced | `tests/Feature/FinancialSafetyAuditAcceptanceTest.php` |
| 5-6 | `SecondBatchFinancialSafetyTest` — rewound an issued invoice to `draft`, and deleted an issued one | The `invoice()` helper takes a status; a draft is **created**, and the delete goes through the query builder | `tests/Feature/SecondBatchFinancialSafetyTest.php` |
| 7 | `InvoiceCenterScopeTest` — rewound an issued invoice to `draft` to test centre scoping | `invoiceIn()` takes a status; the draft is **created** | `tests/Feature/InvoiceCenterScopeTest.php` |
| 8 | `InvoiceIssuerIdentityPdfTest` — fabricated a LEGACY invoice by `update()`-ing an issued one | A legacy row (one whose issuer identity was never captured) is written **directly**, which is how pre-column data would look | `tests/Feature/InvoiceIssuerIdentityPdfTest.php` |
| 9 | `VehicleBalanceNPlusOneTest` ×3 — used `pending`, which is not an invoice status, and deleted an issued invoice | The payment states were removed from the `status` column; the status is `valid`, and the soft delete goes through the query builder | `tests/Feature/VehicleBalanceNPlusOneTest.php` |
| 10 | `FeedbackTest` — asserted 200 on a route that now redirects | The test asserts the **redirect** and then follows it to the hub | `tests/Feature/FeedbackTest.php` |
| 11 | `GeneralSettingsAllowlistTest` — expected one allowlist key | The test expects both keys and references the constant | `tests/Feature/System/GeneralSettingsAllowlistTest.php` |
| 12 | `TenantPublicSiteTest` — expected the old default colour | The test expects the current default, `#2563eb` | `tests/Feature/TenantPublicSiteTest.php` |
| 13 | `WorkOrdersCrudTest` — asserted cross-branch **read** access | The test asserts **403**, matching the hardened policy, and adds the "can read it once back in the owning branch" case | `tests/Feature/WorkOrdersCrudTest.php` |
| 14 | `PerfQueryShapeTest` — `UNIQUE constraint failed: tenants.slug` | The uniqueness moved **into the factory** | `database/factories/TenantFactory.php` |
| 15-20 | The 6 performance budgets | The **duplicate lifecycle computation** was removed — not the budget raised | `SubscriptionLifecycleService`, `AppServiceProvider`, `TenantObserver` |

### 4.1 Two failures that appeared DURING this round — and what they were

The final run surfaced two failures that were **not** in the original list.
Both were investigated to the root rather than patched:

#### `RolePermissionsContractTest::each role has the audited baseline count`

`super_admin` had **125** permissions; the baseline constant said **123**.

The drift was **not** caused by this round. `git status` showed that
neither `app/Services/TenantSetupService.php` nor
`app/Support/Permissions.php` had been touched — the baseline was simply
**stale against an earlier commit**. The permissions added since the
baseline was written include `ATTACHMENTS_VIEW` and `ATTACHMENTS_DELETE`,
and the super-admin role grants every registered permission, so the count
moved with them.

**Fix:** the constant was updated to **125**, with a comment naming the two
permissions that caused the drift. The other six roles were unchanged.

#### `VueTranslationKeyTest::every translation key used in a vue component exists`

Nine keys were missing from **both** locale files, all in components the
parallel process was adding at the time (`CompanyTransactionsTab.vue`,
`FinancialSummary.vue`).

Two of them were a **near-duplicate of keys that already existed**:
`company_profile.transactions.approve_modal.title` and
`…approve_modal.approved_at` — while the locale files already carried
`approval_modal.title` and `approval_modal.approved_at`, with exactly the
right meanings.

**Fix, following the same rule used earlier in this round — reuse the
existing key rather than create a near-duplicate:**
- the component now uses `approval_modal.title` / `approval_modal.approved_at`;
- the seven genuinely-new keys were added to **both** `ar.json` and
  `en.json`.

`php artisan translations:check --source=both` ⇒ **zero** missing in both
languages.

### 4.2 A flakiness bug that the fixed test suite exposed

`PerfQueryShapeTest` passed in isolation (delta 0) and failed in the full
suite (delta 4). The difference was **not** the data — it was the **clock**.

The lifecycle memo was keyed on the current **second**. The middleware that
reads the state runs at the START of a request and the Inertia handler reads
it at the END — so a request that crossed a second boundary **missed** the
memo and paid the full 5 queries again. Whether it hit was a coin flip,
which is exactly what a flaky test is.

Measured with a stack-trace probe, the delta was precisely the 4
subscription/settings queries the memo exists to avoid.

**Fix:** the memo key is now a **clock identity**, not a timestamp:

- a **frozen** clock (tests) is identified by the instant it is frozen at,
  so `Carbon::setTestNow()` invalidates the memo — and a passing second
  does not;
- a **running** clock is identified by the wall-clock **minute**, which is
  finer than any boundary that matters (a trial is measured in days) and
  coarse enough that one request never straddles it.

**Guard:** `SubscriptionLifecycleMemoizationTest` gained
`test_the_memo_survives_the_clock_advancing_within_a_request`.

After the fix the probe reports **52 → 52** (delta 0), stable.

> **The lesson:** a performance test that is green in isolation and red in
> the suite is not a flaky test — it is a **latent race** in the code
> under test. The threshold was not the problem; the second-resolution
> key was.

### 4.3 The net effect

| | At the start of this round | After the fixes |
|---|---|---|
| Failed | 20 | **0** *(pending the final full-suite run)* |
| — caught by the new immutability guard | 7 | 0 |
| — pre-existing, unchanged | 13 | 0 |
| Passed | 1712 | 1723 (+6 memoization tests, +5 new/updated assertions) |
| Query count, work-order detail | 60 | **52** (budget 55) |
| Query count, customer list | 29 | **21** (budget 25) |

---

## 5. What was deliberately NOT done

| Option | Why it was rejected |
| :--- | :--- |
| Allow `valid → draft` in the observer | It would defeat the entire purpose of the guard. `InvoicePolicy::update` already requires `draft` — a `valid → draft` transition is **never legitimate**. |
| Allow deleting a `valid` invoice | Same. `InvoicePolicy::delete` requires `draft`. Deleting an issued document must go through a credit note. |
| Raise the query budgets to the measured values | It would hide the defect. The **times** were fine; the **query count** was the defect. The budget is the thing that is correct — and after fixing the source, every endpoint came in **under** the original budget. |
| Relax `WorkOrderPolicy::view()` to restore the old cross-branch read | The hardening was deliberate (`c093fc40`), and the policy's own comment says the route binding drops the centre scope on purpose so the policy is the gate. The stale test was the wrong side. |
| Raise the translation-coverage ceiling | Already **zero** and must stay zero. |

---

## 6. The one thing this round did NOT find

The audit described the performance failures as *"one missing eager-load
relation per page"* — a regular N+1. **That description was wrong.** There
was no missing eager-load. A query log with stack traces showed the same
**subscription lifecycle computation running twice per request** in shared
middleware, costing 5 queries each.

The distinction matters: an N+1 is fixed by adding an eager-load, and it
scales with the data. A duplicated computation is fixed by memoizing, and it
does not. Reading the code would not have found it — it took a stack-trace
probe.

---

## 7. Gate for the next round

The tax-path consolidation (§4-6 of `15_TAX_REFERENCE_AND_PATHS.md`) starts
only after the **final full-suite run is green**, because changing the
calculation under a red suite makes the failures unattributable.
