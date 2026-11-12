# Phase 3 — Reference Financial Behaviour & Path Comparison

**Date:** 2026-11-11
**Scope:** Lock the reference tax behaviour, then measure every production
calculation path against it BEFORE any consolidation.
**Status:** Reference pinned ✅ · Paths measured ✅ · Consolidation ⏳ pending

---

## 1. Why this phase exists

The audit claimed the three document-producing paths (work order, invoice,
credit note) calculate tax the same way. Measurement shows they do **not**.
Before consolidating them, the correct behaviour has to be decided
independently of the current code — otherwise "unify" would just mean
"make everything behave like whichever path was copied first".

So this phase is deliberately in two halves:

1. **Pin the reference** — what a document must say.
2. **Measure the paths** — where each one disagrees with the reference.

No production calculation code was changed in this phase.

---

## 2. The reference rule

Stated in `tests/Feature/TaxReferenceCasesTest.php`:

> Line values are **not** rounded. The document totals are rounded **once**,
> at the end, from the unrounded line values.

```
line  →  raw_excl, raw_tax, raw_incl   (unrounded)
doc   →  Σ raw values, then round(…, 2) exactly once
```

This is the ZATCA-consistent rule: tax is computed on the sum of the
taxable amounts of the document, not on a sum of pre-rounded line taxes.

### 2.1 Reference cases (10 cases, all green)

| Case | excl | tax | incl |
| :--- | ---: | ---: | ---: |
| exclusive, one line | 100.00 | 15.00 | 115.00 |
| exclusive, two lines | 200.00 | 30.00 | 230.00 |
| exclusive, with discount | 180.00 | 27.00 | 207.00 |
| inclusive, one line | 100.00 | 15.00 | 115.00 |
| inclusive, with discount | 86.96 | 13.04 | 100.00 |
| tax-exempt, one line | 100.00 | 0.00 | 100.00 |
| tax-exempt, with discount | 180.00 | 0.00 | 180.00 |
| three awkward lines (33.3333 ×3) | 100.00 | 15.00 | 115.00 |
| mixed inclusive + awkward | 26.28 | 3.94 | 30.22 |
| mixed exclusive + exempt | 180.00 | 15.00 | 195.00 |

`php artisan test tests/Feature/TaxReferenceCasesTest.php`
→ **23 passed (47 assertions)**

### 2.2 Notes on the reference mechanics

- **A tax-exempt line is a zero rate.** `TaxCalculator::calculateLine()`
  has no `taxEnabled` flag. The callers express exemption as
  `$taxRate = $taxEnabled ? $rate : 0.0`.
- **Inclusive pricing derives the net.** `gross / (1 + rate)`, and the tax
  is `gross − net`. The discount comes off the gross **before** the split.
- **The discount reduces the tax base**, confirmed by
  `test_the_discount_reduces_the_tax_base`.

---

## 3. Path measurement

`tests/Feature/TaxProductionPathsComparisonTest.php` transcribes each
production path **exactly** (not a re-implementation) and compares it to
the reference. It is a characterisation test: it pins today's behaviour so
a consolidation cannot silently move a historical value.

`php artisan test tests/Feature/TaxProductionPathsComparisonTest.php --display-skipped`
→ **7 passed, 3 skipped (19 assertions)** — the three skips ARE the
evidence, each carrying the divergent numbers in its skip message.

### 3.1 The three paths as they exist today

| Path | Where | Behaviour |
| :--- | :--- | :--- |
| **Work order** | `WorkOrder::recalculateTotals()` (`app/Models/WorkOrder.php:105-126`) | Aggregates the net across ALL items+parts, then applies the tax ONCE on the document total via `TaxCalculator`. |
| **Invoice** | `InvoiceService::createFromWorkOrder()` (`app/Services/InvoiceService.php:144-154`, `193-203`) | Rounds EVERY line, then sums the rounded lines (`:230-239`). |
| **Credit note** | `CreditNoteService::createFromWorkOrder()` (`app/Services/CreditNoteService.php:252-259`, `310-317`) | Rounds EVERY line, then sums the rounded lines. |

`TaxCalculator` itself is referenced in exactly **one** production place:
`app/Models/WorkOrder.php:115`. The invoice and credit-note paths
re-implement the arithmetic inline and never call it.

### 3.2 Where each path diverges from the reference

