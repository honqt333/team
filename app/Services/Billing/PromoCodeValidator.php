<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Models\Billing\PromoCode;
use App\Models\Billing\PromoCodeUsage;

/**
 * The one place that decides whether a promo code may be used.
 *
 * The rules used to be written four times — in the price calculation,
 * in the validation endpoint, in the subscribe flow and in the bank
 * transfer flow — and they did not agree. The price calculation and
 * the two payment flows only looked at `is_active`, so an expired code
 * still produced a discount, while the validation endpoint also
 * checked the dates. A code the form accepted could therefore be
 * refused at the moment of payment, and a code the form refused could
 * still discount the price that was shown.
 *
 * The registration form had a fifth copy of the problem: it accepted
 * any string, ignored it entirely, and left the customer believing a
 * discount had been applied.
 */
class PromoCodeValidator
{
    public const REASON_NOT_FOUND = 'not_found';

    public const REASON_INACTIVE = 'inactive';

    public const REASON_NOT_STARTED = 'not_started';

    public const REASON_EXPIRED = 'expired';

    public const REASON_EXHAUSTED = 'exhausted';

    public const REASON_TENANT_EXHAUSTED = 'tenant_exhausted';

    /**
     * The translation key that explains a refusal to the customer.
     *
     * @var array<string, string>
     */
    private const MESSAGES = [
        self::REASON_NOT_FOUND => 'company_profile.subscription.promo_invalid',
        self::REASON_INACTIVE => 'company_profile.subscription.promo_inactive',
        self::REASON_NOT_STARTED => 'company_profile.subscription.promo_not_started',
        self::REASON_EXPIRED => 'company_profile.subscription.promo_expired',
        self::REASON_EXHAUSTED => 'company_profile.subscription.promo_exhausted',
        self::REASON_TENANT_EXHAUSTED => 'company_profile.subscription.promo_exhausted',
    ];

    /**
     * Check a code, optionally for a tenant that already used it.
     */
    public function check(string $code, ?int $tenantId = null): PromoCodeCheck
    {
        $code = trim($code);

        if ($code === '') {
            return PromoCodeCheck::invalid(self::REASON_NOT_FOUND);
        }

        $promo = PromoCode::where('code', $code)->first();

        if (! $promo) {
            return PromoCodeCheck::invalid(self::REASON_NOT_FOUND);
        }

        if (! $promo->is_active) {
            return PromoCodeCheck::invalid(self::REASON_INACTIVE);
        }

        if ($promo->starts_at && $promo->starts_at->isFuture()) {
            return PromoCodeCheck::invalid(self::REASON_NOT_STARTED);
        }

        if ($promo->expires_at && $promo->expires_at->isPast()) {
            return PromoCodeCheck::invalid(self::REASON_EXPIRED);
        }

        if ($promo->max_uses !== null && $promo->times_used >= $promo->max_uses) {
            return PromoCodeCheck::invalid(self::REASON_EXHAUSTED);
        }

        if ($tenantId !== null) {
            $used = $promo->usages()->where('tenant_id', $tenantId)->count();

            if ($used >= ($promo->max_uses_per_tenant ?? 999)) {
                return PromoCodeCheck::invalid(self::REASON_TENANT_EXHAUSTED);
            }
        }

        return PromoCodeCheck::valid($promo);
    }

    /**
     * The money a code takes off a price.
     *
     * A code that gives trial DAYS does not touch a price — the days
     * are a benefit on a free trial, not an amount in riyals. The old
     * code treated every non-percentage type as a fixed amount, so a
     * "7 trial days" code was subtracted as 7 riyals.
     */
    public function discountFor(PromoCodeCheck $check, float $basePrice): float
    {
        if (! $check->isValid()) {
            return 0.0;
        }

        $promo = $check->promo;

        $discount = match ($promo->discount_type) {
            'percentage' => $basePrice * ((float) $promo->discount_value / 100),
            'fixed' => (float) $promo->discount_value,
            default => 0.0,
        };

        return min($discount, $basePrice);
    }

    /**
     * The message that explains a refusal to the customer.
     */
    public function messageFor(PromoCodeCheck $check): string
    {
        $key = self::MESSAGES[$check->reason ?? self::REASON_NOT_FOUND]
            ?? self::MESSAGES[self::REASON_NOT_FOUND];

        return __($key);
    }

    /**
     * Record that a tenant used a code.
     *
     * The row is what makes `max_uses` and `max_uses_per_tenant` mean
     * anything: without it the counter of uses stays at zero and no
     * code ever reaches its limit.
     */
    public function recordUsage(
        PromoCode $promo,
        int $tenantId,
        ?int $subscriptionId = null,
        float $discountAmount = 0.0
    ): PromoCodeUsage {
        /** @var PromoCodeUsage $usage */
        $usage = $promo->usages()->create([
            'tenant_id' => $tenantId,
            'subscription_id' => $subscriptionId,
            'discount_amount' => $discountAmount,
            'used_at' => now(),
        ]);

        $promo->incrementUsage();

        return $usage;
    }
}
