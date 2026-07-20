<?php

namespace App\Observers;

use App\Events\Payment\PaymentRecorded;
use App\Models\Payment;

class PaymentObserver
{
    public function created(Payment $payment): void
    {
        event(new PaymentRecorded($payment));
    }
}