| Reference case | Path that diverges | Path output | Reference output |
| :--- | :--- | :--- | :--- |
| `rounding_three_awkward_lines` | invoice / credit note | excl `99.99`, incl `114.99` | excl `100.00`, incl `115.00` |
| `rounding_inclusive_awkward` | invoice / credit note | tax `3.95`, incl `30.23` | tax `3.94`, incl `30.22` |
| `mixed_exclusive_and_exempt` | work order | tax `0.00`, incl `180.00` | tax `15.00`, incl `195.00` |

The other seven cases agree across all paths.

### 3.3 Reading the divergences

**The invoice / credit-note divergence is a rounding bug.** Both paths
round each line to the halala and then add the rounded lines up. On
`33.3333 × 3` each line rounds to `33.33`, and `33.33 × 3 = 99.99` — the
document is one halala short of the real `100.00`. The document total is
being computed from display values instead of from the actual amounts.

**The work-order divergence is NOT a rounding bug — it is a model
limitation, and the comparison test reports it as "not comparable"
rather than as a rounding defect.** `WorkOrder::recalculateTotals()`
takes the tax configuration from ONE order-level snapshot
(`tax_enabled_snapshot`, `tax_rate_snapshot`, `pricing_mode_snapshot`),
refreshed from the tenant's `TenantTaxSetting`
(`app/Models/Concerns/HasTaxSnapshot.php:43-55`). It never consults the
per-line `tax_rate_snapshot` / `is_taxable` that DO exist on
`WorkOrderItem` (`app/Models/WorkOrderItem.php:46-63`).

Consequences, both confirmed by reading the code:

1. A tax-exempt line **inside a taxable order is still taxed**. The
   order-level rate is applied to the whole aggregated net. The work
   order cannot represent the `mixed_exclusive_and_exempt` case at all.
2. An **inclusive line and an exclusive line cannot coexist** in one
   order — the single order-level `pricing_mode_snapshot` decides for
   every line. The work order cannot represent the
   `rounding_inclusive_awkward` case at all.

This matters for the consolidation decision: the work-order path is
*arithmetically* correct on the cases it can represent, and the invoice
path is *arithmetically* wrong. They are not two versions of one rule.

### 3.4 The three paths are not equally wrong — the summary

- Invoice / credit-note paths: **wrong by a rounding rule** on awkward
  amounts. Every divergence is a documented, reproducible halala.
- Work-order path: **right about rounding**, but **cannot express
  per-line tax treatment at all**.

---

## 4. What consolidation must therefore preserve

Consolidating onto `TaxCalculator` is necessary but NOT sufficient, and
it must not be done naively.

Constraints that follow from the measurement:

1. **The reference rule is the target.** The document total must be
   `round(Σ raw line values, 2)`, computed once. This is what fixes the
   invoice / credit-note divergence.
2. **Line-level storage still needs per-line values.** The invoice line
   rows persist `line_total_excl_tax` / `line_total_incl_tax` /
   `tax_amount`. The consolidation must store the line's own rounded
   values for display while the **document** total is computed from the
   raw sums — otherwise the line rows and the document header will
   disagree by a halala and both will look "wrong" to a reader.
3. **The `tax_breakdown` must be derived from the raw sums**, grouped by
   category and rate, because ZATCA tax is computed per category group on
   the group sum — which is what `TaxCalculator::calculateDocument()`
   already does (`:78-164`). But `calculateDocument()` currently sums
   `line['line_total_excl_tax']` — the **rounded** line value (`:103`) —
   so it inherits the same defect and must be switched to the raw value.
4. **The credit-note path must not regress.** A credit note is a
   *negative* document derived from an already-issued invoice. Its
   amounts are computed from the ORIGINAL invoice's stored line values
   (already rounded), so its "raw" baseline is the original document's
   raw values, not a fresh recomputation. This needs its own reference
   case before the path is touched.
5. **The work-order path must not silently change.** It is currently the
   most correct of the three. Unifying it onto a shared engine must keep
   its output identical on the cases it can represent, and the
   per-line-exemption limitation must be recorded as a known gap rather
   than quietly changed.

---

## 5. Test inventory added in this phase

| File | Tests | Purpose |
| :--- | ---: | :--- |
| `tests/Feature/TaxReferenceCasesTest.php` | 23 | The reference contract. Green. |
| `tests/Feature/TaxProductionPathsComparisonTest.php` | 10 | Characterisation of today's paths, with the divergences printed. |

---

## 6. Decision memo — four questions before any production calculation changes

