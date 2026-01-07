<?php

namespace App\Services\Invoice;

use App\Models\Billing\SubscriptionInvoice;
use App\Models\Tenant;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class SubscriptionInvoiceService
{
    /**
     * Create invoice for subscription.
     */
    public function createForSubscription(
        \App\Models\Billing\Subscription $subscription,
        ?float $discount = 0,
        ?string $promoCode = null
    ): SubscriptionInvoice {
        $tenant = $subscription->tenant;
        $plan = $subscription->plan;
        
        // Calculate amounts
        $subtotal = $subscription->price;
        $vatRate = 15.00; // Saudi VAT
        $discountAmount = $discount;
        $taxableAmount = $subtotal - $discountAmount;
        $vatAmount = round($taxableAmount * ($vatRate / 100), 2);
        $total = $taxableAmount + $vatAmount;
        
        // Create invoice
        $invoice = SubscriptionInvoice::create([
            'subscription_id' => $subscription->id,
            'tenant_id' => $tenant->id,
            'invoice_number' => SubscriptionInvoice::generateNumber(),
            'subtotal' => $subtotal,
            'vat_rate' => $vatRate,
            'vat_amount' => $vatAmount,
            'discount' => $discountAmount,
            'total' => $total,
            'status' => 'pending',
            'due_date' => now()->addDays(7),
            'is_installment' => false,
            'installment_count' => 1,
        ]);
        
        return $invoice;
    }

    /**
     * Mark invoice as paid.
     */
    public function markAsPaid(
        SubscriptionInvoice $invoice,
        string $gateway,
        string $reference,
        array $details = []
    ): SubscriptionInvoice {
        $invoice->update([
            'status' => 'paid',
            'paid_at' => now(),
            'payment_gateway' => $gateway,
            'payment_reference' => $reference,
            'payment_details' => $details,
        ]);
        
        return $invoice;
    }

    /**
     * Generate PDF for invoice.
     */
    public function generatePdf(SubscriptionInvoice $invoice): string
    {
        $invoice->load(['subscription.plan', 'tenant']);
        
        $pdf = Pdf::loadView('pdf.subscription-invoice', [
            'invoice' => $invoice,
            'tenant' => $invoice->tenant,
            'subscription' => $invoice->subscription,
            'plan' => $invoice->subscription->plan,
        ]);
        
        // Set paper to A4
        $pdf->setPaper('a4');
        
        // Store PDF
        $filename = "invoices/subscription/{$invoice->invoice_number}.pdf";
        Storage::disk('public')->put($filename, $pdf->output());
        
        return $filename;
    }

    /**
     * Get PDF path for invoice.
     */
    public function getPdfPath(SubscriptionInvoice $invoice): ?string
    {
        $filename = "invoices/subscription/{$invoice->invoice_number}.pdf";
        
        if (Storage::disk('public')->exists($filename)) {
            return Storage::disk('public')->path($filename);
        }
        
        // Generate if not exists
        $this->generatePdf($invoice);
        return Storage::disk('public')->path($filename);
    }

    /**
     * Get PDF URL for invoice.
     */
    public function getPdfUrl(SubscriptionInvoice $invoice): string
    {
        $filename = "invoices/subscription/{$invoice->invoice_number}.pdf";
        
        if (!Storage::disk('public')->exists($filename)) {
            $this->generatePdf($invoice);
        }
        
        return Storage::disk('public')->url($filename);
    }
}
