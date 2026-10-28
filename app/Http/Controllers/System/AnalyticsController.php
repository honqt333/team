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
     * Churn rate over a window.
     *
     * The base is the subscriptions that were RUNNING at the start of
     * the window, and the numerator is the cancellations that happened
     * inside the window among that base.
     *
     * The old version counted the subscriptions whose status is
     * `active` NOW and that were created before the window. A
     * subscription that was active at the start and got cancelled
     * during the window fell out of the base while still being counted
     * in the numerator, so the rate came out too high — and could pass
     * 100% when subscriptions created inside the window were cancelled
     * too. The dates on the subscription are the historical record, so
     * they are what decides membership of the base now.
     */
    private function computeChurnRate(Carbon $from, Carbon $to): float
    {
        // The base: started by the start of the window, not yet ended,
        // not cancelled before it.
        $base = Subscription::where('starts_at', '<=', $from)
            ->where(function ($query) use ($from) {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', $from);
            })
            ->where(function ($query) use ($from) {
                $query->whereNull('cancelled_at')->orWhere('cancelled_at', '>=', $from);
            })
            ->count();

        if ($base === 0) {
            return 0.0;
        }

        // Cancellations inside the window, among that base.
        // `cancelled_at` is the dated event; `updated_at` is the
        // fallback for rows written before that column existed.
        $cancelledDuring = Subscription::where('status', 'cancelled')
            ->where('starts_at', '<=', $from)
            ->whereRaw('COALESCE(cancelled_at, updated_at) BETWEEN ? AND ?', [$from, $to])
            ->count();

        // The numerator is a subset of the base, so the rate cannot
        // pass 100% — the clamp is a guard against a future change
        // breaking that assumption, not a correction.
        return round(min(100, ($cancelledDuring / $base) * 100), 1);
    }

    /**
     * Top 10 plans by active subscriber count. Used in the dashboard
     * to show which tiers are popular. Names are returned in the
     * current locale so the front end doesn't have to look them up.
     *
     * The MRR of a plan is the monthly-normalised sum of what its
     * subscribers actually pay — the same calculation as the headline
     * MRR. It used to be `subscribers × list price`, which ignored
     * the yearly cycle and every discount, so a discounted yearly
     * plan showed a monthly figure the tenant was not paying.
     */
    public function topPlans(Request $request)
    {
        $locale = app()->getLocale();

        $rows = Plan::query()
            ->with(['subscriptions' => fn ($q) => $q->where('status', 'active')])
            ->where('is_active', true)
            ->get(['id', 'name', 'name_ar', 'name_en', 'price_monthly', 'price_yearly'])
            ->sortByDesc(fn ($plan) => $plan->subscriptions->count())
            ->take(10)
            ->values();

        $plans = $rows->map(fn ($plan) => [
            'id' => $plan->id,
            'name' => $locale === 'ar'
                ? ($plan->name_ar ?? $plan->name)
                : ($plan->name_en ?? $plan->name),
            'active_subscribers' => $plan->subscriptions->count(),
            'mrr' => round($plan->subscriptions->sum(function ($sub) {
                $price = (float) $sub->price;

                return $sub->billing_cycle === 'yearly'
                    ? $price / 12
                    : $price;
            }), 2),
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
            // The schema has no status-change history, so a past month
            // is reconstructed from the dates on the subscription
            // (start, scheduled end, cancellation date). A month in
            // which a subscription was edited rather than cancelled is
            // therefore judged by the subscription's own dates.
            'basis' => 'subscription_dates',
        ]);
    }

    /**
     * % of cohort tenants whose subscription covered the target month.
     *
     * The coverage is decided by the DATES of the subscription, not by
     * its current status. The old version required `status === 'active'`
     * and asked for a date overlap, so a subscription that covered the
     * month and expired or was cancelled afterwards counted as "not
     * retained" in a month it was actually paid for.
     *
     * A subscription that was cancelled early ends at its cancellation
     * date, not at the scheduled end — `cancelled_at` is the dated
     * event, so it is what the overlap uses.
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
                $startsAt = $sub->starts_at;
                $endsAt = $sub->ends_at;

                if (! $startsAt || ! $endsAt) {
                    continue;
                }

                // An early cancellation ends the subscription there.
                if ($sub->cancelled_at && $sub->cancelled_at->lt($endsAt)) {
                    $endsAt = $sub->cancelled_at;
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
            // See computeHealthScoreAt(): the past is reconstructed from
            // the dates on the records, because the schema keeps no
            // status-change history.
            'basis' => 'record_dates',
        ]);
    }

    /**
     * Compute the health score "as of" a given point in time.
     *
     * Every signal is decided by the DATES on the record, not by its
     * current status, so a month is judged by what was true then:
     *
     *   - an invoice was overdue if it was due by then and had not
     *     been paid yet at that moment (it may be paid now);
     *   - a subscription was running if it had started and not ended
     *     by then, counting an early cancellation at its cancellation
     *     date;
     *   - a receipt was rejected if it had been reviewed as rejected
     *     in the 90 days before the snapshot.
     *
     * LIMITATION: there is no status-change history in the schema, so
     * a subscription that was edited (rather than cancelled) has no
     * dated record of the edit. The response says so, so the trend is
     * not read as an exact reconstruction.
     */
    private function computeHealthScoreAt(Tenant $tenant, Carbon $at): array
    {
        $score = 100;
        $flags = [];

        // Overdue invoices as of the snapshot: due by then, and not
        // yet paid at that moment.
        $overdue = SubscriptionInvoice::where('tenant_id', $tenant->id)
            ->where('due_at', '<=', $at)
            ->where(function ($query) use ($at) {
                $query->whereNull('paid_at')->orWhere('paid_at', '>', $at);
            })
            ->exists();

        if ($overdue) {
            $score -= 20;
            $flags[] = 'overdue_invoice';
        }

        // The subscription that was running at the snapshot.
        //
        // The effective end of a subscription is the earlier of its
        // scheduled end and its cancellation date, so the row is picked
        // in the database instead of loading every subscription of the
        // tenant into memory for each point of the trend.
        $runningSub = Subscription::where('tenant_id', $tenant->id)
            ->where('starts_at', '<=', $at)
            ->whereNotNull('ends_at')
            ->whereRaw(
                'CASE WHEN cancelled_at IS NOT NULL AND cancelled_at < ends_at THEN cancelled_at ELSE ends_at END >= ?',
                [$at]
            )
            ->orderByDesc('ends_at')
            ->first();

        if (! $runningSub) {
            $score -= 10;
            $flags[] = 'no_active_subscription';
        } else {
            $endsAt = $runningSub->ends_at;

            if ($runningSub->cancelled_at && $runningSub->cancelled_at->lt($endsAt)) {
                $endsAt = $runningSub->cancelled_at;
            }

            $daysToEnd = (int) $endsAt->diffInDays($at, false);

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
