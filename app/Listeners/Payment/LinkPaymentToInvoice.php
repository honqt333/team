<?php

declare(strict_types=1);

namespace App\Listeners\Payment;

use App\Events\Payment\PaymentRecorded;

/**
 * Link a newly created payment to its work order's invoice (if any).
 *
 * Triggered by PaymentObserver when a payment is first created.
 * Replaces the inline side-effect previously hardcoded in Payment::boot().
 */
class LinkPaymentToInvoice
{
    public function handle(PaymentRecorded $event): void
    {
        $payment = $event->payment;

        // Only link if:
        // 1. Payment belongs to a work order
        // 2. Payment doesn't already have an invoice_id
        if ($payment->work_order_id && ! $payment->invoice_id) {
            $invoice = $payment->workOrder?->invoice;

            if ($invoice) {
                $payment->invoice_id = $invoice->id;
                $payment->saveQuietly();
            }
        }
    }
}
