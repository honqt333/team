<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AdminActivityLog;
use App\Models\AdminUser;
use App\Models\BankTransferReceipt;
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
use App\Services\TenantStorageService;
use App\Support\Permissions;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TenantActivationAndExtensionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private AdminUser $admin;

    private Plan $paidPlan;

    private Plan $trialPlan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = AdminUser::create([
            'name' => 'Lifecycle Admin',
            'email' => 'lifecycle-admin@example.test',
            'password' => Hash::make('secret-password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->trialPlan = Plan::firstOrCreate(
            ['slug' => 'trial'],
            [
                'name' => 'Trial Plan',
                'name_ar' => 'تجريبية',
                'name_en' => 'Trial Plan',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'trial_days' => 14,
                'limits' => ['max_users' => 2, 'max_centers' => 1, 'storage_gb' => 2],
                'is_active' => true,
            ]
        );

        $this->paidPlan = Plan::create([
            'name' => 'Pro Plan',
            'name_ar' => 'الباقة الاحترافية',
            'name_en' => 'Pro Plan',
            'slug' => 'pro-annual-'.uniqid(),
            'price_monthly' => 0,
            'price_yearly' => 2400,
            'trial_days' => 0,
            'limits' => ['max_users' => 2, 'max_centers' => 2, 'storage_gb' => 50],
            'is_active' => true,
        ]);
    }

    public function test_reactivating_suspended_paid_tenant_restores_active_and_clears_auto_suspend(): void
    {
        $tenant = Tenant::factory()->create([
            'status' => 'suspended',
            'suspended_at' => now()->subDays(2),
            'auto_suspended_at' => now()->subDays(2),
            'suspension_reason' => 'auto_suspend_dunning_tier_4',
        ]);

        Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $this->paidPlan->id,
            'status' => 'active',
            'billing_cycle' => 'yearly',
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->addMonths(11),
            'price' => 2400,
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email_verified_at' => null,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('system.tenants.activate', $tenant))
            ->assertRedirect();

        $tenant->refresh();
        $user->refresh();

        $this->assertSame('active', $tenant->status);
        $this->assertNull($tenant->suspended_at);
        $this->assertNull($tenant->auto_suspended_at);
        $this->assertNull($tenant->suspension_reason);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_extend_trial_starts_from_now_when_past_and_preserves_paid_history(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-11 12:00:00'));

        $tenant = Tenant::factory()->create([
            'status' => 'suspended',
            'trial_ends_at' => Carbon::now()->subDays(20),
            'suspended_at' => Carbon::now()->subDays(5),
            'suspension_reason' => 'trial ended',
        ]);

        $trialSub = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $this->trialPlan->id,
            'status' => 'expired',
            'billing_cycle' => 'yearly',
            'starts_at' => Carbon::now()->subDays(34),
            'ends_at' => Carbon::now()->subDays(20),
            'trial_ends_at' => Carbon::now()->subDays(20),
            'price' => 0,
            'auto_renew' => false,
        ]);

        $oldPaidEnd = Carbon::now()->subMonths(3);
        $oldPaidSub = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $this->paidPlan->id,
            'status' => 'expired',
            'billing_cycle' => 'yearly',
            'starts_at' => Carbon::now()->subMonths(15),
            'ends_at' => $oldPaidEnd,
            'price' => 2400,
            'auto_renew' => false,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('system.tenants.extend-trial', $tenant), ['days' => 10])
            ->assertRedirect();

        $tenant->refresh();
        $trialSub->refresh();
        $oldPaidSub->refresh();

        $expectedEnd = Carbon::now()->addDays(10);
        $this->assertSame('trial', $tenant->status);
        $this->assertNull($tenant->suspended_at);
        $this->assertTrue($tenant->trial_ends_at->equalTo($expectedEnd));

        $this->assertSame('trialing', $trialSub->status);
        $this->assertTrue($trialSub->ends_at->equalTo($expectedEnd));

        // Historical paid subscription must remain untouched
        $this->assertSame('expired', $oldPaidSub->status);
        $this->assertTrue($oldPaidSub->ends_at->equalTo($oldPaidEnd));

        Carbon::setTestNow();
    }

    public function test_subscription_activate_and_extend_use_unified_lifecycle_and_clear_suspension(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-11 12:00:00'));

        $tenant = Tenant::factory()->create([
            'status' => 'suspended',
            'suspended_at' => Carbon::now()->subDay(),
            'auto_suspended_at' => Carbon::now()->subDay(),
            'suspension_reason' => 'unpaid',
        ]);

        $oldTrial = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $this->trialPlan->id,
            'status' => 'trialing',
            'billing_cycle' => 'yearly',
            'starts_at' => Carbon::now()->subDays(5),
            'ends_at' => Carbon::now()->addDays(9),
            'price' => 0,
        ]);

        $expiredSub = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $this->paidPlan->id,
            'status' => 'expired',
            'billing_cycle' => 'yearly',
            'starts_at' => Carbon::now()->subYear()->subDays(10),
            'ends_at' => Carbon::now()->subDays(10),
            'price' => 2400,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('system.subscriptions.extend', $expiredSub), ['days' => 30])
            ->assertRedirect();

        $expiredSub->refresh();
        $oldTrial->refresh();
        $tenant->refresh();

        $this->assertSame('active', $expiredSub->status);
        $this->assertTrue($expiredSub->ends_at->equalTo(Carbon::now()->addDays(30)));
        $this->assertSame('expired', $oldTrial->status);
        $this->assertSame('active', $tenant->status);
        $this->assertNull($tenant->suspended_at);
        $this->assertNull($tenant->auto_suspended_at);

        // Now test activate() on another pending subscription carries over remaining 30 days
        $pendingSub = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $this->paidPlan->id,
            'status' => 'pending_payment',
            'billing_cycle' => 'yearly',
            'starts_at' => Carbon::now(),
            'ends_at' => Carbon::now()->addYear(),
            'price' => 2400,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('system.subscriptions.activate', $pendingSub))
            ->assertRedirect();

        $pendingSub->refresh();
        $expiredSub->refresh();

        $this->assertSame('active', $pendingSub->status);
        $this->assertSame('expired', $expiredSub->status);
        $this->assertTrue($pendingSub->ends_at->equalTo(Carbon::now()->addDays(30)->addYear()));

        Carbon::setTestNow();
    }

    public function test_admin_creating_trial_subscription_disables_auto_renew_and_writes_audit(): void
    {
        $tenant = Tenant::factory()->create(['status' => 'expired']);

        $this->actingAs($this->admin, 'admin')
            ->post(route('system.subscriptions.store'), [
                'tenant_id' => $tenant->id,
                'plan_id' => $this->trialPlan->id,
                'billing_cycle' => 'yearly',
            ])
            ->assertRedirect();

        $sub = Subscription::where('tenant_id', $tenant->id)->latest('id')->firstOrFail();
        $this->assertSame('trialing', $sub->status);
        $this->assertFalse((bool) $sub->auto_renew);
        $this->assertDatabaseHas(AdminActivityLog::class, [
            'action' => 'subscription_create',
            'model_id' => $sub->id,
        ]);
    }

    public function test_partial_bank_transfer_approval_does_not_activate_full_subscription(): void
    {
        $tenant = Tenant::factory()->create(['status' => 'expired']);
        $sub = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $this->paidPlan->id,
            'status' => 'pending_payment',
            'billing_cycle' => 'yearly',
            'starts_at' => now(),
            'ends_at' => now()->addYear(),
            'price' => 2400,
        ]);

        $invoice = SubscriptionInvoice::create([
            'subscription_id' => $sub->id,
            'tenant_id' => $tenant->id,
            'invoice_number' => 'SUB-PARTIAL-1',
            'subtotal' => 2400,
            'vat' => 0,
            'discount' => 0,
            'total' => 2400,
            'status' => 'pending',
            'issued_at' => now(),
            'due_at' => now()->addDays(7),
        ]);

        $payment = SubscriptionPayment::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $sub->id,
            'subscription_invoice_id' => $invoice->id,
            'amount' => 2400,
            'currency' => 'SAR',
            'gateway' => 'bank_transfer',
            'status' => 'pending',
        ]);

        $receipt = BankTransferReceipt::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $sub->id,
            'subscription_payment_id' => $payment->id,
            'transfer_amount' => 2400,
            'transfer_reference' => 'REF-PARTIAL',
            'transfer_date' => now()->toDateString(),
            'bank_name' => 'Al Rajhi',
            'account_name' => 'Tenant Co',
            'receipt_path' => 'receipts/test.pdf',
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('system.bank-receipts.approve', $receipt), [
                'approved_amount' => 1000,
            ])
            ->assertSessionHas('success');

        $this->assertSame('pending', $invoice->fresh()->status);
        $this->assertSame('pending_payment', $sub->fresh()->status);
        $this->assertSame('expired', $tenant->fresh()->status);
    }

    public function test_auto_renew_issues_invoice_without_free_extension_and_expires_after_grace(): void
    {
        Mail::fake();
        Carbon::setTestNow(Carbon::parse('2026-11-11 10:00:00'));
        Setting::set(SubscriptionLifecycleService::SETTING_GRACE_PERIOD_DAYS, '5', 'subscription');

        $tenant = Tenant::factory()->create(['status' => 'active', 'email' => 'renew@example.test']);
        $sub = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $this->paidPlan->id,
            'status' => 'active',
            'billing_cycle' => 'yearly',
            'starts_at' => Carbon::now()->subYear(),
            'ends_at' => Carbon::now(),
            'price' => 2400,
            'auto_renew' => true,
        ]);

        $service = app(SubscriptionRenewalService::class);
        $service->processRenewals();

        $sub->refresh();
        // ends_at must NOT jump +1 year without actual payment
        $this->assertTrue($sub->ends_at->equalTo(Carbon::now()));
        $this->assertDatabaseHas('subscription_invoices', [
            'subscription_id' => $sub->id,
            'status' => 'pending',
        ]);

        // Advance past 5-day grace period; unpaid auto_renew sub must expire cleanly
        Carbon::setTestNow(Carbon::now()->addDays(6));
        $service->processRenewals();

        $this->assertSame('expired', $sub->fresh()->status);
        $this->assertSame('expired', $tenant->fresh()->status);

        Carbon::setTestNow();
    }

    public function test_user_limit_and_unlimited_storage_check_respect_max_users_limit(): void
    {
        $this->seed(PermissionsSeeder::class);

        $tenant = Tenant::factory()->create(['status' => 'active']);
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);

        Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $this->paidPlan->id, // max_users = 2
            'status' => 'active',
            'billing_cycle' => 'yearly',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addYear(),
            'price' => 2400,
        ]);

        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
            'is_active' => true,
            'two_factor_confirmed_at' => now(),
        ]);
        $admin->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $role = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web',
            'tenant_id' => $tenant->id,
        ]);
        $role->syncPermissions(Permissions::all());
        $admin->assignRole($role);

        // Second user (reaches limit 2)
        User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
            'is_active' => true,
        ]);

        $staffRole = Role::create([
            'tenant_id' => $tenant->id,
            'name' => 'cashier_test',
            'label_ar' => 'كاشير',
            'label_en' => 'Cashier',
            'guard_name' => 'web',
        ]);

        // Third user must be blocked by max_users = 2
        $response = $this->actingAs($admin)
            ->withSession(['current_center_id' => $center->id])
            ->post(route('settings.users.store'), [
                'name' => 'Third User',
                'email' => 'third@example.test',
                'password' => 'secret1234',
                'password_confirmation' => 'secret1234',
                'centers' => [$center->id],
                'role_id' => $staffRole->id,
                'is_active' => true,
            ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('users', ['email' => 'third@example.test']);
    }

    public function test_unlimited_storage_check_recognizes_max_users_and_max_centers(): void
    {
        $unlimitedPlan = Plan::create([
            'name' => 'Enterprise',
            'name_ar' => 'مؤسسات',
            'name_en' => 'Enterprise',
            'slug' => 'ent-unlimited-'.uniqid(),
            'price_monthly' => 0,
            'price_yearly' => 9999,
            'limits' => ['max_users' => 999, 'max_centers' => 999],
            'is_active' => true,
        ]);
        $entTenant = Tenant::factory()->create(['status' => 'active']);
        Subscription::create([
            'tenant_id' => $entTenant->id,
            'plan_id' => $unlimitedPlan->id,
            'status' => 'active',
            'billing_cycle' => 'yearly',
            'starts_at' => now(),
            'ends_at' => now()->addYear(),
            'price' => 9999,
        ]);

        $this->assertSame(999, TenantStorageService::getAllocatedStorageGb($entTenant));
    }

    public function test_assign_plan_from_tenant_show_exposes_plan_slug_and_upgrades_yearly_subscription(): void
    {
        $tenant = Tenant::factory()->create(['status' => 'trial', 'trial_ends_at' => now()->addDays(7)]);
        Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $this->trialPlan->id,
            'status' => 'trialing',
            'billing_cycle' => 'yearly',
            'starts_at' => now()->subDays(7),
            'ends_at' => now()->addDays(7),
            'trial_ends_at' => now()->addDays(7),
            'price' => 0,
        ]);

        $showResponse = $this->actingAs($this->admin, 'admin')
            ->get(route('system.tenants.show', $tenant));

        $showResponse->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('System/Tenants/Show')
                ->has('plans.0.slug')
                ->has('plans.0.price_yearly')
            );

        $this->actingAs($this->admin, 'admin')
            ->from(route('system.tenants.show', $tenant))
            ->post(route('system.subscriptions.store'), [
                'tenant_id' => $tenant->id,
                'plan_id' => $this->paidPlan->id,
                'billing_cycle' => 'yearly',
            ])
            ->assertRedirect(route('system.tenants.show', $tenant))
            ->assertSessionHas('success');

        $this->assertSame('active', $tenant->fresh()->status);
        $this->assertDatabaseHas('subscriptions', [
            'tenant_id' => $tenant->id,
            'plan_id' => $this->paidPlan->id,
            'status' => 'active',
            'billing_cycle' => 'yearly',
            'price' => 2400,
        ]);
    }

    public function test_admin_can_assign_subscription_with_office_cash_payment_and_record_payment(): void
    {
        $tenant = Tenant::factory()->create(['status' => 'expired']);

        $this->actingAs($this->admin, 'admin')
            ->post(route('system.subscriptions.store'), [
                'tenant_id' => $tenant->id,
                'plan_id' => $this->paidPlan->id,
                'billing_cycle' => 'yearly',
                'settlement_type' => 'paid_now',
                'payment_method' => 'cash',
                'paid_amount' => 2400,
                'payment_reference' => 'CASH-OFFICE-01',
                'payment_notes' => 'Paid in cash at Riyadh office',
            ])
            ->assertRedirect();

        $sub = Subscription::where('tenant_id', $tenant->id)->latest('id')->firstOrFail();

        $this->assertSame('active', $sub->status);
        $this->assertSame('active', $tenant->fresh()->status);
        $this->assertDatabaseHas('subscription_invoices', [
            'subscription_id' => $sub->id,
            'status' => 'paid',
            'total' => 2400,
        ]);
        $this->assertDatabaseHas('subscription_payments', [
            'subscription_id' => $sub->id,
            'gateway' => 'cash',
            'payment_method' => 'cash',
            'gateway_ref' => 'CASH-OFFICE-01',
            'status' => 'paid',
            'amount' => 2400,
        ]);

        // Also ensure assigning paid_now without optional payment_notes / payment_reference does not fail
        $tenant2 = Tenant::factory()->create(['status' => 'trial']);
        $this->actingAs($this->admin, 'admin')
            ->post(route('system.subscriptions.store'), [
                'tenant_id' => $tenant2->id,
                'plan_id' => $this->paidPlan->id,
                'billing_cycle' => 'yearly',
                'settlement_type' => 'paid_now',
                'payment_method' => 'cash',
                'paid_amount' => 2400,
            ])
            ->assertRedirect();

        $this->assertSame('active', $tenant2->fresh()->status);
    }

    public function test_pending_invoice_subscription_activates_later_when_marked_paid_in_cash(): void
    {
        $tenant = Tenant::factory()->create(['status' => 'expired']);

        $this->actingAs($this->admin, 'admin')
            ->post(route('system.subscriptions.store'), [
                'tenant_id' => $tenant->id,
                'plan_id' => $this->paidPlan->id,
                'billing_cycle' => 'yearly',
                'settlement_type' => 'pending_invoice',
                'payment_method' => 'cash',
            ])
            ->assertRedirect();

        $sub = Subscription::where('tenant_id', $tenant->id)->latest('id')->firstOrFail();
        $invoice = SubscriptionInvoice::where('subscription_id', $sub->id)->firstOrFail();

        $this->assertSame('pending_payment', $sub->status);
        $this->assertSame('expired', $tenant->fresh()->status);
        $this->assertSame('pending', $invoice->status);

        // Later, tenant pays cash at office and admin marks invoice paid
        $this->actingAs($this->admin, 'admin')
            ->post(route('system.invoices.mark-paid', $invoice), [
                'payment_method' => 'cash',
                'paid_amount' => 2400,
                'reference' => 'CASH-LATER-99',
                'notes' => 'Collected at front desk',
            ])
            ->assertRedirect();

        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame('active', $sub->fresh()->status);
        $this->assertSame('active', $tenant->fresh()->status);
        $this->assertDatabaseHas('subscription_payments', [
            'subscription_invoice_id' => $invoice->id,
            'gateway' => 'cash',
            'payment_method' => 'cash',
            'gateway_ref' => 'CASH-LATER-99',
            'status' => 'paid',
            'amount' => 2400,
        ]);
    }

    public function test_tenant_cancelling_paid_subscription_disables_auto_renew_without_locking_out_paid_term(): void
    {
        $this->seed(PermissionsSeeder::class);

        $tenant = Tenant::factory()->create(['status' => 'active']);
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
            'two_factor_confirmed_at' => now(),
        ]);
        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $role = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web',
            'tenant_id' => $tenant->id,
        ]);
        $role->syncPermissions(Permissions::all());
        $user->assignRole($role);

        $sub = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $this->paidPlan->id,
            'status' => 'active',
            'billing_cycle' => 'yearly',
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->addMonths(11),
            'price' => 2400,
            'auto_renew' => true,
        ]);

        // Cancelling keeps active status until ends_at, disables auto_renew, and sets cancelled_at
        $this->actingAs($user)
            ->withSession(['current_center_id' => $center->id])
            ->post(route('app.billing.cancel', $sub))
            ->assertRedirect();

        $sub->refresh();
        $this->assertSame('active', $sub->status);
        $this->assertFalse((bool) $sub->auto_renew);
        $this->assertNotNull($sub->cancelled_at);
        $this->assertSame('active', $tenant->fresh()->status);

        // Resuming re-enables auto_renew and clears cancelled_at
        $this->actingAs($user)
            ->withSession(['current_center_id' => $center->id])
            ->post(route('app.billing.resume', $sub))
            ->assertRedirect();

        $sub->refresh();
        $this->assertSame('active', $sub->status);
        $this->assertTrue((bool) $sub->auto_renew);
        $this->assertNull($sub->cancelled_at);
    }
}
