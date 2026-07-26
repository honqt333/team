<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Billing\SubscriptionInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent by the dunning automation when an invoice crosses one of
 * the tier thresholds (3d / 7d / 14d / 30d). The subject and body
 * are passed in pre-rendered by DunningService so the mailable
 * stays a dumb transport — all the localization and tone logic
 * lives in one place.
 *
 * ShouldQueue = the email is pushed to the queue and sent in the
 * background. We don't want the cron job to block on SMTP latency.
 */
class SubscriptionOverdueMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public SubscriptionInvoice $invoice,
        public string $tierKey,
        public string $subjectLine,
        public string $bodyText,
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
            ],
        );
    }
}
