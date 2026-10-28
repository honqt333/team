<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Billing\Plan;
use App\Models\Billing\Subscription;
use App\Models\Billing\SubscriptionInvoice;
use App\Models\Billing\SubscriptionPayment;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PerformanceBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * A gateway callback has to match the payment it claims to settle.
 *
 * The callback used to activate a subscription on the strength of the
 * gateway's "success" alone: the amount, the currency and the
 * transaction reference were never compared with the local record, so
 * a cheap successful transaction (or one that had already settled a
 * different payment) could be replayed to switch a subscription on.
 */
class SubscriptionPaymentCallbackMatchingTest extends TestCase
{
    use RefreshDatabase;

    private const MOYASAR_PAYMENT_URL = 'https://api.moyasar.com/v1/payments/*';

    /**
     * Build a tenant with a pending subscription and a pending payment
     * of 500 SAR waiting for a Moyasar transaction.
     */
    private function seedPendingPayment(string $gatewayPaymentId = 'pay_legit_0001'): array
    {
        app(PerformanceBaselineSeeder::class)->run();

        // The Moyasar gateway signs its requests with the secret key;
        // without one the client cannot even build the request.
        config([
            'payment.gateways.moyasar.secret_key' => 'test-secret-key',
            'payment.gateways.moyasar.publishable_key' => 'test-publishable-key',
        ]);

        $tenant = Tenant::first();

        $plan = Plan::create([
            'name' => 'Starter',
            'name_ar' => 'أساسي',
            'name_en' => 'Starter',
            'slug' => 'starter-'.uniqid(),
            'price_monthly' => 500,
            'price_yearly' => 5000,
            'is_active' => true,
        ]);

        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'pending',
            'billing_cycle' => 'monthly',
            'price' => 500,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $invoice = SubscriptionInvoice::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'invoice_number' => 'INV-TEST-'.uniqid(),
            'subtotal' => 500,
            'discount' => 0,
            'vat' => 0,
            'total' => 500,
            'status' => 'pending',
            'issued_at' => now(),
            'due_at' => now()->addMonth(),
        ]);

        $payment = SubscriptionPayment::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'subscription_invoice_id' => $invoice->id,
            'amount' => 500,
            'currency' => 'SAR',
            'gateway' => 'moyasar',
            'gateway_payment_id' => $gatewayPaymentId,
            'status' => 'pending',
        ]);

        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        return [$tenant, $user, $subscription, $payment];
    }

    /**
     * Fake a Moyasar transaction. Amounts are in halalas, as the real
     * gateway reports them.
     */
    private function fakeGateway(int $amountInHalalas = 50000, string $currency = 'SAR', string $paymentId = 'pay_legit_0001'): void
    {
        Http::fake([
            self::MOYASAR_PAYMENT_URL => Http::response([
                'id' => $paymentId,
                'status' => 'paid',
                'amount' => $amountInHalalas,
                'currency' => $currency,
                'source' => ['type' => 'creditcard', 'message' => 'ok'],
            ], 200),
        ]);
    }

    public function test_a_matching_callback_activates_the_subscription(): void
    {
        [$tenant, $user, $subscription, $payment] = $this->seedPendingPayment();

        $this->fakeGateway();

        $this->actingAs($user)
            ->get('/app/settings/billing/callback/'.$payment->id.'?id=pay_legit_0001')
            ->assertStatus(200);

        $payment->refresh();
        $subscription->refresh();

        $this->assertSame('paid', $payment->status);
        $this->assertSame('active', $subscription->status);
    }

    public function test_a_callback_with_a_lower_amount_is_rejected(): void
    {
        [$tenant, $user, $subscription, $payment] = $this->seedPendingPayment();

        // The gateway collected 50 SAR, the payment expects 500 SAR.
        $this->fakeGateway(5000);

        $this->actingAs($user)
            ->get('/app/settings/billing/callback/'.$payment->id.'?id=pay_legit_0001')
            ->assertStatus(200);

        $payment->refresh();
        $subscription->refresh();

        $this->assertNotSame('paid', $payment->status);
        $this->assertNotSame('active', $subscription->status);
        $this->assertStringContainsString('لا يطابق المبلغ المطلوب', (string) $payment->failure_reason);
    }

    public function test_a_callback_with_a_different_currency_is_rejected(): void
    {
        [$tenant, $user, $subscription, $payment] = $this->seedPendingPayment();

        $this->fakeGateway(50000, 'USD');

        $this->actingAs($user)
            ->get('/app/settings/billing/callback/'.$payment->id.'?id=pay_legit_0001')
            ->assertStatus(200);

        $payment->refresh();
        $subscription->refresh();

        $this->assertNotSame('paid', $payment->status);
        $this->assertNotSame('active', $subscription->status);
        $this->assertStringContainsString('لا تطابق العملة المطلوبة', (string) $payment->failure_reason);
    }

    public function test_a_callback_with_a_different_transaction_reference_is_rejected(): void
    {
        [$tenant, $user, $subscription, $payment] = $this->seedPendingPayment('pay_expected_0001');

        // A different (but successful) transaction is offered instead.
        $this->fakeGateway(50000, 'SAR', 'pay_somebody_else');

        $this->actingAs($user)
            ->get('/app/settings/billing/callback/'.$payment->id.'?id=pay_somebody_else')
            ->assertStatus(200);

        $payment->refresh();
        $subscription->refresh();

        $this->assertNotSame('paid', $payment->status);
        $this->assertNotSame('active', $subscription->status);
        $this->assertStringContainsString('لا يطابق المرجع المسجل', (string) $payment->failure_reason);
    }

    public function test_a_transaction_that_already_settled_another_payment_cannot_be_replayed(): void
    {
        [$tenant, $user, $subscription, $payment] = $this->seedPendingPayment('pay_reused_0001');

        // Another payment of the same tenant was already settled with
        // this very gateway transaction.
        $otherSubscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $subscription->plan_id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'price' => 500,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        SubscriptionPayment::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $otherSubscription->id,
            'amount' => 500,
            'currency' => 'SAR',
            'gateway' => 'moyasar',
            'gateway_payment_id' => 'pay_reused_0001',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $this->fakeGateway(50000, 'SAR', 'pay_reused_0001');

        $this->actingAs($user)
            ->get('/app/settings/billing/callback/'.$payment->id.'?id=pay_reused_0001')
            ->assertStatus(200);

        $payment->refresh();
        $subscription->refresh();

        $this->assertNotSame('paid', $payment->status);
        $this->assertNotSame('active', $subscription->status);
        $this->assertStringContainsString('مرتبطة بدفعة أخرى', (string) $payment->failure_reason);
    }

    public function test_a_transaction_of_another_tenant_cannot_settle_this_payment(): void
    {
        [$tenant, $user, $subscription, $payment] = $this->seedPendingPayment('pay_foreign_0001');

        // The same gateway transaction already settled a payment that
        // belongs to a different tenant.
        $otherTenant = Tenant::create([
            'name' => 'Other Tenant',
            'slug' => 'other-'.uniqid(),
            'status' => 'active',
        ]);

        $otherSubscription = Subscription::create([
            'tenant_id' => $otherTenant->id,
            'plan_id' => $subscription->plan_id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'price' => 500,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        SubscriptionPayment::create([
            'tenant_id' => $otherTenant->id,
            'subscription_id' => $otherSubscription->id,
            'amount' => 500,
            'currency' => 'SAR',
            'gateway' => 'moyasar',
            'gateway_payment_id' => 'pay_foreign_0001',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $this->fakeGateway(50000, 'SAR', 'pay_foreign_0001');

        $this->actingAs($user)
            ->get('/app/settings/billing/callback/'.$payment->id.'?id=pay_foreign_0001')
            ->assertStatus(200);

        $payment->refresh();
        $subscription->refresh();

        $this->assertNotSame('paid', $payment->status);
        $this->assertNotSame('active', $subscription->status);
    }

    public function test_a_callback_for_a_payment_of_another_tenant_is_not_found(): void
    {
        [$tenant, $user, $subscription, $payment] = $this->seedPendingPayment();

        $otherTenant = Tenant::create([
            'name' => 'Other Tenant',
            'slug' => 'other-'.uniqid(),
            'status' => 'active',
        ]);

        $foreignSubscription = Subscription::create([
            'tenant_id' => $otherTenant->id,
            'plan_id' => $subscription->plan_id,
            'status' => 'pending',
            'billing_cycle' => 'monthly',
            'price' => 500,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $foreignPayment = SubscriptionPayment::create([
            'tenant_id' => $otherTenant->id,
            'subscription_id' => $foreignSubscription->id,
            'amount' => 500,
            'currency' => 'SAR',
            'gateway' => 'moyasar',
            'gateway_payment_id' => 'pay_foreign_0002',
            'status' => 'pending',
        ]);

        $this->fakeGateway();

        $this->actingAs($user)
            ->get('/app/settings/billing/callback/'.$foreignPayment->id.'?id=pay_foreign_0002')
            ->assertNotFound();

        $foreignPayment->refresh();

        $this->assertSame('pending', $foreignPayment->status);
    }
}