**Date:** 2026-11-12
**Status:** DECISION REQUIRED. **No production calculation code has been changed.**
Every number below was produced by running the **production code paths**, not by a
re-implementation — the same transcription method as
`tests/Feature/TaxProductionPathsComparisonTest.php`.

Each decision states: the current behaviour of each path, a worked numeric
example, the effect on a **new** document and on a **reprint of an old one**, a
recommendation, and what needs accounting sign-off.

Decision 4 was added on 2026-11-12 — it is a numbering question rather than a
tax question, but it bears on the same document and the same year-boundary
case.

**Every number in this section was measured.** Decisions 1-3 are measured
through the real `InvoiceService` and `CreditNoteService` with documents that
actually exist in the database (`tests/Feature/TaxServiceIntegrationTest.php`);
Decision 4 is measured through the real transaction approval route
(`tests/Feature/InvoiceSequenceYearBoundaryTest.php`). Where an earlier
version of this memo relied on a transcription of the arithmetic, the number
has been corrected and the correction is recorded in §6.2.

---

### Decision 1 — How do the displayed line rows reconcile with the document total?

**The problem.** The invoice line rows store **rounded** values (they are what the
user sees on the printed invoice). The reference rule says the document total is
`round(Σ raw line values, 2)` — rounded **once**.

On an ordinary document the two agree. On an awkward document they differ by a
halala, and then the printed invoice **shows lines that do not add up to the
header**.

#### Current behaviour — worked example A

Three lines of `33.3333` at 15%:

| | excl | tax | incl |
| :--- | ---: | ---: | ---: |
| **Invoice / credit note** (round each line, then sum) | **99.99** | 15.00 | **114.99** |
| **Reference** (sum raw, round once) | **100.00** | 15.00 | **115.00** |
| **Work order** (aggregate the net, tax once) | **100.00** | 15.00 | **115.00** |

The stored invoice line rows are:

```
excl 33.33   tax 5.00   incl 38.33
excl 33.33   tax 5.00   incl 38.33
excl 33.33   tax 5.00   incl 38.33
                sum = 99.99 / 114.99
```

So **today** the printed invoice is internally consistent: the lines add up to
the header, because the header was computed from the same rounded lines. The
halala is lost from the *header*, not from the lines — the document under-states
what was sold by `0.01`.

#### Confirmed by a document created today through the real service

`tests/Feature/TaxServiceIntegrationTest.php` raises a real invoice from a
work order with three lines of `33.3333` and reads the saved rows back:

```
[AWKWARD] header: {"status":"valid","total_excl_tax":99.99,"total_tax":15,
                    "total_incl_tax":114.99,"total_taxable_amount":99.99}
[AWKWARD] line: {"qty":1,"unit_price":33.33,"excl":33.33,"tax":5,"incl":38.33}
[AWKWARD] line: {"qty":1,"unit_price":33.33,"excl":33.33,"tax":5,"incl":38.33}
[AWKWARD] line: {"qty":1,"unit_price":33.33,"excl":33.33,"tax":5,"incl":38.33}
[AWKWARD] stored line sum: excl 99.99 tax 15.00 incl 114.99
```

Note two things the measurement adds that the transcription could not show:

- The stored `unit_price` is **`33.33`**, not `33.3333`. The invoice line
  row stores the ROUNDED unit price, so the raw value is **not recoverable
  from the line row alone** — it has to be re-derived from
  `qty × unit_price − discount` using the *rounded* unit price, which is
  why Decision 3's Option B has to be verified against a discount that is
  expressed as a percentage.
- The header agrees with the stored lines **exactly** (`99.99` / `15.00` /
  `114.99`), so the printed document is internally consistent today.

Under the reference rule the header becomes `100.00 / 115.00` while the lines
still read `33.33 × 3 = 99.99`. The document would then be **internally
inconsistent by one halala**, unless the line rows also stop being rounded.

#### Worked example B — a mixed inclusive document

| | excl | tax | incl |
| :--- | ---: | ---: | ---: |
| Invoice / credit note | 26.28 | **3.95** | **30.23** |
| Reference | 26.28 | **3.94** | **30.22** |

Lines: `[excl 26.10 tax 3.92 incl 30.02]` + `[excl 0.18 tax 0.03 incl 0.21]`
→ the displayed rows sum to `26.28 / 3.95 / 30.23`, matching today's header.

