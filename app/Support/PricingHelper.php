<?php

declare(strict_types=1);

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
        float|int|string|null $discountValue
    ): float {
        // Eloquent's `decimal:2` cast returns the raw string from the
        // database (to preserve precision). Form requests can also
        // hand us a string. Coerce here so every caller — model
        // boot hook, controller, test — gets the same behavior and
        // we never throw a TypeError on a perfectly valid input.
        $discountValue = self::coerceToFloatOrNull($discountValue);

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
        float|int|string|null $discountValue
    ): float {
        $discountAmount = self::computeDiscountAmount($unitPrice, $discountType, $discountValue);

        return max(0, $unitPrice - $discountAmount);
    }

    /**
     * Compute complete line pricing.
     *
     * @param float $unitPrice The price before discount
     * @param string $discountType 'none', 'percentage', or 'fixed'
     * @param float|null $discountValue The discount value
     * @param int|float $qty Quantity
     * @param float $minPrice Minimum allowed price (0 = no minimum) - for reference only
     * @return array ['discount_amount', 'final_unit_price', 'line_total']
     */
    public static function computeLineTotal(
        float $unitPrice,
        string $discountType,
        float|int|string|null $discountValue,
        int|float $qty,
        float $minPrice = 0
    ): array {
        $discountAmount = self::computeDiscountAmount($unitPrice, $discountType, $discountValue);
        $finalUnitPrice = max(0, $unitPrice - $discountAmount);

        if ($minPrice > 0 && $finalUnitPrice < $minPrice) {
            throw new InvalidArgumentException("Final unit price ({$finalUnitPrice}) cannot be below the minimum price ({$minPrice}).");
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

    /**
     * Coerce a discount value to float|null.
     *
     * Why this exists: PHP's `?float` parameter is strict — a string
     * "10.50" from a request or an Eloquent `decimal:2` cast throws
     * a TypeError. Rather than scatter `(float) ... ?: null` at
     * every caller, we centralize the coercion here. The function is
     * private: callers should not be tempted to use it elsewhere
     * because it would hide the same problem in their own code.
     */
    private static function coerceToFloatOrNull(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            // Reject obviously bad input early so a string like
            // "abc" doesn't silently become 0.0 (which would
            // erase the user's discount by accident).
            if (! is_numeric($value)) {
                throw new InvalidArgumentException(
                    "Discount value must be numeric, got: '{$value}'"
                );
            }
            $value = (float) $value;
        }

        if (is_int($value)) {
            $value = (float) $value;
        }

        if (! is_float($value)) {
            // bool, array, object — none of these are valid.
            throw new InvalidArgumentException(
                'Discount value must be a number, got: '.gettype($value)
            );
        }

        return $value;
    }
}
