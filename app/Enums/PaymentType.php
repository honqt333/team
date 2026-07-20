<?php

namespace App\Enums;

enum PaymentType: string
{
    case PAYMENT = 'payment';
    case REFUND = 'refund';
    case BAD_DEBT = 'bad_debt';

    public function label(): string
    {
        return match ($this) {
            self::PAYMENT => __('payments.types.payment'),
            self::REFUND => __('payments.types.refund'),
            self::BAD_DEBT => __('payments.types.bad_debt'),
        };
    }
}
