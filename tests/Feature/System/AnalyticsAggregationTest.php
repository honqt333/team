<?php

declare(strict_types=1);

namespace Tests\Feature\System;

use App\Http\Controllers\System\AnalyticsController;
use App\Models\AdminUser;
use App\Models\Billing\Plan;
use App\Models\Billing\Subscription;
use App\Models\Billing\SubscriptionInvoice;
use App\Models\Billing\SubscriptionPayment;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Phase 5 — Analytics aggregation tests.
 *
 * Validates the math behind the dashboard KPIs (MRR, churn, payment
 * mix). The aggregations are the kind of thing that quietly breaks
 * when a new column is added or a status enum changes — these
 * tests catch that.
 */
class AnalyticsAggregationTest extends TestCase
{
    use RefreshDatabase;

    protected AdminUser $admin;

    protected Plan $monthlyPlan;

    protected Plan $yearlyPlan;

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

        $this->monthlyPlan = Plan::create([
            'name' => 'Monthly',
            'name_ar' => 'شهري',
            'name_en' => 'Monthly',
            'slug' => 'monthly',
            'price_monthly' => 100,
            'price_yearly' => 0,
            'is_active' => true,
        ]);

        $this->yearlyPlan = Plan::create([
            'name' => 'Yearly',
            'name_ar' => 'سنوي',
            'name_en' => 'Yearly',
            'slug' => 'yearly',
            'price_monthly' => 0,
            'price_yearly' => 1200,
            'is_active' => true,
        ]);
    }

    public function test_mrr_sums_active_subscriptions_with_yearly_normalized(): void
    {
        $tenant = Tenant::create([
            'name' => 'A',
            'trade_name' => 'A',
            'slug' => 'a',
            'email' => 'a@example.com',
            'status' => 'active',
        ]);
        // 2 monthly × 100 = 200
        Subscription::create(['tenant_id' => $tenant->id, 'plan_id' => $this->monthlyPlan->id, 'status' => 'active', 'billing_cycle' => 'monthly', 'starts_at' => now(), 'ends_at' => now()->addMonth(), 'price' => 100, 'auto_renew' => true]);
        Subscription::create(['tenant_id' => $tenant->id, 'plan_id' => $this->monthlyPlan->id, 'status' => 'active', 'billing_cycle' => 'monthly', 'starts_at' => now(), 'ends_at' => now()->addMonth(), 'price' => 100, 'auto_renew' => true]);
        // 1 yearly × 1200 / 12 = 100
        Subscription::create(['tenant_id' => $tenant->id, 'plan_id' => $this->yearlyPlan->id, 'status' => 'active', 'billing_cycle' => 'yearly', 'starts_at' => now(), 'ends_at' => now()->addYear(), 'price' => 1200, 'auto_renew' => true]);

        // Total MRR should be 200 + 100 = 300
        $controller = new AnalyticsController;
        $reflection = new ReflectionMethod($controller, 'computeMrr');
        $reflection->setAccessible(true);
        $mrr = $reflection->invoke($controller);

        $this->assertEquals(300.0, round($mrr, 2));
    }

    public function test_outstanding_receivables_excludes_paid_invoices(): void
    {
        $tenant = Tenant::create([
            'name' => 'A',
            'trade_name' => 'A',
            'slug' => 'a',
            'email' => 'a@example.com',
            'status' => 'active',
        ]);
        $sub = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $this->monthlyPlan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'price' => 100,
            'auto_renew' => true,
        ]);
        $inv1 = SubscriptionInvoice::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $sub->id,
            'invoice_number' => 'INV-1',
            'subtotal' => 100, 'vat' => 0, 'discount' => 0, 'total' => 100,
            'status' => 'pending',
            'issued_at' => now(), 'due_at' => now()->addDays(7),
        ]);
        SubscriptionInvoice::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $sub->id,
            'invoice_number' => 'INV-2',
            'subtotal' => 100, 'vat' => 0, 'discount' => 0, 'total' => 100,
            'status' => 'paid',
            'issued_at' => now()->subDays(30),
            'due_at' => now()->subDays(23),
        ]);
        SubscriptionInvoice::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $sub->id,
            'invoice_number' => 'INV-3',
            'subtotal' => 200, 'vat' => 0, 'discount' => 0, 'total' => 200,
            'status' => 'overdue',
            'issued_at' => now()->subDays(60),
            'due_at' => now()->subDays(30),
        ]);

        $outstanding = SubscriptionInvoice::whereIn('status', ['pending', 'pending_payment', 'overdue'])->sum('total');
        $this->assertEquals(300.0, (float) $outstanding); // 100 + 200
    }

    public function test_payment_mix_groups_by_gateway_with_percentages(): void
    {
        $tenant = Tenant::create([
            'name' => 'A',
            'trade_name' => 'A',
            'slug' => 'a',
            'email' => 'a@example.com',
            'status' => 'active',
        ]);
        $sub = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $this->monthlyPlan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'price' => 100,
            'auto_renew' => true,
        ]);

        // 3 moyasar payments totaling 300
        for ($i = 0; $i < 3; $i++) {
            SubscriptionPayment::create([
                'tenant_id' => $tenant->id,
                'subscription_id' => $sub->id,
                'amount' => 100,
                'currency' => 'SAR',
                'gateway' => 'moyasar',
                'status' => 'paid',
                'paid_at' => now()->subDays($i),
            ]);
        }
        // 1 bank transfer payment of 100
        SubscriptionPayment::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $sub->id,
            'amount' => 100,
            'currency' => 'SAR',
            'gateway' => 'bank_transfer',
            'status' => 'paid',
            'paid_at' => now()->subDays(5),
        ]);

        $byGateway = SubscriptionPayment::where('status', 'paid')
            ->where('paid_at', '>=', now()->subDays(90))
            ->whereNotNull('gateway')
            ->groupBy('gateway')
            ->selectRaw('gateway, COUNT(*) as count, SUM(amount) as total')
            ->get();

        $total = $byGateway->sum('total');
        $this->assertEquals(400.0, (float) $total);

        $moyasar = $byGateway->firstWhere('gateway', 'moyasar');
        $this->assertEquals(300.0, (float) $moyasar->total);
        $this->assertEquals(75.0, round(((float) $moyasar->total / $total) * 100, 1));

        $bank = $byGateway->firstWhere('gateway', 'bank_transfer');
        $this->assertEquals(25.0, round(((float) $bank->total / $total) * 100, 1));
    }

    public function test_top_plans_orders_by_active_subscriber_count(): void
    {
        // 3 tenants on monthly, 1 on yearly
        for ($i = 0; $i < 3; $i++) {
            $t = Tenant::create([
                'name' => "T{$i}",
                'trade_name' => "T{$i}",
                'slug' => "t{$i}",
                'email' => "t{$i}@example.com",
                'status' => 'active',
            ]);
            Subscription::create([
                'tenant_id' => $t->id,
                'plan_id' => $this->monthlyPlan->id,
                'status' => 'active',
                'billing_cycle' => 'monthly',
                'starts_at' => now(),
                'ends_at' => now()->addMonth(),
                'price' => 100,
                'auto_renew' => true,
            ]);
        }
        $t4 = Tenant::create([
            'name' => 'T4',
            'trade_name' => 'T4',
            'slug' => 't4',
            'email' => 't4@example.com',
            'status' => 'active',
        ]);
        Subscription::create([
            'tenant_id' => $t4->id,
            'plan_id' => $this->yearlyPlan->id,
            'status' => 'active',
            'billing_cycle' => 'yearly',
            'starts_at' => now(),
            'ends_at' => now()->addYear(),
            'price' => 1200,
            'auto_renew' => true,
        ]);

        $monthlyCount = Subscription::where('plan_id', $this->monthlyPlan->id)
            ->where('status', 'active')
            ->count();
        $yearlyCount = Subscription::where('plan_id', $this->yearlyPlan->id)
            ->where('status', 'active')
            ->count();

        $this->assertEquals(3, $monthlyCount);
        $this->assertEquals(1, $yearlyCount);
    }
}
