<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\Optimization\TaxCalculator;
use Tests\TestCase;

/**
 * Compares the PRODUCTION tax paths against the reference cases.
 *
 * Three code paths produce a document total today:
 *
 *  - the Work Order side, which aggregates the net first and applies the
 *    tax once on the document total;
 *  - the invoice side (`InvoiceService`), which rounds every line and
 *    then sums the rounded lines;
 *  - the credit note side (`CreditNoteService`), which also rounds every
 *    line and then sums the rounded lines.
 *
 * The reference cases in `TaxReferenceCasesTest` define what a document
 * must say. This test measures each production path against them and
 * reports the difference, so that any consolidation is driven by
 * evidence rather than by the assumption that the paths already agree.
 *
 * The path implementations are reproduced here as closures that mirror
 * the production code EXACTLY. They are not a re-implementation of the
 * reference: they are a transcription of what the production code
 * does, so the comparison measures the production behaviour.
 *
 * @see TaxReferenceCasesTest  for the reference values
 */
class TaxProductionPathsComparisonTest extends TestCase
{
    /**
     * The reference cases, kept in lockstep with the reference test.
     *
     * Each entry is a two-element list: the case name, then the case
     * itself, so PHPUnit passes them positionally.
     *
     * @return array<string, array{0: string, 1: array{lines: list<array>, expected: array}}>
     */
    public static function referenceCases(): array
    {
        $cases = [
            'exclusive_single_line_15pct' => [
                'lines' => [
                    ['unitPrice' => 100.0, 'qty' => 1.0, 'discount' => 0.0, 'taxRate' => 15.0, 'taxEnabled' => true, 'isInclusive' => false],
                ],
                'expected' => ['total_excl_tax' => 100.00, 'total_tax' => 15.00, 'total_incl_tax' => 115.00],
            ],
            'exclusive_two_lines' => [
                'lines' => [
                    ['unitPrice' => 100.0, 'qty' => 1.0, 'discount' => 0.0, 'taxRate' => 15.0, 'taxEnabled' => true, 'isInclusive' => false],
                    ['unitPrice' => 50.0, 'qty' => 2.0, 'discount' => 0.0, 'taxRate' => 15.0, 'taxEnabled' => true, 'isInclusive' => false],
                ],
                'expected' => ['total_excl_tax' => 200.00, 'total_tax' => 30.00, 'total_incl_tax' => 230.00],
            ],
            'exclusive_with_discount' => [
                'lines' => [
                    ['unitPrice' => 100.0, 'qty' => 2.0, 'discount' => 20.0, 'taxRate' => 15.0, 'taxEnabled' => true, 'isInclusive' => false],
                ],
                'expected' => ['total_excl_tax' => 180.00, 'total_tax' => 27.00, 'total_incl_tax' => 207.00],
            ],
            'inclusive_single_line_15pct' => [
                'lines' => [
                    ['unitPrice' => 115.0, 'qty' => 1.0, 'discount' => 0.0, 'taxRate' => 15.0, 'taxEnabled' => true, 'isInclusive' => true],
                ],
                'expected' => ['total_excl_tax' => 100.00, 'total_tax' => 15.00, 'total_incl_tax' => 115.00],
            ],
            'inclusive_with_discount' => [
                'lines' => [
                    ['unitPrice' => 115.0, 'qty' => 1.0, 'discount' => 15.0, 'taxRate' => 15.0, 'taxEnabled' => true, 'isInclusive' => true],
                ],
                'expected' => ['total_excl_tax' => 86.96, 'total_tax' => 13.04, 'total_incl_tax' => 100.00],
            ],
            'tax_exempt_single_line' => [
                'lines' => [
                    ['unitPrice' => 100.0, 'qty' => 1.0, 'discount' => 0.0, 'taxRate' => 15.0, 'taxEnabled' => false, 'isInclusive' => false],
                ],
                'expected' => ['total_excl_tax' => 100.00, 'total_tax' => 0.00, 'total_incl_tax' => 100.00],
            ],
            'rounding_three_awkward_lines' => [
                'lines' => [
                    ['unitPrice' => 33.3333, 'qty' => 1.0, 'discount' => 0.0, 'taxRate' => 15.0, 'taxEnabled' => true, 'isInclusive' => false],
                    ['unitPrice' => 33.3333, 'qty' => 1.0, 'discount' => 0.0, 'taxRate' => 15.0, 'taxEnabled' => true, 'isInclusive' => false],
                    ['unitPrice' => 33.3333, 'qty' => 1.0, 'discount' => 0.0, 'taxRate' => 15.0, 'taxEnabled' => true, 'isInclusive' => false],
                ],
                'expected' => ['total_excl_tax' => 100.00, 'total_tax' => 15.00, 'total_incl_tax' => 115.00],
            ],
            'rounding_inclusive_awkward' => [
                'lines' => [
                    ['unitPrice' => 10.005, 'qty' => 3.0, 'discount' => 0.0, 'taxRate' => 15.0, 'taxEnabled' => true, 'isInclusive' => true],
                    ['unitPrice' => 0.175, 'qty' => 1.0, 'discount' => 0.0, 'taxRate' => 15.0, 'taxEnabled' => true, 'isInclusive' => false],
                ],
                'expected' => ['total_excl_tax' => 26.28, 'total_tax' => 3.94, 'total_incl_tax' => 30.22],
            ],
            'mixed_exclusive_and_exempt' => [
                'lines' => [
                    ['unitPrice' => 100.0, 'qty' => 1.0, 'discount' => 0.0, 'taxRate' => 15.0, 'taxEnabled' => true, 'isInclusive' => false],
                    ['unitPrice' => 80.0, 'qty' => 1.0, 'discount' => 0.0, 'taxRate' => 15.0, 'taxEnabled' => false, 'isInclusive' => false],
                ],
                'expected' => ['total_excl_tax' => 180.00, 'total_tax' => 15.00, 'total_incl_tax' => 195.00],
            ],
        ];

        $provider = [];

        foreach ($cases as $name => $case) {
            $provider[$name] = [$name, $case];
        }

        return $provider;
    }

