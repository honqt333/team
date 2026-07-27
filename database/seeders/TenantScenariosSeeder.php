<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Billing\Plan;
use App\Models\Billing\Subscription;
use App\Models\Center;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

/**
 * Test-fixture seeder: creates one tenant per subscription lifecycle
 * scenario so the /system/billing and /system/tenants panels can be
 * exercised without manual setup.
 *
 * Scenarios produced (deterministic slugs → idempotent re-runs):
 *   1. new-tenant         — Just registered. Tenant status = active,
 *                            no subscription row yet. Visible in
 *                            "Trial Available" CTA in /system/billing.
 *   2. trial-just-ended   — Tenant was on the Trial plan, trial_ends_at
 *                            is in the past, subscription is still
 *                            status=trialing (so the panel can show the
 *                            "trial expired" banner). Tenant status
 *                            = readonly.
 *   3. active-subscriber  — Tenant is paying. Subscription status =
 *                            active, ends_at 30 days in the future,
 *                            auto_renew = true. Tenant status = active.
 *   4. expired-subscriber — Tenant's subscription ended in the past.
 *                            status = expired, ends_at in the past.
 *                            Tenant status = readonly.
 *   5. bonus: trial-ending-soon — trial_ends_at 2 days away. Useful
 *                            for the "trial ends soon" notification.
 *
 * Login credentials (all 5 users share the same password):
 *   new+tenant@example.test / password
 *   trial+ended@example.test / password
 *   active+sub@example.test / password
 *   expired+sub@example.test / password
 *   trial+ending@example.test / password
 *
 * Run: php artisan db:seed --class=TenantScenariosSeeder
 */
class TenantScenariosSeeder extends Seeder
{
    public function run(): void
    {
        // Reset Spatie team cache so role lookups after the seed
        // don't leak the previous tenant's team id.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $pro = Plan::where('slug', 'pro')->firstOrFail();
        $basic = Plan::where('slug', 'basic')->firstOrFail();
        $trial = Plan::where('slug', 'trial')->firstOrFail();

        $this->seedNewTenant();
        $this->seedTrialJustEnded($trial);
        $this->seedActiveSubscriber($pro);
        $this->seedExpiredSubscriber($basic);
        $this->seedTrialEndingSoon($trial);

        $this->command->info('✅ Tenant scenarios seeded (5 tenants, 5 users, 5 centers, 4 subscriptions).');
        $this->command->info('   Default password for all scenario users: "password".');
    }

