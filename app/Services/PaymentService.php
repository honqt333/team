<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    /**
     * Record a payment against an invoice
     */
    public function recordPayment(Invoice $invoice, array $data): Payment
    {
        return DB::transaction(function () use ($invoice, $data) {
            $payment = Payment::create([
                'tenant_id' => $invoice->tenant_id,
                'center_id' => $invoice->center_id,
                'invoice_id' => $invoice->id,
                'amount' => $data['amount'],
                'payment_date' => $data['payment_date'] ?? now(),
                'payment_method' => $data['payment_method'] ?? 'cash',
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'received_by' => $data['received_by'] ?? auth()->id(),
            ]);

            // Update invoice payment status
            $invoice->updatePaymentStatus();

            return $payment;
        });
    }

    /**
     * Delete a payment and recalculate invoice status
     */
    public function deletePayment(Payment $payment): bool
    {
        return DB::transaction(function () use ($payment) {
            $invoice = $payment->invoice;
            
            $payment->delete();
            
            // Recalculate invoice status
            $invoice->updatePaymentStatus();

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
     */
    public function recordFullPayment(Invoice $invoice, string $method = 'cash', ?string $reference = null): Payment
    {
        return $this->recordPayment($invoice, [
            'amount' => $invoice->balance,
            'payment_method' => $method,
            'reference' => $reference,
        ]);
    }
}
