# Round Record — Closing the Review Notes

**Date:** 2026-11-12
**Base commit (commitment ID):** `f45f7a0a` — *feat(dealers,work-orders,vehicles): add dealers, partner billing, reports, and compiled Vite assets*
**Branch:** `feature/phase-2-goal-3`
**Project:** `/Users/ahmad/Herd/carag-v2` — خدمة برو / Khidmh Pro

**Concurrent work preserved:** the parallel process's Dealer feature was
committed at `f45f7a0a` and is the base of this round. Its 14 stash entries
were **not** touched, and no commit was made by this round — all changes are
left in the working tree for review.

---

## 1. The three fixes — scope, files, acceptance

### Fix 1 — The invoice number and the issue date are bound to the ISSUE transition

**The defect.** `InvoiceObserver` applied one `ISSUE_FIELDS` list to **every**
accepted status transition. So `invoice_number` and `issue_date` stayed
writable during `valid → reported` and `valid → cancelled` — the fields were
allowed everywhere the transition map allowed a transition.

**The fix.** The allowlist is now keyed by the **specific transition**
(`"from>to"`), in `app/Observers/InvoiceObserver.php`:

| Transition | May write |
| :--- | :--- |
| `draft → valid` (issue) | `status`, `invoice_number`, `issue_date`, `currency_code` |
| `valid → reported` (reporting) | `status` + the ZATCA chain (`zatca_uuid`, `zatca_qr_tlv`, `zatca_hash`, `zatca_prev_hash`, `xml_path`) |
| `draft → cancelled` | `status` |
| `valid → cancelled` | `status` |

The **real paths were inspected before the allowlist was written**, as
required:

| Path | Where | What it writes |
| :--- | :--- | :--- |
| **Issue** | `InvoiceService::issueInvoice()` — `app/Services/InvoiceService.php:299-305` | `status`, `invoice_number`, `issue_date`, `currency_code` — all in one save |
| **Reporting** | **no production writer exists** — `zatca_uuid` / `zatca_hash` / `zatca_prev_hash` / `xml_path` appear only in the observer's allowlist; `zatca_qr_tlv` is written in a **separate** save after issuance (`InvoiceService.php:308-312`) | the ZATCA chain |
| **Cancellation** | `SubscriptionInvoicesController::cancel()` — `app/Http/Controllers/System/SubscriptionInvoicesController.php:278` — a **different model** (`SubscriptionInvoice`), so not covered by this observer | `status` |

`currency_code` is allowed on the issue transition because the issue save
writes `$invoice->currency_code ?: tenantCurrency($tenant)` — a draft with no
currency of its own gets the tenant's there, in the same save as the status
change. Allowing it makes the contract match the code's intent instead of
rejecting a legitimate resolution.

**Every dirty field of the save is still inspected**, and a save that mixes an
allowed change with a forbidden one is rejected **in full** through
`ValidationException`.

**Acceptance** — `tests/Feature/InvoiceImmutabilityTest.php`, **25 tests / 86
assertions**, all green. Six new tests:

| Test | What it proves |
| :--- | :--- |
| `the_reporting_transition_cannot_move_the_invoice_number` | `valid → reported` + a number change ⇒ refused; the row re-read from the database still shows `valid` and the original number and date |
| `the_reporting_transition_cannot_move_the_issue_date` | the same for the date |
| `the_cancellation_transition_cannot_move_the_invoice_number` | `valid → cancelled` + a number change ⇒ refused; the row unchanged |
| `the_cancellation_transition_cannot_move_the_issue_date` | the same for the date |
| `the_issue_transition_may_set_the_number_and_the_date` | the issue transition **still works** — number and date land atomically with the status |
| `the_legitimate_reporting_and_cancellation_transitions_succeed` | the reporting transition may stamp the ZATCA chain; the cancellation may change the state — a contract that refuses everything would be an outage, not a contract |

The observer is registered by the application (`AppServiceProvider`), and the
test never registers it itself.

**Additional edge case verified by probe** (then removed): `draft → cancelled`
alone is **allowed** and preserves the number; `draft → cancelled` **plus** a
number rewrite is **refused**.

### Fix 2 — The approval date of an administrative transaction, and the date of the invoice it raises

**The defect.** Two separate problems, both confirmed by measurement:

1. **The test could not detect the bug it was written for.** It used
   `$approvedDate = now()->toDateString()` — *today* — so an implementation that
   ignores the sent value and stamps `now()` would be **indistinguishable** from
   a correct one. And it asserted `assertDatabaseHas('invoices', ['issue_date' => $approvedDate])`
   against a **`timestamp`** column, so a bare date **never matched** — the
   assertion was unusable.