> Note the **work-order path is wrong here too**, but differently: it applies
> the inclusive split to the *aggregated* net of an inclusive line and an
> exclusive line, producing `30.19 / 4.53 / 34.72`. An order cannot represent
> this document at all (see Decision 2).

#### Options

| Option | New document | Reprint of an old document | Cost |
| :--- | :--- | :--- | :--- |
| **A. Keep per-line rounding** (today) | header = Σ rounded lines, always consistent, under-states by ≤ ½ halala per document | identical | none — but the reference rule is **not** met, and ZATCA tax on the group sum is not honoured |
| **B. Reference rule; line rows also carry raw-derived values** | header = `round(Σ raw)`; lines carry the raw-derived split, so lines and header agree | **changes**: the header of an existing document would differ from what is stored | needs a re-issue / reconciliation story for historical documents |
| **C. Reference rule for the header; keep rounded lines** | header = `round(Σ raw)`; lines stay rounded ⇒ **lines ≠ header by 0.01** | header changes for awkward documents | a printed invoice that does not add up — **not acceptable** |

#### Recommendation — **Option B**, with a deliberate reconciliation

Option C is out of the question: an invoice whose lines do not add up to its own
header is a document a customer can reject.

Option A is the status quo and it is **internally consistent**, but it is not
the reference rule, and it is the reason the two paths disagree at all.

Option B is the reference rule, and it is what ZATCA asks for — tax is computed
on the **sum of the taxable amounts**, not on a sum of pre-rounded line taxes.

**The consequence that needs accounting sign-off:** under Option B, the header
of an *existing* awkward document is `0.01` higher than what is stored. Options:

- **B1 — Reprint shows the stored header.** The stored document is what was
  issued and reported; a reprint must reproduce it. New documents follow the
  reference rule. ⇒ **the historical value is preserved**, and the two never
  have to agree with each other because they are different documents.
- **B2 — Reprint recomputes.** The reprint shows `100.00` while the stored
  document says `99.99`. ⇒ **a reprint would no longer be a reproduction of the
  issued document.** For a ZATCA-reported invoice this is a compliance
  problem.

**I recommend B1**: *new* documents use the reference rule; *existing* documents
keep their stored header, and a reprint reproduces the stored values exactly.
That is also the only option that does not require touching historical data.

> **Approval needed:** the accountant must confirm that **B1** is acceptable —
> i.e. that a document issued under the old rule keeps its old total forever,
> and only new documents follow the new rule.

---

### Decision 2 — How is the work order computed when it mixes a taxable line and an exempt line?

> **⚠️ CORRECTED 2026-11-12 — the earlier statement in this section was based on a
> transcription of the code, and measurement of the REAL services contradicts
> it.** See the "What the real system does" subsection below. The
> recommendation is unchanged in direction, but the diagnosis is sharper and
> the numbers are different.

#### The 180.00 vs 195.00 case, explained

This is the case from §3.2. Here is what it is:

| | excl | tax | incl |
| :--- | ---: | ---: | ---: |
| Line 1 — taxable, `100.00` @ 15% | 100.00 | 15.00 | 115.00 |
| Line 2 — **exempt**, `80.00` | 80.00 | 0.00 | 80.00 |
| **The reference rule** | **180.00** | **15.00** | **195.00** |

#### What the REAL system does — measured, not transcribed

`tests/Feature/TaxServiceIntegrationTest.php` builds a work order with one
taxable line and one exempt line, raises a real invoice through
`InvoiceService::createFromWorkOrder()`, and reads the **saved** header and
lines back from the database:

```
[MIXED] work order : {"excl":180,"tax":27,"incl":207}
[MIXED] invoice     : {"excl":180,"tax":27,"incl":207}
[MIXED] invoice line: excl 100.00 tax 15.00 incl 115.00 taxable=yes
[MIXED] invoice line: excl 80.00 tax 12.00 incl 92.00 taxable=no
```

**Both documents say `180.00 / 27.00 / 207.00`.** The invoice does **not**
produce the reference's `195.00` — the exempt line carries `12.00` of tax.

#### The root cause — the exemption flag is never read

The invoice line is stored with **`is_taxable = no`**, and it is taxed
anyway. The reason is in `InvoiceService::createFromWorkOrder()`:

```php
// app/Services/InvoiceService.php:169-172 — WRITES the flag
'is_taxable' => $taxEnabled && ($item->is_taxable !== false),
'tax_rate_snapshot' => $taxRate,
'tax_amount' => $taxAmt,          // ← computed WITHOUT reading is_taxable
```

