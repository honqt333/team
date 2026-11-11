<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Tenant;
use App\Services\Billing\SubscriptionLifecycleService;

/**
 * Keeps the memoized subscription lifecycle in step with the tenant.
 *
 * `SubscriptionLifecycleService::resolveTenantState()` memoizes its
 * result for the duration of a request, because two places need it on
 * every request — `EnsureTenantActive` (the gate) and
 * `HandleInertiaRequests` (the shared state for the frontend) — and
 * resolving it costs 5 queries.
 *
 * The memo is only valid while the underlying state is unchanged. This
 * observer invalidates it whenever a field that feeds the resolution
 * changes, so a mutation made during the request is never masked by a
 * stale cached value.
 *
 * @see SubscriptionLifecycleService::resolveTenantState()
 */
class TenantObserver
{
    /**
     * The fields that take part in the lifecycle resolution.
     *
     * `status` decides the branch (suspended / expired / readonly),
     * `trial_ends_at` decides the trial window, and `ends_at` decides
     * the validity window of the active subscription.
     *
     * @var list<string>
     */
    private const LIFECYCLE_FIELDS = [
        'status',
        'trial_ends_at',
        'ends_at',
    ];

    public function updated(Tenant $tenant): void
    {
        if (! $tenant->wasChanged(self::LIFECYCLE_FIELDS)) {
            return;
        }

        $this->flush($tenant);
    }

    private function flush(Tenant $tenant): void
    {
        app(SubscriptionLifecycleService::class)->flushResolvedState((int) $tenant->getKey());
    }
}
