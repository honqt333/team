# Engineering Close-Out — Record

**Date:** 2026-11-12
**Base commit:** `f45f7a0a` — *feat(dealers,work-orders,vehicles): add dealers, partner billing, reports, and compiled Vite assets*
**Branch:** `feature/phase-2-goal-3`
**Final commit:** see §7

---

## 1. The year-crossing result

### What was measured

`tests/Feature/InvoiceSequenceYearBoundaryTest.php` drives the **real
transaction approval route** with the clock frozen on **2026-01-03** and an
approval date of **2025-12-20**, then reads the saved invoice and the
sequence counters back from the database.

```
[BOUNDARY] issue_date       = 2025-12-20
[BOUNDARY] invoice_number   = INV-1-2026-000001
[BOUNDARY] processing year  = 2026
[BOUNDARY] issue-date year  = 2025
[BOUNDARY] sequence type=icv     year=NULL current_value=1
[BOUNDARY] sequence type=invoice year=2026 current_value=1
[BOUNDARY] number year = 2026 vs issue-date year = 2025
```

### The answer: **no rule exists**

| Source searched | Result |
| :--- | :--- |
| `AGENTS.md` (the constitution) | **No rule** — no section mentions numbering, the sequence, or the year |
| `docs/ADR/0001-0003` | **No rule** |
| `audit_reports/**` | **No rule** — the reports document the *mechanism* (the unique index on `tenant_id, center_id, type, year`), never the *year source* |
| The code | `now()->year`, introduced in `9231c808` (Dec 2025) — when the invoice had **no issue date at all**, so `now()` was the only option. It was never revisited when the issue date became settable. |
| The tests | `SecondBatchFinancialSafetyTest` asserts the sequence is not consumed twice for a stale issuance. It says nothing about which year. |

**The current behaviour is an accident of the original implementation, not a
decision.**

### The decision required — **Rule A or Rule B**

| | Rule A — the number follows the **issue date** | Rule B — the number follows the **processing moment** (today) |
| :--- | :--- | :--- |
| Approved 2025-12-20, processed 2026-01-03 | `issue_date = 2025-12-20`, `INV-1-2025-000001`, sequence year **2025** | `issue_date = 2025-12-20`, `INV-1-2026-000001`, sequence year **2026** |
| The number agrees with the date | ✅ | ❌ |
| The number agrees with the tax period | ✅ | ❌ — the document is taxed under 2025, numbered 2026 |
| The number agrees with the ZATCA QR | ✅ | ❌ |
| The ICV (ZATCA counter) | **unchanged** — it is called with **no year** (`InvoiceService.php:298`), so it never resets and stays strictly increasing, as ZATCA requires | unchanged |

**Recommendation: Rule A**, because the tax period
(`TaxPeriodService`), the financial summary
(`FinancialSummaryReportService`) and the ZATCA QR timestamp
(`generateZatcaQr`) **already** use `issue_date`. Rule A is the only rule
under which the document has one year.

**This changes a financial document number**, so it is the owner's and the
accountant's call. **The production behaviour is left unchanged.**

### A second, smaller defect in the same place

`Tenant::formatDocumentCode()` fills `{MM}` and `{DD}` from `now()`
regardless of the year it was given:

```php
// app/Models/Tenant.php:218-219
'{MM}' => now()->format('m'),
'{DD}' => now()->format('d'),
```

A tenant whose format is `INV-{YYYY}-{MM}-{SEQ}` gets the **processing**
month and day beside the year. Same class of defect, same decision.

### What was NOT changed

- `InvoiceService::issueInvoice()` still uses `now()->year`.
- `Tenant::formatDocumentCode()` still uses `now()` for `{MM}`/`{DD}`.
- The sequence counters, the ICV, and every caller's behaviour are untouched.

**The number and the date stay frozen after issuance** — the previous round's
guard still rejects any attempt to move them, and the year-boundary test
proves it.

---

## 2. The tax reconciliation table

`tests/Feature/TaxServiceIntegrationTest.php` raises a real invoice from a
work order with three lines of `33.3333`, then creates credit notes through
the real `CreditNoteService` one line at a time until the returnable amount
is exhausted, reading every saved document back.

### The invoice

