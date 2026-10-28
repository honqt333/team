<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\PaymentExceedsBalanceException;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PaymentService
{
    /**
     * Cents of tolerance when comparing a payment with the balance.
     *
     * Invoices carry VAT that is aggregated from line items, so a
     * balance can be a fraction of a riyal away from the amount that
     * is due. Anything above this is a real over-payment.
     */
    private const BALANCE_TOLERANCE = 0.01;

    /**
     * Record a payment against an invoice.
     *
     * The invoice row is locked and its balance re-read INSIDE the
     * transaction. The controller checks the amount against the balance
     * it loaded earlier, but that value is stale the moment another
     * collector touches the same invoice — two collectors could
     * otherwise both see the same outstanding balance and both be
     * accepted.
     *
     * Note: `lockForUpdate()` is a no-op on SQLite (it has no row
     * locks); it takes effect on MySQL/InnoDB, which is what the
     * production database uses.
     *
     * If the invoice is linked to a WorkOrder, the payment is also linked
     * back to the WorkOrder so both views (WO + Invoice) show the same
     * running total. Payment totals on the WorkOrder side are derived
     * dynamically (see WorkOrder::getTotalPaidAttribute), so no extra
     * aggregate write is needed — just the foreign keys must be in sync.
     *
     * @throws PaymentExceedsBalanceException
     */
    public function recordPayment(Invoice $invoice, array $data): Payment
    {
        return DB::transaction(function () use ($invoice, $data) {
            $locked = $this->lockInvoice($invoice);

            $amount = round((float) ($data['amount'] ?? 0), 2);

            if ($amount <= 0) {
                throw new InvalidArgumentException('مبلغ الدفعة يجب أن يكون أكبر من صفر.');
            }

            $balance = round((float) $locked->balance, 2);

            if ($amount - self::BALANCE_TOLERANCE > $balance) {
                throw new PaymentExceedsBalanceException(
                    'مبلغ الدفعة ('.number_format($amount, 2, '.', '').') أكبر من رصيد الفاتورة المستحق ('
                    .number_format($balance, 2, '.', '').').'
                );
            }

            return $this->createPayment($locked, array_merge($data, ['amount' => $amount]));
        });
    }

    /**
     * Delete a payment and recalculate invoice status.
     */
    public function deletePayment(Payment $payment): bool
    {
        return DB::transaction(function () use ($payment) {
            $invoice = $payment->invoice;

            if (! $invoice) {
                $payment->delete();

                return true;
            }

            // Same reason as recordPayment(): the recalculation has to
            // happen against a locked row, or it can interleave with a
            // payment that is being recorded right now.
            $locked = $this->lockInvoice($invoice);

            $payment->delete();

            // Recalculate invoice status
            $locked->updatePaymentStatus();

            return true;
        });
    }

    /**
     * Get payments summary for an invoice
     */
    public function getPaymentsSummary(Invoice $invoice): array
    {
        $payments = $invoice->payments()->with('receivedBy')->orderBy('payment_date', 'desc')->get();

        return [
            'payments' => $payments,
            'total_paid' => $invoice->total_paid,
            'balance' => $invoice->balance,
            'payment_status' => $invoice->payment_status,
            'is_paid' => $invoice->is_paid,
        ];
    }

    /**
     * Record full payment (convenience method)
     *
     * The amount is the balance of the LOCKED invoice, not the one the
     * caller happened to hold — otherwise two "pay full" requests could
     * both record the same outstanding balance.
     *
     * @throws PaymentExceedsBalanceException
     */
    public function recordFullPayment(Invoice $invoice, string $method = 'cash', ?string $reference = null): Payment
    {
        return DB::transaction(function () use ($invoice, $method, $reference) {
            $locked = $this->lockInvoice($invoice);

            $balance = round((float) $locked->balance, 2);

            if ($balance <= self::BALANCE_TOLERANCE) {
                throw new PaymentExceedsBalanceException('لا يوجد رصيد مستحق على هذه الفاتورة.');
            }

            return $this->createPayment($locked, [
                'amount' => $balance,
                'payment_method' => $method,
                'reference' => $reference,
            ]);
        });
    }

    /**
     * Load the invoice again and hold a row lock on it.
     *
     * The global tenant/center scopes still apply, so this can only
     * ever lock the very invoice the caller was authorized for.
     */
    private function lockInvoice(Invoice $invoice): Invoice
    {
        return Invoice::query()
            ->lockForUpdate()
            ->findOrFail($invoice->id);
    }

    /**
     * Create the payment row and refresh the invoice totals.
     */
    private function createPayment(Invoice $invoice, array $data): Payment
    {
        $payment = Payment::create([
            'tenant_id' => $invoice->tenant_id,
            'center_id' => $invoice->center_id,
            'invoice_id' => $invoice->id,
            // Mirror the WO link so the payment shows up on both sides.
            'work_order_id' => $invoice->work_order_id,
            'amount' => $data['amount'],
            'payment_date' => $data['payment_date'] ?? now(),
            'payment_method' => $data['payment_method'] ?? 'cash',
            'reference' => $data['reference'] ?? null,
            'notes' => $data['notes'] ?? null,
            'received_by' => $data['received_by'] ?? auth()->id(),
            'type' => $data['type'] ?? 'payment',
        ]);

        // Update invoice payment status
        $invoice->updatePaymentStatus();

        return $payment;
    }
}
