<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\InternalNotification;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NotificationsIndexCrudTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Tenant $otherTenant;

    protected Center $center;

    protected User $user;

    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create(['status' => 'active']);
        $this->otherTenant = Tenant::factory()->create(['status' => 'active']);
        $this->center = Center::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);
        $this->user->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);

        $this->otherUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);
        $this->otherUser->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('app.notifications.index'));
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_sees_only_their_own_tenant_notifications(): void
    {
        InternalNotification::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'type' => 'work_order_created',
            'title' => 'أمر عمل جديد',
            'body' => 'تم فتح أمر عمل رقم 100',
            'icon' => 'clipboard',
        ]);

        // Notification for another user in same tenant
        InternalNotification::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->otherUser->id,
            'type' => 'work_order_created',
            'title' => 'إشعار لمستخدم آخر',
            'body' => 'لا يجب أن يظهر',
            'icon' => 'bell',
        ]);

        // Notification in another tenant
        $otherTenantUser = User::factory()->create(['tenant_id' => $this->otherTenant->id]);
        InternalNotification::create([
            'tenant_id' => $this->otherTenant->id,
            'user_id' => $otherTenantUser->id,
            'type' => 'invoice_created',
            'title' => 'إشعار لمستأجر آخر',
            'icon' => 'receipt',
        ]);

        $response = $this->actingAs($this->user)->get(route('app.notifications.index'));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Notifications/Index')
                ->has('notifications.data', 1)
                ->where('notifications.data.0.title', 'أمر عمل جديد')
            );
    }

    public function test_user_can_mark_single_notification_as_read(): void
    {
        $notification = InternalNotification::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'type' => 'quote_approved',
            'title' => 'اعتماد عرض سعر',
            'icon' => 'check',
        ]);

        $this->assertNull($notification->read_at);

        $response = $this->actingAs($this->user)
            ->postJson(route('app.notifications.read', ['notification' => $notification->id]));

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_cannot_mark_other_user_notification_as_read(): void
    {
        $notification = InternalNotification::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->otherUser->id,
            'type' => 'quote_approved',
            'title' => 'إشعار مستخدم آخر',
            'icon' => 'bell',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('app.notifications.read', ['notification' => $notification->id]));

        $response->assertNotFound();
        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        InternalNotification::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'type' => 'work_order_created',
            'title' => 'إشعار 1',
            'icon' => 'clipboard',
        ]);

        InternalNotification::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'type' => 'invoice_created',
            'title' => 'إشعار 2',
            'icon' => 'receipt',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('app.notifications.read-all'));

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertSame(
            0,
            InternalNotification::where('tenant_id', $this->tenant->id)
                ->where('user_id', $this->user->id)
                ->whereNull('read_at')
                ->count()
        );
    }
}
