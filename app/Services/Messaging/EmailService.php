<?php

namespace App\Services\Messaging;

use App\Models\Integration\Integration;
use App\Models\Integration\IntegrationLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class EmailService
{
    protected ?Integration $integration;

    public function __construct(?Integration $integration = null)
    {
        $this->integration = $integration ?? Integration::getDefault('email');
    }

    /**
     * Send an email.
     */
    public function send(
        string $to,
        string $subject,
        string $body,
        bool $isHtml = true,
        ?int $tenantId = null
    ): array {
        if (!$this->integration || !$this->integration->is_active) {
            // Fallback to default Laravel mailer
            return $this->sendViaDefaultMailer($to, $subject, $body, $isHtml);
        }

        $startTime = microtime(true);

        try {
            $result = $this->sendViaProvider($to, $subject, $body, $isHtml);
            $responseTime = (int) ((microtime(true) - $startTime) * 1000);

            IntegrationLog::log(
                $this->integration->id,
                'send_email',
                ['to' => $to, 'subject' => $subject],
                $result,
                'success',
                null,
                $responseTime,
                $tenantId
            );

            return ['success' => true, 'message_id' => $result['message_id'] ?? null];
        } catch (\Exception $e) {
            $responseTime = (int) ((microtime(true) - $startTime) * 1000);

            IntegrationLog::log(
                $this->integration->id,
                'send_email',
                ['to' => $to, 'subject' => $subject],
                [],
                'failed',
                $e->getMessage(),
                $responseTime,
                $tenantId
            );

            throw $e;
        }
    }

    protected function sendViaProvider(string $to, string $subject, string $body, bool $isHtml): array
    {
        $config = $this->integration->config ?? [];

        return match ($this->integration->provider) {
            'smtp' => $this->sendViaSmtp($config, $to, $subject, $body, $isHtml),
            'mailgun' => $this->sendViaMailgun($config, $to, $subject, $body, $isHtml),
            'sendgrid' => $this->sendViaSendgrid($config, $to, $subject, $body, $isHtml),
            default => throw new \Exception('مزود غير مدعوم'),
        };
    }

    protected function sendViaSmtp(array $config, string $to, string $subject, string $body, bool $isHtml): array
    {
        config([
            'mail.mailers.dynamic_smtp' => [
                'transport' => 'smtp',
                'host' => $config['host'],
                'port' => $config['port'],
                'encryption' => $config['encryption'] ?? 'tls',
                'username' => $config['username'],
                'password' => $config['password'],
            ],
        ]);

        if ($isHtml) {
            Mail::mailer('dynamic_smtp')->html($body, function ($message) use ($config, $to, $subject) {
                $message->from($config['from_address'], $config['from_name'] ?? 'Khidma Pro');
                $message->to($to);
                $message->subject($subject);
            });
        } else {
            Mail::mailer('dynamic_smtp')->raw($body, function ($message) use ($config, $to, $subject) {
                $message->from($config['from_address'], $config['from_name'] ?? 'Khidma Pro');
                $message->to($to);
                $message->subject($subject);
            });
        }

        return ['message_id' => uniqid('smtp_')];
    }

    protected function sendViaMailgun(array $config, string $to, string $subject, string $body, bool $isHtml): array
    {
        $response = Http::withBasicAuth('api', $config['api_key'])
            ->asForm()
            ->post("https://api.mailgun.net/v3/{$config['domain']}/messages", [
                'from' => ($config['from_name'] ?? 'Khidma Pro') . ' <' . $config['from_address'] . '>',
                'to' => $to,
                'subject' => $subject,
                $isHtml ? 'html' : 'text' => $body,
            ]);

        if (!$response->successful()) {
            throw new \Exception('خطأ Mailgun: ' . $response->body());
        }

        return ['message_id' => $response->json('id')];
    }

    protected function sendViaSendgrid(array $config, string $to, string $subject, string $body, bool $isHtml): array
    {
        $response = Http::withToken($config['api_key'])
            ->post('https://api.sendgrid.com/v3/mail/send', [
                'personalizations' => [['to' => [['email' => $to]]]],
                'from' => ['email' => $config['from_address'], 'name' => $config['from_name'] ?? 'Khidma Pro'],
                'subject' => $subject,
                'content' => [['type' => $isHtml ? 'text/html' : 'text/plain', 'value' => $body]],
            ]);

        if (!$response->successful()) {
            throw new \Exception('خطأ SendGrid: ' . $response->body());
        }

        return ['message_id' => $response->header('X-Message-Id') ?? uniqid('sg_')];
    }

    protected function sendViaDefaultMailer(string $to, string $subject, string $body, bool $isHtml): array
    {
        if ($isHtml) {
            Mail::html($body, fn($msg) => $msg->to($to)->subject($subject));
        } else {
            Mail::raw($body, fn($msg) => $msg->to($to)->subject($subject));
        }

        return ['success' => true, 'message_id' => uniqid('default_')];
    }
}
