<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Models\Center;
use App\Models\Credits\TenantWhatsappBalance;
use App\Models\Credits\WhatsappUsageLog;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Messaging\WhatsappService;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class NotificationSettingsTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Center $center;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
        Permission::firstOrCreate(['name' => Permissions::COMPANY_MANAGE, 'guard_name' => 'web']);

        $this->admin = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
            'is_active' => true,
        ]);
        $this->admin->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);
        $this->admin->givePermissionTo(Permissions::COMPANY_MANAGE);
    }

    public function test_notifications_page_renders_with_whatsapp_summary_and_logs(): void
    {
        TenantWhatsappBalance::create([
            'tenant_id' => $this->tenant->id,
            'balance' => 150,
            'total_purchased' => 200,
            'total_used' => 50,
        ]);

        WhatsappUsageLog::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'phone_number' => '966500000001',
            'message_type' => 'template',
            'template_name' => 'work_order_created',
            'credits_used' => 1,
            'provider' => 'whatsapp_cloud',
            'status' => 'sent',
        ]);

        $this->actingAs($this->admin)
            ->get(route('app.addons.whatsapp.index', ['tab' => 'logs']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/Notifications/Index')
                ->where('initialTab', 'logs')
                ->where('isWhatsappAddon', true)
                ->where('backHref', route('app.addons'))
                ->where('whatsappSummary.balance', 150)
                ->where('whatsappSummary.total_used', 50)
                ->has('whatsappLogs', 1)
            );
    }

    public function test_company_notifications_tab_redirects_to_whatsapp_addon_templates_tab(): void
    {
        $this->actingAs($this->admin)
            ->get(route('settings.company', ['tab' => 'notifications']))
            ->assertRedirect(route('app.addons.whatsapp.index', ['tab' => 'templates']));
    }

    public function test_can_update_whatsapp_channels_events_and_templates(): void
    {
        $this->actingAs($this->admin)
            ->put(route('app.addons.whatsapp.update'), [
                'channels' => [
                    'in_app' => true,
                    'email' => false,
                    'sms' => false,
                    'whatsapp' => true,
                ],
                'channel_configs' => [
                    'email_recipient' => 'ops@example.com',
                    'whatsapp_phone' => '966501234567',
                ],
                'events' => [
                    'work_order_created' => [
                        'in_app' => true,
                        'email' => false,
                        'sms' => false,
                        'whatsapp' => true,
                    ],
                ],
                'templates' => [
                    'work_order_created' => 'مرحباً {customer_name}، تم فتح كرت عمل {work_order_number}',
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->tenant->refresh();
        $this->assertTrue($this->tenant->isNotificationChannelEnabled('whatsapp'));
        $this->assertEquals('966501234567', $this->tenant->notification_settings['channel_configs']['whatsapp_phone']);
        $this->assertStringContainsString('{work_order_number}', $this->tenant->getNotificationTemplate('work_order_created'));

        // Updating internal notifications must preserve WhatsApp/SMS channels and templates
        $this->actingAs($this->admin)
            ->put(route('settings.notifications.update'), [
                'channels' => [
                    'in_app' => true,
                    'email' => true,
                ],
                'channel_configs' => [
                    'email_recipient' => 'alerts@example.com',
                ],
                'events' => [
                    'work_order_created' => [
                        'in_app' => true,
                        'email' => true,
                    ],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->tenant->refresh();
        $this->assertTrue($this->tenant->isNotificationChannelEnabled('whatsapp'));
        $this->assertTrue($this->tenant->isNotificationChannelEnabled('email'));
        $this->assertEquals('966501234567', $this->tenant->notification_settings['channel_configs']['whatsapp_phone']);
        $this->assertEquals('alerts@example.com', $this->tenant->notification_settings['channel_configs']['email_recipient']);
        $this->assertTrue((bool) $this->tenant->notification_settings['events']['work_order_created']['whatsapp']);
        $this->assertTrue((bool) $this->tenant->notification_settings['events']['work_order_created']['email']);
        $this->assertStringContainsString('{work_order_number}', $this->tenant->getNotificationTemplate('work_order_created'));
    }

    public function test_send_test_whatsapp_requires_phone_and_dispatches_via_whatsapp_service(): void
    {
        $this->actingAs($this->admin)
            ->post(route('app.addons.whatsapp.test-whatsapp'), ['phone' => ''])
            ->assertRedirect()
            ->assertSessionHas('error');

        $mock = Mockery::mock(WhatsappService::class);
        $mock->shouldReceive('sendText')
            ->once()
            ->withArgs(fn (string $to, string $msg, ?int $tenantId) => $to === '966509998877' && $tenantId === $this->tenant->id)
            ->andReturn(['success' => true, 'message_id' => 'wamid.test']);
        $this->app->instance(WhatsappService::class, $mock);

        $this->actingAs($this->admin)
            ->post(route('app.addons.whatsapp.test-whatsapp'), ['phone' => '966509998877'])
            ->assertRedirect()
            ->assertSessionHas('success');
    }
}
