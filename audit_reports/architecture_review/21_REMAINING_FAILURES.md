# The Remaining Failures — Re-run and Classification

**Date:** 2026-11-12
**Revision under test:** `f45f7a0a` + the working-tree changes of this task
**Baseline for comparison:** the run recorded in `20_ROUND_CLOSE_NOTES.md`

---

## 1. Headline

| | Original baseline | Previous round | **Final run** |
|---|---|---|---|
| Failures | **13** | **6** | **5** |
| Passed | 1,636 | 1,815 | **1,822** |
| Skipped | 5 | 8 | **8** |

**Two failures closed between the rounds**: the `I18nCoverageTest` pair, closed
by the parallel Dealer agent adding the missing `Dealers/Show.vue` keys —
`php artisan translations:check --source=both` now reports **0 missing keys in
both languages**.

**One failure changed identity**: the reschedule test now aborts on a
`centers.slug` collision *before* its assertion runs (see ⑤). It is the same
pre-existing factory defect, surfacing differently.

**No failure was closed by this task.** All five remaining failures are
attributed below to a specific feature, each with the evidence that isolates
it — an isolated base-commit measurement, a `git log -S` that names the
commit, or a file-level attribution.

**The staged changes contribute zero to every one of them.** Measured in
three isolated trees: base = 56 queries, base+staged = 56 (byte-identical SQL
dumps), base+staged+unstaged = 57.

---

## 2. The five failures

### ① `PerformanceBaselineTest::work orders show meets perf budget`

| | |
| :--- | :--- |
| **Expectation** | `assertLessThan(55, $queries)` — *"Used 57 queries (target 55)"* |
| **Actual** | **57** queries |
| **Minimal reproduction** | `php artisan test tests/Feature/PerformanceBaselineTest.php --filter="work_orders_show_meets_perf_budget"` — 1 test, 1 assertion |
| **Verdict** | 🔴 **appeared before this round** — and the **+1** is caused by the parallel Dealer/purchase feature |
| **Evidence** | Three isolated trees with a **copied** `vendor/` (a symlinked one resolves `base_path()` to the main repo and measures the wrong tree): base `f45f7a0a` = **56**, base+staged = **56**, base+staged+unstaged = **57**. The staged diff's SQL dump is **byte-identical** to the base dump (`md5 01372a24…`), and an event probe confirms `InvoiceObserver::updating/deleting/forceDeleting` **never fire** on this GET. The +1 is the new eager load at `WorkOrderController.php:373` (`supplierServiceAllocation.purchaseInvoice.supplier`), whose relation was added at `WorkOrderItem.php:241`. |
| **Also proved** | The budget was **already exceeded at the base commit with zero diffs applied** (56 > 55), so removing the +1 does **not** make it green. The endpoint's fixed overhead is 13 dropdown queries at `WorkOrderController.php:415-495`, and the count does not scale with data (**delta 0** between 1 and 27 work orders) — it is not an N+1. |
| **Budget** | **UNCHANGED** — `assertLessThan(55, …)` is preserved exactly. The assertion was not relaxed. |
| **Owner** | The Dealer/purchase feature. The fix is to stop fetching the whole dealer list on the work-order detail page (the work order carries a single `dealer_id`), not to raise the budget. The 55-vs-56 base gap is a separate, pre-existing decision. |

> Note the count moved 56 → 55 between the two runs — the other agent is
> still working on that feature. The budget is not raised either way.

### ② `ThirdBatchSafetyTest::public decision is terminal and only logged once`

| | |
| :--- | :--- |
| **Expectation** | `->post('/view/quote/'.$quote->uuid.'/approve')->assertSessionHas('success')` |
| **Actual** | *"Session is missing expected key [success]"* — the route returns `back()->with('error', 'quotes.empty_decision')` |
| **Minimal reproduction** | `php artisan test tests/Feature/ThirdBatchSafetyTest.php --filter="public_decision_is_terminal"` — 1 test, 1 assertion |
| **Verdict** | 🔴 **appeared before this round** — and **caused by the multi-country/currency feature** |
| **Evidence** | `git log -S "isQuoteEmpty" -- app/Http/Controllers/Public/PublicQuoteController.php` ⇒ **`ddc4782b`**. That commit added the guard and its message in both locales (`lang/ar/quotes.php:14`, `lang/en/quotes.php:14`: *"An empty quote cannot be actioned."*). |
| **The mechanism** | The test's fixture (`tests/Feature/ThirdBatchSafetyTest.php:31-39`) creates a quote with **no lines, no parts and no subtotal**. `PublicQuoteController::isQuoteEmpty()` (`:258-264`) refuses exactly that:<br>`subtotal <= 0 && total <= 0 && lines()->count() === 0 && parts()->count() === 0` |
| **Why the test is wrong, not the code** | The guard is a **feature**, not a defect: approving an empty quote would create an empty work order. The behaviour is documented in both locales. The **fixture** is stale — it predates the guard. |
| **The minimal fix** | Give the fixture a quote with **one line** (or a subtotal), so it exercises the *decision* the test is about (approve → reject → approve is terminal) rather than the *emptiness* guard. The assertions themselves are correct and must not change. |
| **Owner** | The quote feature. Out of this task's scope: it is a **fixture** in a test file this task does not touch, and the fix belongs with the feature that added the guard. |

