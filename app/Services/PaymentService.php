<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\InvoiceType;
use App\Exceptions\PaymentExceedsBalanceException;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PurchaseInvoice;
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
     * Record a payment against a purchase invoice (e.g. expenses, supplier invoices).
     *
     * @throws PaymentExceedsBalanceException
     */
    public function recordPurchasePayment(PurchaseInvoice $purchaseInvoice, array $data): Payment
    {
        return DB::transaction(function () use ($purchaseInvoice, $data) {
            $locked = $this->lockPurchaseInvoice($purchaseInvoice);

            $amount = round((float) ($data['amount'] ?? 0), 2);

            if ($amount <= 0) {
                throw new InvalidArgumentException('مبلغ الدفعة يجب أن يكون أكبر من صفر.');
            }

            $balance = round((float) $locked->balance, 2);

            if ($amount - self::BALANCE_TOLERANCE > $balance) {
                throw new PaymentExceedsBalanceException(
                    'مبلغ الدفعة ('.number_format($amount, 2, '.', '').') أكبر من رصيد فاتورة المشتريات المستحق ('
                    .number_format($balance, 2, '.', '').').'
                );
            }

            $payment = Payment::create([
                'tenant_id' => $locked->tenant_id,
                'center_id' => $locked->center_id,
                'purchase_invoice_id' => $locked->id,
                'amount' => $amount,
                'payment_date' => $data['payment_date'] ?? now(),
                'payment_method' => $data['payment_method'] ?? 'cash',
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'received_by' => $data['received_by'] ?? auth()->id(),
                'type' => $data['type'] ?? Payment::TYPE_PAYMENT,
            ]);

            $this->recalculatePurchaseInvoice($locked);

            return $payment;
        });
    }

    /**
     * Recalculate purchase invoice balance and status based on payments and returns.
     */
    public function recalculatePurchaseInvoice(PurchaseInvoice $purchaseInvoice): void
    {
        $total = round((float) ($purchaseInvoice->total ?? 0), 2);

        $totalReturns = round((float) $purchaseInvoice->returnInvoices()
            ->whereNull('deleted_at')
            ->sum('total'), 2);

        $totalPaid = round((float) $purchaseInvoice->payments()
            ->whereNull('deleted_at')
            ->where('type', Payment::TYPE_PAYMENT)
            ->sum('amount'), 2);

        $totalRefunds = round((float) $purchaseInvoice->payments()
            ->whereNull('deleted_at')
            ->where('type', Payment::TYPE_REFUND)
            ->sum('amount'), 2);

        $netPaid = $totalPaid - $totalRefunds;
        $newBalance = max(0.0, round($total - $totalReturns - $netPaid, 2));

        $status = $purchaseInvoice->status;

        if (! in_array($status, [PurchaseInvoice::STATUS_CANCELLED, PurchaseInvoice::STATUS_DRAFT], true)) {
            $status = $newBalance <= self::BALANCE_TOLERANCE
                ? PurchaseInvoice::STATUS_PAID
                : PurchaseInvoice::STATUS_OPEN;
        }

        $purchaseInvoice->update([
            'balance' => $newBalance,
            'status' => $status,
        ]);
    }

    /**
     * Delete a payment and recalculate invoice status.
     */
    public function deletePayment(Payment $payment): bool
    {
        return DB::transaction(function () use ($payment) {
            $invoice = $payment->invoice;
            $purchaseInvoice = $payment->purchaseInvoice;

            if ($invoice) {
                // Same reason as recordPayment(): the recalculation has to
                // happen against a locked row, or it can interleave with a
                // payment that is being recorded right now.
                $locked = $this->lockInvoice($invoice);

                $payment->delete();

                // Recalculate invoice status
                $locked->updatePaymentStatus();

                return true;
            }

            if ($purchaseInvoice) {
                $lockedPurchase = $this->lockPurchaseInvoice($purchaseInvoice);

                $payment->delete();

                // Recalculate purchase invoice status & balance
                $this->recalculatePurchaseInvoice($lockedPurchase);

                return true;
            }

            $payment->delete();

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

            $isCreditNote = $locked->type === InvoiceType::CREDIT_NOTE
                || ($locked->type instanceof InvoiceType ? $locked->type->value : (string) $locked->type) === 'credit_note';

            return $this->createPayment($locked, [
                'amount' => $balance,
                'payment_method' => $method,
                'reference' => $reference,
                'type' => $isCreditNote ? 'refund' : 'payment',
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

    private function lockPurchaseInvoice(PurchaseInvoice $purchaseInvoice): PurchaseInvoice
    {
        return PurchaseInvoice::query()
            ->lockForUpdate()
            ->findOrFail($purchaseInvoice->id);
    }

    /**
     * Create the payment row and refresh the invoice totals.
     */
    private function createPayment(Invoice $invoice, array $data): Payment
    {
        $isCreditNote = $invoice->type === InvoiceType::CREDIT_NOTE
            || ($invoice->type instanceof InvoiceType ? $invoice->type->value : (string) $invoice->type) === 'credit_note';

        $type = $data['type'] ?? ($isCreditNote ? 'refund' : 'payment');

        if ($isCreditNote && $type === 'payment') {
            $type = 'refund';
        }

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
            'type' => $type,
        ]);

        // Update invoice payment status
        $invoice->updatePaymentStatus();

        return $payment;
    }
}
