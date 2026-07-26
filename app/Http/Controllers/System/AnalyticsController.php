<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\BankTransferReceipt;
use App\Models\Billing\Plan;
use App\Models\Billing\Subscription;
use App\Models\Billing\SubscriptionInvoice;
use App\Models\Billing\SubscriptionPayment;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Phase 5 — Customer-facing analytics for the system admin.
 *
 * Each method is a single-purpose endpoint that the Vue dashboard
 * calls once on mount. We deliberately don't aggregate these in the
 * front-end to keep the data sources inspectable (curl-able) and the
 * caching strategy server-driven.
 *
 * Numbers are computed on demand with indexed aggregations. For the
 * current scale (low thousands of tenants) this is fast enough; if
 * the install grows, swap each method to read from a denormalised
 * daily_metrics table maintained by a scheduler.
 */
class AnalyticsController extends Controller
{
    /**
     * 6 headline KPIs for the dashboard.
     * One endpoint, one SELECT bundle — keeps the dashboard mount
     * round-trip count to a minimum.
     */
    public function kpis(Request $request): Response
    {
        $now = now();
        $thirtyDaysAgo = $now->copy()->subDays(30);
        $startOfMonth = $now->copy()->startOfMonth();
        $lastMonth = $now->copy()->subMonth();
        $startOfLastMonth = $lastMonth->copy()->startOfMonth();

        return Inertia::render('System/Analytics/Kpis', [
            'kpis' => [
                // MRR — sum of monthly-normalised subscription prices
                // for active tenants. Yearly subs divided by 12.
                'mrr' => $this->computeMrr(),

                // Churn — percentage of tenants who cancelled in the
                // current month vs active at the start of the month.
                'churn_rate' => $this->computeChurnRate($startOfMonth, $now),

                // New tenants in the last 30 days (rolling, not just
                // this month — matches how growth is usually tracked).
                'new_tenants_30d' => Tenant::where('created_at', '>=', $thirtyDaysAgo)->count(),

                // Outstanding receivables — sum of unpaid invoices
                // (pending + pending_payment + overdue). Counts the
                // full invoice total, not what's been partially paid.
                'outstanding_receivables' => SubscriptionInvoice::whereIn(
                    'status',
                    ['pending', 'pending_payment', 'overdue']
                )->sum('total'),

                // Trial tenants — useful as a "near-term MRR" signal.
                'trial_tenants' => Tenant::where('status', 'trial')->count(),

                // Churn vs last month — delta for the trend arrow.
                'churn_last_month' => $this->computeChurnRate(
                    $startOfLastMonth,
                    $startOfMonth
                ),
            ],
        ]);
    }

    /**
     * MRR calculation. For each active subscription, convert to a
     * monthly figure (yearly / 12) and sum. Excludes trial/expired
     * because they're not generating revenue.
     */
    private function computeMrr(): float
    {
        return (float) Subscription::with('plan')
            ->where('status', 'active')
            ->get()
            ->sum(function ($sub) {
                $price = (float) $sub->price;

                return $sub->billing_cycle === 'yearly'
                    ? $price / 12
                    : $price;
            });
    }

    /**
     * Churn rate over a window. Counts subscriptions that ended
     * during the window and divides by the active count at the
     * window's start (so a tenant that was already cancelled
     * before the window doesn't skew the rate).
     */
    private function computeChurnRate(Carbon $from, Carbon $to): float
    {
        $activeAtStart = Subscription::where('status', 'active')
            ->where('created_at', '<', $from)
            ->count();

        if ($activeAtStart === 0) {
            return 0.0;
        }

        // We use the Subscription status column to count cancellations
        // rather than parsing ends_at — a tenant with status=cancelled
        // during the window is the cleanest signal.
        $cancelledDuring = Subscription::where('status', 'cancelled')
            ->whereBetween('updated_at', [$from, $to])
            ->count();

        return round(($cancelledDuring / $activeAtStart) * 100, 1);
    }

