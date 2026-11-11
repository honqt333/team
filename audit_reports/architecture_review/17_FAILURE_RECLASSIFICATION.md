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

### 2.1 Stale tests — the behaviour moved, the test did not (4)

| Test | Evidence | Classification |
|---|---|---|
| `FeedbackTest::test_system_admin_can_view_all_feedbacks_and_reply` | `routes/web.php:1394` → `redirect()->route('system.support.index', ['tab' => 'feedbacks'])`; the route was consolidated by commit `82c92ec5` | ✅ **stale** — the behaviour is correct, the test points at the old URL |
| `System\GeneralSettingsAllowlistTest::the allowlist contains only the key the page uses` | *"The allowlist must match the keys the page actually sends."* — the page's key set changed | ✅ **stale** |
| `TenantPublicSiteTest` (`Property [site.primary_color] does not match the expected value`) | `tests/Feature/TenantPublicSiteTest.php:251` | ✅ **stale** — the public-site colour source changed |
| `WorkOrdersCrudTest::cross branch data sharing and policies` | Reproduces in isolation (`11.22s`, same failure) ⇒ **not flaky** | ✅ **stale** |

### 2.2 Performance budgets — N+1 with a query budget (6)

| Test | Result | Classification |
|---|---|---|
| `PerformanceBaselineTest::work orders show` | 60 queries (target 55) | 🔴 **N+1** — fix the query, not the budget |
| `PerformanceBaselineTest::vehicles list` | 35 (target 30) | 🔴 **N+1** |
| `PerformanceBaselineTest::workshop board` | 34 (target 30) | 🔴 **N+1** |
| `PerformanceBaselineTest::inventory balances list` | 33 (target 30) | 🔴 **N+1** |
| `PerformanceBaselineTest::customers list` | 29 (target 25) | 🔴 **N+1** |
| `PerfQueryShapeTest::the failing endpoints do not scale with the data` | 552s | 🔴 **N+1** — the slow one |

**Deliberately NOT raised.** The times are already excellent (38–92ms); the
defect is the **query count**, and the deviation is a regular 3–5 across
every page — the signature of **one missing eager-load relation per page**,
not random slowness.

### 2.3 Test isolation — a row created by convention (2)

| Test | Failure | Classification |
|---|---|---|
| `PerfQueryShapeTest` | `UNIQUE constraint failed: tenants.slug` — `CustomerFactory.php:26` | ⚪ **isolation** — the factory derives the slug from the name, and two tests in the file use the same name. The factory needs a unique suffix, not the test a different name. |
| `VehicleBalanceNPlusOneTest` (×3) | `An invoice in "pending" status cannot be deleted` | ⚪ **test data** — see below |

### 2.4 `VehicleBalanceNPlusOneTest` — an invalid status value (3)

`tests/Feature/VehicleBalanceNPlusOneTest.php:99,105` create an `Invoice`
with `status => 'pending'`.

**`pending` is not a valid invoice status.** The column is
`enum('status', ['draft', 'valid', 'reported', 'cancelled'])`
(`database/migrations/2025_12_29_141556_create_invoices_and_tax_settings_tables.php:42`).
`pending` belongs to **`SubscriptionInvoice`**
(`database/migrations/2026_01_04_202314_create_subscription_invoices_table.php:25`:
`enum('status', ['pending', 'paid', 'failed', 'refunded', 'cancelled'])`).

SQLite does not enforce enums, so the invalid value was silently accepted
and the row was created — and then the test tried to **delete** it, which
the immutability guard correctly refuses because it is not a `draft`.

**Classification:** ⚪ **invalid test fixture**, not a product defect. The
test wants an invoice that exists but is not counted; `draft` is the
correct status for that (and it is deletable, which the test needs).

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

## 4. Net effect

| | Before | After |
|---|---|---|
| Failed | 13 | 20 |
| — of which: caught by the new guard | 0 | **7** |
| — of which: pre-existing, unchanged | 13 | 13 |
| Passed | 1636 | **1712** |
| Assertions | 9681 | **10181** |
| Skipped | 5 | 8 (+3 = the deliberate divergence skips in the tax comparison) |

The suite got **more honest**, not smaller: 76 more tests pass, and the 7
new failures are a guard catching attempts to mutate issued financial
documents — which is precisely the behaviour the guard was created for.

---

## 5. Next actions, in order

1. **Fix the 7 fixture setups** so they create documents in the required
   state instead of transitioning them. *(This round.)*
2. **Fix the 4 stale tests** to point at the current contract.
3. **Fix the N+1 relations** in the 6 performance cases — the eager-load,
   not the budget.
4. **Fix the factory slug collision** in `CustomerFactory` so the
   uniqueness is intrinsic to the factory.
5. **Re-run the full suite** and confirm the remaining set is empty or
   fully explained.

**Gate rule for the next round:** the tax-path consolidation (§6 of
`15_TAX_REFERENCE_AND_PATHS.md`) does not start until the suite is green,
because changing the calculation under a red suite makes the failures
unattributable.
