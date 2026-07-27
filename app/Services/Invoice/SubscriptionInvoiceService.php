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

        // Pull company info from PaymentSettings so the PDF header
        // and footer reflect the admin's edits at
        // /system/settings/company (not a hardcoded "Khidma Pro"
        // string in the view). Falls back to sensible defaults if
        // the settings row is empty.
        $paymentSettings = PaymentSettings::first();
        $companyInfo = $this->buildCompanyInfo($paymentSettings);

        // The 2026_07_25_140000 migration collapsed vat_rate +
        // vat_amount into a single `vat` column. We compute the
        // rate here from the actual numbers (or pull the admin-
        // configured rate from settings) so the PDF can render
        // "ضريبة القيمة المضافة (15%)" with the live value.
        $vatRate = (float) ($paymentSettings?->subscription_vat_rate ?? 15);

        // Build the PDF. Set isRemoteEnabled=true BEFORE
        // loadView so the <img> tags in the view are fetched and
        // embedded. The default config has this off (sensible
        // for arbitrary HTML), so we toggle it explicitly here.
        $pdf = app('dompdf.wrapper');
        $pdf->getDomPDF()->getOptions()->set('isRemoteEnabled', true);

        $pdf->loadView('pdf.subscription-invoice', [
            'invoice' => $invoice,
            'tenant' => $invoice->tenant,
            'subscription' => $invoice->subscription,
            'plan' => $invoice->subscription->plan,
            'company' => $companyInfo,
            'vat_rate' => $vatRate,
        ]);

        // Set paper to A4
        $pdf->setPaper('a4');

        // Store PDF
        $filename = "invoices/subscription/{$invoice->invoice_number}.pdf";
        Storage::disk('public')->put($filename, $pdf->output());

        return $filename;
    }

    /**
     * Build the company info array passed to the PDF view.
     * Falls back to a sensible default name if the admin hasn't
     * configured company info yet (better to show "Khidma Pro"
     * than a blank header).
     */
    private function buildCompanyInfo(?PaymentSettings $settings): array
    {
        $name = $settings?->company_legal_name
            ?: ($settings?->company_legal_name_en ?: 'Khidma Pro');
        $nameEn = $settings?->company_legal_name_en
            ?: ($settings?->company_legal_name ?: 'Khidma Pro');

        // Convert logo + stamp paths to data: URLs (base64-encoded).
        // DomPDF in a CLI / headless context can't reliably fetch
        // http://localhost/storage/... because the dev server may
        // not be running, and even when it is, the headless
        // request is fire-and-forget. Embedding the image as a
        // data: URL makes the PDF fully self-contained — admins
        // can email it or download it without any server running.
        $publicDisk = Storage::disk('public');

        $logoDataUrl = null;

        if ($settings?->company_logo_path && $publicDisk->exists($settings->company_logo_path)) {
            $logoDataUrl = $this->fileToDataUrl(
                $publicDisk->path($settings->company_logo_path),
            );
        }

        $stampDataUrl = null;

        if ($settings?->company_stamp_path && $publicDisk->exists($settings->company_stamp_path)) {
            $stampDataUrl = $this->fileToDataUrl(
                $publicDisk->path($settings->company_stamp_path),
            );
        }

        return [
            'name' => $name,
            'name_en' => $nameEn,
            'vat_number' => $settings?->company_vat_number,
            'cr_number' => $settings?->company_cr_number,
            'phone' => $settings?->company_phone,
            'email' => $settings?->company_email,
            'website' => $settings?->company_website,
            'logo_url' => $logoDataUrl,
            'stamp_url' => $stampDataUrl,
        ];
    }

    /**
     * Convert a local file to a data: URL with the correct
     * MIME type inferred from the file extension. This is the
     * trick that makes the PDF self-contained — no HTTP
     * round-trip needed at render time.
     */
    private function fileToDataUrl(string $absolutePath): ?string
    {
        if (! is_readable($absolutePath)) {
            return null;
        }
        $bytes = file_get_contents($absolutePath);

        if ($bytes === false) {
            return null;
        }
        $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            default => 'application/octet-stream',
        };

        return 'data:'.$mime.';base64,'.base64_encode($bytes);
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
