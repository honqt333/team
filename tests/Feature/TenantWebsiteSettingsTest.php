<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Tenant;
use App\Models\TenantWebsite;
use App\Models\User;
use App\Support\TenantWebsite\ThemeRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Phase 6 — admin-side settings for the tenant public site.
 *
 * Spec coverage:
 *   - settings page is reachable by an authorized user
 *   - cross-tenant mutation is BLOCKED (tenant A's admin
 *     cannot touch tenant B's website)
 *   - color validation rejects non-hex
 *   - social URL validation rejects bad schemes
 *   - theme_key is rejected when not in the allow-list
 *   - status transitions stamp published_at
 *   - file uploads land in the per-tenant folder
 */
class TenantWebsiteSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('settings.website.view', 'web');
        Permission::findOrCreate('settings.website.manage', 'web');
        Permission::findOrCreate('settings.website.delete_image', 'web');
    }

    public function test_settings_page_renders_for_authorized_user(): void
    {
        $user = $this->makeWebsiteManager();

        $this->actingAsWithTeam($user)
            ->get(route('settings.website.edit'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Settings/Website')
                ->has('website')
                ->has('theme_options')
            );
    }

    public function test_settings_page_is_forbidden_for_user_without_view_permission(): void
    {
        $user = $this->makeUser();

        $this->actingAsWithTeam($user)
            ->get(route('settings.website.edit'))
            ->assertForbidden();
    }

    public function test_update_persists_settings(): void
    {
        $user = $this->makeWebsiteManager();

        $this->actingAsWithTeam($user)
            ->put(route('settings.website.update'), [
                'site_name' => 'مركز الاختبار',
                'tagline' => 'شعار تجريبي',
                'primary_color' => '#abcdef',
                'secondary_color' => '#123456',
                'status' => TenantWebsite::STATUS_PUBLISHED,
                'theme_key' => 'default',
                'contact_phone' => '0500000000',
                'contact_email' => 'test@example.com',
            ])
            ->assertRedirect();

        $website = TenantWebsite::where('tenant_id', $user->tenant_id)->first();
        $this->assertNotNull($website);
        $this->assertSame('مركز الاختبار', $website->site_name);
        $this->assertSame('#abcdef', $website->primary_color);
        $this->assertSame('default', $website->theme_key);
        $this->assertNotNull($website->published_at);
    }

    public function test_publish_stamps_published_at(): void
    {
        $user = $this->makeWebsiteManager();

        $this->actingAsWithTeam($user)
            ->put(route('settings.website.update'), [
                'site_name' => 'X',
                'primary_color' => '#000000',
                'secondary_color' => '#ffffff',
                'status' => TenantWebsite::STATUS_PUBLISHED,
                'theme_key' => 'default',
            ])
            ->assertRedirect();

        $this->assertNotNull(
            TenantWebsite::where('tenant_id', $user->tenant_id)->first()->published_at
        );
    }

    public function test_unpublish_clears_published_at(): void
    {
        $user = $this->makeWebsiteManager();
        $tenant = Tenant::find($user->tenant_id);

        TenantWebsite::factory()->for($tenant)->published()->create();

        $this->actingAsWithTeam($user)
            ->put(route('settings.website.update'), [
                'site_name' => 'X',
                'primary_color' => '#000000',
                'secondary_color' => '#ffffff',
                'status' => TenantWebsite::STATUS_UNPUBLISHED,
                'theme_key' => 'default',
            ])
            ->assertRedirect();

        $this->assertNull(
            TenantWebsite::where('tenant_id', $user->tenant_id)->first()->published_at
        );
    }

    public function test_invalid_color_is_rejected(): void
    {
        $user = $this->makeWebsiteManager();

        $response = $this->actingAsWithTeam($user)
            ->put(route('settings.website.update'), [
                'site_name' => 'X',
                'primary_color' => 'red', // not a hex
                'secondary_color' => '#ffffff',
                'status' => TenantWebsite::STATUS_DRAFT,
                'theme_key' => 'default',
            ]);

        $response->assertSessionHasErrors('primary_color');
    }

    public function test_invalid_secondary_color_is_rejected(): void
    {
        $user = $this->makeWebsiteManager();

        $response = $this->actingAsWithTeam($user)
            ->put(route('settings.website.update'), [
                'site_name' => 'X',
                'primary_color' => '#ffffff',
                'secondary_color' => '<script>alert(1)</script>',
                'status' => TenantWebsite::STATUS_DRAFT,
                'theme_key' => 'default',
            ]);

        $response->assertSessionHasErrors('secondary_color');
    }

    public function test_invalid_theme_key_is_rejected(): void
    {
        $user = $this->makeWebsiteManager();

        $response = $this->actingAsWithTeam($user)
            ->put(route('settings.website.update'), [
                'site_name' => 'X',
                'primary_color' => '#ffffff',
                'secondary_color' => '#000000',
                'status' => TenantWebsite::STATUS_DRAFT,
                'theme_key' => 'premium_dark', // not in allow-list
            ]);

        $response->assertSessionHasErrors('theme_key');
    }

    public function test_invalid_social_url_is_rejected(): void
    {
        $user = $this->makeWebsiteManager();

        $response = $this->actingAsWithTeam($user)
            ->put(route('settings.website.update'), [
                'site_name' => 'X',
                'primary_color' => '#ffffff',
                'secondary_color' => '#000000',
                'status' => TenantWebsite::STATUS_DRAFT,
                'theme_key' => 'default',
                'social_links' => [
                    'facebook' => 'not-a-url',
                ],
            ]);

        $response->assertSessionHasErrors('social_links.facebook');
    }

    public function test_valid_https_social_url_passes(): void
    {
        $user = $this->makeWebsiteManager();

        $this->actingAsWithTeam($user)
            ->put(route('settings.website.update'), [
                'site_name' => 'X',
                'primary_color' => '#ffffff',
                'secondary_color' => '#000000',
                'status' => TenantWebsite::STATUS_DRAFT,
                'theme_key' => 'default',
                'social_links' => [
                    'facebook' => 'https://facebook.com/test',
                    'instagram' => 'http://instagram.com/test',
                ],
            ])
            ->assertRedirect();

        $website = TenantWebsite::where('tenant_id', $user->tenant_id)->first();
        $this->assertSame('https://facebook.com/test', $website->social_links['facebook']);
    }

    public function test_logo_upload_stores_in_tenant_folder(): void
    {
        Storage::fake('public');
        $user = $this->makeWebsiteManager();

        $file = UploadedFile::fake()->image('logo.png');

        $this->actingAsWithTeam($user)
            ->put(route('settings.website.update'), [
                'site_name' => 'X',
                'primary_color' => '#ffffff',
                'secondary_color' => '#000000',
                'status' => TenantWebsite::STATUS_DRAFT,
                'theme_key' => 'default',
                'logo' => $file,
            ])
            ->assertRedirect();

        $website = TenantWebsite::where('tenant_id', $user->tenant_id)->first();
        $this->assertNotNull($website->logo_path);
        $this->assertStringStartsWith("tenants/{$user->tenant_id}/website/", $website->logo_path);
        Storage::disk('public')->assertExists($website->logo_path);
    }

    /**
     * Regression guard for the PUT-via-POST upload pattern.
     *
     * The Vue form uses Inertia's transform hook to inject
     * `_method=put` into a multipart/form-data POST request
     * instead of sending a real PUT. This is necessary because
     * PHP's multipart parser does not always populate
     * $_FILES for PUT requests depending on the SAPI / web
     * server (php-fpm + nginx is a known offender).
     *
     * This test simulates the EXACT shape of the request the
     * browser sends, so a future refactor that switches to
     * a real PUT will fail the test and force a deliberate
     * re-evaluation of the upload pipeline.
     */
    public function test_upload_via_post_with_method_spoofing(): void
    {
        Storage::fake('public');
        $user = $this->makeWebsiteManager();

        $logo = UploadedFile::fake()->image('logo.png');
        $cover = UploadedFile::fake()->image('cover.jpg');

        $response = $this->actingAsWithTeam($user)
            ->post(route('settings.website.update'), [
                '_method' => 'put',
                'site_name' => 'X',
                'primary_color' => '#ffffff',
                'secondary_color' => '#000000',
                'status' => TenantWebsite::STATUS_DRAFT,
                'theme_key' => 'default',
                'logo' => $logo,
                'cover' => $cover,
            ]);

        $response->assertRedirect();
        $website = TenantWebsite::where('tenant_id', $user->tenant_id)->first();
        $this->assertNotNull($website->logo_path, 'logo_path was not set — multipart upload via _method=put failed');
        $this->assertNotNull($website->cover_path, 'cover_path was not set — multipart upload via _method=put failed');
        Storage::disk('public')->assertExists($website->logo_path);
        Storage::disk('public')->assertExists($website->cover_path);
    }

    public function test_user_in_tenant_a_cannot_edit_tenant_b_website(): void
    {
        // Two tenants, two websites. We seed tenant A's user
        // with manage permission. The update form's tenant
        // identity is auth()->user()->tenant_id, so even if
        // the request body tries to point to tenant B, the
        // controller will only touch tenant A's row.
        $userA = $this->makeWebsiteManager();
        $tenantB = Tenant::factory()->create(['slug' => 'b-tenant']);
        $websiteB = TenantWebsite::factory()->for($tenantB)->published()->create([
            'site_name' => 'B original',
        ]);

        $this->actingAsWithTeam($userA)
            ->put(route('settings.website.update'), [
                'site_name' => 'B hacked',
                'primary_color' => '#ffffff',
                'secondary_color' => '#000000',
                'status' => TenantWebsite::STATUS_PUBLISHED,
                'theme_key' => 'default',
            ])
            ->assertRedirect();

        // B was NOT touched.
        $this->assertSame('B original', $websiteB->fresh()->site_name);
    }

    public function test_admin_can_see_own_tenant_website_only(): void
    {
        $userA = $this->makeWebsiteManager();
        $tenantB = Tenant::factory()->create(['slug' => 'isolation-b']);
        TenantWebsite::factory()->for($tenantB)->published()->create([
            'site_name' => 'B site',
        ]);

        $this->actingAsWithTeam($userA)
            ->get(route('settings.website.edit'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('website.tenant_id', $userA->tenant_id)
            );
    }

    public function test_delete_image_endpoint_clears_path(): void
    {
        Storage::fake('public');
        $user = $this->makeWebsiteManager();
        $tenant = Tenant::find($user->tenant_id);

        $path = "tenants/{$tenant->id}/website/logo_test.png";
        Storage::disk('public')->put($path, 'fake');

        TenantWebsite::factory()->for($tenant)->create([
            'logo_path' => $path,
        ]);

        $this->actingAsWithTeam($user)
            ->deleteJson(route('settings.website.image.delete'), ['key' => 'logo'])
            ->assertOk();

        $website = TenantWebsite::where('tenant_id', $tenant->id)->first();
        $this->assertNull($website->logo_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_delete_image_rejects_unknown_key(): void
    {
        $user = $this->makeWebsiteManager();

        $this->actingAsWithTeam($user)
            ->deleteJson(route('settings.website.image.delete'), ['key' => 'evil'])
            ->assertStatus(422);
    }

    public function test_user_without_manage_permission_cannot_update(): void
    {
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
        ]);
        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        // Give view but NOT manage.
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $user->givePermissionTo('settings.website.view');

        $this->actingAsWithTeam($user)
            ->put(route('settings.website.update'), [
                'site_name' => 'X',
                'primary_color' => '#ffffff',
                'secondary_color' => '#000000',
                'status' => TenantWebsite::STATUS_DRAFT,
                'theme_key' => 'default',
            ])
            ->assertForbidden();
    }

    public function test_first_visit_creates_website_row(): void
    {
        $user = $this->makeWebsiteManager();

        $this->assertDatabaseMissing('tenant_websites', [
            'tenant_id' => $user->tenant_id,
        ]);

        $this->actingAsWithTeam($user)
            ->get(route('settings.website.edit'))
            ->assertOk();

        $this->assertDatabaseHas('tenant_websites', [
            'tenant_id' => $user->tenant_id,
            'status' => TenantWebsite::STATUS_DRAFT,
            'theme_key' => ThemeRegistry::DEFAULT_THEME,
        ]);
    }

    public function test_theme_registry_normalizes_unknown_keys(): void
    {
        $this->assertSame('default', ThemeRegistry::normalize('premium_dark'));
        $this->assertSame('default', ThemeRegistry::normalize(null));
        $this->assertSame('default', ThemeRegistry::normalize(123));
        $this->assertSame('default', ThemeRegistry::normalize(''));
        $this->assertSame('default', ThemeRegistry::normalize('default'));
        $this->assertSame('default', ThemeRegistry::normalize('  default  '));
    }

    public function test_theme_registry_returns_options_for_select(): void
    {
        $options = ThemeRegistry::optionsForSelect();
        $this->assertNotEmpty($options);

        foreach ($options as $opt) {
            $this->assertArrayHasKey('value', $opt);
            $this->assertArrayHasKey('label_ar', $opt);
            $this->assertArrayHasKey('label_en', $opt);
            $this->assertContains($opt['value'], ThemeRegistry::allowedKeys());
        }
    }

    public function test_hex_validator_rejects_non_hex(): void
    {
        $this->assertNull(TenantWebsite::safeHex('red'));
        $this->assertNull(TenantWebsite::safeHex('#xyz'));
        $this->assertNull(TenantWebsite::safeHex('#1234'));
        $this->assertNull(TenantWebsite::safeHex('<script>'));
        $this->assertNull(TenantWebsite::safeHex(null));
        $this->assertSame('#abc', TenantWebsite::safeHex('#abc'));
        $this->assertSame('#abcdef', TenantWebsite::safeHex('#abcdef'));
        $this->assertSame('#ABCDEF', TenantWebsite::safeHex('#ABCDEF'));
    }

    // ─────────────────────────────────────────────────────────────────
    // Test helpers
    // ─────────────────────────────────────────────────────────────────

    private function makeWebsiteManager(): User
    {
        $tenant = Tenant::factory()->create(['slug' => 'manage-tenant']);
        $user = $this->makeUser($tenant->id);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $user->givePermissionTo([
            'settings.website.view',
            'settings.website.manage',
        ]);
        $user->refresh();

        return $user;
    }

    private function makeUser(?int $tenantId = null): User
    {
        $tenant = $tenantId ? Tenant::find($tenantId) : Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
        ]);
        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        return $user;
    }
}