```
[AWKWARD] header: {"status":"valid","total_excl_tax":99.99,"total_tax":15,
                    "total_incl_tax":114.99,"total_taxable_amount":99.99}
[AWKWARD] line: {"qty":1,"unit_price":33.33,"excl":33.33,"tax":5,"incl":38.33}
[AWKWARD] line: {"qty":1,"unit_price":33.33,"excl":33.33,"tax":5,"incl":38.33}
[AWKWARD] line: {"qty":1,"unit_price":33.33,"excl":33.33,"tax":5,"incl":38.33}
[AWKWARD] stored line sum: excl 99.99 tax 15.00 incl 114.99
```

Note the stored `unit_price` is **`33.33`**, not `33.3333` — the raw value
is not recoverable from the line row alone.

### The reconciliation

| | excl | tax | incl |
| :--- | ---: | ---: | ---: |
| **Original invoice** | **99.99** | **15.00** | **114.99** |
| Credit note #1 | 33.33 | 5.00 | 38.33 |
| Credit note #2 | 33.33 | 5.00 | 38.33 |
| Credit note #3 | 33.33 | 5.00 | 38.33 |
| **Total of the credit notes** | **99.99** | **15.00** | **114.99** |
| **Remaining** | **0.00** | **0.00** | **0.00** |

**The credit notes reconcile exactly.**

### The three judgements, separated

| Judgement | What it is | Status |
| :--- | :--- | :--- |
| **A — proven by a document created today** | The invoice stores `99.99`; its credit notes sum to `99.99`; the remaining is `0.00`. The invoice and its credit notes never disagree. | ✅ **MEASURED** |
| **B — a historical case needing stored data of a different shape** | A document whose **header** is `100.00` while its **line rows** are `33.33` — which today's code cannot produce, but which the reference rule (Decision 1, Option B) **would** produce. Its credit notes, computed from the rounded lines, would reach `99.99` against a `100.00` header. | ⚠️ **NOT YET REACHABLE** — it exists only once Decision 1 changes the header rule |
| **C — the expected effect of a proposed change** | If the header becomes the raw-sum total while the lines stay rounded, the credit notes reach `99.99` and **one halala is stranded** after a full reversal. | 📋 **PROJECTED** — this is why Decision 1 Option C is rejected |

### The condition under which the test must be re-run

1. **Decision 1** is implemented — case **B** becomes reachable.
2. **Decision 2** is implemented — the credit note must then read
   `is_taxable` too.
3. The credit note's rounding rule changes.

---

## 3. The mixed taxable/exempt case — a claim corrected by measurement

```
[MIXED] work order : {"excl":180,"tax":27,"incl":207}
[MIXED] invoice     : {"excl":180,"tax":27,"incl":207}
[MIXED] invoice line: excl 100.00 tax 15.00 incl 115.00 taxable=yes
[MIXED] invoice line: excl  80.00 tax 12.00 incl  92.00 taxable=no
```

**The invoice does NOT produce the reference's `195.00`.** The exempt line
carries `12.00` of tax, and both the work order and the invoice say `207.00`.

### The root cause — the exemption flag is never read

The line is stored with **`is_taxable = no`** and is taxed anyway. In
`InvoiceService::createFromWorkOrder()` the flag is **written** at `:171` but
the tax is computed at `:150-153` from the **order-level**
`tax_enabled_snapshot` and the line's `tax_rate_snapshot` — the line's own
`is_taxable` is never consulted.

A repository-wide check:

```
$ grep -rn "is_taxable" app/Services/InvoiceService.php app/Services/CreditNoteService.php \
      app/Models/WorkOrder.php app/Models/Quote.php | grep -v "'is_taxable' =>"
(empty — is_taxable is never READ in any calculation path)
```

**Consequence:** the `195.00` reference case — and the whole notion of a
per-line exemption — is **not reachable in production today**. A line can be
marked exempt in the data and still be taxed.

**This corrects the earlier report**, which said the invoice path computes
per line and therefore gets `195.00`, and that the work order and the
invoice disagree on a mixed order. Measured: they **agree**, and **both**
disagree with the reference.

**Recommendation: Option B** — honour the per-line flag, as a separate
explicitly-approved change. It is a **missing rule**, not a rounding
question, and it must not be smuggled into the rounding consolidation.

---

## 4. The remaining failures

Full detail in `21_REMAINING_FAILURES.md`. **7 → 6** on this revision.