### ③ `ThirdBatchSafetyTest::rejection is terminal and expired or deleted quotes cannot be actioned`

| | |
| :--- | :--- |
| **Expectation** | `->post('/view/quote/'.$quote->uuid.'/reject', …)->assertSessionHas('success')` |
| **Actual** | *"Session is missing expected key [success]"* — the same `quotes.empty_decision` refusal |
| **Minimal reproduction** | `php artisan test tests/Feature/ThirdBatchSafetyTest.php --filter="rejection_is_terminal"` — 1 test, 1 assertion |
| **Verdict** | 🔴 **appeared before this round** — **same cause as ②** (`ddc4782b`) |
| **Evidence** | Identical mechanism: the same `quote()` fixture (`:31-39`), the same `isQuoteEmpty()` guard (`:258-264`), added by `ddc4782b`. |
| **Owner** | The quote feature — the same fixture fix as ② covers both. |

### ④ `VueI18nBindingTest::a script block that calls the translator has a binding`

| | |
| :--- | :--- |
| **Expectation** | no script block calls `t()` without a `useI18n()` binding |
| **Actual** | 5 files reported: `Pages/Public/Portal/{Invoices/Index,Invoices/Show,Quotes/Index,Quotes/Show,Appointments/Index}.vue` |
| **Minimal reproduction** | `php artisan test tests/Feature/VueI18nBindingTest.php` — 1 test, 1 assertion |
| **Verdict** | 🔴 **appeared before this round** — and **caused by the multi-country/currency feature** |
| **Evidence** | All five files were last changed by **`ddc4782b`** (`git log -1 -- <file>`), and none of them contains `useI18n` (`grep -c useI18n` ⇒ `0`). |
| **The mechanism — and it is a FALSE POSITIVE** | Each of the five binds `t` through the **shared portal composable**:<br>`const { t, formatDate, formatMoney } = usePortalFormat(props);`<br>`resources/js/Composables/usePortalFormat.js:19` does `const { t: tRaw, locale: i18nLocale } = useI18n();` and returns a `t` (`:26-31`). **The binding is real.** |
| **Why the test reports it** | `VueI18nBindingTest::hasBinding()` (`:105-121`) only recognises `useI18n()`. It does not know that `usePortalFormat()` also provides `t`, so a script block that legitimately gets `t` from it is reported. |
| **The minimal fix** | Teach `hasBinding()` that `usePortalFormat()` is a `t` provider — i.e. recognise `const { t, … } = usePortalFormat(…)` as a binding. The **components need no change**; they are correct. |
| **Owner** | The i18n/currency feature that introduced `usePortalFormat`. The test's rule is the thing that is out of date. |

### ⑤ `I18nCoverageTest::no new missing translation keys in {arabic,english}`

| | |
| :--- | :--- |
| **Expectation** | `MISSING_KEYS_EXPECTED = 0` — *"ar has 9 missing translation keys (allowed: 0)"* / the same for `en` |
| **Actual** | **9 missing keys per language**, **18 in total** |
| **Minimal reproduction** | `php artisan test tests/Feature/I18nCoverageTest.php` — 2 tests, 2 assertions |
| **Verdict** | 🔴 **appeared before this round** — and **caused by the Dealer feature** |
| **Evidence** | Every one of the 9 keys is used in **one file only**: `resources/js/Pages/Dealers/Show.vue` — `customers.add_customer` (`:1973`), `vehicles.form.enter_make` (`:2004`), `vehicles.form.enter_model`, `vehicles.form.select_color`, `vehicles.form.select_customer`, `vehicles.form.select_make`, `vehicles.form.select_make_first`, `vehicles.form.select_model`, `vehicles.form.select_year`. `git status` shows that file **modified** in the working tree; `git log -1` shows it last changed in **`ddc4782b`**. |
| **The command agrees** | `php artisan translations:check --source=both` ⇒ **"Total missing keys: 18"**, and **every one of them is in `Dealers/Show.vue`**. |
| **The threshold** | **UNCHANGED** — `MISSING_KEYS_EXPECTED` stays **0**. The coverage was not relaxed to make the suite green. |
| **The minimal fix** | Add the 9 keys to `resources/js/i18n/lang/ar.json` and `en.json`. Some of them may already exist under a different namespace (`vehicles.form.*` and `customers.add_customer` look like they belong to the vehicles/customers sections rather than to the dealer page). |
| **Owner** | The Dealer feature. |

