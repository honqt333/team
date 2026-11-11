<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Billing\Plan;
use App\Models\Tenant;
use App\Services\Billing\SubscriptionLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Guards the request-scoped memoization of the subscription lifecycle.
 *
 * The lifecycle resolution costs 5 queries and is needed on EVERY
 * request, by two different places:
 *
 *  - `EnsureTenantActive` middleware, which decides whether the tenant
 *    may operate at all;
 *  - `HandleInertiaRequests`, which shares the state with the frontend.
 *
 * Before the memoization, the same computation ran twice per request —
 * roughly 10 of the 29-35 queries every page issued — and that was the
 * cause of every failing performance budget.
 *
 * The memo has three ways to go wrong, and each one has a test here:
 *
 *  1. it could serve a STALE value after the tenant's state changed;
 *  2. it could serve a stale value after the CLOCK moved on — the
 *     resolution is time-dependent, because a trial ends at a wall-clock
 *     moment;
 *  3. it could LEAK across requests, which would be a correctness
 *     disaster under Octane.
 */
class SubscriptionLifecycleMemoizationTest extends TestCase
{
    use RefreshDatabase;

    private function service(): SubscriptionLifecycleService
    {
        return app(SubscriptionLifecycleService::class);
    }

    private function tenantWithSubscription(): Tenant
    {
        $tenant = Tenant::factory()->create([
            'status' => 'active',
            'trial_ends_at' => null,
        ]);

        $plan = Plan::create([
            'name' => 'Monthly',
            'name_ar' => 'شهري',
            'name_en' => 'Monthly',
            'slug' => 'memo-'.uniqid(),
            'price_monthly' => 0,
            'price_yearly' => 0,
            'trial_days' => 14,
            'is_active' => true,
        ]);

        $tenant->subscriptions()->create([
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'yearly',
            'starts_at' => now()->subDays(1),
            'ends_at' => now()->addDays(365),
            'price' => 0,
        ]);

        return $tenant->fresh();
    }

    public function test_the_state_is_resolved_from_the_database_the_first_time(): void
    {
        $tenant = $this->tenantWithSubscription();

        $state = $this->service()->resolveTenantState($tenant);

        $this->assertSame(SubscriptionLifecycleService::STATE_ACTIVE, $state['state']);
        $this->assertTrue($state['can_operate']);
    }

    public function test_the_second_resolution_in_the_same_request_does_not_query_again(): void
    {
        $tenant = $this->tenantWithSubscription();

        $this->service()->resolveTenantState($tenant);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->service()->resolveTenantState($tenant);
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(
            0,
            $count,
            'The second resolution in the same request must not hit the database — that is the whole point of the memo.'
        );
    }

    public function test_a_state_change_invalidates_the_memo(): void
    {
        $tenant = $this->tenantWithSubscription();

        $this->assertSame(
            SubscriptionLifecycleService::STATE_ACTIVE,
            $this->service()->resolveTenantState($tenant)['state']
        );

        // Suspend the tenant. The memo must not keep serving 'active'.
        $tenant->update(['status' => 'suspended', 'suspended_at' => now()]);

        $after = $this->service()->resolveTenantState($tenant->fresh());

        $this->assertSame(
            SubscriptionLifecycleService::STATE_SUSPENDED,
            $after['state'],
            'The memo must not survive a change of the tenant status.'
        );
        $this->assertFalse($after['can_operate']);
    }

    public function test_a_change_of_time_invalidates_the_memo(): void
    {
        $tenant = $this->tenantWithSubscription();

        $this->assertSame(
            SubscriptionLifecycleService::STATE_ACTIVE,
            $this->service()->resolveTenantState($tenant)['state']
        );

        // Move the clock past the end of the subscription.
        Carbon::setTestNow(now()->addDays(400));

        $after = $this->service()->resolveTenantState($tenant->fresh());

        $this->assertNotSame(
            SubscriptionLifecycleService::STATE_ACTIVE,
            $after['state'],
            'The resolution is time-dependent — a memo computed at an earlier instant must not be served.'
        );

        Carbon::setTestNow();
    }

    public function test_the_memo_survives_the_clock_advancing_within_a_request(): void
    {
        $tenant = $this->tenantWithSubscription();

        $this->service()->resolveTenantState($tenant);

        // The middleware and the Inertia handler are separated by the
        // whole request. If the request crosses a SECOND boundary, the
        // memo must still hit — otherwise the saving is a coin flip and
        // the query count becomes flaky.
        //
        // This is a real regression that was caught by
        // `PerfQueryShapeTest`: with the memo keyed on the current
        // second, the delta was 0 in isolation and 4 in the full suite,
        // depending purely on where the second boundary fell.
        //
        // The clock here is the RUNNING clock (no `setTestNow`), so the
        // memo key is the wall-clock minute — a request that takes a
        // second or two never straddles it.
        $before = now()->format('Y-m-d-H-i');

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->service()->resolveTenantState($tenant);
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(
            0,
            $count,
            'The memo must survive the clock advancing inside a request — otherwise the query saving is flaky.'
        );

        $this->assertSame(
            $before,
            now()->format('Y-m-d-H-i'),
            'The test is only meaningful while the clock has not moved to the next minute.'
        );
    }

    public function test_the_memo_is_scoped_to_the_request_and_never_leaks(): void
    {
        $tenant = $this->tenantWithSubscription();

        $instance = $this->service();
        $instance->resolveTenantState($tenant);

        // `scoped()` returns the SAME instance for the whole request —
        // that is what makes the memo effective — and a FRESH instance
        // once the scope is flushed, which is what happens between two
        // requests (and between two requests under Octane).
        $this->assertSame(
            $instance,
            $this->app->make(SubscriptionLifecycleService::class),
            'Within one request the service must be the same instance, otherwise the memo never hits.'
        );

        $this->app->forgetScopedInstances();

        $this->assertNotSame(
            $instance,
            $this->app->make(SubscriptionLifecycleService::class),
            'After the scope is flushed the service must be a new instance, so no memo can leak into the next request.'
        );
    }

    public function test_an_explicit_flush_invalidates_the_memo(): void
    {
        $tenant = $this->tenantWithSubscription();

        $this->service()->resolveTenantState($tenant);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->service()->flushResolvedState((int) $tenant->getKey());
        $this->service()->resolveTenantState($tenant);
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertGreaterThan(
            0,
            $count,
            'After an explicit flush the next resolution must go back to the database.'
        );
    }
}
