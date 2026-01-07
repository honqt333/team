<?php

namespace App\Notifications;

use App\Mail\TemplateMail;
use App\Models\CommunicationTemplate;
use App\Models\Integration\Integration;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;

class VerifyEmailNotification extends Notification
{
    use Queueable;

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        // We handle email sending manually in toMail, so return empty
        $this->sendVerificationEmail($notifiable);
        return [];
    }

    /**
     * Configure mailer from database integration.
     */
    protected function configureMailer(): void
    {
        $integration = Integration::where('type', 'email')
            ->where('is_active', true)
            ->where('is_default', true)
            ->first();

        if ($integration && $integration->isConfigured()) {
            $config = $integration->config;
            
            Config::set('mail.default', 'smtp');
            Config::set('mail.mailers.smtp', [
                'transport' => 'smtp',
                'host' => $config['host'] ?? 'smtp.gmail.com',
                'port' => $config['port'] ?? 587,
                'encryption' => $config['encryption'] ?? 'tls',
                'username' => $config['username'] ?? '',
                'password' => $config['password'] ?? '',
                'timeout' => null,
            ]);

            if (!empty($config['from_address'])) {
                Config::set('mail.from.address', $config['from_address']);
                Config::set('mail.from.name', $config['from_name'] ?? config('app.name'));
            }
        }
    }

    /**
     * Generate verification URL.
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
        // Configure mailer from database
        $this->configureMailer();
        
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
                \Log::error('Email verification failed: ' . $e->getMessage());
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
                \Log::error('Email verification fallback failed: ' . $e->getMessage());
            }
        }
    }
}