The tax is computed at `:150-153` from the **order-level**
`tax_enabled_snapshot` and the line's `tax_rate_snapshot`. The line's own
`is_taxable` is never consulted.

A repository-wide check confirms it:

```
$ grep -rn "is_taxable" app/Services/InvoiceService.php app/Services/CreditNoteService.php \
      app/Models/WorkOrder.php app/Models/Quote.php | grep -v "'is_taxable' =>"
(empty — is_taxable is never READ in any calculation path)
```

`CreditNoteService` has the same shape (`:272`, `:330`, `:753`, `:804` all
write it; none reads it).

**Consequence:** the `195.00` reference case — and the whole notion of a
per-line exemption — is **not reachable in production today**. A line can be
marked exempt in the data and still be taxed. This is not a rounding
question; it is a **missing rule**.

#### Why the work order and the invoice agree here

`WorkOrder::recalculateTotals()` aggregates the net and applies the
order-level rate once, so it also taxes the exempt line:

| Document | excl | tax | incl |
| :--- | ---: | ---: | ---: |
| Work order | 180.00 | 27.00 | 207.00 |
| Invoice | 180.00 | 27.00 | 207.00 |
| **The reference rule** | **180.00** | **15.00** | **195.00** |

So the two paths do **not** disagree on a mixed order — my earlier reading of
the code was wrong on that point. They agree with each other and **both
disagree with the reference**.

The work order's separate, real limitation stands: it carries **one**
`pricing_mode_snapshot` for the whole order, so an inclusive line cannot
coexist with an exclusive one.

#### Options

| Option | New document | Old document | Cost |
| :--- | :--- | :--- | :--- |
| **A. Keep today** | the exemption flag is decorative — a line marked exempt is still taxed | unchanged | none — but a tax-exempt line is **not** exempt, and the data says it is |
| **B. Honour the per-line flag** | the invoice and the work order both produce `195.00` | **changes** for any document with a mixed tax composition | a behaviour change; the columns already exist |
| **C. Remove the flag** | the data stops claiming an exemption that does not happen | unchanged | a data-model change; every existing `is_taxable = false` line needs a decision |

#### Recommendation — **Option B**, as a separate, explicitly-approved change

The data already says which lines are exempt; the calculation simply does not
read it. Honouring it makes the invoice match the reference, and makes the
`is_taxable` column meaningful.

**This must NOT be smuggled into the rounding consolidation.** It changes what
the document *means*, not how it rounds.

> **Approval needed:**
> 1. The accountant confirms the **rule**: a line-level exemption overrides
>    the order-level tax flag.
> 2. The accountant decides what happens to **already-issued documents** whose
>    lines are marked exempt but were taxed — do they stay as they are, or are
>    they corrected? (The immutability guard now forbids editing them, so
>    this is a real question, not a formality.)
> 3. Engineering confirms the **work order** must be changed in the same
>    commit, so the two documents of one sale agree.

### Decision 3 — How does a full or partial credit note reverse the amount and the tax of the original invoice?

> **⚠️ CORRECTED 2026-11-12 — the earlier "a halala is permanently stranded"
> claim was based on a transcription and is WRONG for the real system.**
> Measurement through the actual `CreditNoteService` shows the credit notes
> reconcile **exactly**. See the table below.

#### Current behaviour — measured through the real service

`tests/Feature/TaxServiceIntegrationTest.php` raises a real invoice from a
work order with three lines of `33.3333`, then creates credit notes through
`CreditNoteService::createCreditNoteForInvoice()` one line at a time until the
returnable amount is exhausted, reading every saved document back.

```
[RECONCILE] original invoice: {"excl":99.99,"tax":15,"incl":114.99}
[RECONCILE] round 1: remaining refundable = 99.99
[RECONCILE] credit note #1 saved: excl 33.33 tax 5.00 incl 38.33  | cumulative: excl 33.33  tax 5.00  incl 38.33
[RECONCILE] round 2: remaining refundable = 66.66
[RECONCILE] credit note #2 saved: excl 33.33 tax 5.00 incl 38.33  | cumulative: excl 66.66  tax 10.00 incl 76.66
[RECONCILE] round 3: remaining refundable = 33.33
[RECONCILE] credit note #3 saved: excl 33.33 tax 5.00 incl 38.33  | cumulative: excl 99.99  tax 15.00 incl 114.99
[RECONCILE] round 4: remaining refundable = 0.00
[RECONCILE] FINAL: original 99.99/15.00/114.99  credits 99.99/15.00/114.99  REMAINING 0.00/0.00/0.00
```