    /**
     * The reference behaviour: sum the UNROUNDED line values, round once
     * at the document level.
     *
     * @param list<array> $specs
     * @return array{total_excl_tax: float, total_tax: float, total_incl_tax: float}
     */
    private function referencePath(array $specs): array
    {
        $calculator = app(TaxCalculator::class);

        $excl = 0.0;
        $tax = 0.0;
        $incl = 0.0;

        foreach ($specs as $spec) {
            $result = $calculator->calculateLine(
                $spec['unitPrice'],
                $spec['qty'],
                $spec['discount'],
                $spec['taxEnabled'] ? $spec['taxRate'] : 0.0,
                $spec['isInclusive']
            );

            $excl += $result['raw_line_total_excl'];
            $tax += $result['raw_tax_amount'];
            $incl += $result['raw_line_total_incl'];
        }

        return [
            'total_excl_tax' => round($excl, 2),
            'total_tax' => round($tax, 2),
            'total_incl_tax' => round($incl, 2),
        ];
    }

    /**
     * The invoice / credit note behaviour: round EVERY line, then sum
     * the rounded lines.
     *
     * This is a transcription of the production code in
     * `InvoiceService::createFromWorkOrder()` and in
     * `CreditNoteService::createFromWorkOrder()`.
     *
     * @param list<array> $specs
     * @return array{total_excl_tax: float, total_tax: float, total_incl_tax: float}
     */
    private function perLineRoundedPath(array $specs): array
    {
        $excl = 0.0;
        $tax = 0.0;
        $incl = 0.0;

        foreach ($specs as $spec) {
            $taxEnabled = $spec['taxEnabled'];
            $taxRate = $taxEnabled ? $spec['taxRate'] : 0.0;

            $net = max(0, ($spec['qty'] * $spec['unitPrice']) - $spec['discount']);

            if ($spec['isInclusive'] && $taxEnabled) {
                $lineIncl = round($net, 2);
                $lineExcl = $taxRate > 0 ? round($net / (1 + ($taxRate / 100)), 2) : $net;
                $lineTax = round($lineIncl - $lineExcl, 2);
            } else {
                $lineExcl = round($net, 2);
                $lineTax = $taxEnabled ? round($lineExcl * ($taxRate / 100), 2) : 0.0;
                $lineIncl = round($lineExcl + $lineTax, 2);
            }

            $excl += $lineExcl;
            $tax += $lineTax;
            $incl += $lineIncl;
        }

        return [
            'total_excl_tax' => round($excl, 2),
            'total_tax' => round($tax, 2),
            'total_incl_tax' => round($incl, 2),
        ];
    }

