<?php

declare(strict_types=1);

namespace App\Services\Invoice;

use App\Models\Billing\Subscription;
use App\Models\Billing\SubscriptionInvoice;
use App\Models\PaymentSettings;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class SubscriptionInvoiceService
{
    /**
     * Create invoice for subscription.
     */
    public function createForSubscription(
        Subscription $subscription,
        ?float $discount = 0,
        ?string $promoCode = null
    ): SubscriptionInvoice {
        $tenant = $subscription->tenant;
        $plan = $subscription->plan;

        // Calculate amounts using admin-configurable VAT from PaymentSettings
        // (falls back to 0 if no settings row exists yet).
        $paymentSettings = PaymentSettings::first();
        $discountAmount = (float) $discount;
        $subtotal = (float) $subscription->price - $discountAmount;
        $vatCalc = $paymentSettings?->calculateVat($subtotal)
            ?? ['exclusive' => $subtotal, 'vat' => 0, 'total' => $subtotal];
        $vatAmount = $vatCalc['vat'];
        $total = $vatCalc['total'];

        // Create invoice
        $invoice = SubscriptionInvoice::create([
            'subscription_id' => $subscription->id,
            'tenant_id' => $tenant->id,
            'invoice_number' => SubscriptionInvoice::generateNumber(),
            'subtotal' => $subtotal,
            // 2026_07_25_140000 migration collapsed vat_rate + vat_amount
            // into a single `vat` column. Persist the amount; the rate is
            // implicit in (vat / subtotal) and not stored separately.
            'vat' => $vatAmount,
            'discount' => $discountAmount,
            'total' => $total,
            'status' => 'pending',
            'issued_at' => now(),
            'due_at' => now()->addDays(7),
        ]);

        return $invoice;
    }

    /**
     * Mark invoice as paid.
     */
    public function markAsPaid(
        SubscriptionInvoice $invoice,
        string $gateway,
        ?string $reference = null,
        array $details = []
    ): SubscriptionInvoice {
        // 2026_07_25_140000 migration dropped the payment_gateway /
        // payment_reference / payment_details columns from
        // subscription_invoices. The payment reference now lives on the
        // SubscriptionPayment record (gateway_ref column). We append the
        // admin-supplied reference to the invoice's free-form notes
        // instead so it remains auditable.
        $notes = $invoice->notes ? $invoice->notes."\n" : '';
        $notes .= '[Marked paid] gateway='.$gateway;

        if ($reference) {
            $notes .= ', reference='.$reference;
        }

        if (! empty($details['notes'])) {
            $notes .= ', '.$details['notes'];
        }
        $notes .= ' at '.now()->toIso8601String();

        $invoice->update([
            'status' => 'paid',
            'paid_at' => now(),
            'notes' => $notes,
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

        if (! Storage::disk('public')->exists($filename)) {
            $this->generatePdf($invoice);
        }

        return Storage::disk('public')->url($filename);
    }
}
