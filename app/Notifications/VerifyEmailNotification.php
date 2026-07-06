<?php

namespace App\Notifications;

use App\Mail\TemplateMail;
use App\Models\CommunicationTemplate;
use App\Services\Email\SmtpConfigService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;

class VerifyEmailNotification extends Notification
{
    use Queueable;

    /**
     * Get the notification's delivery channels.
     *
     * The SMTP mailer is configured once per request from the default
     * email integration (see SmtpConfigService + AppServiceProvider::boot),
     * so by the time this notification runs the runtime mail config is
     * already correct. We just hand the message to Mail::send here.
     */
    public function via($notifiable): array
    {
        $this->sendVerificationEmail($notifiable);
        return [];
    }

    /**
     * Generate the signed verification URL.
     */
    protected function verificationUrl($notifiable): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(config('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }

    /**
     * Send the verification email.
     */
    protected function sendVerificationEmail($notifiable): void
    {
        // Make sure the SMTP config from the default email integration is
        // applied even if a notification gets dispatched before the
        // AppServiceProvider boot path runs (e.g. during tests or queued
        // jobs that boot a fresh app).
        app(SmtpConfigService::class)->apply();

        $verificationUrl = $this->verificationUrl($notifiable);

        $template = CommunicationTemplate::getByCode('email_verification');

        if ($template && $template->is_active) {
            $subject = str_replace(
                ['{name}', '{app_name}'],
                [$notifiable->name, config('app.name')],
                $template->subject ?? 'تفعيل حسابك'
            );

            $content = str_replace(
                ['{name}', '{app_name}', '{verification_url}'],
                [$notifiable->name, config('app.name'), $verificationUrl],
                $template->content
            );

            try {
                Mail::to($notifiable->email)->send(new TemplateMail($subject, $content));
            } catch (\Exception $e) {
                Log::error('Email verification failed: ' . $e->getMessage());
            }
        } else {
            // Fallback: send simple email
            try {
                Mail::to($notifiable->email)->send(new TemplateMail(
                    'تفعيل حسابك - ' . config('app.name'),
                    '<div style="font-family: sans-serif; text-align: right; direction: rtl;">
                        <h2>مرحباً ' . $notifiable->name . '!</h2>
                        <p>يرجى الضغط على الرابط أدناه لتفعيل حسابك:</p>
                        <p><a href="' . $verificationUrl . '" style="background-color: #4f46e5; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px;">تفعيل الحساب</a></p>
                    </div>'
                ));
            } catch (\Exception $e) {
                Log::error('Email verification fallback failed: ' . $e->getMessage());
            }
        }
    }
}
