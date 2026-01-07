<?php

namespace App\Mail;

use App\Models\Billing\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionRenewalReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Subscription $subscription;
    public int $daysRemaining;

    public function __construct(Subscription $subscription, int $daysRemaining)
    {
        $this->subscription = $subscription->load(['tenant', 'plan']);
        $this->daysRemaining = $daysRemaining;
    }

    public function envelope(): Envelope
    {
        $days = $this->daysRemaining === 1 ? 'غداً' : "خلال {$this->daysRemaining} أيام";
        return new Envelope(
            subject: "⏰ تذكير: اشتراكك ينتهي {$days}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.subscription-renewal-reminder',
            with: [
                'subscription' => $this->subscription,
                'tenant' => $this->subscription->tenant,
                'plan' => $this->subscription->plan,
                'daysRemaining' => $this->daysRemaining,
            ],
        );
    }
}
