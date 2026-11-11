<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Billing\Plan;
use App\Models\Billing\Subscription;
use App\Models\Center;
use App\Models\PaymentSettings;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Permissions;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class YearlyOnlyBillingTest extends TestCase
{
    use RefreshDatabase;

    private AdminUser $admin;

    private Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = AdminUser::create([
            'name' => 'Billing Admin',
            'email' => 'billing-admin@example.test',
            'password' => Hash::make('secret-password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->plan = Plan::create([
            'name' => 'Pro Plan',
            'name_ar' => 'الباقة الاحترافية',
            'name_en' => 'Pro Plan',
            'slug' => 'pro-yearly-'.uniqid(),
            'price_monthly' => 0,
            'price_yearly' => 2400,
            'trial_days' => 14,
            'is_active' => true,
        ]);

        PaymentSettings::create([
            'bank_transfer_enabled' => true,
            'subscription_vat_enabled' => false,
        ]);
    }

    private function createTenantAdmin(): User
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

        return $user;
    }

    public function test_plans_controller_stores_yearly_only_and_zeroes_monthly(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('system.plans.store'), [
                'name_ar' => 'الباقة السنوية',
                'name_en' => 'Annual Plan',
                'slug' => 'annual-only-plan',
                'price_yearly' => 3600,
                'trial_days' => 14,
                'is_active' => true,
            ])
            ->assertRedirect();

        $plan = Plan::where('slug', 'annual-only-plan')->firstOrFail();
        $this->assertSame(3600.0, (float) $plan->price_yearly);
        $this->assertSame(0.0, (float) $plan->price_monthly);
        $this->assertSame(0.0, $plan->yearly_discount);
    }

    public function test_system_subscriptions_reject_monthly_cycle_and_use_yearly_price(): void
    {
        $tenant = Tenant::factory()->create(['status' => 'trial']);

        $this->actingAs($this->admin, 'admin')
            ->post(route('system.subscriptions.store'), [
                'tenant_id' => $tenant->id,
                'plan_id' => $this->plan->id,
                'billing_cycle' => 'monthly',
            ])
            ->assertSessionHasErrors('billing_cycle');

        $this->actingAs($this->admin, 'admin')
            ->post(route('system.subscriptions.store'), [
                'tenant_id' => $tenant->id,
                'plan_id' => $this->plan->id,
                'billing_cycle' => 'yearly',
            ])
            ->assertRedirect();

        $subscription = Subscription::where('tenant_id', $tenant->id)->latest('id')->firstOrFail();
        $this->assertSame('yearly', $subscription->billing_cycle);
        $this->assertSame(2400.0, (float) $subscription->price);
    }

    public function test_tenant_billing_endpoints_reject_monthly_and_price_yearly(): void
    {
        $user = $this->createTenantAdmin();

        $this->actingAs($user)
            ->postJson(route('app.billing.calculate'), [
                'plan_id' => $this->plan->id,
                'billing_cycle' => 'monthly',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('billing_cycle');

        $this->actingAs($user)
            ->postJson(route('app.billing.calculate'), [
                'plan_id' => $this->plan->id,
                'billing_cycle' => 'yearly',
            ])
            ->assertOk()
            ->assertJsonPath('plan.billing_cycle', 'yearly')
            ->assertJsonPath('base_price', 2400);

        $this->actingAs($user)
            ->postJson(route('app.billing.subscribe'), [
                'plan_id' => $this->plan->id,
                'billing_cycle' => 'monthly',
                'gateway' => 'bank_transfer',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('billing_cycle');
    }

    public function test_promo_codes_reject_monthly_cycle(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('system.promo-codes.store'), [
                'code' => 'MONTHLYNO',
                'name' => 'Invalid Monthly Promo',
                'discount_type' => 'percentage',
                'discount_value' => 10,
                'max_uses_per_tenant' => 1,
                'billing_cycle' => 'monthly',
            ])
            ->assertSessionHasErrors('billing_cycle');

        $this->actingAs($this->admin, 'admin')
            ->post(route('system.promo-codes.store'), [
                'code' => 'YEARLYOK',
                'name' => 'Valid Yearly Promo',
                'discount_type' => 'percentage',
                'discount_value' => 15,
                'max_uses_per_tenant' => 1,
                'billing_cycle' => 'yearly',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('promo_codes', [
            'code' => 'YEARLYOK',
            'billing_cycle' => 'yearly',
        ]);
    }
}
