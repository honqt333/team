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

## 6. Open questions for the consolidation round

These are **not** decided here; they need the user's call before any
production calculation code is touched.

1. **Should the invoice line rows store rounded values, and the document
   header store the raw-sum total?** (Constraint 2.) The alternative —
   making the document total the sum of the rounded lines — preserves
   internal consistency at the cost of the reference rule. The reference
   rule was chosen because ZATCA requires tax on the group sum.
2. **Should the work order gain per-line tax treatment?** That is a
   schema + behaviour change (`WorkOrderItem.tax_rate_snapshot` and
   `is_taxable` already exist but are unused by the totals calculation).
   It is out of scope for a tax consolidation round and should be a
   separate, explicitly-approved change.
3. **Should the credit note's baseline be the original invoice's raw
   values?** A credit note that rounds differently from the invoice it
   reverses is a reconciliation problem for the accountant.
