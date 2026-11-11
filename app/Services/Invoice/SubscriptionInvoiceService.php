<?php

declare(strict_types=1);

namespace App\Services\Invoice;

use App\Models\Billing\Subscription;
use App\Models\Billing\SubscriptionInvoice;
use App\Models\PaymentSettings;
use App\Support\ArabicPdfShaper;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

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

        // Record WHO marked the invoice as paid. The
        // `subscription_invoices` table no longer has a `marked_by`
        // column (dropped by the 2026_07_25_140000 migration), so the
        // actor used to be passed in `$details['marked_by']` and then
        // silently DROPPED on the floor — the audit trail for a manual
        // payment confirmation was never recorded. We fold it into the
        // notes, which survive on the invoice.
        if (! empty($details['marked_by'])) {
            $notes .= ', marked_by='.$details['marked_by'];
        }

        $notes .= ' at '.now()->toIso8601String();

        $invoice->update([
            'status' => 'paid',
            'paid_at' => now(),
            'notes' => $notes,
        ]);

        $this->invalidatePdf($invoice);

        return $invoice;
    }

    /**
     * Generate PDF for invoice.
     */
    public function generatePdf(SubscriptionInvoice $invoice): string
    {
        $invoice->loadMissing(['subscription.plan', 'tenant']);

        // Subscription invoices are issued by the platform, so the
        // company info comes from PaymentSettings (the admin edits it
        // at /system/settings/company). The invoice stores a snapshot
        // of that identity at creation; reprints must keep it even if
        // the platform is renamed later.
        $paymentSettings = PaymentSettings::first();
        $companyInfo = $this->buildCompanyInfo($invoice, $paymentSettings);

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
        $rawHtml = (string) view('pdf.subscription-invoice', [
            'invoice' => $invoice,
            'tenant' => $invoice->tenant,
            'subscription' => $invoice->subscription,
            'plan' => $invoice->subscription?->plan,
            'company' => $companyInfo,
            'vat_rate' => $vatRate,
        ])->render();

        $shapedHtml = ArabicPdfShaper::shapeHtml($rawHtml);

        $pdf = app('dompdf.wrapper');
        $pdf->getDomPDF()->getOptions()->set('isRemoteEnabled', true);
        $pdf->loadHtml($shapedHtml);

        // Set paper to A4
        $pdf->setPaper('a4');

        // Store PDF
        $filename = "invoices/subscription/{$invoice->invoice_number}.pdf";

        if (! Storage::disk('local')->put($filename, $pdf->output())) {
            throw new RuntimeException('Invoice PDF storage failed.');
        }

        return $filename;
    }

    /**
     * Build the company info array passed to the PDF view.
     *
     * The issuer identity of a subscription invoice is the platform,
     * configured in PaymentSettings. The values frozen on the invoice
     * at creation win, so a reprint after a platform rename keeps
     * showing the identity that was in force when it was issued.
     */
    private function buildCompanyInfo(SubscriptionInvoice $invoice, ?PaymentSettings $settings): array
    {
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

        return $invoice->issuerIdentity($settings) + [
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

        if (Storage::disk('local')->exists($filename)) {
            return Storage::disk('local')->path($filename);
        }

        // Generate if not exists
        $this->generatePdf($invoice);

        return Storage::disk('local')->path($filename);
    }

    /**
     * Delete cached PDF so the next preview/download reflects updated status or metadata.
     */
    public function invalidatePdf(SubscriptionInvoice $invoice): void
    {
        $filename = "invoices/subscription/{$invoice->invoice_number}.pdf";

        if (Storage::disk('local')->exists($filename)) {
            Storage::disk('local')->delete($filename);
        }
    }

    /**
     * Invalidate any cached PDF and immediately regenerate it from latest invoice data.
     */
    public function refreshPdf(SubscriptionInvoice $invoice): string
    {
        $this->invalidatePdf($invoice);

        return $this->generatePdf($invoice->fresh(['subscription.plan', 'tenant']));
    }

    /**
     * Get PDF URL for invoice.
     */
    public function getPdfUrl(SubscriptionInvoice $invoice): string
    {
        $filename = "invoices/subscription/{$invoice->invoice_number}.pdf";

        if (! Storage::disk('local')->exists($filename)) {
            $this->generatePdf($invoice);
        }

        return route('system.invoices.download', [
            'invoice' => $invoice->id,
            'inline' => 1,
            'v' => $invoice->updated_at?->timestamp ?? time(),
        ], false);
    }
}