Every row below was **independently re-verified** against an isolated
worktree checked out at the base commit `f45f7a0a`, with (a) neither diff,
(b) only the staged diff, and (c) both diffs applied. The attribution states
which of those three runs reproduces the failure.

| # | Test | Expectation | Actual | Minimal repro | Attribution |
|---|---|---|---|---|---|
| ① | `CustomerPortalActionsHttpTest::reschedule rejects outside working hours` | the reschedule POST to be rejected | **`UniqueConstraintViolationException` on `centers.slug`** | `php artisan test tests/Feature/CustomerPortalActionsHttpTest.php --filter="test_reschedule_rejects_outside_working_hours"` | **Pre-existing flake — a factory collision, not a logic failure.** The fixture builds the center through `Center::factory()`, whose slug is `Str::slug(fake()->words(3, true))` (`database/factories/CenterFactory.php:26-31`). Faker's lorem vocabulary is small: **20,000 draws produce 19,843 distinct slugs and 157 collisions** (measured). The test creates a second center in the same class, so a collision with an earlier center aborts the test **before** the assertion under test is even reached. **It passes 5/5 in isolation** (9.3s each). The file is untouched by both agents. |
| ② | `PerformanceBaselineTest::work orders show meets perf budget` | `< 55` queries | **57** | `php artisan test tests/Feature/PerformanceBaselineTest.php --filter="work_orders_show_meets_perf_budget"` | **Pre-existing at the base commit, +1 from the parallel agent.** Measured in three isolated trees with a **copied** `vendor/` (a symlinked one resolves `base_path()` to the main repo and measures the wrong tree): base `f45f7a0a` = **56**, base+staged = **56**, base+staged+unstaged = **57**. The staged diff's delta is **0** — the SQL dumps of base and base+staged are **byte-identical** (`md5 01372a24…`), and the probe confirms the three staged observer hooks (`InvoiceObserver::updating/deleting/forceDeleting`) **never fire** on this GET. The +1 is the parallel agent's new eager load at `WorkOrderController.php:373` (`supplierServiceAllocation.purchaseInvoice.supplier`, relation added at `WorkOrderItem.php:241`). **Note:** the budget was already exceeded **at base with no diffs** (56 > 55), so removing the +1 does not make it green — the 55 budget is off by one against the endpoint's fixed overhead. |
| ③ | `ThirdBatchSafetyTest::public decision is terminal and only logged once` | `assertSessionHas('success')` | `quotes.empty_decision` | `php artisan test tests/Feature/ThirdBatchSafetyTest.php` | **Fixture defect.** `PublicQuoteController::isQuoteEmpty()` (`app/Http/Controllers/Public/PublicQuoteController.php:258`) rejects a quote whose subtotal/total are 0 and that has no lines or parts. The fixture at `tests/Feature/ThirdBatchSafetyTest.php:33` creates exactly such a quote. The guard itself is correct behaviour, not a bug. |
| ④ | `ThirdBatchSafetyTest::rejection is terminal and expired or deleted quotes…` | `assertSessionHas('success')` | same | same | same guard, same fixture |
| ⑤ | `VueI18nBindingTest::a script block that calls the translator has a binding` | no unbound `t()` | 5 portal files | `php artisan test tests/Feature/VueI18nBindingTest.php` | **False positive in the test.** All five files bind `t` through `usePortalFormat()` (`resources/js/Composables/usePortalFormat.js:16,25,66`), which calls `useI18n()` internally. The test's `hasBinding()` (`tests/Feature/VueI18nBindingTest.php:105-121`) recognises only the literal string `useI18n()`. Pre-existing since `ddc4782b`; **none of the five files is modified** by either agent. |

**On ②③④⑤ — none is caused by the staged changes.** `InvoiceService`,
`InvoiceObserver` and `CompanyTransactionController` are not reachable from
`GET /app/work-orders/{id}` (grep-verified and event-probed), and the isolated
base+staged run produced a byte-identical SQL dump (56 → 56).

**Two failures closed on their own** since the previous run: the
`I18nCoverageTest` pair, closed by the parallel agent adding the 93 missing
`Dealers/Show.vue` keys — `php artisan translations:check --source=both` now
reports **0 missing keys in both languages**.

