<?php

declare(strict_types=1);

namespace Tests\Feature\System;

use App\Models\AdminUser;
use App\Models\Billing\Plan;
use App\Models\Billing\Subscription;
use App\Models\Billing\SubscriptionPayment;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Payment\PaymentManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery;
use Tests\TestCase;

class SystemPaymentResultPagesTest extends TestCase
{
    use RefreshDatabase;

    private AdminUser $superAdmin;

    private Tenant $tenant;

    private Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = AdminUser::create([
            'name' => 'Super Admin',
            'email' => 'payment-admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->tenant = Tenant::factory()->create(['status' => 'active']);
        $this->plan = Plan::create([
            'name' => 'Pro Plan',
            'name_ar' => 'باقة متقدمة',
            'name_en' => 'Pro Plan',
            'slug' => 'pro-payment-'.uniqid(),
            'price_monthly' => 100,
            'price_yearly' => 1000,
            'is_active' => true,
        ]);
    }

    private function createSubscription(string $status = 'active'): Subscription
    {
        return Subscription::create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
            'status' => $status,
            'billing_cycle' => 'yearly',
            'price' => 1000.00,
            'starts_at' => now(),
            'ends_at' => now()->addYear(),
        ]);
    }

    private function createPayment(?Subscription $subscription, string $status = 'paid', ?string $failureReason = null): SubscriptionPayment
    {
        return SubscriptionPayment::create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $subscription?->id,
            'amount' => 1000.00,
            'currency' => 'SAR',
            'gateway' => 'moyasar',
            'payment_method' => 'moyasar',
            'status' => $status,
            'failure_reason' => $failureReason,
        ]);
    }

    public function test_success_page_renders_with_valid_subscription(): void
    {
        $subscription = $this->createSubscription('active');
        $payment = $this->createPayment($subscription, 'paid');

        $this->actingAs($this->superAdmin, 'admin')
            ->get(route('system.payment.success', $payment))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('System/Payment/Success')
                ->where('payment.id', $payment->id)
                ->where('payment.status', 'paid')
                ->where('isConfirmedPaid', true)
                ->where('subscription.id', $subscription->id)
            );
    }

    public function test_success_page_renders_safely_without_subscription(): void
    {
        $payment = $this->createPayment(null, 'paid');

        $this->actingAs($this->superAdmin, 'admin')
            ->get(route('system.payment.success', $payment))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('System/Payment/Success')
                ->where('payment.id', $payment->id)
                ->where('isConfirmedPaid', true)
                ->where('subscription', null)
            );
    }

    public function test_failed_page_renders_with_valid_subscription(): void
    {
        $subscription = $this->createSubscription('failed');
        $payment = $this->createPayment($subscription, 'failed', 'Insufficient funds');

        $this->actingAs($this->superAdmin, 'admin')
            ->get(route('system.payment.failed', $payment))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('System/Payment/Failed')
                ->where('payment.id', $payment->id)
                ->where('payment.status', 'failed')
                ->where('isConfirmedPaid', false)
                ->where('payment.failure_reason', 'Insufficient funds')
                ->where('subscription.id', $subscription->id)
            );
    }

    public function test_failed_page_renders_safely_without_subscription(): void
    {
        $payment = $this->createPayment(null, 'failed', 'Gateway declined');

        $this->actingAs($this->superAdmin, 'admin')
            ->get(route('system.payment.failed', $payment))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('System/Payment/Failed')
                ->where('payment.id', $payment->id)
                ->where('subscription', null)
            );
    }

    public function test_unauthorized_users_cannot_access_payment_result_pages(): void
    {
        $subscription = $this->createSubscription('active');
        $payment = $this->createPayment($subscription, 'paid');

        $this->get(route('system.payment.success', $payment))
            ->assertRedirect(route('login'));

        $this->get(route('system.payment.failed', $payment))
            ->assertRedirect(route('login'));

        $tenantUser = User::factory()->create(['is_system_admin' => false]);

        $this->actingAs($tenantUser, 'web')
            ->get(route('system.payment.success', $payment))
            ->assertForbidden();

        $this->actingAs($tenantUser, 'web')
            ->get(route('system.payment.failed', $payment))
            ->assertForbidden();

        $limitedAdmin = AdminUser::create([
            'name' => 'Limited Admin',
            'email' => 'limited-payment-admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'support',
            'permissions' => ['system.dashboard.view'],
            'is_active' => true,
        ]);

        $this->actingAs($limitedAdmin, 'admin')
            ->get(route('system.payment.success', $payment))
            ->assertForbidden();

        $this->actingAs($limitedAdmin, 'admin')
            ->get(route('system.payment.failed', $payment))
            ->assertForbidden();
    }

    public function test_visiting_success_page_does_not_mark_pending_payment_as_paid(): void
    {
        $subscription = $this->createSubscription('pending_payment');
        $payment = $this->createPayment($subscription, 'pending');

        $this->actingAs($this->superAdmin, 'admin')
            ->get(route('system.payment.success', $payment))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('System/Payment/Success')
                ->where('payment.status', 'pending')
                ->where('isConfirmedPaid', false)
            );

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertNull($payment->fresh()->paid_at);
        $this->assertSame('pending_payment', $subscription->fresh()->status);
    }

    public function test_visiting_success_page_for_non_paid_statuses_reports_unconfirmed_status(): void
    {
        $subscription = $this->createSubscription('pending_payment');

        foreach (['pending', 'initiated', 'failed', 'cancelled', 'refunded'] as $status) {
            $payment = $this->createPayment($subscription, $status);

            $this->actingAs($this->superAdmin, 'admin')
                ->get(route('system.payment.success', $payment))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('System/Payment/Success')
                    ->where('payment.status', $status)
                    ->where('isConfirmedPaid', false)
                );

            $this->assertSame($status, $payment->fresh()->status);
        }
    }

    public function test_incomplete_callback_marks_failed_and_failed_page_renders_after_provisional_cleanup(): void
    {
        $subscription = $this->createSubscription('pending_payment');
        $payment = $this->createPayment($subscription, 'pending');

        $mockManager = Mockery::mock(PaymentManager::class);
        $mockManager->shouldReceive('verify')
            ->once()
            ->andReturn([
                'success' => false,
                'message' => 'Incomplete gateway callback',
                'raw_response' => ['status' => 'failed'],
            ]);
        $this->app->instance(PaymentManager::class, $mockManager);

        $response = $this->actingAs($this->superAdmin, 'admin')
            ->get(route('system.payment.callback', ['payment' => $payment->id]));

        $response->assertRedirect(route('system.payment.failed', ['payment' => $payment->id]));

        $payment->refresh();
        $this->assertSame('failed', $payment->status);
        $this->assertNull($payment->subscription_id);

        $this->actingAs($this->superAdmin, 'admin')
            ->get(route('system.payment.failed', $payment))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('System/Payment/Failed')
                ->where('payment.id', $payment->id)
                ->where('subscription', null)
            );
    }

    public function test_retry_without_subscription_returns_error_without_crashing(): void
    {
        $payment = $this->createPayment(null, 'failed', 'Gateway declined');

        $this->actingAs($this->superAdmin, 'admin')
            ->from(route('system.payment.failed', $payment))
            ->post(route('system.payment.retry', $payment))
            ->assertRedirect(route('system.payment.failed', $payment))
            ->assertSessionHas('error');
    }
}
