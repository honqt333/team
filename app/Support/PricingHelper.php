<?php

namespace App\Support;

use InvalidArgumentException;

class PricingHelper
{
    /**
     * Compute discount amount based on type and value.
     *
     * @param float $unitPrice The price before discount
     * @param string $discountType 'none', 'percentage', or 'fixed'
     * @param float|null $discountValue The discount value
     * @return float The discount amount
     */
    public static function computeDiscountAmount(
        float $unitPrice,
        string $discountType,
        ?float $discountValue
    ): float {
        if ($discountType === 'none' || $discountValue === null || $discountValue <= 0) {
            return 0;
        }

        return match ($discountType) {
            'percentage' => ($unitPrice * min($discountValue, 100)) / 100,
            'fixed' => min($discountValue, $unitPrice),
            default => 0,
        };
    }

    /**
     * Compute final unit price after discount.
     *
     * @param float $unitPrice The price before discount
     * @param string $discountType 'none', 'percentage', or 'fixed'
     * @param float|null $discountValue The discount value
     * @return float The final unit price
     */
    public static function computeFinalUnitPrice(
        float $unitPrice,
        string $discountType,
        ?float $discountValue
    ): float {
        $discountAmount = self::computeDiscountAmount($unitPrice, $discountType, $discountValue);
        return max(0, $unitPrice - $discountAmount);
    }

    /**
     * Compute complete line pricing with validation.
     *
     * @param float $unitPrice The price before discount
     * @param string $discountType 'none', 'percentage', or 'fixed'
     * @param float|null $discountValue The discount value
     * @param int|float $qty Quantity
     * @param float $minPrice Minimum allowed price (0 = no minimum)
     * @return array ['discount_amount', 'final_unit_price', 'line_total']
     * @throws InvalidArgumentException If final price is below minimum
     */
    public static function computeLineTotal(
        float $unitPrice,
        string $discountType,
        ?float $discountValue,
        int|float $qty,
        float $minPrice = 0
    ): array {
        $discountAmount = self::computeDiscountAmount($unitPrice, $discountType, $discountValue);
        $finalUnitPrice = max(0, $unitPrice - $discountAmount);

        // Validate minimum price
        if ($minPrice > 0 && $finalUnitPrice < $minPrice) {
            throw new InvalidArgumentException(
                __('pricing.final_price_below_minimum', [
                    'final' => number_format($finalUnitPrice, 2),
                    'min' => number_format($minPrice, 2),
                ])
            );
        }

        $lineTotal = $finalUnitPrice * $qty;

        return [
            'discount_amount' => round($discountAmount, 2),
            'final_unit_price' => round($finalUnitPrice, 2),
            'line_total' => round($lineTotal, 2),
        ];
    }

    /**
     * Apply default discount from service to line if configured.
     *
     * @param array $service Service data with discount config
     * @return array ['discount_type', 'discount_value']
     */
    public static function getDefaultDiscount(array $service): array
    {
        $type = $service['default_discount_type'] ?? 'none';
        $value = $service['default_discount_value'] ?? null;

        if ($type === 'none') {
            return [
                'discount_type' => 'none',
                'discount_value' => null,
            ];
        }

        return [
            'discount_type' => $type,
            'discount_value' => $value,
        ];
    }

    /**
     * Validate that a discount configuration is valid.
     *
     * @param string $type
     * @param float|null $value
     * @return bool
     */
    public static function isValidDiscount(string $type, ?float $value): bool
    {
        if ($type === 'none') {
            return true;
        }

        if ($value === null || $value < 0) {
            return false;
        }

        if ($type === 'percentage' && $value > 100) {
            return false;
        }

        return true;
    }
}
