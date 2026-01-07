<?php

namespace App\Mail;

use App\Models\Billing\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionExpiredMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Subscription $subscription;

    public function __construct(Subscription $subscription)
    {
        $this->subscription = $subscription->load(['tenant', 'plan']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '⚠️ انتهى اشتراكك - يرجى التجديد',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.subscription-expired',
            with: [
                'subscription' => $this->subscription,
                'tenant' => $this->subscription->tenant,
                'plan' => $this->subscription->plan,
            ],
        );
    }
}
