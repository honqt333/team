<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\Integration\Integration;
use App\Models\Integration\IntegrationLog;
use App\Services\Sms\AuthenticaService;
use App\Services\System\SystemAuditService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;
use Mail;

class IntegrationsController extends Controller
{
    protected SystemAuditService $audit;

    public function __construct(
        ?SystemAuditService $audit = null
    ) {
        $this->audit = $audit ?? app(SystemAuditService::class);
    }

    /**
     * Placeholder sent to the browser in place of a stored secret.
     *
     * It is a non-empty mask so the form can tell "still set" apart
     * from "never filled in" and skip overwriting the stored value.
     */
    private const REDACTED_PLACEHOLDER = '••••••••';

    /**
     * List all integrations.
     *
     * SECURITY: the `config` column holds the provider credentials —
     * Twilio auth tokens, Unifonic App SIDs, API keys, SMTP passwords.
     * The list page renders only the catalogue entries (name, type,
     * active/default flags), so the column is not selected at all:
     * a secret that is never queried cannot leak if a later change
     * drops a redaction call.
     *
     * The detail page still needs the values, and gets them masked by
     * redactIntegration() instead.
     */
    public function index(): Response
    {
        $integrations = Integration::query()
            ->select([
                'id',
                'type',
                'provider',
                'name',
                'name_ar',
                'description',
                'is_active',
                'is_default',
                'purpose',
                'sort_order',
            ])
            ->orderBy('type')
            ->orderBy('sort_order')
            ->get();

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
            // SECURITY: mask sensitive values before
            // sending the integration row to the
            // browser. The api_key is type=password
            // in the form, but the raw value would
            // still be readable via the page source
            // / Inertia payload. The mask is a
            // non-empty placeholder so the form can
            // detect "still set" and skip an
            // overwrite if the user does not retype.
            'integration' => $this->redactIntegration($integration),
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

        if (! isset($providers[$validated['provider']])) {
            return back()->withErrors(['provider' => __('messages.system.integrations.provider_unavailable')]);
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

        $this->audit->log('integration_create', Integration::class, $integration->id, [
            'type' => $integration->type,
            'provider' => $integration->provider,
        ], $request);

        return redirect()->route('system.integrations.show', $integration)
            ->with('success', __('messages.system.integrations.added'));
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

            $fieldRules = [$isRequired, $type];

            // P2 — Authentica template_id and any
            // other `number` field can declare a
            // positive-integer constraint via the
            // `min` metadata key. We honour it on
            // save. The minimum is always inclusive
            // (the default Authentica v2 template id
            // is 1, and we never want a non-positive
            // value reaching the channel payload).
            if (($field['type'] ?? null) === 'number' && isset($field['min'])) {
                $fieldRules[] = 'min:'.(int) $field['min'];
            }

            $rules["config.{$field['key']}"] = implode('|', $fieldRules);
        }

        $validated = $request->validate($rules);

        // Preserve existing sensitive values if not provided.
        //
        // Two cases mean "leave the stored secret alone":
        //   1. the field was left blank, and
        //   2. the field still holds the redaction placeholder — the
        //      form is initialised from the masked payload, so an
        //      operator who opens the settings page and saves without
        //      retyping the secret sends the mask back.
        //
        // Without case 2 the mask itself would be stored as the live
        // credential and break the provider integration.
        $newConfig = $validated['config'] ?? [];
        $existingConfig = $integration->config ?? [];

        foreach ($configFields as $field) {
            $isSensitive = $field['type'] === 'password' || ($field['sensitive'] ?? false) === true;

            if (! $isSensitive) {
                continue;
            }

            $incoming = $newConfig[$field['key']] ?? null;

            if (empty($incoming) || $incoming === self::REDACTED_PLACEHOLDER) {
                $newConfig[$field['key']] = $existingConfig[$field['key']] ?? '';
            }
        }

        $integration->update([
            'config' => $newConfig,
            'is_active' => $validated['is_active'] ?? false,
            'is_default' => $validated['is_default'] ?? false,
            'purpose' => $validated['purpose'] ?? 'all',
        ]);

        // If setting as default, unset other defaults of same type so
        // only one integration is the default per channel.
        if ($validated['is_default'] ?? false) {
            Integration::where('type', $integration->type)
                ->where('id', '!=', $integration->id)
                ->update(['is_default' => false]);
        }

        $this->audit->log('integration_update', Integration::class, $integration->id, [
            'type' => $integration->type,
            'provider' => $integration->provider,
            'is_active' => $integration->is_active,
            'is_default' => $integration->is_default,
            'purpose' => $integration->purpose,
        ], $request);

        return back()->with('success', __('messages.system.integrations.settings_saved'));
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

            return back()->with('success', __('messages.system.integrations.test_sent'));
        } catch (Exception $e) {
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

            return back()->withErrors(['test' => __('messages.system.integrations.test_failed', ['error' => $e->getMessage()])]);
        }
    }

    /**
     * Delete integration.
     */
    public function destroy(Integration $integration)
    {
        $integrationId = $integration->id;
        $provider = $integration->provider;
        $type = $integration->type;

        $integration->delete();

        $this->audit->log('integration_delete', Integration::class, $integrationId, [
            'type' => $type,
            'provider' => $provider,
        ]);

        return redirect()->route('system.integrations.index')
            ->with('success', __('messages.system.integrations.deleted'));
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
            default => throw new Exception('المزود غير مدعوم للاختبار'),
        };
    }

    private function testUnifonic(array $config, string $phone): array
    {
        $brandName = config('app.name_ar', 'خدمة برو').' / '.config('app.name', 'Khidmh Pro');
        $response = Http::post('https://el.cloud.unifonic.com/rest/SMS/messages', [
            'AppSid' => $config['app_sid'],
            'SenderID' => $config['sender_id'],
            'Body' => "رسالة اختبار من {$brandName}",
            'Recipient' => $phone,
        ]);

        if (! $response->successful()) {
            throw new Exception('فشل في إرسال الرسالة: '.$response->body());
        }

        return $response->json();
    }

    private function testTwilio(array $config, string $phone): array
    {
        $brandName = config('app.name_ar', 'خدمة برو').' / '.config('app.name', 'Khidmh Pro');
        $response = Http::withBasicAuth($config['account_sid'], $config['auth_token'])
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$config['account_sid']}/Messages.json", [
                'To' => $phone,
                'From' => $config['from_number'],
                'Body' => "رسالة اختبار من {$brandName}",
            ]);

        if (! $response->successful()) {
            throw new Exception('فشل في إرسال الرسالة: '.$response->body());
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

        if (! $response->successful()) {
            throw new Exception('فشل في إرسال الرسالة: '.$response->body());
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

        Mail::mailer('test_smtp')->raw('رسالة اختبار من خدمة برو / Khidmh Pro', function ($message) use ($config, $email) {
            $message->from($config['from_address'], $config['from_name'] ?? 'Khidmh Pro');
            $message->to($email);
            $message->subject('اختبار البريد الإلكتروني');
        });

        return ['status' => 'sent', 'to' => $email];
    }

    private function testAuthentica(array $config, string $phone): array
    {
        $service = new AuthenticaService($config['api_key'] ?? '');
        $result = $service->sendOtp($phone);

        if (! $result['success']) {
            throw new Exception('فشل في إرسال الرسالة: '.($result['message'] ?? 'خطأ غير معروف'));
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
                'message' => __('messages.system.integration_balance_unsupported'),
            ], 400);
        }

        $config = $integration->config ?? [];
        $service = new AuthenticaService($config['api_key'] ?? '');
        $result = $service->getBalance();

        return response()->json($result);
    }

    /**
     * P2 — strip sensitive values from an
     * Integration row before sending it to the
     * browser.
     *
     * The `api_key` for Authentica, Twilio, Msegat,
     * 360dialog, WhatsApp Cloud, mailgun, and
     * sendgrid lives in the JSON `config` column.
     * The form renders these fields as
     * type="password", but the raw value would
     * still appear in the Inertia payload and be
     * readable from the browser dev tools.
     *
     * The redaction replaces the value with a
     * non-empty placeholder. The form's
     * "preserve existing if blank" logic detects
     * a non-blank placeholder and keeps the
     * existing DB value; the operator can still
     * type a new value to replace it.
     *
     * We mask every field declared as
     * `type: 'password'` in `getConfigFields()` for
     * the integration's provider, plus any field
     * explicitly flagged `sensitive: true` — some
     * provider credentials are rendered as plain
     * text in the form (Unifonic's App SID is sent
     * on every API call and authenticates the
     * request) but must not be readable in the
     * payload. The list is resolved server-side so
     * we never have to hardcode a key name here.
     */
    private function redactIntegration(Integration $integration): Integration
    {
        $configFields = Integration::getConfigFields($integration->provider);

        // Build the list of sensitive keys from the
        // declared field metadata. We do NOT
        // blind-mask every key in $integration->config
        // — that would hide legitimate non-sensitive
        // values (template_id, sender_id, etc.).
        $sensitiveKeys = [];

        foreach ($configFields as $field) {
            if (($field['type'] ?? null) === 'password' || ($field['sensitive'] ?? false) === true) {
                $sensitiveKeys[] = $field['key'];
            }
        }

        $config = $integration->config ?? [];

        foreach ($sensitiveKeys as $key) {
            if (! empty($config[$key])) {
                // Non-empty placeholder so the form
                // sees the field as "set" and skips
                // overwriting it. The actual value
                // never leaves the server.
                $config[$key] = self::REDACTED_PLACEHOLDER;
            }
        }

        $integration->config = $config;
        $integration->syncOriginal();

        return $integration;
    }
}
