<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Billing\Plan;
use App\Models\Billing\Subscription;
use App\Models\Center;
use App\Models\Tenant;
use App\Models\TenantWebsite;
use App\Models\User;
use App\Support\CustomDomainValidator;
use App\Support\Permissions;
use App\Support\TenantWebsite\ThemeRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SlugBranchPlanDomainValidationTest extends TestCase
{
    use RefreshDatabase;

    private const VALID_INTERNAL_SLUG_REGEX = '/\A[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\z/';

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ([
            Permissions::WEBSITE_VIEW,
            Permissions::WEBSITE_MANAGE,
            'settings.branches.view',
            'settings.branches.manage',
            'settings.company.manage',
            'centers.view',
            'centers.create',
        ] as $perm) {
            Permission::findOrCreate($perm, 'web');
        }

        Permission::findOrCreate('system.plans.manage', 'admin');
    }

    private function makeTenantAdmin(): array
    {
        $tenant = Tenant::factory()->create(['status' => 'active']);
        $plan = Plan::firstOrCreate(
            ['slug' => 'unlimited-test'],
            [
                'name' => 'Unlimited',
                'name_ar' => 'غير محدودة',
                'name_en' => 'Unlimited',
                'price_monthly' => 0,
                'price_yearly' => 100,
                'trial_days' => 14,
                'limits' => ['max_centers' => 999, 'centers' => 999],
                'is_active' => true,
            ]
        );

        Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'yearly',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addYear(),
            'price' => 100,
        ]);

        $center = Center::factory()->create([
            'tenant_id' => $tenant->id,
            'is_main' => true,
            'is_active' => true,
            'slug' => 'main-'.$tenant->id,
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
        ]);
        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $user->assignRole('super_admin');
        $user->givePermissionTo([
            Permissions::WEBSITE_VIEW,
            Permissions::WEBSITE_MANAGE,
            'centers.view',
            'centers.create',
        ]);

        return [$tenant, $center, $user];
    }

    private function actingAsTenant(User $user): self
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($user->tenant_id);

        return $this->actingAs($user);
    }

    private function makeSystemAdmin(): AdminUser
    {
        return AdminUser::create([
            'name' => 'System Admin',
            'email' => 'sysadmin-slug@example.com',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
            'permissions' => ['system.plans.manage'],
            'is_active' => true,
        ]);
    }

    public function test_branch_slug_handles_english_arabic_symbols_emoji_long_names_and_collisions(): void
    {
        [$tenant, $mainCenter, $user] = $this->makeTenantAdmin();
        $originalTenantSlug = $tenant->slug;
        $originalMainSlug = $mainCenter->slug;

        $cases = [
            ['name_en' => 'Riyadh North Branch', 'name_ar' => 'فرع شمال الرياض'],
            ['name_en' => null, 'name_ar' => 'فرع جدة الرئيسي'],
            ['name_en' => '!!!@@@###', 'name_ar' => null],
            ['name_en' => '🚗🔧🎉', 'name_ar' => null],
            ['name_en' => str_repeat('VeryLongBranchNameForTestingTruncation ', 6), 'name_ar' => null],
            ['name_en' => 'Duplicate Branch Name', 'name_ar' => null],
            ['name_en' => 'Duplicate Branch Name', 'name_ar' => null],
        ];

        $createdSlugs = [];

        foreach ($cases as $index => $payload) {
            $response = $this->actingAsTenant($user)->post(route('settings.branches.store'), [
                'name_ar' => $payload['name_ar'],
                'name_en' => $payload['name_en'],
                'center_type' => 'branch',
                'is_active' => true,
            ]);

            $response->assertRedirect(route('settings.branches'));

            $latest = Center::where('tenant_id', $tenant->id)->orderByDesc('id')->firstOrFail();
            $this->assertMatchesRegularExpression(self::VALID_INTERNAL_SLUG_REGEX, $latest->slug);
            $this->assertLessThanOrEqual(107, strlen($latest->slug));
            $this->assertNotContains($latest->slug, $createdSlugs, "Case {$index} produced duplicate slug.");
            $createdSlugs[] = $latest->slug;
        }

        $this->assertSame($originalTenantSlug, $tenant->fresh()->slug);
        $this->assertSame($originalMainSlug, $mainCenter->fresh()->slug);
    }

    public function test_plan_slug_validation_on_create_and_update_preserves_subscriptions(): void
    {
        $admin = $this->makeSystemAdmin();

        // Invalid slugs rejected on create
        foreach (['PRO', 'pro plan', 'pro/1', '-pro', 'pro-', 'pro--1', 'خطة'] as $badSlug) {
            $this->actingAs($admin, 'admin')
                ->post(route('system.plans.store'), [
                    'name_ar' => 'باقة اختبار',
                    'name_en' => 'Test Plan',
                    'slug' => $badSlug,
                    'price_yearly' => 199,
                    'trial_days' => 14,
                ])
                ->assertSessionHasErrors('slug');
        }

        // Valid slug accepted on create
        $this->actingAs($admin, 'admin')
            ->post(route('system.plans.store'), [
                'name_ar' => 'باقة احترافية سنوية',
                'name_en' => 'Pro Yearly',
                'slug' => 'pro-yearly-2026',
                'price_yearly' => 499,
                'trial_days' => 14,
            ])
            ->assertRedirect(route('system.plans.index'));

        $plan = Plan::where('slug', 'pro-yearly-2026')->firstOrFail();

        $tenant = Tenant::factory()->create();
        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'yearly',
            'starts_at' => now(),
            'ends_at' => now()->addYear(),
            'price' => 499,
        ]);

        // Invalid slug rejected on update
        $this->actingAs($admin, 'admin')
            ->put(route('system.plans.update', $plan), [
                'name_ar' => 'باقة احترافية سنوية',
                'name_en' => 'Pro Yearly',
                'slug' => 'Invalid Slug',
                'price_yearly' => 499,
                'trial_days' => 14,
            ])
            ->assertSessionHasErrors('slug');

        // Valid slug change succeeds and subscription stays linked by plan_id
        $this->actingAs($admin, 'admin')
            ->put(route('system.plans.update', $plan), [
                'name_ar' => 'باقة احترافية سنوية',
                'name_en' => 'Pro Yearly',
                'slug' => 'pro-annual-v2',
                'price_yearly' => 499,
                'trial_days' => 14,
            ])
            ->assertRedirect(route('system.plans.index'));

        $this->assertSame('pro-annual-v2', $plan->fresh()->slug);
        $this->assertSame($plan->id, $subscription->fresh()->plan_id);
    }

    public function test_custom_domain_validation_normalization_uniqueness_and_non_activation(): void
    {
        [$tenantA, , $userA] = $this->makeTenantAdmin();
        [$tenantB, , $userB] = $this->makeTenantAdmin();

        $basePayload = [
            'primary_color' => '#2563eb',
            'secondary_color' => '#0ea5e9',
            'status' => TenantWebsite::STATUS_PUBLISHED,
            'theme_key' => ThemeRegistry::DEFAULT_THEME,
        ];

        // Reject protocols, paths, query strings, ports, IPs, and reserved platform domains
        foreach ([
            'https://myworkshop.com',
            'http://myworkshop.com',
            'myworkshop.com/portal',
            'myworkshop.com?foo=bar',
            'myworkshop.com:8080',
            '192.168.1.1',
            'localhost',
            'khidmh.pro',
            'sub.khidmh.pro',
        ] as $invalidDomain) {
            $this->actingAsTenant($userA)
                ->put(route('settings.website.update'), array_merge($basePayload, [
                    'custom_domain' => $invalidDomain,
                ]))
                ->assertSessionHasErrors('custom_domain');
        }

        // Valid domain with uppercase & www. preserves canonical host, normalizes case, and stays pending
        $this->actingAsTenant($userA)
            ->put(route('settings.website.update'), array_merge($basePayload, [
                'custom_domain' => 'WWW.MyWorkshop.SA',
            ]))
            ->assertSessionHasNoErrors();

        $websiteA = TenantWebsite::where('tenant_id', $tenantA->id)->firstOrFail();
        $this->assertSame('www.myworkshop.sa', $websiteA->custom_domain);
        $this->assertSame('pending', $websiteA->custom_domain_status);
        $this->assertNull($websiteA->custom_domain_verified_at);

        // Tenant B cannot claim the same normalized canonical domain
        $this->actingAsTenant($userB)
            ->put(route('settings.website.update'), array_merge($basePayload, [
                'custom_domain' => 'www.myworkshop.sa',
            ]))
            ->assertSessionHasErrors('custom_domain');

        $websiteB = TenantWebsite::where('tenant_id', $tenantB->id)->first();
        $this->assertNull($websiteB?->custom_domain);

        // Changing an existing verified domain resets verification back to pending
        $websiteA->update([
            'custom_domain_status' => 'verified',
            'custom_domain_verified_at' => now(),
        ]);

        $this->actingAsTenant($userA)
            ->put(route('settings.website.update'), array_merge($basePayload, [
                'custom_domain' => 'myworkshop.sa',
            ]))
            ->assertSessionHasNoErrors();

        $websiteA->refresh();
        $this->assertSame('myworkshop.sa', $websiteA->custom_domain);
        $this->assertSame('pending', $websiteA->custom_domain_status);
        $this->assertNull($websiteA->custom_domain_verified_at);

        // Removing custom_domain resets status to none and clears verified_at
        $websiteA->update([
            'custom_domain_status' => 'verified',
            'custom_domain_verified_at' => now(),
        ]);

        $this->actingAsTenant($userA)
            ->put(route('settings.website.update'), array_merge($basePayload, [
                'custom_domain' => '',
            ]))
            ->assertSessionHasNoErrors();

        $websiteA->refresh();
        $this->assertNull($websiteA->custom_domain);
        $this->assertSame('none', $websiteA->custom_domain_status);
        $this->assertNull($websiteA->custom_domain_verified_at);

        // IDN normalization unit check when intl is enabled
        if (function_exists('idn_to_ascii')) {
            $ascii = CustomDomainValidator::normalizeAndValidate('www.münchen.de');
            $this->assertSame('www.xn--mnchen-3ya.de', $ascii);
        }
    }

    public function test_system_trial_plan_slug_is_protected_while_other_fields_remain_editable(): void
    {
        $admin = $this->makeSystemAdmin();

        $trialPlan = Plan::firstOrCreate(
            ['slug' => 'trial'],
            [
                'name' => 'الباقة التجريبية',
                'name_ar' => 'الباقة التجريبية',
                'name_en' => 'Trial Plan',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'trial_days' => 14,
                'is_active' => true,
            ]
        );

        $tenant = Tenant::factory()->create();
        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $trialPlan->id,
            'status' => 'trialing',
            'billing_cycle' => 'yearly',
            'starts_at' => now(),
            'ends_at' => now()->addDays(14),
            'trial_ends_at' => now()->addDays(14),
            'price' => 0,
        ]);

        // Changing system plan slug 'trial' must fail validation
        $this->actingAs($admin, 'admin')
            ->put(route('system.plans.update', $trialPlan), [
                'name_ar' => 'تجريبية معدلة',
                'name_en' => 'Updated Trial',
                'slug' => 'custom-trial',
                'price_yearly' => 0,
                'trial_days' => 21,
            ])
            ->assertSessionHasErrors('slug');

        $this->assertSame('trial', $trialPlan->fresh()->slug);

        // Updating allowed fields while keeping slug = 'trial' succeeds
        $this->actingAs($admin, 'admin')
            ->put(route('system.plans.update', $trialPlan), [
                'name_ar' => 'تجريبية معدلة',
                'name_en' => 'Updated Trial',
                'slug' => 'trial',
                'description_ar' => 'وصف محدث',
                'price_yearly' => 0,
                'trial_days' => 21,
            ])
            ->assertRedirect(route('system.plans.index'));

        $trialPlan->refresh();
        $this->assertSame('trial', $trialPlan->slug);
        $this->assertSame('تجريبية معدلة', $trialPlan->name_ar);
        $this->assertSame(21, $trialPlan->trial_days);
        $this->assertSame($trialPlan->id, $subscription->fresh()->plan_id);

        // Deleting protected system plan is blocked even without active subscriptions
        $subscription->delete();
        $this->actingAs($admin, 'admin')
            ->delete(route('system.plans.destroy', $trialPlan))
            ->assertSessionHas('error');

        $this->assertNotNull(Plan::find($trialPlan->id));
    }
}
