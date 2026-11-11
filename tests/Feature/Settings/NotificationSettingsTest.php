<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Models\Center;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class NotificationSettingsTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Center $center;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
            'is_active' => true,
        ]);
        $this->user->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);

        $role = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web',
            'tenant_id' => $this->tenant->id,
        ]);
        $this->user->assignRole($role);
    }

    public function test_notification_settings_page_can_be_rendered(): void
    {
        $this->actingAs($this->user)
            ->get(route('settings.integrations'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Settings/Integrations'));

        $response = $this->actingAs($this->user)
            ->get(route('settings.notifications.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Settings/Notifications/Index')
            ->has('settings')
            ->has('templates')
        );
    }

    public function test_notification_settings_can_be_updated(): void
    {
        $payload = [
            'channels' => [
                'in_app' => true,
                'email' => true,
                'sms' => false,
                'whatsapp' => true,
            ],
            'channel_configs' => [
                'email_recipient' => 'alerts@example.com',
                'whatsapp_phone' => '+966500000000',
            ],
            'events' => [
                'work_order_created' => [
                    'in_app' => false,
                    'email' => true,
                    'sms' => false,
                    'whatsapp' => true,
                ],
                'invoice_issued' => [
                    'in_app' => true,
                    'email' => true,
                    'sms' => false,
                    'whatsapp' => false,
                ],
            ],
            'templates' => [
                'work_order_created' => 'تم فتح كرت عمل جديد رقم {work_order_number}',
                'invoice_issued' => 'فاتورة جديدة برقم {invoice_number}',
            ],
        ];

        $response = $this->actingAs($this->user)
            ->put(route('settings.notifications.update'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->tenant->refresh();
        $settings = $this->tenant->getEffectiveNotificationSettings();

        $this->assertTrue($settings['channels']['email']);
        $this->assertTrue($settings['channels']['whatsapp']);
        $this->assertEquals('alerts@example.com', $settings['channel_configs']['email_recipient']);
        $this->assertFalse($settings['events']['work_order_created']['in_app']);
        $this->assertTrue($settings['events']['work_order_created']['whatsapp']);
        $this->assertEquals(
            'تم فتح كرت عمل جديد رقم {work_order_number}',
            $this->tenant->getNotificationTemplate('work_order_created')
        );
    }

    public function test_send_test_notification_creates_internal_notification(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('settings.notifications.test'));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('internal_notifications', [
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'type' => 'system_test',
        ]);
    }

    public function test_notification_service_respects_tenant_preferences(): void
    {
        // 1. When event is disabled for in_app, notify returns null and does not persist
        $this->tenant->update([
            'notification_settings' => [
                'channels' => ['in_app' => true],
                'events' => [
                    'work_order_created' => ['in_app' => false],
                ],
            ],
        ]);

        $result = NotificationService::notify(
            tenantId: $this->tenant->id,
            userId: $this->user->id,
            type: 'work_order_created',
            title: 'Work Order Alert',
        );

        $this->assertNull($result);
        $this->assertDatabaseMissing('internal_notifications', [
            'tenant_id' => $this->tenant->id,
            'type' => 'work_order_created',
        ]);

        // 2. When channel is globally disabled
        $this->tenant->update([
            'notification_settings' => [
                'channels' => ['in_app' => false],
                'events' => [
                    'work_order_completed' => ['in_app' => true],
                ],
            ],
        ]);

        $result2 = NotificationService::notify(
            tenantId: $this->tenant->id,
            userId: $this->user->id,
            type: 'work_order_completed',
            title: 'Complete Alert',
        );

        $this->assertNull($result2);
        $this->assertDatabaseMissing('internal_notifications', [
            'tenant_id' => $this->tenant->id,
            'type' => 'work_order_completed',
        ]);
    }

    public function test_unauthenticated_user_is_redirected(): void
    {
        $response = $this->get(route('settings.notifications.index'));
        $response->assertRedirect(route('login'));
    }
}
