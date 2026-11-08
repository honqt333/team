<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Billing\Subscription;
use App\Models\Billing\SubscriptionPayment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email sent to tenant users when their subscription payment succeeds
 * and the subscription becomes active.
 */
class SubscriptionActivatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Subscription $subscription,
        public SubscriptionPayment $payment,
        public User $recipient
    ) {}

    public function envelope(): Envelope
    {
        $appName = config('app.name_ar', config('app.name', 'خدمة برو'));

        return new Envelope(
            subject: "{$appName} - تم تفعيل اشتراكك بنجاح",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.subscription-activated',
            with: [
                'subscription' => $this->subscription,
                'payment' => $this->payment,
                'planName' => $this->subscription->plan?->name_ar
                    ?? $this->subscription->plan?->name_en
                    ?? 'الاشتراك',
                'amount' => number_format((float) $this->payment->amount, 2),
                'currency' => $this->payment->currency ?? 'SAR',
                'endsAt' => $this->subscription->ends_at?->format('Y-m-d'),
                'recipientName' => $this->recipient->name ?? $this->recipient->email,
                'dashboardUrl' => route('app.subscription.index'),
            ],
        );
    }
}
