<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Models\Billing\PromoCode;

/**
 * The outcome of checking a promo code.
 *
 * Either the code, or the reason it cannot be used.
 */
final class PromoCodeCheck
{
    private function __construct(
        public readonly ?PromoCode $promo,
        public readonly ?string $reason = null,
    ) {}

    public static function valid(PromoCode $promo): self
    {
        return new self($promo);
    }

    public static function invalid(string $reason): self
    {
        return new self(null, $reason);
    }

    public function isValid(): bool
    {
        return $this->promo !== null;
    }
}
