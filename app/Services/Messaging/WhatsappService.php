<?php

namespace App\Services\Messaging;

use App\Models\Integration\Integration;
use App\Models\Integration\IntegrationLog;
use App\Models\Credits\TenantWhatsappBalance;
use App\Models\Credits\WhatsappUsageLog;
use Illuminate\Support\Facades\Http;

class WhatsappService
{
    protected ?Integration $integration;

    public function __construct(?Integration $integration = null)
    {
        $this->integration = $integration ?? Integration::getDefault('whatsapp');
    }

    /**
     * Send a WhatsApp template message.
     */
    public function sendTemplate(
        string $to,
        string $templateName,
        array $parameters = [],
        ?int $tenantId = null,
        string $language = 'ar'
    ): array {
        if (!$this->integration || !$this->integration->is_active) {
            throw new \Exception('لا يوجد مزود WhatsApp مفعّل');
        }

        // Check tenant balance
        if ($tenantId) {
            $balance = TenantWhatsappBalance::getOrCreate($tenantId);
            if (!$balance->hasCredits()) {
                throw new \Exception('رصيد WhatsApp غير كافي');
            }
        }

        $startTime = microtime(true);

        try {
            $result = $this->sendTemplateViaProvider($to, $templateName, $parameters, $language);
            $responseTime = (int) ((microtime(true) - $startTime) * 1000);

            IntegrationLog::log(
                $this->integration->id,
                'send_whatsapp_template',
                ['to' => $to, 'template' => $templateName],
                $result,
                'success',
                null,
                $responseTime,
                $tenantId
            );

            if ($tenantId) {
                $balance->useCredits(1);

                WhatsappUsageLog::create([
                    'tenant_id' => $tenantId,
                    'phone_number' => $to,
                    'message_type' => 'template',
                    'template_name' => $templateName,
                    'credits_used' => 1,
                    'provider' => $this->integration->provider,
                    'provider_message_id' => $result['message_id'] ?? null,
                    'status' => 'sent',
                ]);
            }

            return [
                'success' => true,
                'message_id' => $result['message_id'] ?? null,
            ];
        } catch (\Exception $e) {
            $responseTime = (int) ((microtime(true) - $startTime) * 1000);

            IntegrationLog::log(
                $this->integration->id,
                'send_whatsapp_template',
                ['to' => $to, 'template' => $templateName],
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
     * Send a text message (session message).
     */
    public function sendText(string $to, string $message, ?int $tenantId = null): array
    {
        if (!$this->integration || !$this->integration->is_active) {
            throw new \Exception('لا يوجد مزود WhatsApp مفعّل');
        }

        $config = $this->integration->config ?? [];
        $startTime = microtime(true);

        try {
            $result = match ($this->integration->provider) {
                'whatsapp_cloud' => $this->sendTextViaCloud($config, $to, $message),
                '360dialog' => $this->sendTextVia360dialog($config, $to, $message),
                default => throw new \Exception('مزود غير مدعوم'),
            };

            $responseTime = (int) ((microtime(true) - $startTime) * 1000);

            IntegrationLog::log(
                $this->integration->id,
                'send_whatsapp_text',
                ['to' => $to],
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
                'send_whatsapp_text',
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

    protected function sendTemplateViaProvider(string $to, string $template, array $params, string $lang): array
    {
        $config = $this->integration->config ?? [];

        return match ($this->integration->provider) {
            'whatsapp_cloud' => $this->sendTemplateViaCloud($config, $to, $template, $params, $lang),
            '360dialog' => $this->sendTemplateVia360dialog($config, $to, $template, $params, $lang),
            default => throw new \Exception('مزود غير مدعوم'),
        };
    }

    protected function sendTemplateViaCloud(array $config, string $to, string $template, array $params, string $lang): array
    {
        $components = [];
        if (!empty($params)) {
            $components[] = [
                'type' => 'body',
                'parameters' => array_map(fn($p) => ['type' => 'text', 'text' => $p], $params),
            ];
        }

        $response = Http::withToken($config['access_token'])
            ->post("https://graph.facebook.com/v17.0/{$config['phone_number_id']}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'template',
                'template' => [
                    'name' => $template,
                    'language' => ['code' => $lang],
                    'components' => $components,
                ],
            ]);

        if (!$response->successful()) {
            throw new \Exception('خطأ WhatsApp Cloud: ' . $response->body());
        }

        return ['message_id' => $response->json('messages.0.id')];
    }

    protected function sendTextViaCloud(array $config, string $to, string $message): array
    {
        $response = Http::withToken($config['access_token'])
            ->post("https://graph.facebook.com/v17.0/{$config['phone_number_id']}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'text',
                'text' => ['body' => $message],
            ]);

        if (!$response->successful()) {
            throw new \Exception('خطأ WhatsApp Cloud: ' . $response->body());
        }

        return ['message_id' => $response->json('messages.0.id')];
    }

    protected function sendTemplateVia360dialog(array $config, string $to, string $template, array $params, string $lang): array
    {
        $response = Http::withHeaders(['D360-API-KEY' => $config['api_key']])
            ->post('https://waba.360dialog.io/v1/messages', [
                'to' => $to,
                'type' => 'template',
                'template' => [
                    'namespace' => $config['namespace'] ?? '',
                    'name' => $template,
                    'language' => ['code' => $lang, 'policy' => 'deterministic'],
                    'components' => !empty($params) ? [
                        ['type' => 'body', 'parameters' => array_map(fn($p) => ['type' => 'text', 'text' => $p], $params)]
                    ] : [],
                ],
            ]);

        if (!$response->successful()) {
            throw new \Exception('خطأ 360dialog: ' . $response->body());
        }

        return ['message_id' => $response->json('messages.0.id')];
    }

    protected function sendTextVia360dialog(array $config, string $to, string $message): array
    {
        $response = Http::withHeaders(['D360-API-KEY' => $config['api_key']])
            ->post('https://waba.360dialog.io/v1/messages', [
                'to' => $to,
                'type' => 'text',
                'text' => ['body' => $message],
            ]);

        if (!$response->successful()) {
            throw new \Exception('خطأ 360dialog: ' . $response->body());
        }

        return ['message_id' => $response->json('messages.0.id')];
    }
}
