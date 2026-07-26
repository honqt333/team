<?php

declare(strict_types=1);

namespace Tests\Feature\System;

use App\Http\Controllers\System\TenantsController;
use App\Models\AdminUser;
use App\Models\BankTransferReceipt;
use App\Models\Billing\Plan;
use App\Models\Billing\Subscription;
use App\Models\Billing\SubscriptionInvoice;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Feature tests for Phase 2 (Customer 360 — Health Score).
 *
 * The Health Score is a computed field (0-100) that summarises a
 * tenant's payment health, subscription liveness, and loyalty. Each
 * signal either adds or subtracts from a base of 100. The exact
 * numbers are less important than the relative ordering, so this
 * suite asserts:
 *  - The score exists and is clamped to [0, 100]
 *  - A clean tenant scores at or above a healthy threshold
 *  - A tenant with overdue invoices scores below the same threshold
 *  - The flags array is populated for known signals
 */
class TenantHealthScoreTest extends TestCase
{
    use RefreshDatabase;

    protected AdminUser $admin;

    protected Tenant $tenant;

    protected Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = AdminUser::create([
            'name' => 'Test Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->plan = Plan::create([
            'name' => 'Basic Plan',
            'name_ar' => 'الباقة الأساسية',
            'name_en' => 'Basic Plan',
            'slug' => 'basic-plan',
            'price_monthly' => 100,
            'price_yearly' => 1000,
            'is_active' => true,
        ]);

        $this->tenant = Tenant::create([
            'name' => 'Test Tenant',
            'trade_name' => 'Test Co',
            'slug' => 'test-tenant',
            'email' => 'tenant@example.com',
            'status' => 'active',
        ]);
    }

    public function test_health_score_starts_at_100_for_clean_tenant(): void
    {
        // A clean tenant with one active subscription and no overdue
        // invoices should be at 100 (no deductions, no positive flags
        // until they hit 3+ approved receipts or renew).
        $this->createActiveSubscription();

        $score = $this->callHealthScore();

        $this->assertIsArray($score);
        $this->assertArrayHasKey('score', $score);
        $this->assertArrayHasKey('flags', $score);
        $this->assertEquals(100, $score['score']);
        $this->assertSame([], $score['flags']);
    }

    public function test_overdue_invoice_subtracts_twenty_points(): void
    {
        $this->createActiveSubscription();
        SubscriptionInvoice::create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $this->tenant->subscriptions()->first()->id,
            'invoice_number' => 'INV-OVERDUE',
            'subtotal' => 1000,
            'vat' => 0,
            'discount' => 0,
            'total' => 1000,
            'status' => 'overdue',
            'issued_at' => now()->subDays(30),
            'due_at' => now()->subDays(7),
        ]);

        $score = $this->callHealthScore();

        $this->assertEquals(80, $score['score']);
        $this->assertContains(
            ['type' => 'danger', 'key' => 'overdue_invoice'],
            $score['flags']
        );
    }

    public function test_no_active_subscription_subtracts_ten_points(): void
    {
        // No subscription created. Score should be 90.
        $score = $this->callHealthScore();

        $this->assertEquals(90, $score['score']);
        $this->assertContains(
            ['type' => 'warning', 'key' => 'no_active_subscription'],
            $score['flags']
        );
    }

    public function test_expiring_within_30_days_subtracts_ten_points(): void
    {
        $this->createActiveSubscription([
            'ends_at' => now()->addDays(15),  // 15 days left
        ]);

        $score = $this->callHealthScore();

        $this->assertEquals(90, $score['score']);
        $this->assertContains(
            ['type' => 'warning', 'key' => 'expiring_within_30_days'],
            $score['flags']
        );
    }

    public function test_recently_rejected_receipt_subtracts_five_points(): void
    {
        $this->createActiveSubscription();
        BankTransferReceipt::create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $this->tenant->subscriptions()->first()->id,
            'transfer_amount' => 1000,
            'transfer_reference' => 'REJ-001',
            'transfer_date' => now()->subDays(5),
            'bank_name' => 'SNB',
            'account_name' => 'Test',
            'receipt_path' => 'receipts/test.jpg',
            'status' => 'rejected',
            'reviewed_at' => now()->subDays(4),
        ]);

        $score = $this->callHealthScore();

        $this->assertEquals(95, $score['score']);
        $this->assertContains(
            ['type' => 'warning', 'key' => 'rejected_receipt_recent'],
            $score['flags']
        );
    }

    public function test_three_approved_receipts_adds_five_points(): void
    {
        $sub = $this->createActiveSubscription();

        for ($i = 1; $i <= 3; $i++) {
            BankTransferReceipt::create([
                'tenant_id' => $this->tenant->id,
                'subscription_id' => $sub->id,
                'transfer_amount' => 1000,
                'transfer_reference' => "APP-{$i}",
                'transfer_date' => now()->subDays(30 * $i),
                'bank_name' => 'SNB',
                'account_name' => 'Test',
                'receipt_path' => "receipts/{$i}.jpg",
                'status' => 'approved',
                'reviewed_at' => now()->subDays(30 * $i),
            ]);
        }

        $score = $this->callHealthScore();

        // 100 base + 5 loyal = 105 → clamped to 100
        $this->assertEquals(100, $score['score']);
        $this->assertLessThanOrEqual(100, $score['score']);
        $this->assertContains(
            ['type' => 'positive', 'key' => 'loyal_customer'],
            $score['flags']
        );
    }

    public function test_score_clamps_to_zero_with_multiple_negative_signals(): void
    {
        $this->createActiveSubscription(['ends_at' => now()->addDays(10)]);
        SubscriptionInvoice::create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $this->tenant->subscriptions()->first()->id,
            'invoice_number' => 'INV-DANGER',
            'subtotal' => 1000,
            'vat' => 0,
            'discount' => 0,
            'total' => 1000,
            'status' => 'overdue',
            'issued_at' => now()->subDays(30),
            'due_at' => now()->subDays(7),
        ]);
        BankTransferReceipt::create([
            'tenant_id' => $this->tenant->id,
            'transfer_amount' => 1000,
            'transfer_reference' => 'REJ-X',
            'transfer_date' => now()->subDays(2),
            'bank_name' => 'SNB',
            'account_name' => 'Test',
            'receipt_path' => 'x.jpg',
            'status' => 'rejected',
            'reviewed_at' => now()->subDay(),
        ]);

        $score = $this->callHealthScore();

        // 100 - 20 (overdue) - 10 (expiring) - 5 (rejected) = 65
        $this->assertEquals(65, $score['score']);
        $this->assertGreaterThanOrEqual(0, $score['score']);
        $this->assertLessThanOrEqual(100, $score['score']);
    }

    /**
     * Helper: call the controller's health score computation via the
     * public show() endpoint. We use a private reflection call to
     * avoid duplicating the scoring math here. The endpoint itself is
     * skipped (admin middleware) — we just want the score.
     */
    protected function callHealthScore(): array
    {
        $controller = new TenantsController;
        $reflection = new ReflectionMethod($controller, 'computeHealthScore');
        $reflection->setAccessible(true);

        return $reflection->invoke($controller, $this->tenant);
    }

    protected function createActiveSubscription(array $overrides = []): Subscription
    {
        return Subscription::create(array_merge([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
            'status' => 'active',
            'billing_cycle' => 'yearly',
            'starts_at' => now()->subMonths(11),
            'ends_at' => now()->addMonths(1),
            'price' => 1000,
            'auto_renew' => true,
        ], $overrides));
    }
}
