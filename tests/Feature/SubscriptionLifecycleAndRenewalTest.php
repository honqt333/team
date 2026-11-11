<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\BankTransferReceipt;
use App\Models\Billing\DunningConfig;
use App\Models\Billing\Plan;
use App\Models\Billing\Subscription;
use App\Models\Billing\SubscriptionInvoice;
use App\Models\Billing\SubscriptionPayment;
use App\Models\Center;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Billing\SubscriptionLifecycleService;
use App\Services\Billing\SubscriptionRenewalService;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SubscriptionLifecycleAndRenewalTest extends TestCase
{
    use RefreshDatabase;

    protected Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plan = Plan::create([
            'name' => 'Pro Plan',
            'name_ar' => 'الباقة الاحترافية',
            'name_en' => 'Pro Plan',
            'slug' => 'pro-lifecycle-'.uniqid(),
            'price_monthly' => 200,
            'price_yearly' => 2000,
            'is_active' => true,
        ]);
    }

    protected function createTenantUser(array $tenantAttrs = []): array
    {
        $this->seed(PermissionsSeeder::class);

        $tenant = Tenant::factory()->create(array_merge([
            'status' => 'active',
        ], $tenantAttrs));

        $center = Center::factory()->create(['tenant_id' => $tenant->id]);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
            'two_factor_confirmed_at' => now(),
        ]);
        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $superAdminRole = Role::firstOrCreate([
            'tenant_id' => $tenant->id,
            'name' => 'super_admin',
            'guard_name' => 'web',
        ]);
        $user->assignRole($superAdminRole);

        return [$tenant, $center, $user];
    }

    public function test_renewal_carries_over_remaining_days_when_subscription_is_still_active(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-11 10:00:00'));
        [$tenant] = $this->createTenantUser();

        // Active subscription with 20 days remaining
        $existingEndsAt = Carbon::now()->addDays(20);
        Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $this->plan->id,
            'status' => 'active',
            'billing_cycle' => 'yearly',
            'starts_at' => Carbon::now()->subDays(345),
            'ends_at' => $existingEndsAt,
            'price' => 2000,
        ]);

        $pendingRenewal = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $this->plan->id,
            'status' => 'pending',
            'billing_cycle' => 'yearly',
            'starts_at' => Carbon::now(),
            'ends_at' => Carbon::now()->addYear(),
            'price' => 2000,
        ]);

        $lifecycle = app(SubscriptionLifecycleService::class);
        $activated = $lifecycle->activateSubscription($pendingRenewal);

        // Must preserve remaining 20 days + add 1 full year from old ends_at
        $expectedEnd = $existingEndsAt->copy()->addYear();
        $this->assertTrue($activated->ends_at->equalTo($expectedEnd));
        $this->assertSame('active', $activated->status);
        $this->assertSame('active', $tenant->fresh()->status);

        Carbon::setTestNow();
    }

    public function test_renewal_starts_from_activation_date_when_previous_subscription_is_expired(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-11 10:00:00'));
        [$tenant] = $this->createTenantUser(['status' => 'expired']);

        // Expired 5 days ago (even within grace period)
        Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $this->plan->id,
            'status' => 'expired',
            'billing_cycle' => 'yearly',
            'starts_at' => Carbon::now()->subYear()->subDays(5),
            'ends_at' => Carbon::now()->subDays(5),
            'price' => 2000,
        ]);

        $pendingRenewal = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $this->plan->id,
            'status' => 'pending',
            'billing_cycle' => 'yearly',
            'starts_at' => Carbon::now()->subDay(),
            'ends_at' => Carbon::now()->addYear()->subDay(),
            'price' => 2000,
        ]);

        $lifecycle = app(SubscriptionLifecycleService::class);
        $activated = $lifecycle->activateSubscription($pendingRenewal);

        // Must start from today (now()) and add 1 year, NOT backdated to old ends_at
        $expectedEnd = Carbon::now()->addYear();
        $this->assertTrue($activated->starts_at->equalTo(Carbon::now()));
        $this->assertTrue($activated->ends_at->equalTo($expectedEnd));
        $this->assertSame('active', $tenant->fresh()->status);

        Carbon::setTestNow();
    }

    public function test_tenant_can_operate_during_configured_grace_period_and_is_blocked_afterwards(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-11 10:00:00'));
        Setting::set(SubscriptionLifecycleService::SETTING_GRACE_PERIOD_DAYS, '5', 'subscription');

        [$tenant, $center, $user] = $this->createTenantUser();

        // Subscription expired 2 days ago (within 5-day grace period)
        $sub = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $this->plan->id,
            'status' => 'active',
            'billing_cycle' => 'yearly',
            'starts_at' => Carbon::now()->subYear()->subDays(2),
            'ends_at' => Carbon::now()->subDays(2),
            'price' => 2000,
        ]);

        $lifecycle = $tenant->fresh()->getSubscriptionLifecycle();
        $this->assertSame(SubscriptionLifecycleService::STATE_GRACE_PERIOD, $lifecycle['state']);
        $this->assertTrue($lifecycle['can_operate']);
        $this->assertTrue($lifecycle['is_in_grace_period']);
        $this->assertSame(3, $lifecycle['grace_days_remaining']);

        // During grace period, POST write operations to app routes succeed (not blocked by EnsureTenantActive 403)
        $response = $this->actingAs($user)
            ->post('/app/customers', [
                'name' => 'Grace Period Customer',
                'phone' => '0551234567',
            ]);
        $this->assertNotEquals(403, $response->getStatusCode());

        // Move forward 4 days (now 6 days past ends_at > 5 day grace period)
        Carbon::setTestNow(Carbon::parse('2026-11-15 10:00:00'));

        $expiredLifecycle = $tenant->fresh()->getSubscriptionLifecycle();
        $this->assertSame(SubscriptionLifecycleService::STATE_EXPIRED, $expiredLifecycle['state']);
        $this->assertFalse($expiredLifecycle['can_operate']);
        $this->assertFalse($expiredLifecycle['is_in_grace_period']);

        // Past grace period, normal POST write operations are blocked with 403
        $blockedResponse = $this->actingAs($user)
            ->post('/app/customers', [
                'name' => 'Blocked Customer',
                'phone' => '0559876543',
            ]);
        $blockedResponse->assertStatus(403);

        // Billing routes remain accessible so the expired tenant can still pay/renew!
        $billingPage = $this->actingAs($user)->get('/app/settings/billing');
        $billingPage->assertOk();

        $calcResponse = $this->actingAs($user)->postJson('/app/settings/billing/calculate', [
            'plan_id' => $this->plan->id,
            'billing_cycle' => 'yearly',
        ]);
        $calcResponse->assertOk();

        Carbon::setTestNow();
    }

    public function test_trial_state_is_distinct_and_expires_without_paid_grace_period(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-11 10:00:00'));
        Setting::set(SubscriptionLifecycleService::SETTING_GRACE_PERIOD_DAYS, '7', 'subscription');

        [$tenant] = $this->createTenantUser([
            'status' => 'trial',
            'trial_ends_at' => Carbon::now()->addDays(10),
        ]);

        $trialPlan = Plan::firstOrCreate(
            ['slug' => 'trial'],
            [
                'name' => 'Trial',
                'name_ar' => 'تجربة',
                'name_en' => 'Trial',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'trial_days' => 14,
                'is_active' => true,
            ]
        );

        Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $trialPlan->id,
            'status' => 'trialing',
            'billing_cycle' => 'yearly',
            'starts_at' => Carbon::now()->subDays(4),
            'ends_at' => Carbon::now()->addDays(10),
            'trial_ends_at' => Carbon::now()->addDays(10),
            'price' => 0,
        ]);

        $activeTrial = $tenant->fresh()->getSubscriptionLifecycle();
        $this->assertSame(SubscriptionLifecycleService::STATE_TRIAL, $activeTrial['state']);
        $this->assertTrue($activeTrial['is_on_trial']);
        $this->assertTrue($activeTrial['can_operate']);

        // Move past trial end: trial expires immediately without paid grace period
        Carbon::setTestNow(Carbon::now()->addDays(11));
        $endedTrial = $tenant->fresh()->getSubscriptionLifecycle();
        $this->assertSame(SubscriptionLifecycleService::STATE_EXPIRED, $endedTrial['state']);
        $this->assertFalse($endedTrial['is_on_trial']);
        $this->assertFalse($endedTrial['can_operate']);

        Carbon::setTestNow();
    }

    public function test_system_admin_can_configure_grace_period_and_reuses_plan_and_dunning_settings(): void
    {
        $admin = AdminUser::create([
            'name' => 'System Admin',
            'email' => 'sysadmin@example.com',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->put(route('system.settings.general.update'), [
                'key' => SubscriptionLifecycleService::SETTING_GRACE_PERIOD_DAYS,
                'value' => 10,
            ])
            ->assertRedirect();

        Plan::updateOrCreate(
            ['slug' => 'trial'],
            [
                'name' => 'Trial',
                'name_ar' => 'تجربة',
                'name_en' => 'Trial',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'trial_days' => 21,
                'is_active' => true,
            ]
        );

        DunningConfig::updateOrCreate(
            ['tenant_id' => null],
            [
                'enabled' => true,
                'auto_suspend_after_days' => 45,
            ]
        );

        $lifecycle = app(SubscriptionLifecycleService::class);
        $this->assertSame(10, $lifecycle->getGracePeriodDays());
        $this->assertSame(21, $lifecycle->getDefaultTrialDays());
        $this->assertSame(45, $lifecycle->getAutoSuspendDays());
    }

    public function test_bank_transfer_approval_activates_with_exact_renewal_dates(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-11 10:00:00'));

        $admin = AdminUser::create([
            'name' => 'Finance Admin',
            'email' => 'finance@example.com',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        [$tenant] = $this->createTenantUser();

        // Active sub ending in 15 days
        $oldEnds = Carbon::now()->addDays(15);
        $oldSub = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $this->plan->id,
            'status' => 'active',
            'billing_cycle' => 'yearly',
            'starts_at' => Carbon::now()->subDays(350),
            'ends_at' => $oldEnds,
            'price' => 2000,
        ]);

        $pendingSub = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $this->plan->id,
            'status' => 'pending_payment',
            'billing_cycle' => 'yearly',
            'starts_at' => Carbon::now()->subDays(2),
            'ends_at' => Carbon::now()->addYear(),
            'price' => 2000,
        ]);

        $invoice = SubscriptionInvoice::create([
            'subscription_id' => $pendingSub->id,
            'tenant_id' => $tenant->id,
            'invoice_number' => 'SUB-TEST-BANK-01',
            'subtotal' => 2000,
            'total' => 2000,
            'status' => 'pending',
        ]);

        $payment = SubscriptionPayment::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $pendingSub->id,
            'subscription_invoice_id' => $invoice->id,
            'amount' => 2000,
            'currency' => 'SAR',
            'gateway' => 'bank_transfer',
            'status' => 'pending',
        ]);

        $receipt = BankTransferReceipt::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $pendingSub->id,
            'subscription_payment_id' => $payment->id,
            'transfer_amount' => 2000,
            'bank_name' => 'Al Rajhi',
            'account_name' => 'Test Co',
            'transfer_reference' => 'REF-GRACE-12345',
            'transfer_date' => now()->toDateString(),
            'receipt_path' => 'receipts/test.pdf',
            'status' => 'pending',
        ]);

        $this->actingAs($admin, 'admin')
            ->post("/system/bank-transfer-receipts/{$receipt->id}/approve", [
                'approved_amount' => 2000,
            ])
            ->assertRedirect();

        $pendingSub->refresh();
        $oldSub->refresh();

        $this->assertSame('expired', $oldSub->status);
        $this->assertSame('active', $pendingSub->status);
        $this->assertTrue($pendingSub->ends_at->equalTo($oldEnds->copy()->addYear()));

        Carbon::setTestNow();
    }

    public function test_renewal_cron_expires_post_grace_and_suspends_unactivated_tenants(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-11 10:00:00'));
        Setting::set(SubscriptionLifecycleService::SETTING_GRACE_PERIOD_DAYS, '5', 'subscription');
        DunningConfig::updateOrCreate(
            ['tenant_id' => null],
            [
                'enabled' => true,
                'auto_suspend_after_days' => 30,
            ]
        );

        [$staleTenant] = $this->createTenantUser(['status' => 'expired']);
        Subscription::create([
            'tenant_id' => $staleTenant->id,
            'plan_id' => $this->plan->id,
            'status' => 'expired',
            'billing_cycle' => 'yearly',
            'starts_at' => Carbon::now()->subYear()->subDays(40),
            'ends_at' => Carbon::now()->subDays(40),
            'price' => 2000,
        ]);

        [$pastGraceTenant] = $this->createTenantUser(['status' => 'active']);
        $pastGraceSub = Subscription::create([
            'tenant_id' => $pastGraceTenant->id,
            'plan_id' => $this->plan->id,
            'status' => 'active',
            'billing_cycle' => 'yearly',
            'starts_at' => Carbon::now()->subYear()->subDays(6),
            'ends_at' => Carbon::now()->subDays(6),
            'auto_renew' => false,
            'price' => 2000,
        ]);

        $results = app(SubscriptionRenewalService::class)->processRenewals();

        $this->assertSame('expired', $pastGraceSub->fresh()->status);
        $this->assertSame('expired', $pastGraceTenant->fresh()->status);
        $this->assertSame('suspended', $staleTenant->fresh()->status);
        $this->assertGreaterThanOrEqual(1, $results['expired']);

        Carbon::setTestNow();
    }

    public function test_billing_page_preserves_shared_tenant_brand_and_center_props(): void
    {
        [$tenant, $center, $user] = $this->createTenantUser([
            'trade_name' => 'مركز الخليج المعتمد',
            'legal_name' => 'شركة الخليج لصيانة السيارات',
            'dunning_opted_out' => true,
            'dunning_language' => 'ar',
        ]);

        $response = $this->actingAs($user)->get('/app/settings/billing');
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Settings/Billing/Index')
            ->where('tenant.trade_name', 'مركز الخليج المعتمد')
            ->where('tenant.name', 'مركز الخليج المعتمد')
            ->where('tenant.dunning_opted_out', true)
            ->where('tenant.dunning_language', 'ar')
            ->has('tenant.logo_url')
            ->has('tenant.subscription_lifecycle')
            ->where('center.id', $center->id)
        );
    }
}