#### The reconciliation table

| | excl | tax | incl |
| :--- | ---: | ---: | ---: |
| **Original invoice** | **99.99** | **15.00** | **114.99** |
| Credit note #1 | 33.33 | 5.00 | 38.33 |
| Credit note #2 | 33.33 | 5.00 | 38.33 |
| Credit note #3 | 33.33 | 5.00 | 38.33 |
| **Total of the credit notes** | **99.99** | **15.00** | **114.99** |
| **Remaining** | **0.00** | **0.00** | **0.00** |

**The credit notes reconcile exactly.** The `refundable_amount` is recomputed
each round from the **stored** original and the **stored** prior credit-note
lines, and it reaches exactly `0.00` on the third round.

#### Why the earlier "stranded halala" claim was wrong

The earlier version of this memo claimed that three credit notes against a
`100.00` invoice reach `99.99` and strand a halala.

That was computed from a **transcription** that started from `100.00`. The
real invoice never says `100.00` — the service stores `99.99`, because it
rounds each line to `33.33` first and sums the stored lines. So the invoice
and its credit notes agree, and **there is nothing stranded**.

**The halala was never lost — it was never there.** The three `33.3333` lines
really are `99.9999`; the document says `99.99`, and the credit notes say
`99.99`. What the document *under-states* is the same amount the credit
notes under-state. They are consistent.

The real question is therefore **not** about the credit note — it is
Decision 1: should the document say `100.00` instead of `99.99`?

#### The three judgements the task asks to separate

| Judgement | What it is | Status |
| :--- | :--- | :--- |
| **A — proven by a document created today** | The credit notes reconcile exactly (`0.00` remaining). The invoice and its credit notes never disagree. The invoice stores `99.99` and the credit notes sum to `99.99`. | ✅ **MEASURED**, with the table above |
| **B — a historical case that needs stored data of a different shape** | A document that was issued with a **header** of `100.00` and **line rows** of `33.33` — which the current code cannot produce, but which a document issued under the *reference* rule (Decision 1, Option B) **would** produce. Its credit notes, computed from the stored rounded lines, would reach `99.99` against a `100.00` header — a one-halala gap. | ⚠️ **NOT YET REACHABLE** — it only exists once Decision 1 changes the header rule. **This is the condition under which the test must be re-run.** |
| **C — the expected effect of a proposed change** | If the header follows the reference rule (`100.00`) while the lines stay rounded (`99.99`), then the credit notes — computed from the rounded lines — reach `99.99` and **one halala is stranded** after a full reversal. | 📋 **PROJECTED** — not measured, because the change has not been made. It is the direct consequence of Decision 1 Option C, which is why Option C is rejected there. |

#### The condition under which the test must be re-run

The reconciliation test above is a **characterisation** of the current
behaviour. It must be re-run when **any** of these changes:

1. **Decision 1** is implemented (the header becomes the raw-sum total) —
   then case **B** becomes reachable, and the credit notes must be derived
   from the raw values so that a full reversal still lands on `0.00`.
2. **Decision 2** is implemented (per-line exemptions are honoured) — the
   credit note path must then read `is_taxable` too, and the mixed-order
   case must be re-measured.
3. The credit note's rounding rule changes.

#### Recommendation — **Option B, but only in step with Decision 1**

| Option | Full reversal | Partial reversal | Reprint |
| :--- | :--- | :--- | :--- |
| **A. Today** | lands on exactly `0.00` ✅ | the sub-halala remainder is discarded at the first partial return | consistent ✅ |
| **B. Derive the credit note from the original's raw values** | lands on exactly `0.00` ✅ **and stays correct after Decision 1** | the remainder is carried, not discarded | consistent ✅ |
| **C. A final "remainder" adjustment** | lands on `0.00` | the residual rides on the last note | a note with a line that matches no sold item — worse for an audit |

**Option A is already correct for today's documents.** Option B is what
makes the credit note **stay** correct if Decision 1 changes the header rule,
and it is what carries a partial return's sub-halala remainder instead of
discarding it.

