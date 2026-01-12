<?php

namespace App\Notifications;

use App\Models\CommunicationTemplate;
use App\Services\Messaging\EmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class WelcomeEmployeeInvitation extends Notification
{
    use Queueable;

    /**
     * Get the notification's delivery channels.
     * We send email directly here (same pattern as VerifyEmailNotification)
     */
    public function via(object $notifiable): array
    {
        // Send email directly using EmailService
        $this->sendInvitationEmail($notifiable);
        
        // Only save to database for notification history
        return ['database'];
    }

    /**
     * Send the invitation email using EmailService.
     */
    protected function sendInvitationEmail(object $notifiable): void
    {
        $activationUrl = URL::signedRoute(
            'invitations.accept',
            ['user' => $notifiable->id],
            now()->addDays(3)
        );

        // Load template from database
        $template = CommunicationTemplate::getByCode('employee_invitation', 'email');

        // Default values
        $subject = 'مرحباً بك في الفريق - تفعيل حسابك في ' . config('app.name');
        $content = $this->getDefaultContent($notifiable->name, $activationUrl);

        if ($template && $template->is_active) {
            // Replace variables in template
            $subject = str_replace(
                ['{name}', '{app_name}', '{activation_url}'],
                [$notifiable->name, config('app.name'), $activationUrl],
                $template->subject
            );

            $content = str_replace(
                ['{name}', '{app_name}', '{activation_url}'],
                [$notifiable->name, config('app.name'), $activationUrl],
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
                $notifiable->tenant_id
            );
            \Log::info("WelcomeEmployeeInvitation: Sent to {$notifiable->email}");
        } catch (\Exception $e) {
            \Log::error("WelcomeEmployeeInvitation failed: " . $e->getMessage());
        }
    }

    /**
     * Default content if template not found in database.
     */
    protected function getDefaultContent(string $name, string $url): string
    {
        return view('emails.employee-invitation', [
            'userName' => $name,
            'activationUrl' => $url,
            'appName' => config('app.name'),
        ])->render();
    }

    /**
     * Get the array representation of the notification (for database channel).
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'employee_invitation',
            'user_id' => $notifiable->id,
            'message' => 'تم إرسال دعوة تفعيل الحساب',
        ];
    }
}





