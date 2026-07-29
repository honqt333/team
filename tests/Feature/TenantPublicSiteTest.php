<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\TenantWebsite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 6 — Public tenant site.
 *
 * The public site is the marketing surface that lives at
 * /{tenant-slug}. These tests exercise the URL contract,
 * tenant isolation, and active-only filtering that the spec
 * calls out:
 *
 *   1. published site renders
 *   2. unpublished / draft / missing site 404s
 *   3. tenant B's services / centers do NOT appear on
 *      tenant A's page
 *   4. inactive services / centers are filtered out
 *
 * Auth is NOT exercised here — the public site is anonymous.
 * The companion test file TenantWebsiteSettingsTest covers
 * the admin-side mutations.
 */
class TenantPublicSiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_tenant_site_renders(): void
    {
        $tenant = Tenant::factory()->create(['slug' => 'demo-center']);
        TenantWebsite::factory()
            ->for($tenant)
            ->published()
            ->withContact()
            ->create();

        $this->get('/demo-center')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/Site/Index')
                ->where('site.tenant_slug', 'demo-center')
                ->where('site.site_name', fn ($name) => is_string($name) && $name !== '')
            );
    }

    public function test_draft_tenant_site_returns_404(): void
    {
        $tenant = Tenant::factory()->create(['slug' => 'draft-tenant']);
        TenantWebsite::factory()->for($tenant)->create(); // default status = draft

        $this->get('/draft-tenant')->assertNotFound();
    }

    public function test_unpublished_tenant_site_returns_404(): void
    {
        $tenant = Tenant::factory()->create(['slug' => 'unpublished-tenant']);
        TenantWebsite::factory()->for($tenant)->unpublished()->create();

        $this->get('/unpublished-tenant')->assertNotFound();
    }

    public function test_missing_tenant_website_row_returns_404(): void
    {
        // No website row at all — should still 404, not crash.
        Tenant::factory()->create(['slug' => 'no-website']);

        $this->get('/no-website')->assertNotFound();
    }

    public function test_unknown_slug_returns_404(): void
    {
        $this->get('/no-such-tenant')->assertNotFound();
    }

    public function test_only_active_services_are_shown(): void
    {
        $tenant = Tenant::factory()->create(['slug' => 'svc-test']);
        TenantWebsite::factory()->for($tenant)->published()->create();

        Service::factory()->create([
            'tenant_id' => $tenant->id,
            'name_ar' => 'خدمة نشطة',
            'name_en' => 'Active Service',
            'is_active' => true,
            'type' => Service::TYPE_INTERNAL,
        ]);

        Service::factory()->create([
            'tenant_id' => $tenant->id,
            'name_ar' => 'خدمة معطلة',
            'name_en' => 'Inactive Service',
            'is_active' => false,
            'type' => Service::TYPE_INTERNAL,
        ]);

        // Non-internal service (package) should NOT be shown even if active.
        Service::factory()->create([
            'tenant_id' => $tenant->id,
            'name_ar' => 'باكج',
            'name_en' => 'Package',
            'is_active' => true,
            'type' => Service::TYPE_PACKAGE,
        ]);

        $this->get('/svc-test')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/Site/Index')
                ->where('services', fn ($services) => collect($services)->pluck('name')->all() === ['Active Service'])
            );
    }

    public function test_only_active_centers_are_shown(): void
    {
        $tenant = Tenant::factory()->create(['slug' => 'centers-test']);
        TenantWebsite::factory()->for($tenant)->published()->create();

        $active = Center::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
            'name_ar' => 'فرع نشط',
            'name_en' => 'Active Center',
        ]);

        Center::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => false,
            'name_ar' => 'فرع معطل',
            'name_en' => 'Inactive Center',
        ]);

        $this->get('/centers-test')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/Site/Index')
                ->where('centers', fn ($centers) => collect($centers)->pluck('id')->all() === [$active->id])
            );
    }

    public function test_tenant_a_does_not_leak_services_from_tenant_b(): void
    {
        $tenantA = Tenant::factory()->create(['slug' => 'tenant-a']);
        $tenantB = Tenant::factory()->create(['slug' => 'tenant-b']);

        TenantWebsite::factory()->for($tenantA)->published()->create();
        TenantWebsite::factory()->for($tenantB)->published()->create();

        Service::factory()->create([
            'tenant_id' => $tenantA->id,
            'name_ar' => 'خدمة A',
            'name_en' => 'A Service',
            'is_active' => true,
            'type' => Service::TYPE_INTERNAL,
        ]);

        Service::factory()->create([
            'tenant_id' => $tenantB->id,
            'name_ar' => 'خدمة B',
            'name_en' => 'B Service',
            'is_active' => true,
            'type' => Service::TYPE_INTERNAL,
        ]);

        $this->get('/tenant-a')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('services', fn ($services) => collect($services)->pluck('name')->all() === ['A Service'])
            );

        $this->get('/tenant-b')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('services', fn ($services) => collect($services)->pluck('name')->all() === ['B Service'])
            );
    }

    public function test_tenant_a_does_not_leak_centers_from_tenant_b(): void
    {
        $tenantA = Tenant::factory()->create(['slug' => 'a-centers']);
        $tenantB = Tenant::factory()->create(['slug' => 'b-centers']);

        TenantWebsite::factory()->for($tenantA)->published()->create();
        TenantWebsite::factory()->for($tenantB)->published()->create();

        $centerA = Center::factory()->create([
            'tenant_id' => $tenantA->id,
            'is_active' => true,
            'name_ar' => 'مركز A',
            'name_en' => 'A Center',
        ]);

        $centerB = Center::factory()->create([
            'tenant_id' => $tenantB->id,
            'is_active' => true,
            'name_ar' => 'مركز B',
            'name_en' => 'B Center',
        ]);

        $this->get('/a-centers')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('centers', fn ($centers) => collect($centers)->pluck('id')->all() === [$centerA->id])
            );

        $this->get('/b-centers')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('centers', fn ($centers) => collect($centers)->pluck('id')->all() === [$centerB->id])
            );
    }

    public function test_site_includes_tenant_specific_branding(): void
    {
        $tenant = Tenant::factory()->create(['slug' => 'branded']);
        TenantWebsite::factory()->for($tenant)->published()->create([
            'site_name' => 'مركز البطل',
            'primary_color' => '#ff0000',
            'secondary_color' => '#00ff00',
            'tagline' => 'tagline-payload',
        ]);

        $this->get('/branded')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('site.site_name', 'مركز البطل')
                ->where('site.primary_color', '#ff0000')
                ->where('site.secondary_color', '#00ff00')
                ->where('site.tagline', 'tagline-payload')
            );
    }

    public function test_invalid_color_in_db_falls_back_to_default(): void
    {
        $tenant = Tenant::factory()->create(['slug' => 'bad-color']);
        // Bypass the controller's validation by writing directly.
        TenantWebsite::factory()->for($tenant)->published()->create([
            'primary_color' => 'not-a-hex',
            'secondary_color' => '<script>',
        ]);

        $this->get('/bad-color')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('site.primary_color', '#4f46e5') // default
                ->where('site.secondary_color', '#0ea5e9') // default
            );
    }

    public function test_unauthorized_tenant_indicates_are_not_in_response(): void
    {
        // Even if data exists, the public payload must NOT
        // include work orders, appointments, customers, etc.
        // We assert the response shape stays narrow.
        $tenant = Tenant::factory()->create(['slug' => 'shape-test']);
        TenantWebsite::factory()->for($tenant)->published()->create();

        $this->get('/shape-test')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/Site/Index')
                ->has('site')
                ->has('services')
                ->has('centers')
                ->missing('workOrders')
                ->missing('appointments')
                ->missing('customers')
                ->missing('employees')
            );
    }

    public function test_reserved_slugs_do_not_hit_tenant_site(): void
    {
        // The /{tenant} catch-all must NOT shadow the platform's
        // top-level routes. We seed a published site whose slug
        // collides with reserved words to prove the regex keeps
        // them out of the tenant route.
        //
        // We assert on the response content (not just status)
        // because some platform routes return 200 (login page)
        // while others return 302 (dashboard) or 404 (app).
        // What matters is that the response is the platform's,
        // NOT the Public/Site/Index component.
        foreach (['login', 'register', 'dashboard', 'app', 'system', 'view', 'phone', 'locale', '2fa', 'landing-preview'] as $slug) {
            $tenant = Tenant::factory()->create(['slug' => $slug]);
            TenantWebsite::factory()->for($tenant)->published()->create();

            $response = $this->get('/'.$slug);
            $content = $response->getContent();

            $this->assertStringNotContainsString(
                'Public/Site/Index',
                $content,
                "Reserved slug '/{$slug}' returned the public tenant site — it should fall through to a platform route."
            );
        }
    }
}