    /**
     * Top 10 plans by active subscriber count. Used in the dashboard
     * to show which tiers are popular. Names are returned in the
     * current locale so the front end doesn't have to look them up.
     */
    public function topPlans(Request $request)
    {
        $locale = app()->getLocale();

        $rows = Plan::query()
            ->withCount(['subscriptions as active_subscribers' => fn ($q) => $q->where('status', 'active')])
            ->where('is_active', true)
            ->orderByDesc('active_subscribers')
            ->limit(10)
            ->get(['id', 'name', 'name_ar', 'name_en', 'price_monthly', 'price_yearly']);

        $plans = $rows->map(fn ($plan) => [
            'id' => $plan->id,
            'name' => $locale === 'ar'
                ? ($plan->name_ar ?? $plan->name)
                : ($plan->name_en ?? $plan->name),
            'active_subscribers' => $plan->active_subscribers,
            'mrr' => $plan->active_subscribers * (float) $plan->price_monthly,
        ]);

        return response()->json(['plans' => $plans]);
    }

    /**
     * Payment method mix — last 90 days. Gives the admin a sense
     * of how much of the revenue is going through each gateway so
     * they can spot a sudden shift (e.g. Tabby going down) and react.
     */
    public function paymentMix(Request $request)
    {
        $ninetyDaysAgo = now()->subDays(90);

        $byGateway = SubscriptionPayment::where('status', 'paid')
            ->where('paid_at', '>=', $ninetyDaysAgo)
            ->whereNotNull('gateway')
            ->groupBy('gateway')
            ->selectRaw('gateway, COUNT(*) as count, SUM(amount) as total')
            ->get();

        $total = $byGateway->sum('total');
        $rows = $byGateway->map(fn ($row) => [
            'gateway' => $row->gateway,
            'count' => (int) $row->count,
            'total' => (float) $row->total,
            'percentage' => $total > 0 ? round(((float) $row->total / $total) * 100, 1) : 0,
        ])->sortByDesc('total')->values();

        return response()->json([
            'mix' => $rows,
            'total' => (float) $total,
        ]);
    }

    /**
     * Cohort retention table — tenants grouped by signup month with
     * retention % at month 1, 2, 3, 6, 12. Reads the Subscription
     * model: a tenant is "retained" in month N if they have a paid
     * subscription covering that month.
     *
     * The output is a 2D array suitable for rendering as a heatmap:
     *   [
     *     ['month' => '2025-01', 'cohort_size' => 12, 'm1' => 92, 'm2' => 83, ...],
     *     ...
     *   ]
     * Front-end can build a coloured grid where each cell is the %.
     */
    public function cohortRetention(Request $request)
    {
        $monthsBack = (int) $request->input('months', 6);
        $monthsBack = max(1, min($monthsBack, 12));

        $startMonth = now()->copy()->subMonths($monthsBack - 1)->startOfMonth();
        $currentMonth = now()->copy()->startOfMonth();

        // Pull all tenants created in the window with their subscriptions
        // once — N+1 avoidance.
        $tenants = Tenant::with('subscriptions')
            ->where('created_at', '>=', $startMonth)
            ->get();

        $cohorts = [];

        foreach ($tenants as $tenant) {
            $signupMonth = $tenant->created_at->copy()->startOfMonth();
            $cohortKey = $signupMonth->format('Y-m');
            $cohorts[$cohortKey] ??= [
                'month' => $cohortKey,
                'cohort_size' => 0,
            ];
            $cohorts[$cohortKey]['cohort_size']++;
        }

        // For each cohort, compute retention at month N
        foreach ($cohorts as $key => &$cohort) {
            $cohortDate = Carbon::createFromFormat('Y-m', $key);

            for ($n = 1; $n <= $monthsBack; $n++) {
                $targetMonth = $cohortDate->copy()->addMonths($n);

                if ($targetMonth->gt($currentMonth)) {
                    // Future — no data yet
                    $cohort["m{$n}"] = null;
                    continue;
                }
                $retention = $this->computeRetentionAtMonth(
                    $tenants,
                    $cohortDate,
                    $targetMonth
                );
                $cohort["m{$n}"] = $retention;
            }
        }
        unset($cohort);

        ksort($cohorts);

        return response()->json([
            'cohorts' => array_values($cohorts),
            'window' => $monthsBack,
        ]);
    }