    /**
     * Scenario 1 — brand-new tenant, no subscription yet.
     * Tenant is active but the billing panel should show
     * "Choose a plan" / "Start trial" CTA.
     */
    private function seedNewTenant(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'new-tenant'],
            [
                'name' => 'ورشة الجديد (New Tenant)',
                'status' => 'active',
                'legal_name' => 'ورشة الجديد للخدمات',
                'trade_name' => 'New Tenant Workshop',
                'phone' => '+966500000001',
                'email' => 'new+tenant@example.test',
            ]
        );

        $center = $this->createCenter($tenant, 'new-tenant-main', 'الفرع الرئيسي');
        $this->createAdminUser($tenant, $center, 'new+tenant@example.test', 'New Tenant Admin');
    }

    /**
     * Scenario 2 — trial that already expired.
     * Tenant status flipped to "readonly" by the lifecycle job;
     * subscription row kept at status=trialing with trial_ends_at
     * in the past so the panel can show "Trial expired" copy and
     * an "Upgrade" CTA.
     */
    private function seedTrialJustEnded(Plan $trial): void
    {
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'trial-just-ended'],
            [
                'name' => 'ورشة انتهت تجربتها (Trial Ended)',
                'status' => 'readonly',
                'legal_name' => 'ورشة انتهت تجربتها',
                'trade_name' => 'Trial Ended Workshop',
                'phone' => '+966500000002',
                'email' => 'trial+ended@example.test',
                'trial_ends_at' => now()->subDays(3),
                'suspension_reason' => 'trial_expired',
            ]
        );

        $center = $this->createCenter($tenant, 'trial-ended-main', 'الفرع الرئيسي');

        // Subscription row that represents the expired trial.
        Subscription::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'plan_id' => $trial->id,
                'status' => 'trialing',
            ],
            [
                'billing_cycle' => 'monthly',
                'starts_at' => now()->subDays(17),
                'trial_ends_at' => now()->subDays(3),
                'ends_at' => null,
                'price' => 0,
                'auto_renew' => false,
            ]
        );

        $this->createAdminUser($tenant, $center, 'trial+ended@example.test', 'Trial Ended Admin');
    }

    /**
     * Scenario 3 — paying customer, healthy subscription.
     * 30 days of runway left, auto-renew on, latest payment recorded.
     */
    private function seedActiveSubscriber(Plan $pro): void
    {
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'active-subscriber'],
            [
                'name' => 'ورشة المشترك النشط (Active Subscriber)',
                'status' => 'active',
                'legal_name' => 'ورشة المشترك النشط',
                'trade_name' => 'Active Subscriber Workshop',
                'phone' => '+966500000003',
                'email' => 'active+sub@example.test',
            ]
        );

        $center = $this->createCenter($tenant, 'active-sub-main', 'الفرع الرئيسي');

        Subscription::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'plan_id' => $pro->id,
                'status' => 'active',
            ],
            [
                'billing_cycle' => 'monthly',
                'starts_at' => now()->subDays(5),
                'ends_at' => now()->addDays(25),
                'trial_ends_at' => null,
                'price' => $pro->price_monthly,
                'auto_renew' => true,
                'payment_method' => 'moyasar',
            ]
        );

        $this->createAdminUser($tenant, $center, 'active+sub@example.test', 'Active Subscriber Admin');
    }

    /**
     * Scenario 4 — subscription that ran out.
     * Tenant status = readonly, subscription status = expired,
     * ends_at in the past, auto_renew off. The billing panel
     * should show "Subscription expired" with a "Renew" CTA.
     */
    private function seedExpiredSubscriber(Plan $basic): void
    {
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'expired-subscriber'],
            [
                'name' => 'ورشة منتهية الاشتراك (Expired Subscriber)',
                'status' => 'readonly',
                'legal_name' => 'ورشة منتهية الاشتراك',
                'trade_name' => 'Expired Subscriber Workshop',
                'phone' => '+966500000004',
                'email' => 'expired+sub@example.test',
                'suspension_reason' => 'subscription_expired',
            ]
        );

        $center = $this->createCenter($tenant, 'expired-sub-main', 'الفرع الرئيسي');

        Subscription::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'plan_id' => $basic->id,
                'status' => 'expired',
            ],
            [
                'billing_cycle' => 'monthly',
                'starts_at' => now()->subDays(60),
                'ends_at' => now()->subDays(5),
                'trial_ends_at' => null,
                'cancelled_at' => now()->subDays(6),
                'price' => $basic->price_monthly,
                'auto_renew' => false,
                'payment_method' => 'bank_transfer',
            ]
        );

        $this->createAdminUser($tenant, $center, 'expired+sub@example.test', 'Expired Subscriber Admin');
    }

    /**
     * Bonus scenario — trial that ends in 2 days.
     * Useful for testing the "trial ending soon" banner / email.
     */
    private function seedTrialEndingSoon(Plan $trial): void
    {
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'trial-ending-soon'],
            [
                'name' => 'ورشة قاربت تجربتها على الانتهاء (Trial Ending)',
                'status' => 'active',
                'legal_name' => 'ورشة قاربت تجربتها على الانتهاء',
                'trade_name' => 'Trial Ending Soon Workshop',
                'phone' => '+966500000005',
                'email' => 'trial+ending@example.test',
                'trial_ends_at' => now()->addDays(2),
            ]
        );

        $center = $this->createCenter($tenant, 'trial-ending-main', 'الفرع الرئيسي');

        Subscription::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'plan_id' => $trial->id,
                'status' => 'trialing',
            ],
            [
                'billing_cycle' => 'monthly',
                'starts_at' => now()->subDays(12),
                'trial_ends_at' => now()->addDays(2),
                'ends_at' => null,
                'price' => 0,
                'auto_renew' => false,
            ]
        );

        $this->createAdminUser($tenant, $center, 'trial+ending@example.test', 'Trial Ending Admin');
    }

    // -----------------------------------------------------------------
    // Shared helpers
    // -----------------------------------------------------------------

    private function createCenter(Tenant $tenant, string $slug, string $name): Center
    {
        return Center::firstOrCreate(
            ['slug' => "{$slug}-{$tenant->id}"],
            [
                'tenant_id' => $tenant->id,
                'name' => $name,
                'is_active' => true,
                'is_main' => true,
            ]
        );
    }

    private function createAdminUser(Tenant $tenant, Center $center, string $email, string $name): User
    {
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'tenant_id' => $tenant->id,
                'current_center_id' => $center->id,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        // Attach to center if not already attached
        if (! $user->centers()->where('center_id', $center->id)->exists()) {
            $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);
        }

        // Set Spatie team context to this tenant, then assign the
        // tenant-scoped super_admin role. The role was created by
        // TenantSetupService::booted() when the tenant was first
        // persisted, so it must already exist.
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

        $superAdmin = Role::where('name', 'super_admin')
            ->where('tenant_id', $tenant->id)
            ->first();

        if ($superAdmin && ! $user->hasRole('super_admin')) {
            $user->assignRole($superAdmin);
        }

        return $user;
    }
}
