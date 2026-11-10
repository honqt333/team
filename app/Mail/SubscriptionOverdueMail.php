<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Billing\SubscriptionInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/**
 * Sent by the dunning automation when an invoice crosses one of
 * the tier thresholds (3d / 7d / 14d / 30d). The subject and body
 * are passed in pre-rendered by DunningService so the mailable
 * stays a dumb transport — all the localization and tone logic
 * lives in one place.
 *
 * ShouldQueue = the email is pushed to the queue and sent in the
 * background. We don't want the cron job to block on SMTP latency.
 *
 * Phase 7.1: optionally attaches the invoice PDF so the tenant
 * has a copy in their inbox without having to log in. The PDF
 * is generated on-the-fly by the service and passed in as a
 * filesystem path — we don't generate it inside the mailable
 * because we want the email to enqueue without blocking on
 * PDF generation.
 */
class SubscriptionOverdueMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public SubscriptionInvoice $invoice,
        public string $tierKey,
        public string $subjectLine,
        public string $bodyText,
        public ?string $attachmentPath = null,
    ) {
        $this->invoice->loadMissing('tenant');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.subscription-overdue',
            with: [
                'invoice' => $this->invoice,
                'tenant' => $this->invoice->tenant,
                'bodyText' => $this->bodyText,
                'tierKey' => $this->tierKey,
                'attachmentPath' => $this->attachmentPath,
            ],
        );
    }

    /**
     * Attach the invoice PDF if a path was provided AND the file
     * actually exists on disk. We guard the disk check so a
     * missing PDF doesn't blow up the queue worker — the email
     * still goes out, just without the attachment.
     */
    public function attachments(): array
    {
        if (! $this->attachmentPath) {
            return [];
        }

        if (! Storage::disk('local')->exists($this->attachmentPath)) {
            return [];
        }

        return [
            Attachment::fromStorageDisk('local', $this->attachmentPath)
                ->as(basename($this->attachmentPath))
                ->withMime('application/pdf'),
        ];
    }
}
