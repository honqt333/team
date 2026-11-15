<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Credits\TenantWhatsappBalance;
use App\Models\Credits\WhatsappUsageLog;
use App\Models\Integration\Integration;
use App\Models\Tenant;
use App\Services\Messaging\WhatsappService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class NotificationSettingsController extends Controller
{
    /**
     * Display the notification & WhatsApp settings management page.
     */
    public function index(Request $request): Response
    {
        /** @var Tenant $tenant */
        $tenant = $request->user()->tenant;
        $isWhatsappAddon = $request->routeIs('app.addons.whatsapp.*');

        $settings = $tenant->getEffectiveNotificationSettings();

        $templates = [];
        $whatsappSummary = [
            'provider_active' => false,
            'provider_name' => null,
            'balance' => 0,
            'total_purchased' => 0,
            'total_used' => 0,
        ];
        $whatsappLogs = [];

        if ($isWhatsappAddon) {
            // Default template strings if not set
            $templates = [
                'work_order_created' => $tenant->getNotificationTemplate(
                    'work_order_created',
                    __('company_profile.notification_templates.work_order_created')
                ),
                'work_order_completed' => $tenant->getNotificationTemplate(
                    'work_order_completed',
                    __('company_profile.notification_templates.work_order_completed')
                ),
                'quote_shared' => $tenant->getNotificationTemplate(
                    'quote_shared',
                    __('company_profile.notification_templates.quote_shared')
                ),
                'invoice_issued' => $tenant->getNotificationTemplate(
                    'invoice_issued',
                    __('company_profile.notification_templates.invoice_issued')
                ),
                'appointment_booked' => $tenant->getNotificationTemplate(
                    'appointment_booked',
                    'مرحباً {customer_name}، تم تأكيد حجز موعدكم رقم {reference_number} بتاريخ {date}. نسعد بخدمتكم في {company_name}.'
                ),
                'appointment_reminder' => $tenant->getNotificationTemplate(
                    'appointment_reminder',
                    'مرحباً {customer_name}، نود تذكيركم بموعد صيانة مركبتكم رقم {reference_number} غداً بتاريخ {date}. نتطلع لزيارتكم في {company_name}.'
                ),
                'low_stock_alert' => $tenant->getNotificationTemplate(
                    'low_stock_alert',
                    'تنبيه مخزون: القطعة {item_name} وصلت إلى الحد الأدنى ({quantity} متوفرة).'
                ),
            ];

            $balance = TenantWhatsappBalance::getOrCreate($tenant->id);
            $whatsappIntegration = Integration::getDefault('whatsapp');

            $whatsappLogs = WhatsappUsageLog::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->latest()
                ->limit(30)
                ->get()
                ->map(fn (WhatsappUsageLog $log) => [
                    'id' => $log->id,
                    'phone_number' => $log->phone_number,
                    'message_type' => $log->message_type,
                    'message_type_label' => $log->message_type_label,
                    'template_name' => $log->template_name,
                    'credits_used' => (int) $log->credits_used,
                    'status' => $log->status,
                    'status_label' => $log->status_label,
                    'error_message' => $log->error_message,
                    'created_at' => $log->created_at?->format('Y-m-d H:i'),
                ])
                ->values();

            $whatsappSummary = [
                'provider_active' => (bool) ($whatsappIntegration?->is_active ?? false),
                'provider_name' => $whatsappIntegration?->provider,
                'balance' => (int) $balance->balance,
                'total_purchased' => (int) $balance->total_purchased,
                'total_used' => (int) $balance->total_used,
            ];
        }

        $allowedTabs = $isWhatsappAddon
            ? ['channels', 'events', 'templates', 'logs']
            : ['channels', 'events'];
        $requestedTab = (string) $request->query('tab', 'channels');
        $initialTab = in_array($requestedTab, $allowedTabs, true)
            ? $requestedTab
            : 'channels';

        return Inertia::render('Settings/Notifications/Index', [
            'settings' => $settings,
            'templates' => $templates,
            'initialTab' => $initialTab,
            'isWhatsappAddon' => $isWhatsappAddon,
            'backHref' => route('app.addons'),
            'whatsappSummary' => $whatsappSummary,
            'whatsappLogs' => $whatsappLogs,
        ]);
    }

    /**
     * Update notification settings and templates for the tenant.
     */
    public function update(Request $request): RedirectResponse
    {
        /** @var Tenant $tenant */
        $tenant = $request->user()->tenant;

        $validated = $request->validate([
            'channels' => ['required', 'array'],
            'channels.in_app' => ['boolean'],
            'channels.email' => ['boolean'],
            'channels.sms' => ['boolean'],
            'channels.whatsapp' => ['boolean'],
            'channel_configs' => ['nullable', 'array'],
            'channel_configs.email_recipient' => ['nullable', 'string', 'max:255'],
            'channel_configs.whatsapp_phone' => ['nullable', 'string', 'max:50'],
            'events' => ['required', 'array'],
            'events.*.in_app' => ['nullable', 'boolean'],
            'events.*.email' => ['nullable', 'boolean'],
            'events.*.sms' => ['nullable', 'boolean'],
            'events.*.whatsapp' => ['nullable', 'boolean'],
            'templates' => ['nullable', 'array'],
            'templates.work_order_created' => ['nullable', 'string', 'max:1000'],
            'templates.work_order_completed' => ['nullable', 'string', 'max:1000'],
            'templates.quote_shared' => ['nullable', 'string', 'max:1000'],
            'templates.invoice_issued' => ['nullable', 'string', 'max:1000'],
            'templates.appointment_booked' => ['nullable', 'string', 'max:1000'],
            'templates.appointment_reminder' => ['nullable', 'string', 'max:1000'],
            'templates.low_stock_alert' => ['nullable', 'string', 'max:1000'],
        ]);

        $existing = $tenant->getEffectiveNotificationSettings();
        $incomingChannels = $validated['channels'] ?? [];
        $incomingConfigs = $validated['channel_configs'] ?? [];
        $incomingEvents = $validated['events'] ?? [];

        $mergedEvents = $existing['events'] ?? [];
        foreach ($incomingEvents as $eventKey => $flags) {
            $currentFlags = $mergedEvents[$eventKey] ?? [];
            foreach (['in_app', 'email', 'sms', 'whatsapp'] as $channel) {
                if (array_key_exists($channel, $flags)) {
                    $currentFlags[$channel] = (bool) $flags[$channel];
                }
            }
            $mergedEvents[$eventKey] = $currentFlags;
        }

        $notificationSettings = [
            'channels' => [
                'in_app' => array_key_exists('in_app', $incomingChannels)
                    ? (bool) $incomingChannels['in_app']
                    : (bool) ($existing['channels']['in_app'] ?? true),
                'email' => array_key_exists('email', $incomingChannels)
                    ? (bool) $incomingChannels['email']
                    : (bool) ($existing['channels']['email'] ?? false),
                'sms' => array_key_exists('sms', $incomingChannels)
                    ? (bool) $incomingChannels['sms']
                    : (bool) ($existing['channels']['sms'] ?? false),
                'whatsapp' => array_key_exists('whatsapp', $incomingChannels)
                    ? (bool) $incomingChannels['whatsapp']
                    : (bool) ($existing['channels']['whatsapp'] ?? false),
            ],
            'channel_configs' => [
                'email_recipient' => array_key_exists('email_recipient', $incomingConfigs)
                    ? (string) ($incomingConfigs['email_recipient'] ?? '')
                    : (string) ($existing['channel_configs']['email_recipient'] ?? ''),
                'whatsapp_phone' => array_key_exists('whatsapp_phone', $incomingConfigs)
                    ? (string) ($incomingConfigs['whatsapp_phone'] ?? '')
                    : (string) ($existing['channel_configs']['whatsapp_phone'] ?? ''),
            ],
            'events' => $mergedEvents,
        ];

        $updatePayload = [
            'notification_settings' => $notificationSettings,
        ];

        if (! empty($validated['templates'])) {
            $updatePayload['notification_templates'] = array_merge(
                $tenant->notification_templates ?? [],
                $validated['templates']
            );
        }

        $tenant->update($updatePayload);

        return back()->with('success', __('settings.notifications.saved_successfully'));
    }

    /**
     * Send a test in-app notification to the authenticated user.
     */
    public function sendTest(Request $request): RedirectResponse
    {
        /** @var Tenant $tenant */
        $tenant = $request->user()->tenant;
        $user = $request->user();

        NotificationService::notify(
            tenantId: $tenant->id,
            userId: $user->id,
            type: 'system_test',
            title: __('settings.notifications.test_title'),
            body: __('settings.notifications.test_body', ['time' => now()->format('H:i')]),
            actionUrl: route('settings.notifications.index'),
            actorId: $user->id,
            icon: 'bell'
        );

        return back()->with('success', __('settings.notifications.test_sent_successfully'));
    }

    /**
     * Send a test WhatsApp message using the configured tenant phone.
     */
    public function sendTestWhatsapp(Request $request, WhatsappService $whatsappService): RedirectResponse
    {
        /** @var Tenant $tenant */
        $tenant = $request->user()->tenant;

        $validated = $request->validate([
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $settings = $tenant->getEffectiveNotificationSettings();
        $phone = trim((string) ($validated['phone'] ?: ($settings['channel_configs']['whatsapp_phone'] ?? '')));

        if ($phone === '') {
            return back()->with('error', __('settings.notifications.whatsapp_phone_required'));
        }

        $companyName = $tenant->trade_name ?: $tenant->legal_name ?: config('app.name_ar', 'خدمة برو');
        $message = __('settings.notifications.whatsapp_test_message', [
            'company' => $companyName,
            'time' => now()->format('H:i'),
        ]);

        try {
            $whatsappService->sendText($phone, $message, $tenant->id);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('settings.notifications.whatsapp_test_sent_successfully'));
    }
}
