<?php

declare(strict_types=1);

namespace Tests\Feature\System;

use App\Http\Controllers\System\WebsiteSettingsController;
use App\Models\AdminUser;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PerformanceBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WebsiteSettingsTest extends TestCase
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

    private function createAdminUser(string $role = 'super_admin'): AdminUser
    {
        return AdminUser::create([
            'name' => 'Admin '.$role,
            'email' => 'admin-'.$role.'@example.test',
            'password' => bcrypt('password'),
            'role' => $role,
            'is_active' => true,
        ]);
    }

    public function test_guest_cannot_access_system_website_settings(): void
    {
        $response = $this->get('/system/settings/website');
        $response->assertRedirect('/login');
    }

    public function test_regular_user_cannot_access_system_website_settings(): void
    {
        app(PerformanceBaselineSeeder::class)->run();
        $tenant = Tenant::first();

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'is_system_admin' => false,
        ]);

        $response = $this->actingAs($user)->get('/system/settings/website');
        $response->assertForbidden();
    }

    public function test_system_admin_can_view_website_settings(): void
    {
        $admin = $this->createSystemAdmin();

        $response = $this->actingAs($admin)->get('/system/settings/website');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('System/Website/Index')
            ->has('settings.website_title_ar')
            ->has('settings.website_title_en')
            ->has('settings.landing_hero_title_ar')
            ->has('settings.landing_features_list')
            ->has('settings.landing_pricing_title_ar')
        );
    }

    public function test_non_super_admin_cannot_read_or_preview_raw_scripts(): void
    {
        $admin = $this->createAdminUser('admin');

        Setting::set('website.scripts.header', '<script>saved_header()</script>', 'website');
        Setting::set('website.scripts.footer', '<script>saved_footer()</script>', 'website');
        Setting::set('website.chatbot.script', '<script>saved_chatbot()</script>', 'website');
        WebsiteSettingsController::flushSettingsCache();

        // The settings screen must not hand raw script bodies to an
        // actor who is not allowed to change them; the Integrations
        // tab hides the fields and the preview pane strips them.
        $this->actingAs($admin, 'admin')
            ->get('/system/settings/website')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('System/Website/Index')
                ->where('canManageScripts', false)
                ->where('settings.website_scripts_header', '')
                ->where('settings.website_scripts_footer', '')
                ->where('settings.website_chatbot_script', '')
            );

        $this->get('/landing-preview?preview=1')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('isAdminPreview', true)
                ->where('settings.scripts_header', '')
                ->where('settings.scripts_footer', '')
                ->where('settings.chatbot_script', '')
            );

        // The public page still receives the saved scripts — only the
        // management surface is restricted, execution is unchanged.
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/LandingPreview')
                ->where('settings.scripts_header', '<script>saved_header()</script>')
                ->where('settings.scripts_footer', '<script>saved_footer()</script>')
                ->where('settings.chatbot_script', '<script>saved_chatbot()</script>')
            );
    }

    public function test_super_admin_can_read_raw_scripts_on_the_settings_screen(): void
    {
        $superAdmin = $this->createAdminUser('super_admin');

        Setting::set('website.scripts.header', '<script>saved_header()</script>', 'website');
        Setting::set('website.scripts.footer', '<script>saved_footer()</script>', 'website');
        Setting::set('website.chatbot.script', '<script>saved_chatbot()</script>', 'website');
        WebsiteSettingsController::flushSettingsCache();

        $this->actingAs($superAdmin, 'admin')
            ->get('/system/settings/website')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('System/Website/Index')
                ->where('canManageScripts', true)
                ->where('settings.website_scripts_header', '<script>saved_header()</script>')
                ->where('settings.website_scripts_footer', '<script>saved_footer()</script>')
                ->where('settings.website_chatbot_script', '<script>saved_chatbot()</script>')
            );
    }

    public function test_system_admin_can_update_website_settings(): void
    {
        $admin = $this->createSystemAdmin();

        $payload = [
            'settings' => [
                'website_title_ar' => 'منصة خدمة برو التجريبية',
                'website_title_en' => 'Khidmh Pro Live Platform Test',
                'website_contact_email' => 'admin@khidmh.test',
                'website_primary_color' => '#4f46e5',
                'website_announcement_bg_color' => '#6366f1',
                'website_announcement_text_color' => '#ffffff',
                'landing_hero_title_ar' => 'إدارة حديثة ومتطورة',
                'landing_hero_title_en' => 'Modern Workshop Management',
                'landing_features_enabled' => '1',
            ],
        ];

        $response = $this->actingAs($admin)->put('/system/settings/website', $payload);

        $response->assertSessionHas('success');

        $this->assertSame('منصة خدمة برو التجريبية', Setting::get('website.title.ar'));
        $this->assertSame('Khidmh Pro Live Platform Test', Setting::get('website.title.en'));
        $this->assertSame('admin@khidmh.test', Setting::get('website.contact.email'));
        $this->assertSame('#4f46e5', Setting::get('website.primary.color'));
        $this->assertSame('#6366f1', Setting::get('website.announcement.bg.color'));
    }

    public function test_arbitrary_keys_outside_allowlist_are_ignored(): void
    {
        $admin = $this->createSystemAdmin();

        $payload = [
            'settings' => [
                'website_title_ar' => 'عنوان مسموح',
                'unauthorized_backdoor_key' => 'malicious_value',
                'security_2fa_sms_enabled' => 'forced_true',
            ],
        ];

        $this->actingAs($admin)->put('/system/settings/website', $payload);

        $this->assertSame('عنوان مسموح', Setting::get('website.title.ar'));
        $this->assertNull(Setting::get('unauthorized.backdoor.key'));
        $this->assertNull(Setting::get('security.2fa.sms.enabled'));
    }

    public function test_upload_image_endpoint_saves_valid_image(): void
    {
        Storage::fake('public');
        $admin = $this->createSystemAdmin();

        $file = UploadedFile::fake()->image('logo.png', 200, 200);

        $response = $this->actingAs($admin)->postJson('/system/settings/website/upload-image', [
            'key' => 'website_logo',
            'image' => $file,
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['url']);

        $storedLogo = Setting::get('website.logo');
        $this->assertNotNull($storedLogo);
        $this->assertStringContainsString('storage/website/', $storedLogo);
    }

    public function test_upload_image_endpoint_rejects_malicious_svg(): void
    {
        Storage::fake('public');
        $admin = $this->createSystemAdmin();

        $maliciousSvg = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert("XSS")</script></svg>';
        $file = UploadedFile::fake()->createWithContent('exploit.svg', $maliciousSvg);

        $response = $this->actingAs($admin)->postJson('/system/settings/website/upload-image', [
            'key' => 'website_logo',
            'image' => $file,
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => __('messages.system.svg_unsafe'),
        ]);
    }

    public function test_public_landing_page_renders_and_uses_cached_settings(): void
    {
        Setting::set('website.title.ar', 'كاراج الرئيسية المحدثة', 'website');
        WebsiteSettingsController::flushSettingsCache();

        $response = $this->get('/');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Public/LandingPreview')
            ->where('settings.title_ar', 'كاراج الرئيسية المحدثة')
        );

        // Verify cache was populated
        $this->assertTrue(Cache::has(WebsiteSettingsController::CACHE_KEY));
    }

    public function test_public_custom_page_renders_successfully(): void
    {
        $customPages = [
            [
                'slug' => 'about-us',
                'title_ar' => 'عن المنصة',
                'title_en' => 'About Us',
                'content_ar' => 'نظام خدمة برو السحابي المتكامل لإدارة الورش ومراكز الصيانة.',
                'content_en' => 'Khidmh Pro cloud system for auto repair workshops.',
            ],
        ];

        Setting::set('website.custom.pages', json_encode($customPages), 'website');
        WebsiteSettingsController::flushSettingsCache();

        $response = $this->get('/page/about-us');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Public/CustomPage')
            ->where('page.slug', 'about-us')
            ->where('page.title_ar', 'عن المنصة')
        );
    }

    public function test_public_custom_page_returns_404_for_unknown_slug(): void
    {
        Setting::set('website.custom.pages', json_encode([]), 'website');
        WebsiteSettingsController::flushSettingsCache();

        $response = $this->get('/page/unknown-page-slug');
        $response->assertNotFound();
    }

    public function test_admin_preview_bypasses_maintenance_mode(): void
    {
        Setting::set('website.maintenance.mode', '1', 'website');
        WebsiteSettingsController::flushSettingsCache();

        // 1. Regular visitor sees maintenance mode without admin preview
        $visitorResponse = $this->get('/');
        $visitorResponse->assertOk();
        $visitorResponse->assertInertia(fn ($page) => $page
            ->where('settings.maintenance_mode', '1')
            ->where('isAdminPreview', false)
        );

        // 2. Admin visitor with ?preview=1 gets admin preview flag
        $admin = $this->createAdminUser('super_admin');
        $adminResponse = $this->actingAs($admin, 'admin')->get('/?preview=1');

        $adminResponse->assertOk();
        $adminResponse->assertInertia(fn ($page) => $page
            ->where('settings.maintenance_mode', '1')
            ->where('isAdminPreview', true)
        );
    }

    public function test_admin_user_activity_is_logged_on_settings_update(): void
    {
        $admin = $this->createAdminUser('super_admin');

        $payload = [
            'settings' => [
                'website_title_ar' => 'تحديث مسجل للنشاط',
            ],
        ];

        $this->actingAs($admin, 'admin')->put('/system/settings/website', $payload);

        $this->assertDatabaseHas('admin_activity_logs', [
            'admin_user_id' => $admin->id,
            'action' => 'update',
            'model_type' => 'WebsiteSettings',
        ]);
    }

    public function test_non_super_admin_cannot_update_custom_scripts(): void
    {
        $admin = $this->createAdminUser('support');

        Setting::set('website.scripts.header', '<script>original()</script>', 'website');
        WebsiteSettingsController::flushSettingsCache();

        $payload = [
            'settings' => [
                'website_title_ar' => 'تعديل العنوان مقبول',
                'website_scripts_header' => '<script>tampered_by_support()</script>',
            ],
        ];

        $this->actingAs($admin, 'admin')->put('/system/settings/website', $payload);

        // A `support` admin holds no website-settings ability at all
        // (see App\Support\SystemPermissions::ROLE_DEFAULTS), so the
        // whole page is refused — not just the script fields.
        //
        // The route declares `system.permission:system.settings.manage`,
        // which the support role does not carry. This used to let the
        // title through while blocking only the scripts.
        $this->assertNull(
            Setting::get('website.title.ar'),
            'A support admin must not reach the website settings at all.'
        );

        // Script remained untouched because role is 'support' (not super_admin)
        $this->assertSame('<script>original()</script>', Setting::get('website.scripts.header'));
    }

    public function test_an_admin_role_can_update_website_settings_but_not_the_custom_scripts(): void
    {
        // The `admin` role carries the website-settings ability, so the
        // ordinary fields go through — while the script fields stay
        // behind the controller's own super_admin guard.
        $admin = $this->createAdminUser('admin');

        Setting::set('website.scripts.header', '<script>original()</script>', 'website');
        WebsiteSettingsController::flushSettingsCache();

        $payload = [
            'settings' => [
                'website_title_ar' => 'تعديل العنوان مقبول',
                'website_scripts_header' => '<script>tampered_by_admin()</script>',
            ],
        ];

        $this->actingAs($admin, 'admin')->put('/system/settings/website', $payload);

        $this->assertSame('تعديل العنوان مقبول', Setting::get('website.title.ar'));
        $this->assertSame('<script>original()</script>', Setting::get('website.scripts.header'));
    }

    public function test_super_admin_can_update_custom_scripts(): void
    {
        $superAdmin = $this->createAdminUser('super_admin');

        $payload = [
            'settings' => [
                'website_scripts_header' => '<script>google_analytics()</script>',
            ],
        ];

        $this->actingAs($superAdmin, 'admin')->put('/system/settings/website', $payload);

        $this->assertSame('<script>google_analytics()</script>', Setting::get('website.scripts.header'));
    }
}
