<?php

namespace Tests\Feature;

use App\Services\Optimization\TaxCalculator;
use Tests\TestCase;

class TaxCalculationTest extends TestCase
{
    private TaxCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new TaxCalculator();
    }

    protected function assertFloatEquals($expected, $actual)
    {
        $this->assertEqualsWithDelta($expected, $actual, 0.001);
    }

    /** @test */
    public function test_01_exclusive_standard()
    {
        // 100 * 1 @ 15% -> Tax 15, Total 115
        $res = $this->calculator->calculateLine(100, 1, 0, 15, false);
        
        $this->assertFloatEquals(15.00, $res['tax_amount']);
        $this->assertFloatEquals(100.00, $res['line_total_excl_tax']);
        $this->assertFloatEquals(115.00, $res['line_total_incl_tax']);
    }

    /** @test */
    public function test_02_inclusive_standard()
    {
        // 115 * 1 @ 15% -> Base 100, Tax 15
        $res = $this->calculator->calculateLine(115, 1, 0, 15, true);
        
        $this->assertFloatEquals(15.00, $res['tax_amount']);
        $this->assertFloatEquals(100.00, $res['line_total_excl_tax']);
        $this->assertFloatEquals(115.00, $res['line_total_incl_tax']);
    }

    /** @test */
    public function test_03_exclusive_quantity()
    {
        // 100 * 2 @ 15% -> Base: 200, Tax 30, Total 230
        $res = $this->calculator->calculateLine(100, 2, 0, 15, false);

        $this->assertFloatEquals(30.00, $res['tax_amount']);
        $this->assertFloatEquals(200.00, $res['line_total_excl_tax']);
        $this->assertFloatEquals(230.00, $res['line_total_incl_tax']);
    }

    /** @test */
    public function test_04_exclusive_discount()
    {
        // Price 100, Discount 10, VAT 15%.
        // Net: 90. Tax: 13.5. Total: 103.5.
        $res = $this->calculator->calculateLine(100, 1, 10, 15, false);

        $this->assertFloatEquals(13.50, $res['tax_amount']);
        $this->assertFloatEquals(90.00, $res['line_total_excl_tax']);
        $this->assertFloatEquals(103.50, $res['line_total_incl_tax']);
    }

    /** @test */
    public function test_05_inclusive_discount()
    {
        // Price 115 (Unit), Discount 11.5 (Total Amount Discount).
        // Net Incl: 103.5 
        // Base = 103.5 / 1.15 = 90.
        // Tax = 13.5.
        $res = $this->calculator->calculateLine(115, 1, 11.5, 15, true);

        $this->assertFloatEquals(13.50, $res['tax_amount']);
        $this->assertFloatEquals(90.00, $res['line_total_excl_tax']);
        $this->assertFloatEquals(103.50, $res['line_total_incl_tax']);
    }

    /** @test */
    public function test_06_inlcude_discount_percentage_logic_check()
    {
        // NOTE: The calculator takes 'discount amount', not percentage.
        // Scenario: Price 200 (Incl), 50% discount (100 amount).
        // Net Incl: 100.
        // Base: 100 / 1.15 = 86.956... -> 86.96 roughly
        // Tax: 100 - 86.956... = 13.043... -> 13.04
        
        $res = $this->calculator->calculateLine(200, 1, 100, 15, true);
        
        // Expected Logic:
        // Tax = NetIncl - (NetIncl / 1.15)
        // Tax = 100 - 86.9565 = 13.0434...
        // Round(Tax, 2) = 13.04
        // Final Excl = 100 - 13.04 = 86.96
        
        $this->assertFloatEquals(13.04, $res['tax_amount']);
        $this->assertFloatEquals(86.96, $res['line_total_excl_tax']);
        $this->assertFloatEquals(100.00, $res['line_total_incl_tax']);
    }

    /** @test */
    public function test_07_zero_tax()
    {
        // 100 @ 0% -> Tax 0
        $res = $this->calculator->calculateLine(100, 1, 0, 0, false);
        
        $this->assertFloatEquals(0.00, $res['tax_amount']);
        $this->assertFloatEquals(100.00, $res['line_total_excl_tax']);
        $this->assertFloatEquals(100.00, $res['line_total_incl_tax']);
    }

    /** @test */
    public function test_08_rounding_high_precision()
    {
        // 10.123 * 1.15 = 11.64145
        // Exclusive: 10.123 * 15% = 1.51845 -> Rounds to 1.52.
        // Total = 10.123 + 1.52 = 11.643 -> Rounds to 11.64
        
        $res = $this->calculator->calculateLine(10.123, 1, 0, 15, false);
        
        $this->assertFloatEquals(1.52, $res['tax_amount']);
        $this->assertFloatEquals(10.12, $res['line_total_excl_tax']); // 10.123 rounds to 10.12? Or do we keep precision?
        // Ah, logic says: $finalTotalExcl = round($totalExclTax, 2);
        // $totalExclTax = $netTotal = 10.123
        // round(10.123, 2) = 10.12.
        
        $this->assertFloatEquals(10.12, $res['line_total_excl_tax']);
        $this->assertFloatEquals(11.64, $res['line_total_incl_tax']); // 10.12 + 1.52
    }

    /** @test */
    public function test_09_mixed_categories_document_total()
    {
        // Line A: 100 Excl, 15% -> Tax 15.
        // Line B: 100 Excl, 0% -> Tax 0.
        // Total Tax: 15. Total Excl: 200. Total Incl: 215.
        
        $lineA = $this->calculator->calculateLine(100, 1, 0, 15, false);
        $lineA['tax_category_code'] = 'S';
        
        $lineB = $this->calculator->calculateLine(100, 1, 0, 0, false);
        $lineB['tax_category_code'] = 'Z';
        
        $doc = $this->calculator->calculateDocument([$lineA, $lineB]);
        
        $this->assertFloatEquals(200.00, $doc['total_excl_tax']);
        $this->assertFloatEquals(15.00, $doc['total_tax']);
        $this->assertFloatEquals(215.00, $doc['total_incl_tax']);
        $this->assertFloatEquals(200.00, $doc['total_taxable_amount']); // Both S and Z are taxable supply
        
        // Breakdown check
        $this->assertFloatEquals(15.00, $doc['tax_breakdown']['S']['tax_amount']);
        $this->assertFloatEquals(0.00, $doc['tax_breakdown']['Z']['tax_amount']);
    }

    /** @test */
    public function test_10_document_rounding_aggregation()
    {
        // 3 items, each tax is 0.333333...
        // Line 1: 100 / 3 = 33.3333
        // Tax 15% of 33.3333 = 5.00 (approx)
        
        // Let's use a smaller number: 1.00 Exclusive @ 15%
        // Line 1: 1.00 -> Tax 0.15
        
        // Let's force a rounding difference.
        // Item: 1.01 Exclusive @ 15% = 0.1515 -> Rounds to 0.15
        // If we have 10 of these lines.
        // Line Tax = 0.15 * 10 = 1.50.
        // Document Tax on (10.10) * 15% = 1.515 -> Rounds to 1.52.
        
        // Our logic sums the lines (0.15 * 10 = 1.50).
        // Let's verify this behavior.
        
        $lines = [];
        for ($i=0; $i<10; $i++) {
            $lines[] = $this->calculator->calculateLine(1.01, 1, 0, 15, false);
        }
        
        $doc = $this->calculator->calculateDocument($lines);
        
        // Each line tax: 1.01 * 0.15 = 0.1515 -> round(2) = 0.15
        // Total Tax sum = 1.50.
        $this->assertEquals(1.50, $doc['total_tax']);
        
        // Note: ZATCA allows both approaches (Sum of Lines vs Calc on Total). 
        // Sum of Lines is generally safer for invoice consistency.
    }
}
