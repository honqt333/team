<?php

namespace App\Services\Billing;

use App\Models\Billing\Installment;
use App\Models\Billing\SubscriptionInvoice;
use App\Models\Billing\Subscription;
use App\Services\Invoice\SubscriptionInvoiceService;
use Carbon\Carbon;

class InstallmentService
{
    protected SubscriptionInvoiceService $invoiceService;

    public function __construct(SubscriptionInvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    /**
     * Create installments for a subscription.
     * Only for yearly subscriptions.
     * 
     * @param Subscription $subscription
     * @param int $installmentCount Number of installments (2, 3, 4, 6, 12)
     * @param float $discount Optional discount amount
     */
    public function createInstallments(
        Subscription $subscription,
        int $installmentCount = 4,
        float $discount = 0
    ): SubscriptionInvoice {
        // Validate
        if ($subscription->billing_cycle !== 'yearly') {
            throw new \InvalidArgumentException('الأقساط متاحة فقط للاشتراكات السنوية');
        }
        
        $validCounts = [2, 3, 4, 6, 12];
        if (!in_array($installmentCount, $validCounts)) {
            throw new \InvalidArgumentException('عدد الأقساط يجب أن يكون: ' . implode(', ', $validCounts));
        }
        
        // Calculate amounts
        $totalAmount = $subscription->price - $discount;
        $vatRate = 15.00;
        $vatAmount = round($totalAmount * ($vatRate / 100), 2);
        $grandTotal = $totalAmount + $vatAmount;
        
        $installmentAmount = round($grandTotal / $installmentCount, 2);
        
        // Adjust last installment to handle rounding
        $lastInstallmentAmount = $grandTotal - ($installmentAmount * ($installmentCount - 1));
        
        // Create invoice marked as installment
        $invoice = SubscriptionInvoice::create([
            'subscription_id' => $subscription->id,
            'tenant_id' => $subscription->tenant_id,
            'invoice_number' => SubscriptionInvoice::generateNumber(),
            'subtotal' => $subscription->price,
            'vat_rate' => $vatRate,
            'vat_amount' => $vatAmount,
            'discount' => $discount,
            'total' => $grandTotal,
            'status' => 'pending',
            'due_date' => now()->addDays(7),
            'is_installment' => true,
            'installment_count' => $installmentCount,
        ]);
        
        // Create individual installments
        $startDate = Carbon::now();
        $intervalMonths = 12 / $installmentCount;
        
        for ($i = 1; $i <= $installmentCount; $i++) {
            $dueDate = $startDate->copy()->addMonths(($i - 1) * $intervalMonths);
            $amount = ($i === $installmentCount) ? $lastInstallmentAmount : $installmentAmount;
            
            Installment::create([
                'subscription_invoice_id' => $invoice->id,
                'installment_number' => $i,
                'amount' => $amount,
                'due_date' => $dueDate,
                'status' => $i === 1 ? 'pending' : 'pending', // First one due immediately
            ]);
        }
        
        return $invoice;
    }

    /**
     * Mark installment as paid.
     */
    public function markInstallmentPaid(
        Installment $installment,
        string $gateway = 'manual',
        string $reference = null
    ): Installment {
        $installment->update([
            'status' => 'paid',
            'paid_at' => now(),
            'payment_gateway' => $gateway,
            'payment_reference' => $reference ?? 'INST-' . time(),
        ]);
        
        // Check if all installments are paid
        $this->checkInvoiceCompletion($installment->invoice);
        
        return $installment;
    }

    /**
     * Check if all installments are paid and update invoice.
     */
    protected function checkInvoiceCompletion(SubscriptionInvoice $invoice): void
    {
        $invoice->load('installments');
        
        $allPaid = $invoice->installments->every(fn($i) => $i->status === 'paid');
        
        if ($allPaid) {
            $invoice->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);
            
            // Ensure subscription is active
            if ($invoice->subscription) {
                $invoice->subscription->update(['status' => 'active']);
                $invoice->subscription->tenant->update(['status' => 'active']);
            }
        }
    }

    /**
     * Get next unpaid installment.
     */
    public function getNextDueInstallment(SubscriptionInvoice $invoice): ?Installment
    {
        return $invoice->installments()
            ->where('status', 'pending')
            ->orderBy('installment_number')
            ->first();
    }

    /**
     * Update overdue installments.
     */
    public function updateOverdueInstallments(): int
    {
        return Installment::where('status', 'pending')
            ->where('due_date', '<', now())
            ->update(['status' => 'overdue']);
    }

    /**
     * Get installment summary for invoice.
     */
    public function getInstallmentSummary(SubscriptionInvoice $invoice): array
    {
        $invoice->load('installments');
        
        $installments = $invoice->installments;
        $paidCount = $installments->where('status', 'paid')->count();
        $paidAmount = $installments->where('status', 'paid')->sum('amount');
        $pendingAmount = $installments->whereIn('status', ['pending', 'overdue'])->sum('amount');
        
        return [
            'total_count' => $installments->count(),
            'paid_count' => $paidCount,
            'remaining_count' => $installments->count() - $paidCount,
            'paid_amount' => $paidAmount,
            'pending_amount' => $pendingAmount,
            'progress_percent' => $installments->count() > 0 
                ? round(($paidCount / $installments->count()) * 100) 
                : 0,
            'next_installment' => $this->getNextDueInstallment($invoice),
        ];
    }
}
