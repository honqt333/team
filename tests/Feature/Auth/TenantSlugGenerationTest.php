<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\Billing\Plan;
use App\Models\Center;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\TenantWebsite;
use App\Models\User;
use App\Support\TenantSlugGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use Tests\TestCase;

class TenantSlugGenerationTest extends TestCase
{
    use RefreshDatabase;

    private const ROUTE_TENANT_REGEX = '/\A(?!(?:app|system|dashboard|login|logout|register|password|forgot-password|reset-password|verify-email|confirm-password|two-factor|2fa|locale|phone|lang|landing-preview|view|impersonate|impersonate-stop|up|storage|livewire|invitations)$)[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\z/';

    protected function setUp(): void
    {
        parent::setUp();

        Plan::firstOrCreate(
            ['slug' => 'trial'],
            [
                'name' => 'Trial',
                'name_ar' => 'تجريبية',
                'name_en' => 'Trial',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'trial_days' => 14,
                'is_active' => true,
            ]
        );
    }

    private function registerTenant(string $companyName, string $email, string $phone, array $extra = []): Tenant
    {
        Auth::logout();

        $normalizedPhone = '+966'.ltrim($phone, '0');

        $this->withSession([
            'phone_verified' => true,
            'verified_phone_number' => $normalizedPhone,
        ]);

        $response = $this->post('/register', array_merge([
            'company_name' => $companyName,
            'first_name' => 'Ahmad',
            'last_name' => 'Ali',
            'email' => $email,
            'phone' => $phone,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => 'on',
        ], $extra));

        $response->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('email', $email)->firstOrFail();

        return Tenant::findOrFail($user->tenant_id);
    }

    private function assertValidRouteSlug(string $slug): void
    {
        $this->assertMatchesRegularExpression(self::ROUTE_TENANT_REGEX, $slug);
        $this->assertMatchesRegularExpression(TenantSlugGenerator::ROUTE_PATTERN, $slug);
        $this->assertLessThanOrEqual(63, strlen($slug));
        $this->assertSame(strtolower($slug), $slug);
        $this->assertFalse(str_starts_with($slug, '-'));
        $this->assertFalse(str_ends_with($slug, '-'));
        $this->assertNotContains($slug, TenantSlugGenerator::RESERVED_SLUGS);
    }

    public function test_english_company_name_generates_valid_route_slug(): void
    {
        $tenant = $this->registerTenant('Al Najah Auto Care', 'en@example.com', '0511111111');

        $this->assertValidRouteSlug($tenant->slug);
        $this->assertStringStartsWith('al-najah-auto-care-', $tenant->slug);
    }

    public function test_arabic_company_name_generates_valid_route_slug(): void
    {
        $tenant = $this->registerTenant('ورشة النجاح لصيانة السيارات', 'ar@example.com', '0511111112');

        $this->assertValidRouteSlug($tenant->slug);
    }

    public function test_special_symbols_only_company_name_falls_back_safely(): void
    {
        $tenant = $this->registerTenant('!!!***---///', 'sym@example.com', '0511111113');

        $this->assertValidRouteSlug($tenant->slug);
        $this->assertStringStartsWith(TenantSlugGenerator::DEFAULT_BASE.'-', $tenant->slug);
    }

    public function test_emoji_only_company_name_falls_back_safely(): void
    {
        $tenant = $this->registerTenant('🚗🔧🎉🚀', 'emoji@example.com', '0511111114');

        $this->assertValidRouteSlug($tenant->slug);
        $this->assertStringStartsWith(TenantSlugGenerator::DEFAULT_BASE.'-', $tenant->slug);
    }

    public function test_very_long_company_name_is_truncated_within_63_chars_without_trailing_hyphen(): void
    {
        $longName = str_repeat('Super Long Automotive Service Center ', 5);
        $tenant = $this->registerTenant($longName, 'long@example.com', '0511111115');

        $this->assertValidRouteSlug($tenant->slug);
        $this->assertLessThanOrEqual(63, strlen($tenant->slug));
    }

    public function test_slug_collision_is_resolved_uniquely_and_retries_bounded(): void
    {
        $tenantA = $this->registerTenant('Duplicate Workshop', 'dup1@example.com', '0511111116');
        $tenantB = $this->registerTenant('Duplicate Workshop', 'dup2@example.com', '0511111117');

        $this->assertNotSame($tenantA->slug, $tenantB->slug);
        $this->assertValidRouteSlug($tenantA->slug);
        $this->assertValidRouteSlug($tenantB->slug);

        // Deterministic collision retry test
        $attempts = 0;
        $resolved = TenantSlugGenerator::generate('Collision Co', function () use (&$attempts): bool {
            $attempts++;

            return $attempts < 3;
        });

        $this->assertSame(3, $attempts);
        $this->assertValidRouteSlug($resolved);

        // Bounded failure when all attempts collide
        $this->expectException(RuntimeException::class);
        TenantSlugGenerator::generate('Always Collides', static fn (): bool => true, 3);
    }

