<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\Optimization\TaxCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The reference cases for the tax behaviour.
 *
 * These cases exist BEFORE any consolidation of the calculation. They
 * decide what a document must say, independently of which code path
 * produced it — the point being that "what is correct" must not be
 * defined by "what the code currently does".
 *
 * The rules they encode:
 *
 *  - Exclusive pricing — the line amount is net of tax; the tax is
 *    added on top.
 *  - Inclusive pricing — the line amount already contains the tax; the
 *    net amount and the tax are derived from it.
 *  - A tax-exempt line carries no tax at all.
 *  - The discount comes off the gross BEFORE the tax is derived.
 *  - Line values are not rounded. The document totals are rounded once,
 *    at the end, from the unrounded line values.
 *
 * The last rule is the one that matters: rounding each line and then
 * summing the rounded values gives a different document total from
 * summing the unrounded values and rounding once. For most documents
 * the two agree, and for some they differ by a halala.
 *
 * @see TaxCalculator
 */
class TaxReferenceCasesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The canonical engine.
     */
    private function calculator(): TaxCalculator
    {
        return app(TaxCalculator::class);
    }

    /**
     * A line, described in the reference terms.
     *
     * The engine has no "tax enabled" flag: a tax-exempt line is
     * expressed as a zero tax rate, which is how the callers do it
     * (`$taxRate = $taxEnabled ? ... : 0.0`).
     *
     * @return array{tax_amount: float, line_total_excl_tax: float, line_total_incl_tax: float}
     */
    private function line(float $unitPrice, float $qty, float $discount, float $taxRate, bool $taxEnabled, bool $isInclusive): array
    {
        $rate = $taxEnabled ? $taxRate : 0.0;

        $result = $this->calculator()->calculateLine(
            $unitPrice,
            $qty,
            $discount,
            $rate,
            $isInclusive
        );

        // The reference uses the UNROUNDED values, then rounds once at
        // the document level.
        return [
            'tax_amount' => $result['raw_tax_amount'],
            'line_total_excl_tax' => $result['raw_line_total_excl'],
            'line_total_incl_tax' => $result['raw_line_total_incl'],
        ];
    }

    /**
     * @param list<array{unitPrice: float, qty: float, discount: float, taxRate: float, taxEnabled: bool, isInclusive: bool}> $specs
     * @return array{total_excl_tax: float, total_tax: float, total_incl_tax: float}
     */
    private function document(array $specs): array
    {
        $excl = 0.0;
        $tax = 0.0;
        $incl = 0.0;

        foreach ($specs as $spec) {
            $line = $this->line(
                $spec['unitPrice'],
                $spec['qty'],
                $spec['discount'],
                $spec['taxRate'],
                $spec['taxEnabled'],
                $spec['isInclusive']
            );

            $excl += $line['line_total_excl_tax'];
            $tax += $line['tax_amount'];
            $incl += $line['line_total_incl_tax'];
        }

        return [
            'total_excl_tax' => round($excl, 2),
            'total_tax' => round($tax, 2),
            'total_incl_tax' => round($incl, 2),
        ];
    }

    /**
     * The reference cases themselves, with their expected totals.
     *
     * Each entry is a two-element list: the case name, then the case
     * itself. PHPUnit passes the list positionally to the test method.
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
            'tax_exempt_with_discount' => [
                'lines' => [
                    ['unitPrice' => 100.0, 'qty' => 2.0, 'discount' => 20.0, 'taxRate' => 15.0, 'taxEnabled' => false, 'isInclusive' => false],
                ],
                'expected' => ['total_excl_tax' => 180.00, 'total_tax' => 0.00, 'total_incl_tax' => 180.00],
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
     * The canonical engine produces the reference totals.
     *
     * @dataProvider referenceCases
     */
    public function test_the_engine_produces_the_reference_totals(string $name, array $case): void
    {
        $actual = $this->document($case['lines']);

        $this->assertSame(
            $case['expected']['total_excl_tax'],
            $actual['total_excl_tax'],
            "{$name}: total_excl_tax"
        );
        $this->assertSame(
            $case['expected']['total_tax'],
            $actual['total_tax'],
            "{$name}: total_tax"
        );
        $this->assertSame(
            $case['expected']['total_incl_tax'],
            $actual['total_incl_tax'],
            "{$name}: total_incl_tax"
        );
    }

    /**
     * The document totals must be internally consistent: the tax plus
     * the net must be the gross, to the halala.
     *
     * @dataProvider referenceCases
     */
    public function test_the_reference_totals_are_internally_consistent(string $name, array $case): void
    {
        $actual = $this->document($case['lines']);

        $this->assertEqualsWithDelta(
            $actual['total_incl_tax'],
            $actual['total_excl_tax'] + $actual['total_tax'],
            0.01,
            "{$name}: excl + tax must equal incl"
        );
    }

    /**
     * A tax-exempt line never carries tax, whatever the rate.
     *
     * The exemption is expressed as a zero rate.
     */
    public function test_a_tax_exempt_line_never_carries_tax(): void
    {
        $result = $this->calculator()->calculateLine(100.0, 1.0, 0.0, 0.0, false);

        $this->assertSame(0.0, (float) $result['tax_amount']);
        $this->assertSame(100.0, (float) $result['line_total_excl_tax']);
        $this->assertSame(100.0, (float) $result['line_total_incl_tax']);
    }

    /**
     * The discount comes off the gross before the tax is derived.
     */
    public function test_the_discount_reduces_the_tax_base(): void
    {
        $withoutDiscount = $this->calculator()->calculateLine(100.0, 2.0, 0.0, 15.0, false);
        $withDiscount = $this->calculator()->calculateLine(100.0, 2.0, 20.0, 15.0, false);

        $this->assertSame(30.0, (float) $withoutDiscount['tax_amount']);
        $this->assertSame(27.0, (float) $withDiscount['tax_amount']);
    }

    /**
     * Inclusive and exclusive pricing describe the SAME commercial
     * outcome when the inclusive amount is the gross of the exclusive
     * one.
     */
    public function test_inclusive_and_exclusive_describe_the_same_outcome(): void
    {
        $exclusive = $this->calculator()->calculateLine(100.0, 1.0, 0.0, 15.0, false);
        $inclusive = $this->calculator()->calculateLine(115.0, 1.0, 0.0, 15.0, true);

        $this->assertEqualsWithDelta(
            (float) $exclusive['line_total_incl_tax'],
            (float) $inclusive['line_total_incl_tax'],
            0.01
        );
        $this->assertEqualsWithDelta(
            (float) $exclusive['tax_amount'],
            (float) $inclusive['tax_amount'],
            0.01
        );
    }
}
