<?php

declare(strict_types=1);

namespace App\Observers;

use App\Events\Payment\PaymentRecorded;
use App\Models\Payment;

class PaymentObserver
{
    public function created(Payment $payment): void
    {
        event(new PaymentRecorded($payment));
    }

    public function updated(Payment $payment): void
    {
        // Re-dispatch on update so listeners can re-evaluate invoice status
        // (e.g. when a refund is recorded or amount is corrected).
        event(new PaymentRecorded($payment));
    }

    public function deleted(Payment $payment): void
    {
        // Re-dispatch on delete so listeners can re-evaluate invoice status.
        event(new PaymentRecorded($payment));
    }
}