**No budget was raised and no assertion was weakened.** The query budget is
still `assertLessThan(55, …)`; `MISSING_KEYS_EXPECTED` is still **0**.

**None of these was closed by this task** — each belongs to the feature that
caused it, and each touches a file this task does not modify.

**Three failures closed on their own**: `WorkshopBoardAppointmentsTest` × 3,
closed by the Dealer feature's own work between the two runs.

### 4.1 The four decisions that remain open

| # | Decision | Owner | Consequence of not deciding |
|---|---|---|---|
| A | Invoice number follows the **issue date** (Rule A) or the **processing moment** (Rule B)? | Owner + accountant | A document approved 2025-12-20 and processed 2026-01-03 is **taxed under 2025** (every tax path reads `issue_date`) but **numbered 2026** |
| B | Do `{MM}` / `{DD}` in `Tenant::formatDocumentCode()` follow the issue date too? | Owner | They still read `now()`, so they would print the processing date even under Rule A |
| C | Does the displayed line sum have to reconcile with the document total? | Accountant | A `33.33 × 3` document stores `99.99` in the header and `33.33` per line; the unrounded `33.3333` is not stored |
| D | Per-line `is_taxable` — enforce it, or drop the column? | Accountant | The column is written but read by **no** production calculation path, so a `taxable=no` line is still taxed |

---

## 5. The verification actually run

The order was: targeted tests → Vitest → ESLint → translation check →
**build** → **full PHP suite** → `optimize:clear`. The build was completed
**before** the PHP suite, so the HTTP tests never read a Vite manifest that
was being rebuilt.

| Gate | Result |
| :--- | :--- |
| Targeted PHP (10 suites) | ✅ **99 passed · 0 failed · 3 skipped · 366 assertions** |
| `npx vitest run` | ✅ **48 files / 184 tests** |
| `npx eslint resources/js` | ✅ **0 errors** (1,883 warnings — pre-existing style noise) |
| `php artisan translations:check --source=both` | ⚠️ **18 missing** — **every one** in `Dealers/Show.vue` (the Dealer feature's in-progress file). **Zero** from anything this task touched. |
| `npm run build` | ✅ succeeded (34.09s) |
| `php artisan test` (full) | see §6 |
| `php artisan optimize:clear` | see §6 |

---

## 6. The full-suite result and the final state

The full suite was run **after** the Vite build, so no HTTP test ever read a
manifest that was being rewritten.

```
Tests:    5 failed, 8 skipped, 1822 passed (11319 assertions)
Duration: ~2760s
```

| Metric | Original baseline | Previous round | Final |
| :--- | :--- | :--- | :--- |
| Passed | 1,636 | 1,815 | **1,822** |
| Failed | 13 | 6 | **5** |
| Skipped | 5 | 8 | **8** |
| Assertions | 9,681 | 11,277 | **11,319** |

**All 5 remaining failures are itemised and attributed in §4.** None of them
is caused by this task's changes — the isolated base-commit runs prove that.

### The gates, in the order they were run

| Gate | Result |
| :--- | :--- |
| Targeted PHP (10 suites) | ✅ **102 passed · 0 failed · 3 skipped · 317 assertions** |
| `npx vitest run` | ✅ **48 files / 184 tests** |
| `npx eslint resources/js` | ✅ **0 errors** (1,882 warnings — pre-existing style noise) |
| `php artisan translations:check --source=both` | ✅ **0 missing keys** in both languages (6,471 usages) |
| `npm run build` | ✅ succeeded (1m 39s) |
| `php artisan test` (full) | ⚠️ **5 failed · 8 skipped · 1,822 passed** — see §4 |
| `php artisan optimize:clear` | ✅ ran after the suite |

`php artisan optimize:clear` was run; it reported success. The 14 pre-existing
stash entries are intact.

---

## 7. What was committed, and what was left alone

**Committed** (12 files): the observer, `InvoiceService`, the transaction
controller, the two new integration tests, the two updated tests, the tax
decision memo, and two audit records. The shared locale files were staged
**surgically**, so only the one `new_dealer` line each is in this commit —
the Dealer feature's ~80 lines in the same files stay in the working tree.

**Left alone**: every file belonging to another agent — the Dealer feature,
the purchase/partner work, the company-profile work, and the build assets
that the build regenerated.

**The 14 pre-existing stash entries were not touched.**
