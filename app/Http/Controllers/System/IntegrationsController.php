<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\Integration\Integration;
use App\Models\Integration\IntegrationLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;

class IntegrationsController extends Controller
{
    /**
     * List all integrations.
     */
    public function index(): Response
    {
        $integrations = Integration::orderBy('type')->orderBy('sort_order')->get();
        
        $grouped = [
            'sms' => $integrations->where('type', 'sms')->values(),
            'whatsapp' => $integrations->where('type', 'whatsapp')->values(),
            'email' => $integrations->where('type', 'email')->values(),
        ];
        
        $providers = [
            'sms' => Integration::getProviders('sms'),
            'whatsapp' => Integration::getProviders('whatsapp'),
            'email' => Integration::getProviders('email'),
        ];

        return Inertia::render('System/Integrations/Index', [
            'integrations' => $grouped,
            'providers' => $providers,
        ]);
    }

    /**
     * Show integration config page.
     */
    public function show(Integration $integration): Response
    {
        $configFields = Integration::getConfigFields($integration->provider);
        $recentLogs = IntegrationLog::where('integration_id', $integration->id)
            ->latest()
            ->limit(20)
            ->get();

        return Inertia::render('System/Integrations/Show', [
            'integration' => $integration,
            'configFields' => $configFields,
            'logs' => $recentLogs,
            'purposes' => Integration::getPurposes(),
        ]);
    }

    /**
     * Create a new integration.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|in:sms,whatsapp,email',
            'provider' => 'required|string',
        ]);

        $providers = Integration::getProviders($validated['type']);
        
        if (!isset($providers[$validated['provider']])) {
            return back()->withErrors(['provider' => 'المزود غير متاح']);
        }

        $providerInfo = $providers[$validated['provider']];

        $integration = Integration::updateOrCreate(
            ['type' => $validated['type'], 'provider' => $validated['provider']],
            [
                'name' => $providerInfo['name'],
                'name_ar' => $providerInfo['name_ar'],
                'is_active' => false,
                'config' => [],
            ]
        );

        return redirect()->route('system.integrations.show', $integration)
            ->with('success', 'تم إضافة التكامل، يرجى إكمال الإعدادات');
    }

    /**
     * Update integration config.
     */
    public function update(Request $request, Integration $integration)
    {
        $configFields = Integration::getConfigFields($integration->provider);
        
        $rules = [
            'is_active' => 'boolean', 
            'is_default' => 'boolean',
            'purpose' => 'nullable|string|in:all,otp,notifications',
        ];
        foreach ($configFields as $field) {
            $type = ($field['type'] ?? 'text') === 'number' ? 'numeric' : 'string';
            $isRequired = ($field['required'] ?? false) ? 'required' : 'nullable';
            $rules["config.{$field['key']}"] = "$isRequired|$type";
        }
        
        $validated = $request->validate($rules);

        // Preserve existing sensitive values if not provided
        $newConfig = $validated['config'] ?? [];
        $existingConfig = $integration->config ?? [];
        
        foreach ($configFields as $field) {
            if ($field['type'] === 'password' && empty($newConfig[$field['key']])) {
                $newConfig[$field['key']] = $existingConfig[$field['key']] ?? '';
            }
        }

        $integration->update([
            'config' => $newConfig,
            'is_active' => $validated['is_active'] ?? false,
            'purpose' => $validated['purpose'] ?? 'all',
        ]);

        // If setting as default, unset other defaults of same type
        if ($request->is_default) {
            Integration::where('type', $integration->type)
                ->where('id', '!=', $integration->id)
                ->update(['is_default' => false]);
            $integration->update(['is_default' => true]);
        }

        return back()->with('success', 'تم حفظ الإعدادات بنجاح');
    }

