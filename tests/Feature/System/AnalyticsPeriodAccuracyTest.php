<?php

declare(strict_types=1);

namespace Tests\Feature\System;

use App\Http\Controllers\System\AnalyticsController;
use App\Models\Billing\Plan;
use App\Models\Billing\Subscription;
use App\Models\Billing\SubscriptionInvoice;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Tests for the analytics numbers the dashboard shows (audit findings
 * #53, #54 and #55).
 *
 * #53 — the churn base was "subscriptions whose status is active NOW
 * and that were created before the window". A subscription that was
 * running at the start of the window and got cancelled during it fell
 * out of the base while still being counted in the numerator, so the
 * rate came out too high — and could pass 100%.
 *
 * #54 — retention and the health trend were computed from the current
 * status, so a subscription that covered a month and expired
 * afterwards counted as "not retained" in a month it was paid for.
 *
 * #55 — the MRR of a plan was `subscribers × list price`, ignoring
 * the yearly cycle and every discount, so it did not match the
 * headline MRR.
 */
class AnalyticsPeriodAccuracyTest extends TestCase
{
    use RefreshDatabase;

    private Plan $monthlyPlan;

    private Plan $yearlyPlan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->monthlyPlan = Plan::create([
            'name' => 'Monthly',
            'name_ar' => 'شهري',
            'name_en' => 'Monthly',
            'slug' => 'monthly-'.uniqid(),
            'price_monthly' => 100,
            'price_yearly' => 0,
            'is_active' => true,
        ]);

        $this->yearlyPlan = Plan::create([
            'name' => 'Yearly',
            'name_ar' => 'سنوي',
            'name_en' => 'Yearly',
            'slug' => 'yearly-'.uniqid(),
            'price_monthly' => 0,
            'price_yearly' => 1200,
            'is_active' => true,
        ]);
    }

    private function tenant(string $slug = 'a', ?Carbon $createdAt = null): Tenant
    {
        $tenant = Tenant::create([
            'name' => 'A',
            'trade_name' => 'A',
            'slug' => $slug.'-'.uniqid(),
            'email' => $slug.'@example.com',
            'status' => 'active',
        ]);

        if ($createdAt) {
            // The cohort is keyed on the signup month, so the tenant has
            // to have signed up then.
            $tenant->forceFill(['created_at' => $createdAt])->save();
            $tenant->refresh();
        }

        return $tenant;
    }

    private function churnRate(Carbon $from, Carbon $to): float
    {
        $controller = new AnalyticsController;
        $method = new ReflectionMethod($controller, 'computeChurnRate');
        $method->setAccessible(true);

        return (float) $method->invoke($controller, $from, $to);
    }

    // ── #53 — churn ───────────────────────────────────────────────────

    public function test_a_subscription_cancelled_during_the_window_stays_in_the_base(): void
    {
        $tenant = $this->tenant();

        $from = now()->startOfMonth();
        $to = now();

        // Three subscriptions were running at the start of the window.
        foreach (range(1, 3) as $i) {
            Subscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $this->monthlyPlan->id,
                'status' => 'active',
                'billing_cycle' => 'monthly',
                'starts_at' => $from->copy()->subMonths(2),
                'ends_at' => $from->copy()->addMonths(2),
                'price' => 100,
            ]);
        }

        // One of them was cancelled during the window.
        $cancelled = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $this->monthlyPlan->id,
            'status' => 'cancelled',
            'billing_cycle' => 'monthly',
            'starts_at' => $from->copy()->subMonths(2),
            'ends_at' => $from->copy()->addMonths(2),
            'cancelled_at' => $from->copy()->addDays(5),
            'price' => 100,
        ]);

        // Four subscriptions were running at the start of the window —
        // the one that was cancelled during it was one of them — and
        // one of them was cancelled: 1/4 = 25%.
        //
        // The old base counted only the subscriptions whose status is
        // `active` NOW, so the cancelled one fell out of the base
        // while still being counted in the numerator: 1/3 = 33.3%.
        $this->assertEquals(25.0, $this->churnRate($from, $to));

        $cancelled->refresh();
        $this->assertSame('cancelled', $cancelled->status);
    }

    public function test_a_subscription_created_and_cancelled_inside_the_window_does_not_inflate_the_rate(): void
    {
        $tenant = $this->tenant();

        $from = now()->startOfMonth();
        $to = now();

        Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $this->monthlyPlan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'starts_at' => $from->copy()->subMonths(2),
            'ends_at' => $from->copy()->addMonths(2),
            'price' => 100,
        ]);

        // Signed up and left inside the same window.
        Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $this->monthlyPlan->id,
            'status' => 'cancelled',
            'billing_cycle' => 'monthly',
            'starts_at' => $from->copy()->addDays(2),
            'ends_at' => $from->copy()->addMonths(2),
            'cancelled_at' => $from->copy()->addDays(4),
            'price' => 100,
        ]);

        // The base is 1 and no subscription of the base was cancelled.
        $this->assertEquals(0.0, $this->churnRate($from, $to));
    }

    public function test_a_subscription_cancelled_before_the_window_is_not_in_the_base(): void
    {
        $tenant = $this->tenant();

        $from = now()->startOfMonth();
        $to = now();

        Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $this->monthlyPlan->id,
            'status' => 'cancelled',
            'billing_cycle' => 'monthly',
            'starts_at' => $from->copy()->subMonths(6),
            'ends_at' => $from->copy()->addMonths(2),
            'cancelled_at' => $from->copy()->subDays(10),
            'price' => 100,
        ]);

        Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $this->monthlyPlan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'starts_at' => $from->copy()->subMonths(2),
            'ends_at' => $from->copy()->addMonths(2),
            'price' => 100,
        ]);

        $this->assertEquals(0.0, $this->churnRate($from, $to));
    }

    // ── #54 — retention ───────────────────────────────────────────────

    public function test_a_subscription_that_expired_afterwards_still_counts_as_retained(): void
    {
        $cohortDate = now()->copy()->startOfMonth()->subMonths(3);
        $tenant = $this->tenant('cohort', $cohortDate);
        $targetMonth = $cohortDate->copy()->addMonth();

        // The subscription covered the target month and was cancelled
        // two months later. The old code required status === 'active',
        // so the month it was paid for counted as lost.
        Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $this->monthlyPlan->id,
            'status' => 'cancelled',
            'billing_cycle' => 'monthly',
            'starts_at' => $cohortDate->copy(),
            'ends_at' => $cohortDate->copy()->addMonths(3),
            'cancelled_at' => $cohortDate->copy()->addMonths(2),
            'price' => 100,
        ]);

        $tenants = collect([Tenant::with('subscriptions')->find($tenant->id)]);

        $controller = new AnalyticsController;
        $method = new ReflectionMethod($controller, 'computeRetentionAtMonth');
        $method->setAccessible(true);

        $retention = $method->invoke($controller, $tenants, $cohortDate, $targetMonth);

        $this->assertSame(100, $retention);
    }

    public function test_a_subscription_cancelled_before_the_month_does_not_count_as_retained(): void
    {
        $cohortDate = now()->copy()->startOfMonth()->subMonths(3);
        $tenant = $this->tenant('cohort', $cohortDate);
        $targetMonth = $cohortDate->copy()->addMonth();

        // Cancelled in the middle of the cohort month — it did not
        // cover the month after it.
        Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $this->monthlyPlan->id,
            'status' => 'cancelled',
            'billing_cycle' => 'monthly',
            'starts_at' => $cohortDate->copy(),
            'ends_at' => $cohortDate->copy()->addMonths(3),
            'cancelled_at' => $cohortDate->copy()->addDays(10),
            'price' => 100,
        ]);

        $tenants = collect([Tenant::with('subscriptions')->find($tenant->id)]);

        $controller = new AnalyticsController;
        $method = new ReflectionMethod($controller, 'computeRetentionAtMonth');
        $method->setAccessible(true);

        $retention = $method->invoke($controller, $tenants, $cohortDate, $targetMonth);

        $this->assertSame(0, $retention);
    }

    // ── #54 — health trend ────────────────────────────────────────────

    public function test_an_invoice_paid_now_that_was_overdue_then_counts_as_overdue_at_the_snapshot(): void
    {
        $tenant = $this->tenant();

        $at = now()->copy()->subMonths(2)->endOfMonth();

        Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $this->monthlyPlan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'starts_at' => $at->copy()->subMonths(2),
            'ends_at' => $at->copy()->addMonths(4),
            'price' => 100,
        ]);

        // Due before the snapshot, paid after it: it WAS overdue then.
        SubscriptionInvoice::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => null,
            'invoice_number' => 'INV-LATE',
            'subtotal' => 100, 'vat' => 0, 'discount' => 0, 'total' => 100,
            'status' => 'paid',
            'issued_at' => $at->copy()->subDays(40),
            'due_at' => $at->copy()->subDays(10),
            'paid_at' => $at->copy()->addDays(5),
        ]);

        $controller = new AnalyticsController;
        $method = new ReflectionMethod($controller, 'computeHealthScoreAt');
        $method->setAccessible(true);

        $health = $method->invoke($controller, $tenant, $at);

        $this->assertContains('overdue_invoice', $health['flags']);
    }

    public function test_an_invoice_paid_before_the_snapshot_is_not_overdue_at_it(): void
    {
        $tenant = $this->tenant();

        $at = now()->copy()->subMonths(2)->endOfMonth();

        Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $this->monthlyPlan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'starts_at' => $at->copy()->subMonths(2),
            'ends_at' => $at->copy()->addMonths(4),
            'price' => 100,
        ]);

        SubscriptionInvoice::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => null,
            'invoice_number' => 'INV-ONTIME',
            'subtotal' => 100, 'vat' => 0, 'discount' => 0, 'total' => 100,
            'status' => 'paid',
            'issued_at' => $at->copy()->subDays(40),
            'due_at' => $at->copy()->subDays(10),
            'paid_at' => $at->copy()->subDays(20),
        ]);

        $controller = new AnalyticsController;
        $method = new ReflectionMethod($controller, 'computeHealthScoreAt');
        $method->setAccessible(true);

        $health = $method->invoke($controller, $tenant, $at);

        $this->assertNotContains('overdue_invoice', $health['flags']);
    }

    // ── #55 — MRR per plan ───────────────────────────────────────────

    public function test_the_mrr_of_a_plan_uses_the_prices_the_subscribers_pay(): void
    {
        $tenant = $this->tenant();

        // A yearly subscriber on a discounted plan: the list price is
        // 1200, the tenant pays 600 for the year.
        Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $this->yearlyPlan->id,
            'status' => 'active',
            'billing_cycle' => 'yearly',
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->addMonths(11),
            'price' => 600,
        ]);

        // A monthly subscriber at a discounted price.
        Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $this->monthlyPlan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->addMonth(),
            'price' => 70,
        ]);

        $controller = new AnalyticsController;
        $request = Request::create('/system/analytics/top-plans');
        $response = $controller->topPlans($request);
        $plans = $response->getData(true)['plans'];

        $byId = collect($plans)->keyBy('id');

        // 600 / 12 = 50, not 1200 / 12 = 100.
        $this->assertEquals(50.0, (float) $byId[$this->yearlyPlan->id]['mrr']);
        // 70, not 100.
        $this->assertEquals(70.0, (float) $byId[$this->monthlyPlan->id]['mrr']);
        $this->assertSame(1, $byId[$this->yearlyPlan->id]['active_subscribers']);
    }

    public function test_the_plan_mrr_and_the_headline_mrr_agree(): void
    {
        $tenant = $this->tenant();

        Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $this->yearlyPlan->id,
            'status' => 'active',
            'billing_cycle' => 'yearly',
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->addMonths(11),
            'price' => 600,
        ]);

        $controller = new AnalyticsController;

        $mrrMethod = new ReflectionMethod($controller, 'computeMrr');
        $mrrMethod->setAccessible(true);
        $headline = (float) $mrrMethod->invoke($controller);

        $response = $controller->topPlans(Request::create('/system/analytics/top-plans'));
        $plans = collect($response->getData(true)['plans']);

        $this->assertEquals(
            $headline,
            round((float) $plans->sum('mrr'), 2),
            'The per-plan MRR must add up to the headline MRR.'
        );
    }
}
