<?php

declare(strict_types=1);

namespace App\Enums;

enum InvoiceType: string
{
    case INVOICE = 'invoice';
    case CREDIT_NOTE = 'credit_note';
    case DEBIT_NOTE = 'debit_note';

    public function label(): string
    {
        return match ($this) {
            self::INVOICE => __('invoices.types.invoice'),
            self::CREDIT_NOTE => __('invoices.types.credit_note'),
            self::DEBIT_NOTE => __('invoices.types.debit_note'),
        };
    }
}