    /**
     * Test integration.
     */
    public function test(Request $request, Integration $integration)
    {
        $request->validate([
            'test_recipient' => 'required|string',
        ]);

        $startTime = microtime(true);

        try {
            $result = $this->sendTestMessage($integration, $request->test_recipient);
            $responseTime = (int) ((microtime(true) - $startTime) * 1000);

            IntegrationLog::log(
                $integration->id,
                'test',
                ['recipient' => $request->test_recipient],
                $result,
                'success',
                null,
                $responseTime
            );

            return back()->with('success', 'تم إرسال رسالة الاختبار بنجاح');
        } catch (\Exception $e) {
            $responseTime = (int) ((microtime(true) - $startTime) * 1000);

            IntegrationLog::log(
                $integration->id,
                'test',
                ['recipient' => $request->test_recipient],
                [],
                'failed',
                $e->getMessage(),
                $responseTime
            );

            return back()->withErrors(['test' => 'فشل الاختبار: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete integration.
     */
    public function destroy(Integration $integration)
    {
        $integration->delete();
        return redirect()->route('system.integrations.index')
            ->with('success', 'تم حذف التكامل');
    }

    /**
     * Send test message based on integration type.
     */
    private function sendTestMessage(Integration $integration, string $recipient): array
    {
        $config = $integration->config ?? [];

        return match ($integration->provider) {
            'unifonic' => $this->testUnifonic($config, $recipient),
            'twilio' => $this->testTwilio($config, $recipient),
            'whatsapp_cloud' => $this->testWhatsAppCloud($config, $recipient),
            'smtp' => $this->testSmtp($config, $recipient),
            'authentica' => $this->testAuthentica($config, $recipient),
            default => throw new \Exception('المزود غير مدعوم للاختبار'),
        };
    }

    private function testUnifonic(array $config, string $phone): array
    {
        $response = Http::post('https://el.cloud.unifonic.com/rest/SMS/messages', [
            'AppSid' => $config['app_sid'],
            'SenderID' => $config['sender_id'],
            'Body' => 'رسالة اختبار من Khidma Pro',
            'Recipient' => $phone,
        ]);

        if (!$response->successful()) {
            throw new \Exception('فشل في إرسال الرسالة: ' . $response->body());
        }

        return $response->json();
    }

    private function testTwilio(array $config, string $phone): array
    {
        $response = Http::withBasicAuth($config['account_sid'], $config['auth_token'])
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$config['account_sid']}/Messages.json", [
                'To' => $phone,
                'From' => $config['from_number'],
                'Body' => 'رسالة اختبار من خدمة برو / Khidmh Pro',
            ]);

        if (!$response->successful()) {
            throw new \Exception('فشل في إرسال الرسالة: ' . $response->body());
        }

        return $response->json();
    }

    private function testWhatsAppCloud(array $config, string $phone): array
    {
        $response = Http::withToken($config['access_token'])
            ->post("https://graph.facebook.com/v17.0/{$config['phone_number_id']}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $phone,
                'type' => 'text',
                'text' => ['body' => 'رسالة اختبار من خدمة برو / Khidmh Pro ✅'],
            ]);

        if (!$response->successful()) {
            throw new \Exception('فشل في إرسال الرسالة: ' . $response->body());
        }

        return $response->json();
    }

    private function testSmtp(array $config, string $email): array
    {
        // Use Laravel's mailer with dynamic config
        config([
            'mail.mailers.test_smtp' => [
                'transport' => 'smtp',
                'host' => $config['host'],
                'port' => $config['port'],
                'encryption' => $config['encryption'] ?? 'tls',
                'username' => $config['username'],
                'password' => $config['password'],
            ],
        ]);

        \Mail::mailer('test_smtp')->raw('رسالة اختبار من خدمة برو / Khidmh Pro', function ($message) use ($config, $email) {
            $message->from($config['from_address'], $config['from_name'] ?? 'Khidmh Pro');
            $message->to($email);
            $message->subject('اختبار البريد الإلكتروني');
        });

        return ['status' => 'sent', 'to' => $email];
    }

    private function testAuthentica(array $config, string $phone): array
    {
        $service = new \App\Services\Sms\AuthenticaService($config['api_key'] ?? '');
        $result = $service->sendOtp($phone);

        if (!$result['success']) {
            throw new \Exception('فشل في إرسال الرسالة: ' . ($result['message'] ?? 'خطأ غير معروف'));
        }

        return $result['data'];
    }

    /**
     * Get balance from SMS provider (Authentica).
     */
    public function getBalance(Integration $integration)
    {
        if ($integration->provider !== 'authentica') {
            return response()->json([
                'success' => false,
                'message' => 'هذا المزود لا يدعم جلب الرصيد',
            ], 400);
        }

        $config = $integration->config ?? [];
        $service = new \App\Services\Sms\AuthenticaService($config['api_key'] ?? '');
        $result = $service->getBalance();

        return response()->json($result);
    }
}