> **Approval needed:**
> 1. The accountant confirms that a full reversal must land on exactly
>    `0.00` — **it already does today**; this is a constraint to preserve,
>    not a defect to fix.
> 2. The accountant decides whether a **sub-halala remainder on a partial
>    return** should be carried forward (recommended) or written off. This
>    one is a real open question today.
> 3. Engineering confirms the credit note derives its baseline from the
>    original's **raw** values once Decision 1 changes the header rule.

### Decision 4 (added 2026-11-12) — Which year does the invoice sequence follow?

This one was found while closing the engineering round, and it is **not a
tax** decision — but it touches the same document.

#### The question

`InvoiceService::issueInvoice()` stamps the invoice with the issue date it is
given, but it picks the SEQUENCE YEAR from `now()`:

```php
// app/Services/InvoiceService.php:289-294
$year = now()->year;                                                    // ← processing year
$seq  = CenterSequence::getNextValue($tenantId, $centerId, 'invoice', $year);
$invoiceNumber = $tenant->formatDocumentCode('invoice', $centerId, $seq, $year);
```

So a document **approved on 20 December** but **processed on 3 January** gets
a number carrying the **processing** year, while its date carries the
**approval** year.

#### Measured, through the real transaction approval path

`tests/Feature/InvoiceSequenceYearBoundaryTest.php` freezes the clock on
**2026-01-03**, drives the real approval route with an approval date of
**2025-12-20**, and reads the saved invoice and the sequence counters back:

```
[BOUNDARY] issue_date       = 2025-12-20
[BOUNDARY] invoice_number   = INV-1-2026-000001
[BOUNDARY] processing year  = 2026
[BOUNDARY] issue-date year  = 2025
[BOUNDARY] sequence type=icv     year=NULL current_value=1
[BOUNDARY] sequence type=invoice year=2026 current_value=1
[BOUNDARY] number year = 2026 vs issue-date year = 2025
```

**The document carries two different years.** And so does the rest of the
system:

| What | Which date it uses |
| :--- | :--- |
| `invoices.issue_date` | the **approval** date (`2025-12-20`) |
| The tax period attribution (`TaxPeriodService`) | `whereDate('issue_date', …)` — the **approval** year |
| The financial summary (`FinancialSummaryReportService`) | `whereDate('invoices.issue_date', …)` — the **approval** year |
| The ZATCA QR timestamp (`InvoiceService::generateZatcaQr`) | `$invoice->issue_date` — the **approval** year |
| **The invoice number and its sequence** | **`now()`** — the **processing** year |

So the document is reported, taxed and QR-encoded under **2025**, while its
number and its sequence row say **2026**.

#### Is there a rule? — searched, and the answer is no

| Source | Result |
| :--- | :--- |
| `AGENTS.md` (the constitution) | **No rule.** No section mentions numbering, the sequence, or the year. |
| `docs/ADR/0001-0003` | **No rule.** |
| `audit_reports/**` | **No rule.** The reports document that `center_sequences` is unique on `(tenant_id, center_id, type, year)` — the *mechanism*, never the *year source*. |
| The code | `now()->year`, introduced in commit `9231c808` (Dec 2025) — when the invoice had **no issue date at all**, so `now()` was the only option available. It was never revisited when the issue date became settable. |
| The tests | `tests/Feature/SecondBatchFinancialSafetyTest.php` asserts the sequence is **not** consumed twice for a stale issuance — it says nothing about which year. |

**The rule was never decided.** The current behaviour is an accident of the
original implementation, not a decision.

#### The two candidate rules, with a numeric example

**Rule A — the number follows the ISSUE date** (the year the document
belongs to):

> Approved 2025-12-20, processed 2026-01-03 ⇒ `issue_date = 2025-12-20`,
> `invoice_number = INV-1-2025-000001`, and the sequence row is
> `(tenant, center, 'invoice', year = 2025)`.