    public function test_reserved_words_are_protected(): void
    {
        foreach (['admin', 'login', 'dashboard', 'system', 'api', 'page', 'sanctum', 'app'] as $reserved) {
            $slug = TenantSlugGenerator::generate($reserved);
            $this->assertValidRouteSlug($slug);
            $this->assertNotSame($reserved, $slug);
            $this->assertStringStartsWith(TenantSlugGenerator::DEFAULT_BASE.'-', $slug);
        }
    }

    public function test_tenant_relations_continue_using_tenant_id_and_existing_slugs_remain_unchanged(): void
    {
        $existing = Tenant::factory()->create(['slug' => 'legacy-existing-slug']);

        $newTenant = $this->registerTenant('Modern Garage', 'rel@example.com', '0511111118');

        $this->assertSame('legacy-existing-slug', $existing->fresh()->slug);

        $center = Center::where('tenant_id', $newTenant->id)->firstOrFail();
        $user = User::where('tenant_id', $newTenant->id)->firstOrFail();

        $this->assertSame($newTenant->id, $center->tenant_id);
        $this->assertSame($newTenant->id, $user->tenant_id);
        $this->assertSame($center->id, $user->current_center_id);
        $this->assertNotNull($newTenant->subscriptions()->first());
    }

    public function test_registered_tenant_public_site_and_portal_work_when_website_is_published_and_404_when_not(): void
    {
        $tenant = $this->registerTenant('ورشة الأمل الحديثة', 'pub@example.com', '0511111119');
        Auth::logout();

        // Without a published TenantWebsite, the public site and portal must 404.
        $this->get('/'.$tenant->slug)->assertNotFound();
        $this->get('/'.$tenant->slug.'/portal/login')->assertNotFound();

        TenantWebsite::factory()
            ->for($tenant)
            ->published()
            ->withContact()
            ->create();

        $this->get('/'.$tenant->slug)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/Site/Index')
                ->where('site.tenant_slug', $tenant->slug)
            );

        $this->get('/'.$tenant->slug.'/portal/login')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/Portal/Login')
                ->where('tenant.slug', $tenant->slug)
            );
    }

    public function test_registration_cannot_hijack_another_tenants_slug_or_leak_other_tenants_data(): void
    {
        $tenantB = Tenant::factory()->create(['slug' => 'protected-tenant-b']);
        TenantWebsite::factory()->for($tenantB)->published()->withContact()->create();
        $secretServiceB = Service::factory()->create([
            'tenant_id' => $tenantB->id,
            'name_ar' => 'خدمة سرية للمستأجر ب',
            'name_en' => 'Secret Service Tenant B',
            'is_active' => true,
            'type' => Service::TYPE_INTERNAL,
        ]);

        // Attempt to pass another tenant's slug in registration payload
        $tenantA = $this->registerTenant('Tenant A Workshop', 'iso@example.com', '0511111120', [
            'slug' => 'protected-tenant-b',
        ]);
        Auth::logout();

        $this->assertNotSame('protected-tenant-b', $tenantA->slug);
        $this->assertSame('protected-tenant-b', $tenantB->fresh()->slug);

        TenantWebsite::factory()->for($tenantA)->published()->withContact()->create();

        $this->get('/'.$tenantA->slug)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/Site/Index')
                ->where('site.tenant_slug', $tenantA->slug)
                ->where('services', fn ($services) => collect($services)->pluck('id')->doesntContain($secretServiceB->id))
            );
    }

    public function test_newly_reserved_platform_segments_do_not_hit_tenant_site_and_unknown_valid_slug_returns_404(): void
    {
        foreach (['admin', 'api', 'page', 'email', 'reports', 'sanctum', 'portal', 'site', 'userprofile'] as $reserved) {
            $tenant = Tenant::factory()->create(['slug' => $reserved]);
            TenantWebsite::factory()->for($tenant)->published()->create();

            $response = $this->get('/'.$reserved);
            $this->assertStringNotContainsString(
                'Public/Site/Index',
                (string) $response->getContent(),
                "Reserved segment '/{$reserved}' unexpectedly matched the public tenant site."
            );
        }

        // Valid slug format that does not exist in DB must return 404
        $this->get('/non-existent-workshop-999xyz')->assertNotFound();

        // System routes remain reachable and unshadowed
        $this->get('/login')->assertOk();
        $this->get('/admin/login')->assertOk();
        $this->get('/register')->assertOk();
    }
}
