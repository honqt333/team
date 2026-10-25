<?php

declare(strict_types=1);

namespace Tests\Feature\System;

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PerformanceBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HelpSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function createSystemAdmin(): User
    {
        app(PerformanceBaselineSeeder::class)->run();
        $tenant = Tenant::first();

        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        if (! $user) {
            $user = User::where('tenant_id', $tenant->id)->first();
            $user->update(['is_system_admin' => true]);
        }

        return $user;
    }

    public function test_guest_cannot_access_system_help_settings(): void
    {
        $response = $this->get('/system/settings/help');
        $response->assertRedirect('/login');
    }

    public function test_regular_user_cannot_access_system_help_settings(): void
    {
        app(PerformanceBaselineSeeder::class)->run();
        $tenant = Tenant::first();

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'is_system_admin' => false,
        ]);

        $response = $this->actingAs($user)->get('/system/settings/help');
        $response->assertForbidden();
    }

    public function test_system_admin_can_view_help_settings(): void
    {
        $admin = $this->createSystemAdmin();

        $response = $this->actingAs($admin)->get('/system/settings/help');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('System/Help/Index')
            ->has('settings.contact_whatsapp')
            ->has('settings.video_tutorials')
            ->has('settings.categories')
            ->has('settings.topics')
            ->has('settings.faqs')
            ->has('settings.quick_start_items')
        );
    }

    public function test_system_admin_can_update_help_settings(): void
    {
        $admin = $this->createSystemAdmin();

        $updateData = [
            'contact_whatsapp' => '+966500000000',
            'contact_whatsapp_enabled' => true,
            'contact_email' => 'custom-support@carag.sa',
            'contact_phone' => '8001234567',
            'contact_hours' => 'الأحد - الخميس: 9:00 ص - 6:00 م',
            'contact_ticket_url' => 'https://support.carag.sa/tickets/new',
            'video_tutorials' => [
                [
                    'id' => 'v1',
                    'title' => 'فيديو تجريبي جديد',
                    'category' => 'عام',
                    'duration' => '02:00',
                    'description' => 'شرح تجريبي',
                    'video_url' => 'https://youtube.com/watch?v=123',
                ],
            ],
            'categories' => [
                [
                    'id' => 'general',
                    'name' => 'إرشادات عامة',
                ],
            ],
            'topics' => [
                [
                    'id' => 101,
                    'category' => 'general',
                    'stepNumber' => '01',
                    'title' => 'عنوان المقال التجريبي',
                    'summary' => 'ملخص المقال',
                    'steps' => ['خطوة 1', 'خطوة 2'],
                    'tip' => 'ملاحظة هامة',
                    'link' => ['label' => 'الرابط', 'url' => '/app/dashboard'],
                ],
            ],
            'faqs' => [
                [
                    'q' => 'سؤال تجريبي؟',
                    'a' => 'إجابة تجريبية.',
                ],
            ],
            'quick_start_items' => [
                [
                    'title' => 'بطاقة تجريبية',
                    'desc' => 'وصف البطاقة',
                    'icon' => 'IconFileText',
                    'bg' => 'bg-blue-50 dark:bg-blue-900/30',
                    'text' => 'text-blue-600 dark:text-blue-400',
                    'targetCategory' => 'general',
                ],
            ],
        ];

        $response = $this->actingAs($admin)->put('/system/settings/help', $updateData);

        $response->assertRedirect('/system/settings/help');
        $response->assertSessionHas('success');

        // Check that /app/help receives updated settings
        $helpResponse = $this->actingAs($admin)->get('/app/help');
        $helpResponse->assertOk();
        $helpResponse->assertInertia(fn ($page) => $page
            ->where('contactSupport.whatsapp', '+966500000000')
            ->where('contactSupport.email', 'custom-support@carag.sa')
            ->where('videoTutorials.0.title', 'فيديو تجريبي جديد')
            ->where('categories.0.name', 'إرشادات عامة')
            ->where('topics.0.title', 'عنوان المقال التجريبي')
            ->where('faqs.0.q', 'سؤال تجريبي؟')
            ->where('quickStartItems.0.title', 'بطاقة تجريبية')
        );
    }

    public function test_system_admin_can_reset_help_settings_to_defaults(): void
    {
        $admin = $this->createSystemAdmin();

        $response = $this->actingAs($admin)->post('/system/settings/help/reset');

        $response->assertRedirect('/system/settings/help');
        $response->assertSessionHas('success');
    }

    public function test_system_admin_can_fetch_video_metadata_automatically(): void
    {
        $admin = $this->createSystemAdmin();

        Http::fake([
            'https://www.youtube.com/watch?v=*' => Http::response(
                '<html><head><title>Carag Video Tutorial - YouTube</title></head><body>"lengthSeconds":"185"</body></html>',
                200
            ),
        ]);

        $response = $this->actingAs($admin)->postJson('/system/settings/help/fetch-video-meta', [
            'url' => 'https://www.youtube.com/watch?v=abcdefghijk',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'duration' => '03:05',
            'duration_seconds' => 185,
            'title' => 'Carag Video Tutorial',
        ]);
    }
}
