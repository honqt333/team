<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Billing\Plan;
use App\Models\Billing\Subscription;
use App\Models\Billing\SubscriptionInvoice;
use App\Models\Billing\SubscriptionPayment;
use App\Models\Center;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SubscriptionBillingCheckoutSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected function createAuthenticatedUser(): array
    {
        $this->seed(PermissionsSeeder::class);
        $tenant = Tenant::factory()->create();
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

    public function test_cancelled_pending_checkout_does_not_persist_subscription_or_invoice(): void
    {
        [$tenant, $center, $user] = $this->createAuthenticatedUser();

        $plan = Plan::create([
            'name' => 'Pro Plan',
            'name_ar' => 'باقة متقدمة',
            'name_en' => 'Pro Plan',
            'slug' => 'pro-test-'.uniqid(),
            'price_monthly' => 199,
            'price_yearly' => 1990,
            'is_active' => true,
        ]);

        // Create an existing active trial subscription
        $trialSub = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'trialing',
            'billing_cycle' => 'yearly',
            'starts_at' => now(),
            'ends_at' => now()->addDays(14),
            'price' => 0,
        ]);

        // Simulate a checkout attempt starting (pending subscription, invoice, and payment)
        $pendingSub = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'pending',
            'billing_cycle' => 'monthly',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'price' => 199,
        ]);

        $invoice = SubscriptionInvoice::create([
            'subscription_id' => $pendingSub->id,
            'tenant_id' => $tenant->id,
            'invoice_number' => 'SUB-TEST-'.uniqid(),
            'subtotal' => 199,
            'total' => 199,
            'status' => 'pending',
        ]);

        $payment = SubscriptionPayment::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $pendingSub->id,
            'subscription_invoice_id' => $invoice->id,
            'amount' => 199,
            'currency' => 'SAR',
            'gateway' => 'moyasar',
            'status' => 'pending',
        ]);

        // Tenant cancels the pending checkout
        $response = $this->actingAs($user)
            ->withSession(['current_center_id' => $center->id])
            ->postJson(route('app.billing.cancel-pending'));

        $response->assertOk()
            ->assertJson(['success' => true]);

        // The provisional subscription and invoice MUST be deleted
        $this->assertDatabaseMissing('subscriptions', ['id' => $pendingSub->id]);
        $this->assertDatabaseMissing('subscription_invoices', ['id' => $invoice->id]);

        // The payment attempt MUST remain recorded as cancelled with failure_reason
        $this->assertDatabaseHas('subscription_payments', [
            'id' => $payment->id,
            'status' => 'cancelled',
            'failure_reason' => 'ملغي من المستخدم',
            'subscription_id' => null,
            'subscription_invoice_id' => null,
        ]);

        // The tenant's trial subscription MUST still exist
        $this->assertDatabaseHas('subscriptions', ['id' => $trialSub->id, 'status' => 'trialing']);
    }

    public function test_payment_failure_removes_provisional_subscription(): void
    {
        $tenant = Tenant::factory()->create();

        $plan = Plan::create([
            'name' => 'Pro Plan',
            'name_ar' => 'باقة متقدمة',
            'name_en' => 'Pro Plan',
            'slug' => 'pro-test-'.uniqid(),
            'price_monthly' => 199,
            'price_yearly' => 1990,
            'is_active' => true,
        ]);

        $pendingSub = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'pending',
            'billing_cycle' => 'monthly',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'price' => 199,
        ]);

        $invoice = SubscriptionInvoice::create([
            'subscription_id' => $pendingSub->id,
            'tenant_id' => $tenant->id,
            'invoice_number' => 'SUB-TEST-'.uniqid(),
            'subtotal' => 199,
            'total' => 199,
            'status' => 'pending',
        ]);

        $payment = SubscriptionPayment::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $pendingSub->id,
            'subscription_invoice_id' => $invoice->id,
            'amount' => 199,
            'currency' => 'SAR',
            'gateway' => 'moyasar',
            'status' => 'pending',
        ]);

        // Gateway failure
        $payment->markAsFailed('3DS: Card authentication declined.');

        // Provisional subscription and invoice should be cleaned up
        $this->assertDatabaseMissing('subscriptions', ['id' => $pendingSub->id]);
        $this->assertDatabaseMissing('subscription_invoices', ['id' => $invoice->id]);

        // Payment attempt is preserved in log with reason
        $this->assertDatabaseHas('subscription_payments', [
            'id' => $payment->id,
            'status' => 'failed',
            'failure_reason' => '3DS: Card authentication declined.',
        ]);
    }

    public function test_resume_cannot_resume_unpaid_cancelled_subscription(): void
    {
        [$tenant, $center, $user] = $this->createAuthenticatedUser();

        $plan = Plan::create([
            'name' => 'Pro Plan',
            'name_ar' => 'باقة متقدمة',
            'name_en' => 'Pro Plan',
            'slug' => 'pro-test-'.uniqid(),
            'price_monthly' => 199,
            'price_yearly' => 1990,
            'is_active' => true,
        ]);

        $cancelledSub = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'cancelled',
            'billing_cycle' => 'monthly',
            'starts_at' => now()->subDays(5),
            'ends_at' => now()->addDays(25),
            'price' => 199,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_center_id' => $center->id])
            ->post(route('app.billing.resume', $cancelledSub));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('subscriptions', [
            'id' => $cancelledSub->id,
            'status' => 'cancelled',
        ]);
    }
}
