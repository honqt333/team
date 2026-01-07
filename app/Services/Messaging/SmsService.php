<?php

namespace App\Services\Messaging;

use App\Models\Integration\Integration;
use App\Models\Integration\IntegrationLog;
use App\Models\Credits\TenantSmsBalance;
use App\Models\Credits\SmsUsageLog;
use Illuminate\Support\Facades\Http;

class SmsService
{
    protected ?Integration $integration;

    public function __construct(?Integration $integration = null)
    {
        $this->integration = $integration ?? Integration::getDefault('sms');
    }

    /**
     * Send an SMS message.
     */
    public function send(string $to, string $message, ?int $tenantId = null): array
    {
        if (!$this->integration || !$this->integration->is_active) {
            throw new \Exception('لا يوجد مزود SMS مفعّل');
        }

        // Check tenant balance if tenant specified
        if ($tenantId) {
            $balance = TenantSmsBalance::getOrCreate($tenantId);
            if (!$balance->hasCredits()) {
                throw new \Exception('رصيد SMS غير كافي');
            }
        }

        $startTime = microtime(true);

        try {
            $result = $this->sendViaProvider($to, $message);
            $responseTime = (int) ((microtime(true) - $startTime) * 1000);

            // Log success
            IntegrationLog::log(
                $this->integration->id,
                'send_sms',
                ['to' => $to, 'message_length' => strlen($message)],
                $result,
                'success',
                null,
                $responseTime,
                $tenantId
            );

            // Deduct credits if tenant
            if ($tenantId) {
                $balance->useCredits(1);
                
                SmsUsageLog::create([
                    'tenant_id' => $tenantId,
                    'phone_number' => $to,
                    'message_type' => 'notification',
                    'credits_used' => 1,
                    'provider' => $this->integration->provider,
                    'provider_message_id' => $result['message_id'] ?? null,
                    'status' => 'sent',
                ]);
            }

            return [
                'success' => true,
                'message_id' => $result['message_id'] ?? null,
                'provider' => $this->integration->provider,
            ];
        } catch (\Exception $e) {
            $responseTime = (int) ((microtime(true) - $startTime) * 1000);

            IntegrationLog::log(
                $this->integration->id,
                'send_sms',
                ['to' => $to],
                [],
                'failed',
                $e->getMessage(),
                $responseTime,
                $tenantId
            );

            throw $e;
        }
    }

    /**
     * Send via configured provider.
     */
    protected function sendViaProvider(string $to, string $message): array
    {
        $config = $this->integration->config ?? [];

        return match ($this->integration->provider) {
            'unifonic' => $this->sendViaUnifonic($config, $to, $message),
            'twilio' => $this->sendViaTwilio($config, $to, $message),
            'msegat' => $this->sendViaMsegat($config, $to, $message),
            default => throw new \Exception('مزود غير مدعوم: ' . $this->integration->provider),
        };
    }

    protected function sendViaUnifonic(array $config, string $to, string $message): array
    {
        $response = Http::post('https://el.cloud.unifonic.com/rest/SMS/messages', [
            'AppSid' => $config['app_sid'],
            'SenderID' => $config['sender_id'],
            'Body' => $message,
            'Recipient' => $to,
        ]);

        if (!$response->successful()) {
            throw new \Exception('خطأ Unifonic: ' . $response->body());
        }

        $data = $response->json();
        return ['message_id' => $data['MessageStatus']['MessageID'] ?? null];
    }

    protected function sendViaTwilio(array $config, string $to, string $message): array
    {
        $response = Http::withBasicAuth($config['account_sid'], $config['auth_token'])
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$config['account_sid']}/Messages.json", [
                'To' => $to,
                'From' => $config['from_number'],
                'Body' => $message,
            ]);

        if (!$response->successful()) {
            throw new \Exception('خطأ Twilio: ' . $response->body());
        }

        return ['message_id' => $response->json('sid')];
    }

    protected function sendViaMsegat(array $config, string $to, string $message): array
    {
        $response = Http::post('https://www.msegat.com/gw/sendsms.php', [
            'userName' => $config['username'],
            'numbers' => $to,
            'userSender' => $config['sender_name'],
            'apiKey' => $config['api_key'],
            'msg' => $message,
        ]);

        if (!$response->successful()) {
            throw new \Exception('خطأ Msegat: ' . $response->body());
        }

        return ['message_id' => $response->json('id') ?? uniqid()];
    }

    /**
     * Get available balance for tenant.
     */
    public static function getBalance(int $tenantId): int
    {
        return TenantSmsBalance::getOrCreate($tenantId)->balance;
    }
}
