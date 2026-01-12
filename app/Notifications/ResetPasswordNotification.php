<?php

namespace App\Notifications;

use App\Models\CommunicationTemplate;
use App\Services\Messaging\EmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    protected string $token;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     * We send email directly here using EmailService.
     */
    public function via($notifiable): array
    {
        // Send email directly using EmailService
        $this->sendResetEmail($notifiable);
        
        return [];
    }

    /**
     * Send the password reset email using EmailService.
     */
    protected function sendResetEmail($notifiable): void
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        // Load template from database
        $template = CommunicationTemplate::getByCode('password_reset', 'email');

        // Default values
        $subject = 'إعادة تعيين كلمة المرور - ' . config('app.name');
        $content = $this->getDefaultContent($notifiable->name, $url);

        if ($template && $template->is_active) {
            // Replace variables in template
            $subject = str_replace(
                ['{name}', '{app_name}', '{reset_url}'],
                [$notifiable->name, config('app.name'), $url],
                $template->subject
            );

            $content = str_replace(
                ['{name}', '{app_name}', '{reset_url}'],
                [$notifiable->name, config('app.name'), $url],
                $template->content
            );
        }

        try {
            // Send via EmailService which uses Integration SMTP settings
            $emailService = new EmailService();
            $emailService->send(
                $notifiable->email,
                $subject,
                $content,
                true, // isHtml
                $notifiable->tenant_id ?? null
            );
            \Log::info("ResetPasswordNotification: Sent to {$notifiable->email}");
        } catch (\Exception $e) {
            \Log::error("ResetPasswordNotification failed: " . $e->getMessage());
        }
    }

    /**
     * Default content if template not found in database.
     */
    protected function getDefaultContent(string $name, string $url): string
    {
        return '<div style="font-family: sans-serif; text-align: right; direction: rtl; max-width: 600px; margin: 0 auto; background: #fff; padding: 40px; border-radius: 12px;">
            <div style="text-align: center; margin-bottom: 30px;">
                <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); border-radius: 50%; margin: 0 auto 20px; text-align: center; line-height: 80px;">
                    <span style="font-size: 36px;">🔑</span>
                </div>
                <h1 style="color: #111827; font-size: 24px; margin: 0;">إعادة تعيين كلمة المرور</h1>
            </div>
            
            <p style="color: #374151; font-size: 16px; line-height: 1.8;">
                أهلاً <strong>' . htmlspecialchars($name) . '</strong>،
            </p>
            <p style="color: #374151; font-size: 16px; line-height: 1.8;">
                تلقينا طلباً لإعادة تعيين كلمة المرور الخاصة بحسابك في <strong>' . config('app.name') . '</strong>.
                يرجى الضغط على الزر أدناه لإعادة تعيين كلمة المرور.
            </p>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="' . $url . '" style="display: inline-block; padding: 14px 32px; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: #ffffff; text-decoration: none; border-radius: 10px; font-weight: bold; font-size: 16px;">إعادة تعيين كلمة المرور</a>
            </div>
            
            <div style="background-color: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 16px; margin: 20px 0; color: #92400e;">
                ⚠️ هذا الرابط صالح لمدة <strong>60 دقيقة</strong> فقط.
            </div>
            
            <p style="text-align: center; color: #9ca3af; font-size: 13px;">
                🔒 إذا لم تطلب إعادة تعيين كلمة المرور، يرجى تجاهل هذا البريد.
            </p>
            
            <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #e5e7eb; font-size: 12px; color: #9ca3af; text-align: center;">
                &copy; ' . date('Y') . ' ' . config('app.name') . '. جميع الحقوق محفوظة.
            </div>
        </div>';
    }
}