    /**
     * % of cohort tenants whose subscription covered the target month.
     * A tenant "retained" = any active subscription with starts_at <=
     * end of target month AND ends_at >= start of target month.
     */
    private function computeRetentionAtMonth($tenants, Carbon $cohortMonth, Carbon $targetMonth): int
    {
        $targetStart = $targetMonth->copy()->startOfMonth();
        $targetEnd = $targetMonth->copy()->endOfMonth();

        $cohortTenants = $tenants->filter(
            fn ($t) => $t->created_at->copy()->startOfMonth()->equalTo($cohortMonth)
        );

        $cohortSize = $cohortTenants->count();

        if ($cohortSize === 0) {
            return 0;
        }

        $retained = 0;

        foreach ($cohortTenants as $tenant) {
            foreach ($tenant->subscriptions as $sub) {
                if ($sub->status !== 'active') {
                    continue;
                }
                $startsAt = $sub->starts_at;
                $endsAt = $sub->ends_at;

                if (! $startsAt || ! $endsAt) {
                    continue;
                }

                // Subscription covers the target month if it overlaps
                if ($startsAt->lte($targetEnd) && $endsAt->gte($targetStart)) {
                    $retained++;
                    break; // one matching sub is enough
                }
            }
        }

        return (int) round(($retained / $cohortSize) * 100);
    }

    /**
     * Health-score trend for a single tenant over the last N months.
     *
     * Since we don't store historical snapshots, we recompute the
     * score at each month-end using a "what was true at that point
     * in time" replay — for every monthly tick we ask:
     *   - Were there overdue invoices on/after that date?
     *   - Was the active subscription expiring within 30 days then?
     *   - Did the tenant have any rejected receipts in the prior 90 days?
     *
     * The result is a small array of {month, score} pairs that the
     * dashboard can chart. This is a read-only computation; nothing
     * is persisted.
     */
    public function tenantHealthTrend(Request $request, Tenant $tenant)
    {
        $monthsBack = (int) $request->input('months', 6);
        $monthsBack = max(1, min($monthsBack, 24));

        $now = now();
        $points = [];

        for ($i = $monthsBack; $i >= 0; $i--) {
            $point = $now->copy()->subMonths($i)->endOfMonth();
            $points[] = [
                'month' => $point->format('Y-m'),
                'score' => $this->computeHealthScoreAt($tenant, $point),
            ];
        }

        return response()->json([
            'tenant_id' => $tenant->id,
            'points' => $points,
        ]);
    }

    /**
     * Compute the health score "as of" a given point in time. We
     * reuse the same logic as the live score in TenantsController but
     * re-anchor all time-bounded signals to the snapshot date.
     */
    private function computeHealthScoreAt(Tenant $tenant, Carbon $at): array
    {
        $score = 100;
        $flags = [];

        // Overdue invoices as of the snapshot
        $overdue = SubscriptionInvoice::where('tenant_id', $tenant->id)
            ->where('status', 'overdue')
            ->where('due_at', '<=', $at)
            ->exists();

        if ($overdue) {
            $score -= 20;
            $flags[] = 'overdue_invoice';
        }

        $activeSub = Subscription::where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->orderByDesc('ends_at')
            ->first();

        if (! $activeSub) {
            $score -= 10;
            $flags[] = 'no_active_subscription';
        } else {
            $daysToEnd = (int) $activeSub->ends_at?->diffInDays($at, false);

            if ($daysToEnd <= 30 && $daysToEnd >= 0) {
                $score -= 10;
                $flags[] = 'expiring_within_30_days';
            }
        }

        // Rejected receipts in the 90 days before the snapshot
        $hasRejection = BankTransferReceipt::where('tenant_id', $tenant->id)
            ->where('status', 'rejected')
            ->where('reviewed_at', '>=', $at->copy()->subDays(90))
            ->where('reviewed_at', '<=', $at)
            ->exists();

        if ($hasRejection) {
            $score -= 5;
            $flags[] = 'rejected_receipt_recent';
        }

        return [
            'score' => max(0, min(100, $score)),
            'flags' => $flags,
        ];
    }
}