> **This failure was NOT in the seven recorded in `20_ROUND_CLOSE_NOTES.md`.**
> It appeared because the Dealer feature's `Dealers/Show.vue` changed in the
> working tree at **18:43**, during this task. It is recorded here so the
> count is complete and traceable: **7 → 5** on the reviewed revision, with
> the three `WorkshopBoardAppointmentsTest` failures closed by the Dealer
> work and the two `I18nCoverageTest` failures opened by it.

### ⑤ `CustomerPortalActionsHttpTest::reschedule rejects outside working hours`

| | |
| :--- | :--- |
| **Expectation** | the reschedule POST to be rejected with a `booking` validation error |
| **Actual** | **`UniqueConstraintViolationException` on `centers.slug`** — the test aborts *before* the assertion under test is reached |
| **Minimal reproduction** | `php artisan test tests/Feature/CustomerPortalActionsHttpTest.php --filter="test_reschedule_rejects_outside_working_hours"` — passes **5/5 in isolation** (9.3s each) |
| **Verdict** | 🟡 **a pre-existing flake**, not a logic failure |
| **Evidence** | `Center::factory()` derives the slug from `Str::slug(fake()->words(3, true))` (`database/factories/CenterFactory.php:26-31`). Faker's lorem vocabulary is small: **20,000 draws yield 19,843 distinct slugs and 157 collisions** (measured). The test creates a second center in the same class, so a collision with an earlier center aborts it. The failure is a **data collision**, not a defect in the reschedule logic. |
| **Also proved** | `tests/Feature/CustomerPortalActionsHttpTest.php` and `database/factories/CenterFactory.php` are **untouched by both agents** — not in the staged diff, not in the unstaged diff, last changed by `cd514630`. |
| **Owner** | The portal feature. The fix is a deterministic slug in the fixture (e.g. `Str::slug($tenant->slug.'-center-'.$n)`), not a change to the assertion. |

---

## 3. The distinction the task asked for

| | Failed before this round? | Attributed to a specific feature? | Isolating evidence |
| :--- | :--- | :--- | :--- |
| ① work-order query budget | ✅ yes | ✅ **Dealer/purchase feature** (the +1); the 56-over-55 base gap is older still | isolated trees: base=56, base+staged=56 (identical SQL dumps), base+staged+unstaged=57; the extra eager load is at `WorkOrderController.php:373` |
| ② public quote decision | ✅ yes | ✅ **multi-country/currency feature** (`ddc4782b`) | `git log -S "isQuoteEmpty"`; the guard and its bilingual message were added by that commit |
| ③ quote rejection | ✅ yes | ✅ **multi-country/currency feature** (`ddc4782b`) | same guard, same fixture |
| ④ i18n script binding | ✅ yes | ✅ **multi-country/currency feature** (`ddc4782b`) | the five files were last changed by that commit; their `t` binding is real |
| ⑤ reschedule outside working hours | ✅ yes (as a flake) | ✅ **the portal feature's fixture**, not the reschedule logic | `centers.slug` unique violation before the assertion; 157 collisions per 20,000 factory slug draws; passes 5/5 in isolation |

**The two `I18nCoverageTest` failures that appeared in the previous round have
since closed on their own** — the parallel Dealer agent added the missing
`Dealers/Show.vue` keys, and `translations:check --source=both` now reports
**0 missing keys in both languages**.

**Every attribution is backed by a `git log -S` that names the commit**, or by
a file-level attribution with the exact keys listed — not by an inference from
the file names.

**Budgets and assertions were preserved exactly.** The query budget is still
`assertLessThan(55, …)`; no assertion was weakened; no threshold was raised.

---

## 4. What this task did NOT do, deliberately

| | Why |
| :--- | :--- |
| Close ① by raising the query budget | The budget is the thing that is correct. The defect is two unnecessary dealer queries on a page that already has the work order's `dealer_id`. |
| Close ② and ③ by removing the `isQuoteEmpty` guard | The guard prevents approving an empty quote. It is a feature, documented in both locales. The **fixture** is stale, not the guard. |
| Close ④ by adding `useI18n()` to the five portal files | Their `t` binding is **real** — it comes from `usePortalFormat()`. Adding a second binding would be redundant; the test's rule is what needs to learn the composable. |
| Fix any of them as part of this task | Each belongs to a feature owned by another agent, and each touches a file this task does not modify. Fixing them here would mix scopes and make the review ambiguous. |

**Recommended, when the owning feature picks them up:**

- ① → reuse the work order's own `dealer_id` instead of listing every dealer
  on the detail page.
- ② and ③ → one fixture change: give the quote a line.
- ④ → one line in `VueI18nBindingTest::hasBinding()` to recognise
  `usePortalFormat()` as a `t` provider.

---

## 5. The three failures that closed on their own

`WorkshopBoardAppointmentsTest` had three failures in the earlier run
(*"today appointment appears in board payload"*, *"other center appointment
does not appear"*, *"payload is capped at today limit"*). All three pass on
this revision — the Dealer feature's own work closed them. They are recorded
here so the count is traceable: **7 → 4**.