2. **The production path discarded the approval date.**
   `InvoiceService::issueInvoice()` wrote `'issue_date' => now()` unconditionally,
   overwriting the `$approvedAt` the controller had already put on the invoice.
   So the invoice raised from a transaction approved on 13 June carried the
   processing date instead.

**The fix.**

- `app/Services/InvoiceService.php` — `issueInvoice(Invoice $invoice, $issueDate = null)`.
  The date is resolved **inside the issue operation**, and it defaults to
  `now()`. Every existing caller is unaffected.
- `app/Http/Controllers/App/CompanyTransactionController.php` — passes
  `$approvedAt` into the issue call, so the document carries the business date
  it was approved on.

The **purchase** branch of the same controller already used `$approvedAt`
directly (`PurchaseInvoice` is not issued through `issueInvoice`), so it needed
no change.

**Acceptance** — `tests/Feature/CompanyTransactionEnhancementsTest.php`,
**7 tests / 40 assertions**, all green. The rewritten test:

- **freezes the clock** at `2026-06-15 10:00:00`;
- uses **`2026-06-13`** as the approval date — deliberately **not** today, so
  an implementation that stamps `now()` produces `2026-06-15` and the
  difference is visible;
- reads the **transaction and its invoice specifically** (`Invoice::findOrFail`),
  not a row-count assertion;
- compares the invoice date **as a date**, because `issue_date` is a timestamp;
- asserts the invoice is `valid` (issued, not left a draft);
- asserts the invoice date is **not** the processing date.

**The test proves the fix.** With the one-line controller change reverted, the
test fails with *"The issued invoice must carry the approval date, not the
moment the approval was processed."* The change was restored afterwards.

> Note: the controller validates `approved_at` with
> `after_or_equal:transaction_date` and `before_or_equal:today`, so the chosen
> date must sit between the transaction date and today. `2026-06-13` satisfies
> both against the frozen clock.

### Fix 3 — The dealer quick-action label

**The finding.** The key named in the note, `dashboard.quick_actions.new_dealer`,
**does not exist and cannot exist**: `dashboard.quick_actions` is a **string**
(`"الإجراءات السريعة"` / `"Quick Actions"`), not an object.

The key the component actually uses is **`dashboard.new_dealer`**
(`resources/js/Layouts/AppLayout.vue:3706`), and it already existed in both
languages. `php artisan translations:check --source=both` reported **zero**
missing keys before this change.

**What was actually wrong** was the **meaning**, not the existence — and only
in English:

| | Before | After |
| :--- | :--- | :--- |
| ar | `شريك / جهة دفع جديدة` | `إضافة شريك جديد` |
| en | **`New Partner / Fleet`** | **`Add New Partner`** |

The destination screen (`resources/js/Pages/Dealers/Index.vue`) calls the
entity **"الشركاء وجهات الفوترة" / "Partners & Third-Party Payers"** and its
action **"إضافة شريك جديد" / "Add New Partner"**. The English label named
**"Fleet"** — which is only **one of the four** dealer types
(`insurance`, `fleet`, `rental`, `corporate`, `app/Models/Dealer.php:19-32`) —
as if it were the entity, and it did not use the screen's **"Add New …"** verb.

Both languages now use the destination screen's own wording.

**Acceptance** — `php artisan translations:check --source=both` ⇒ **0 missing**
in both languages; `VueTranslationKeyTest` and `I18nCoverageTest` green.

### Fix 4 — The tax decision memo (no production calculation changed)

`audit_reports/architecture_review/15_TAX_REFERENCE_AND_PATHS.md` §6 was
rewritten from three open questions into a **decision memo**. Every number in
it was produced by running the **production code paths**, not a
re-implementation.

The three decisions, each with a worked example, the current behaviour of all
three paths, the effect on a new document and on a reprint, a recommendation,
and what needs accounting sign-off:

| # | Decision | Recommendation | Accounting approval needed |
|---|---|---|---|
| 1 | How the displayed line rows reconcile with the document total | **B** — the reference rule for **new** documents; the **stored** header preserved for **old** ones (B1) | ✅ the B1 reconciliation rule |
| 2 | How a work order mixing a taxable and an exempt line is computed | **B** — per-line tax treatment, as a **separate** approved change | ✅ the rule, and what happens to open orders |
| 3 | How a full or partial credit note reverses the original amount and tax | **B** — derive the credit note from the original's **raw** values | ✅ full reversal must land on `0.00`; whether a sub-halala remainder is carried |

Two findings from the memo are worth stating here, because they are new:

