<?php

namespace App\Listeners\Payment;

use App\Events\Payment\PaymentRecorded;
use App\Models\Payment;
use App\Models\WorkOrder;
use App\Services\InvoiceService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

/**
 * Auto-create invoice when a payment brings a closed (done) WorkOrder
 * to a fully-paid state and no invoice exists yet.
 *
 * Replaces the inline side-effect previously hardcoded in Payment::boot().
 * Uses a global app-instance lock to prevent re-entrancy when
 * InvoiceService->createFromWorkOrder() itself triggers other events.
 */
class HandleAutoInvoiceOnDoneWorkOrder
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly NotificationService $notifications,
    ) {}

    public function handle(PaymentRecorded $event): void
    {
        $payment = $event->payment;

        // Only proceed for non-refund payments.
        if ($payment->type === Payment::TYPE_REFUND) {
            return;
        }

        $workOrder = $payment->workOrder;
        if (! $workOrder) {
            return;
        }

        // Already invoiced — nothing to do.
        if ($workOrder->invoice) {
            return;
        }

        // WO must be in "done" state to auto-create.
        if ($workOrder->status !== WorkOrder::STATUS_DONE) {
            return;
        }

        // Use a 1-cent tolerance so floating-point rounding doesn't block
        // an invoice that is, in practice, fully paid.
        if ((float) $workOrder->balance > 0.01) {
            return;
        }

        // Re-entrancy guard: InvoiceService may itself trigger events that
        // come back through this listener.
        if (app()->bound('payment.auto-invoice-in-progress')) {
            return;
        }
        app()->instance('payment.auto-invoice-in-progress', true);

        try {
            $invoice = $this->invoiceService->createFromWorkOrder($workOrder, $payment->received_by);
            $this->invoiceService->issueInvoice($invoice);

            // Re-link this payment + any other payments on the WO to the
            // newly created invoice so totals stay in sync.
            $workOrder->payments()
                ->whereNull('invoice_id')
                ->update(['invoice_id' => $invoice->id]);
            $invoice->updatePaymentStatus();

            $this->notifications->notifyOwner(
                tenantId: $workOrder->tenant_id,
                type: 'invoice.created',
                title: 'فاتورة جديدة #'.$invoice->invoice_number,
                body: 'تم إنشاء فاتورة تلقائياً من أمر العمل #'.($workOrder->code ?? $workOrder->id).' بعد اكتمال الدفع',
                actionUrl: '/app/invoices/'.$invoice->id,
                actorId: $payment->received_by,
            );
        } catch (\Throwable $e) {
            Log::warning('Auto-create invoice on done WO failed', [
                'work_order_id' => $workOrder->id,
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
        } finally {
            app()->forgetInstance('payment.auto-invoice-in-progress');
        }
    }
}