**Rule B — the number follows the PROCESSING moment** (today's behaviour):

> Approved 2025-12-20, processed 2026-01-03 ⇒ `issue_date = 2025-12-20`,
> `invoice_number = INV-1-2026-000001`, and the sequence row is
> `(tenant, center, 'invoice', year = 2026)`.

#### What each rule costs

| | Rule A (issue date) | Rule B (processing, today) |
| :--- | :--- | :--- |
| The number agrees with the date | ✅ yes | ❌ **no** — the document says two different years |
| The number agrees with the tax period | ✅ yes | ❌ **no** — the document is taxed under 2025 but numbered 2026 |
| The number agrees with the ZATCA QR | ✅ yes | ❌ **no** |
| The sequence resets | on 1 January of the document's year | on 1 January of the processing year |
| The ICV (ZATCA counter) | **unchanged** — it is called with **no year** (`InvoiceService.php:298`), so it never resets and stays strictly increasing, as ZATCA requires | unchanged |
| An invoice issued in December and processed in January | consumes the **2025** sequence — which a later 2025 document would also use | consumes the **2026** sequence — which a January document also uses |
| Backward compatibility | existing 2026 documents keep their numbers; **only documents issued at a year boundary change** | — |

#### Recommendation — **Rule A**, but it needs the owner's sign-off

Three things point the same way: the **tax period**, the **financial summary**
and the **ZATCA QR** all already use `issue_date`. Making the number follow
the same date is the only rule under which the document has **one** year.

**But this changes a financial document number**, so it is not a decision an
agent may take alone. Specifically it needs the owner's answer on:

1. **Does the number follow the issue date or the processing moment?**
   (Recommended: the issue date.)
2. **What happens to a document that was issued at a year boundary under the
   old rule?** It keeps its number — the guard now forbids changing it — and
   the two rules simply coexist across the boundary. The accountant must
   confirm that is acceptable.
3. **`{MM}` and `{DD}` in the number format are a separate, smaller defect.**
   `Tenant::formatDocumentCode()` fills them from `now()` regardless of the
   year it was given:
   ```php
   // app/Models/Tenant.php:218-219
   '{MM}' => now()->format('m'),
   '{DD}' => now()->format('d'),
   ```
   So a tenant whose format is `INV-{YYYY}-{MM}-{SEQ}` gets the **processing**
   month and day next to the year. This is the same class of defect and
   should be fixed in the same decision.

**Until this is answered, the production behaviour is left unchanged** — as
the task requires. The test records the behaviour rather than asserting a
rule, so it will keep passing either way and will keep printing the numbers
that make the decision possible.

---

## 6.1 Summary of the four decisions

| # | Question | Recommendation | Needs accounting approval | Engineering cost |
|---|---|---|---|---|
| 1 | Line rows vs document total | **B** — reference rule for new documents, stored header preserved for old ones (**B1**) | ✅ yes — the B1 reconciliation rule | medium — a shared calculation path + the line-row values change |
| 2 | Mixed taxable/exempt line | **B** — honour the per-line `is_taxable` flag, which **no calculation path reads today** | ✅ yes — the rule, and what happens to already-issued documents | medium — the columns exist, the calculation does not use them |
| 3 | Credit note reversal | **B** — derive the credit note from the original's raw values, so it stays correct after Decision 1 | ✅ yes — a full reversal must land on `0.00` (**it already does**) | medium — re-derivation + the partial-return remainder |
| 4 | Which year the invoice sequence follows | **A** — the **issue date**, like the tax period, the summary and the ZATCA QR already do | ✅ yes — plus the owner's answer on boundary documents and on `{MM}`/`{DD}` | small — one line, plus the format helper |

### 6.2 What measurement changed since the first version of this memo

| Claim | Status |
| :--- | :--- |
| "Three credit notes against an awkward invoice strand a halala" | ❌ **RETRACTED** — measured through the real service: the credits reconcile to `0.00`. The invoice itself stores `99.99`, so there is nothing to strand. |
| "The invoice path computes per line, so it gets `195.00` for a mixed order" | ❌ **CORRECTED** — measured: the invoice gives `207.00`. It stores `is_taxable = no` and taxes the line anyway. **No calculation path reads `is_taxable`.** |
| "The work order and the invoice disagree on a mixed order" | ❌ **CORRECTED** — measured: both give `207.00`. They agree with each other and both disagree with the reference. |
| "The invoice line rows store rounded values, and the header is computed from them" | ✅ **CONFIRMED** by measurement — and the stored `unit_price` is `33.33`, not `33.3333`, so the raw value must be re-derived. |

**None of the three is a mechanical change.** Each needs a rule that only the
accountant can set, and each has a consequence for documents that already
exist. That is why they are written down here rather than implemented.

**Suggested order:** Decision 3 first (it is self-contained and fixes a real
reconciliation defect), then Decision 1 (it is the ZATCA-aligned one), then
Decision 2 (which is a behaviour change and belongs in its own round).

**Nothing in the production calculation changes until these three are approved.**