- **The `180.00 vs 195.00` case is not a rounding issue.** The work order is
  wrong for a **structural** reason: it carries **one** order-level tax
  snapshot, so a mixed order is either all-taxed (`207.00` — the exempt line
  is taxed) or all-untaxed (`180.00` — the taxable line's `15.00` vanishes).
  The invoice path (`195.00`) is the correct one.
- **A full credit note can never fully reverse an awkward invoice.** Three
  credit notes against a `100.00 / 15.00 / 115.00` invoice reach
  `99.99 / 15.00 / 114.99` — **one halala is permanently stranded**, and the
  tax reconciles only because `33.33 × 15%` happens to round cleanly.

**No production calculation code was changed.** The memo states that plainly
and lists the three rules that only the accountant can set.

---

## 2. Verification

The order was: targeted tests → Vue tests → translation check → ESLint → build
→ **then** the full PHP suite (so the HTTP tests never read a Vite manifest
mid-rebuild) → `optimize:clear`.

| Gate | Result |
| :--- | :--- |
| Targeted PHP (8 suites) | ✅ **92 passed · 0 failed · 370 assertions** |
| Targeted PHP (5 more suites) | ✅ **46 passed · 3 skipped · 129 assertions** |
| Targeted PHP (8 suites, final) | ✅ **72 passed · 0 failed · 290 assertions** |
| `npx vitest run` | ✅ **48 files / 184 tests** |
| `php artisan translations:check --source=both` | ✅ **0 missing** in both languages |
| `npx eslint resources/js` | ✅ **0 errors** (1,876 warnings — all pre-existing style noise) |
| `npm run build` | ✅ succeeded (26.15s) |
| `php artisan test` (full) | **1,796 passed · 7 failed · 8 skipped · 11,006 assertions** (1,465s) |
| `php artisan optimize:clear` | ✅ config · cache · compiled · events · routes · views |

### The 7 remaining failures — all proven to be outside this round

None is in a file this round touched. Each was **proven** by measurement, not
assumed:

| Failure | Proof |
| :--- | :--- |
| `PerformanceBaselineTest::work orders show` — **56** queries vs a budget of **55** | With **both** of this round's changes neutered (the observer and `issueInvoice`), the endpoint still measures **56 queries** — identical. The extra 4 come from the **committed** Dealer feature: `Dealer::where(...)` at `app/Http/Controllers/App/WorkOrders/WorkOrderController.php:405` (and `:232`). This round contributes **zero** queries. |
| `VueI18nBindingTest` (1) | With this round's changes **stashed**, the same failure occurs. The files are `Pages/Public/Portal/{Invoices,Quotes,Appointments}/…` — untouched by this round. |
| `ThirdBatchSafetyTest` (2) | Same — reproduced with the changes stashed. |
| `WorkshopBoardAppointmentsTest` (3) | Same — reproduced with the changes stashed. |

**The stash was popped immediately** and the 14 pre-existing stash entries were
verified intact.

### The 8 skipped tests

The 3 deliberate divergence skips in `TaxProductionPathsComparisonTest` (the
measured divergences, printed in their skip messages) + 5 pre-existing skips.

---

## 3. Files changed by this round

| File | Change |
| :--- | :--- |
| `app/Observers/InvoiceObserver.php` | The allowed fields are keyed by the **transition**; `ISSUE_FIELDS` replaced by `TRANSITION_FIELDS` + `allowedFieldsForTransition()` |
| `app/Services/InvoiceService.php` | `issueInvoice()` takes an optional `$issueDate`, resolved inside the issue operation |
| `app/Http/Controllers/App/CompanyTransactionController.php` | Passes `$approvedAt` into the issue call |
| `tests/Feature/InvoiceImmutabilityTest.php` | +6 acceptance tests for the transition-keyed allowlist (19 → 25) |
| `tests/Feature/CompanyTransactionEnhancementsTest.php` | The clock is frozen, the approval date is distinct from today, the invoice is read specifically and compared as a date |
| `resources/js/i18n/lang/ar.json` | `dashboard.new_dealer` → `إضافة شريك جديد` |
| `resources/js/i18n/lang/en.json` | `dashboard.new_dealer` → `Add New Partner` |
| `audit_reports/architecture_review/15_TAX_REFERENCE_AND_PATHS.md` | §6 rewritten as the decision memo |

**Not touched:** the client-side gate work, and any change belonging to the
other agents.

---

## 4. What is still open

1. **The three tax decisions** need the accountant's answers before any
   production calculation changes — the memo lists what is needed for each.
2. **The work-order query budget** (56 vs 55) is the Dealer feature's cost on
   the work-order detail page. It is a real budget miss and belongs to the
   Dealer round — the fix is to eager-load or reuse the dealer list the page
   already fetches, not to raise the budget.
3. **The 6 failures from the Dealer feature** (`VueI18nBindingTest`,
   `ThirdBatchSafetyTest`, `WorkshopBoardAppointmentsTest`) are that feature's
   to close.
