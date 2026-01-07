<?php

namespace App\Mail;

use App\Models\Billing\SubscriptionInvoice;
use App\Services\Invoice\SubscriptionInvoiceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionInvoiceMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public SubscriptionInvoice $invoice;
    protected ?string $pdfPath = null;

    public function __construct(SubscriptionInvoice $invoice)
    {
        $this->invoice = $invoice->load(['subscription.plan', 'tenant']);
    }

    public function envelope(): Envelope
    {
        $subject = $this->invoice->status === 'paid' 
            ? "فاتورة مدفوعة - {$this->invoice->invoice_number}"
            : "فاتورة اشتراك جديدة - {$this->invoice->invoice_number}";
            
        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.subscription-invoice',
            with: [
                'invoice' => $this->invoice,
                'tenant' => $this->invoice->tenant,
                'plan' => $this->invoice->subscription?->plan,
            ],
        );
    }

    public function attachments(): array
    {
        // Generate PDF and attach
        $service = app(SubscriptionInvoiceService::class);
        $pdfPath = $service->getPdfPath($this->invoice);
        
        if ($pdfPath && file_exists($pdfPath)) {
            return [
                Attachment::fromPath($pdfPath)
                    ->as("{$this->invoice->invoice_number}.pdf")
                    ->withMime('application/pdf'),
            ];
        }
        
        return [];
    }
}