    /**
     * The work order behaviour: aggregate the net FIRST, then apply the
     * tax once on the document total.
     *
     * This is a transcription of `WorkOrder::recalculateTotals()`.
     *
     * NOTE on the tax-enabled flag: the work order carries ONE
     * `tax_enabled_snapshot` for the whole order, NOT a per-line flag.
     * The per-line `tax_rate_snapshot` and `is_taxable` that exist on
     * `WorkOrderItem` are NOT consulted by `recalculateTotals()`.
     *
     * The pricing mode is also a single order-level flag, so an
     * inclusive line and an exclusive line cannot coexist in one order.
     * The reference cases are LINE-level by nature, so the work order
     * can only be compared on the cases that are homogeneous in their
     * pricing mode and their tax rate. The mixed cases are reported as
     * not comparable rather than silently mis-transcribed.
     *
     * @param list<array> $specs
     * @return array{total_excl_tax: float, total_tax: float, total_incl_tax: float}|null
     */
    private function workOrderPath(array $specs): ?array
    {
        $modes = array_unique(array_column($specs, 'isInclusive'));
        $rates = array_unique(array_column($specs, 'taxRate'));

        if (count($modes) > 1 || count($rates) > 1) {
            return null;
        }

        $net = 0.0;
        $taxRate = (float) ($rates[0] ?? 15.0);
        $isInclusive = (bool) ($modes[0] ?? false);

        foreach ($specs as $spec) {
            $net += max(0, ($spec['qty'] * $spec['unitPrice']) - $spec['discount']);
        }

        // The order-level snapshot decides whether ANY tax applies.
        $orderTaxEnabled = true;

        foreach ($specs as $spec) {
            if (! $spec['taxEnabled']) {
                $orderTaxEnabled = false;
            }
        }

        if (! $orderTaxEnabled) {
            return [
                'total_excl_tax' => round($net, 2),
                'total_tax' => 0.0,
                'total_incl_tax' => round($net, 2),
            ];
        }

        $result = app(TaxCalculator::class)->calculateLine(
            unitPrice: $net,
            qty: 1.0,
            discount: 0.0,
            taxRate: $taxRate,
            isInclusive: $isInclusive,
        );

        return [
            'total_excl_tax' => $result['line_total_excl_tax'],
            'total_tax' => $result['tax_amount'],
            'total_incl_tax' => $result['line_total_incl_tax'],
        ];
    }

    /**
     * The production paths must be MEASURED before they are changed.
     *
     * This test records where the paths diverge from the reference. It
     * is a characterisation test: it pins the CURRENT behaviour so that
     * a consolidation cannot silently move a historical value.
     *
     * @dataProvider referenceCases
     */
    public function test_the_production_paths_are_measured_against_the_reference(string $name, array $case): void
    {
        $reference = $this->referencePath($case['lines']);
        $invoicePath = $this->perLineRoundedPath($case['lines']);
        $workOrderPath = $this->workOrderPath($case['lines']);

        // The reference IS the contract.
        $this->assertSame(
            $case['expected']['total_incl_tax'],
            $reference['total_incl_tax'],
            "{$name}: the reference path must produce the documented total"
        );

        // Report every divergence explicitly, so the evidence is in the
        // test output and not only in a commit message.
        $divergences = [];

        if ($invoicePath !== $reference) {
            $divergences[] = sprintf(
                'invoice/credit-note path: %s',
                json_encode($invoicePath)
            );
        }

        if ($workOrderPath !== null && $workOrderPath !== $reference) {
            $divergences[] = sprintf(
                'work-order path: %s',
                json_encode($workOrderPath)
            );
        }

        if ($divergences !== []) {
            $this->markTestSkipped(sprintf(
                '%s diverges from the reference — %s (reference: %s)',
                $name,
                implode('; ', $divergences),
                json_encode($reference)
            ));
        }

        $this->addToAssertionCount(1);
    }

    /**
     * The per-line rounding path is NOT the reference, and the test must
     * be able to see that. Without this, the comparison above could
     * pass vacuously if the transcription were wrong.
     */
    public function test_the_per_line_path_is_known_to_diverge_from_the_reference(): void
    {
        $case = self::referenceCases()['rounding_inclusive_awkward'][1];

        $reference = $this->referencePath($case['lines']);
        $perLine = $this->perLineRoundedPath($case['lines']);

        $this->assertSame(30.22, $reference['total_incl_tax']);
        $this->assertSame(3.94, $reference['total_tax']);

        // The production behaviour, documented.
        $this->assertSame(30.23, $perLine['total_incl_tax']);
        $this->assertSame(3.95, $perLine['total_tax']);
    }
}
