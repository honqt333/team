<?php

declare(strict_types=1);

namespace App\Listeners\Payment;

use App\Events\Payment\PaymentRecorded;

class UpdateInvoiceStatusOnPayment
{
    public function handle(PaymentRecorded $event): void
    {
        if ($event->payment->invoice && method_exists($event->payment->invoice, 'updatePaymentStatus')) {
            $event->payment->invoice->updatePaymentStatus();
        }
    }
}
