<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationSettingsController extends Controller
{
    /**
     * Display the notification settings management page.
     */
    public function index(Request $request): Response
    {
        /** @var Tenant $tenant */
        $tenant = $request->user()->tenant;

        $settings = $tenant->getEffectiveNotificationSettings();

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

        return Inertia::render('Settings/Notifications/Index', [
            'settings' => $settings,
            'templates' => $templates,
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

        $notificationSettings = [
            'channels' => [
                'in_app' => (bool) ($validated['channels']['in_app'] ?? true),
                'email' => (bool) ($validated['channels']['email'] ?? false),
                'sms' => (bool) ($validated['channels']['sms'] ?? false),
                'whatsapp' => (bool) ($validated['channels']['whatsapp'] ?? false),
            ],
            'channel_configs' => [
                'email_recipient' => (string) ($validated['channel_configs']['email_recipient'] ?? ''),
                'whatsapp_phone' => (string) ($validated['channel_configs']['whatsapp_phone'] ?? ''),
            ],
            'events' => $validated['events'],
        ];

        $tenant->update([
            'notification_settings' => $notificationSettings,
            'notification_templates' => array_merge(
                $tenant->notification_templates ?? [],
                $validated['templates'] ?? []
            ),
        ]);

        return back()->with('success', __('settings.notifications.saved_successfully'));
    }

    /**
     * Send a test notification to the authenticated user.
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
}
