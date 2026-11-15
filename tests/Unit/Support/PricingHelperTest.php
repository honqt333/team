<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\PricingHelper;
use PHPUnit\Framework\TestCase;

class PricingHelperTest extends TestCase
{
    public function test_no_discount_returns_zero(): void
    {
        $this->assertEquals(0, PricingHelper::computeDiscountAmount(100, 'none', null));
        $this->assertEquals(0, PricingHelper::computeDiscountAmount(100, 'none', 0));
        $this->assertEquals(0, PricingHelper::computeDiscountAmount(100, 'none', -5));
    }

    public function test_percentage_discount(): void
    {
        // 10% of 100 = 10
        $this->assertEquals(10, PricingHelper::computeDiscountAmount(100, 'percentage', 10));

        // 50% of 200 = 100
        $this->assertEquals(100, PricingHelper::computeDiscountAmount(200, 'percentage', 50));

        // Cap at 100%
        $this->assertEquals(100, PricingHelper::computeDiscountAmount(100, 'percentage', 150));
    }

    public function test_fixed_discount_capped_at_price(): void
    {
        $this->assertEquals(30, PricingHelper::computeDiscountAmount(100, 'fixed', 30));
        $this->assertEquals(50, PricingHelper::computeDiscountAmount(50, 'fixed', 100)); // Capped
    }

    public function test_final_unit_price_never_negative(): void
    {
        $this->assertEquals(0, PricingHelper::computeFinalUnitPrice(100, 'fixed', 150));
    }

    public function test_line_total_with_quantity(): void
    {
        $result = PricingHelper::computeLineTotal(100, 'percentage', 10, 3, 0);
        $this->assertEquals(30, $result['discount_amount']);
        $this->assertEquals(90, $result['final_unit_price']);
        $this->assertEquals(270, $result['line_total']);
    }

    public function test_line_total_discount_scales_with_quantity_for_fixed_and_percentage(): void
    {
        foreach ([1, 2, 5] as $qty) {
            $fixed = PricingHelper::computeLineTotal(100, 'fixed', 15, $qty, 0);
            $this->assertEquals(15 * $qty, $fixed['discount_amount']);
            $this->assertEquals(85, $fixed['final_unit_price']);
            $this->assertEquals(85 * $qty, $fixed['line_total']);

            $pct = PricingHelper::computeLineTotal(200, 'percentage', 10, $qty, 0);
            $this->assertEquals(20 * $qty, $pct['discount_amount']);
            $this->assertEquals(180, $pct['final_unit_price']);
            $this->assertEquals(180 * $qty, $pct['line_total']);
        }
    }

    public function test_rounding_to_2_decimals(): void
    {
        $result = PricingHelper::computeLineTotal(99.99, 'percentage', 33.33, 2, 0);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('line_total', $result);
    }
}
