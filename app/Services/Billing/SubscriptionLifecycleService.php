<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Models\Billing\DunningConfig;
use App\Models\Billing\Plan;
use App\Models\Billing\Subscription;
use App\Models\Setting;
use App\Models\Tenant;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Single source of truth for subscription lifecycle states, grace periods,
 * renewal date arithmetic, and subscription activation.
 */
class SubscriptionLifecycleService
{
    /**
     * The lifecycle resolved for the current request, keyed by tenant id.
     *
     * Resolving the lifecycle costs 5 queries (1 settings read + up to 4
     * subscription reads). It is needed on EVERY request, by two
     * different places:
     *
     *  - `EnsureTenantActive` middleware, to decide whether the tenant
     *    may operate at all;
     *  - `HandleInertiaRequests`, to share the state with the frontend.
     *
     * Without memoization the same computation runs twice per request —
     * roughly 10 of the 29-35 queries every page issues — and it was
     * measured on every failing performance endpoint.
     *
     * The memo is keyed by tenant id AND by a CLOCK IDENTITY — see
     * `clockKey()`. The time part matters: the resolution is
     * time-dependent (a trial that ends at 10:00 expires at 10:00), so
     * the memo must not survive a change of "now" — whether the clock
     * moved on inside the request, or a test moved it with
     * `Carbon::setTestNow()`.
     *
     * A state change made during the request (a renewal, a suspension)
     * invalidates the memo too — see `TenantObserver`.
     *
     * @var array<int, array{clock: string, state: array<string, mixed>}>
     */
    private array $resolvedStates = [];

    /**
     * Forget the memoized lifecycle, so the next resolution re-reads the
     * database. Call this after the subscription state changes.
     */
    public function flushResolvedState(?int $tenantId = null): void
    {
        if ($tenantId === null) {
            $this->resolvedStates = [];

            return;
        }

        unset($this->resolvedStates[$tenantId]);
    }

    public const STATE_ACTIVE = 'active';

    public const STATE_TRIAL = 'trial';

    public const STATE_GRACE_PERIOD = 'grace_period';

    public const STATE_EXPIRED = 'expired';

    public const STATE_SUSPENDED = 'suspended';

    public const SETTING_GRACE_PERIOD_DAYS = 'subscription.grace_period_days';

    public function getGracePeriodDays(): int
    {
        $configured = Setting::get(self::SETTING_GRACE_PERIOD_DAYS);

        if ($configured === null || $configured === '') {
            return max(0, (int) config('billing.grace_period', 7));
        }

        return max(0, (int) $configured);
    }

    public function getDefaultTrialDays(): int
    {
        $planDays = Plan::where('slug', 'trial')->value('trial_days');

        return max(1, (int) ($planDays ?: 14));
    }

    public function getAutoSuspendDays(?int $tenantId = null): ?int
    {
        if ($tenantId !== null) {
            return DunningConfig::resolveAutoSuspendDaysForTenant($tenantId);
        }

        $global = DunningConfig::query()
            ->whereNull('tenant_id')
            ->value('auto_suspend_after_days');

        return $global === null ? null : (int) $global;
    }

    /**
     * Resolve the authoritative lifecycle state for a tenant.
     *
     * States:
     * - active:       Paid/active subscription within validity window.
     * - trial:        Active trial period (eligible for trial-specific feature restrictions).
     * - grace_period: Paid subscription expired, within system grace period (work allowed).
     * - expired:      Trial or paid subscription past grace period (read-only).
     * - suspended:    Account suspended (blocked except billing & logout).
     *
     * @return array{
     *   state: string,
     *   can_operate: bool,
     *   is_on_trial: bool,
     *   is_in_grace_period: bool,
     *   grace_period_days: int,
     *   grace_days_remaining: int,
     *   days_remaining: int|null,
     *   ends_at: Carbon|null,
     *   grace_ends_at: Carbon|null
     * }
     */
    public function resolveTenantState(Tenant $tenant): array
    {
        $tenantId = (int) $tenant->getKey();
        $clock = $this->clockKey();

        // Resolved once per request — see `$resolvedStates`.
        //
        // The memo is only valid while the CLOCK is the same. The
        // resolution is time-dependent (a trial ends at a wall-clock
        // moment), so a memo computed at a different "now" would
        // describe a different state.
        //
        // The clock key is deliberately NOT the current second: the
        // middleware and the Inertia handler are separated by the whole
        // request, and a request that crosses a second boundary would
        // then MISS the memo — which is exactly the flakiness this
        // memoization is meant to remove.
        //
        // Instead the key identifies the CLOCK ITSELF:
        //  - when the clock is frozen (tests), the frozen instant is the
        //    key, so `Carbon::setTestNow()` invalidates the memo and a
        //    passing second does not;
        //  - when the clock runs normally, the key is the wall-clock
        //    minute, which is finer than any trial boundary that matters
        //    (a trial is measured in days) and coarse enough that a
        //    single request never straddles it.
        if (isset($this->resolvedStates[$tenantId])
            && $this->resolvedStates[$tenantId]['clock'] === $clock) {
            return $this->resolvedStates[$tenantId]['state'];
        }

        $state = $this->resolveTenantStateUncached($tenant);

        $this->resolvedStates[$tenantId] = [
            'clock' => $clock,
            'state' => $state,
        ];

        return $state;
    }

    /**
     * An identity for the current clock.
     */
    private function clockKey(): string
    {
        $now = Carbon::now();

        // A frozen clock is identified by the instant it is frozen at,
        // so moving it invalidates every memo.
        if (Carbon::hasTestNow()) {
            return 'frozen:'.$now->getTimestamp();
        }

        return 'wall:'.$now->format('Y-m-d-H-i');
    }

    /**
     * The actual resolution. Always goes to the database.
     */
    private function resolveTenantStateUncached(Tenant $tenant): array
    {
        $graceDays = $this->getGracePeriodDays();
        $now = Carbon::now();

        if ($tenant->status === self::STATE_SUSPENDED) {
            return $this->buildStatePayload(
                state: self::STATE_SUSPENDED,
                canOperate: false,
                isOnTrial: false,
                isInGracePeriod: false,
                graceDays: $graceDays,
                graceDaysRemaining: 0,
                daysRemaining: 0,
                endsAt: null,
                graceEndsAt: null,
            );
        }

        // 1. Active non-trial subscription with remaining time (or lifetime null ends_at)
        $activeSub = $tenant->subscriptions()
            ->where('status', 'active')
            ->where(function ($query) use ($now) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>', $now);
            })
            ->orderByDesc('ends_at')
            ->first();

        if ($activeSub && $tenant->status !== 'readonly') {
            $endsAt = $activeSub->ends_at ? Carbon::parse($activeSub->ends_at) : null;

            return $this->buildStatePayload(
                state: self::STATE_ACTIVE,
                canOperate: true,
                isOnTrial: false,
                isInGracePeriod: false,
                graceDays: $graceDays,
                graceDaysRemaining: 0,
                daysRemaining: $this->ceilDaysRemaining($now, $endsAt),
                endsAt: $endsAt,
                graceEndsAt: null,
            );
        }

        // 2. Active trial (either via subscription row or tenant status/trial_ends_at)
        $trialSub = $tenant->subscriptions()
            ->whereIn('status', ['trial', 'trialing'])
            ->orderByDesc('id')
            ->first();

        $trialEndsAt = null;

        if ($trialSub) {
            $trialEndsAt = Carbon::parse($trialSub->trial_ends_at ?? $trialSub->ends_at);
        } elseif ($tenant->status === 'trial' && $tenant->trial_ends_at) {
            $trialEndsAt = Carbon::parse($tenant->trial_ends_at);
        }

        if ($trialEndsAt && $trialEndsAt->isAfter($now) && ! in_array($tenant->status, ['expired', 'readonly'], true)) {
            return $this->buildStatePayload(
                state: self::STATE_TRIAL,
                canOperate: true,
                isOnTrial: true,
                isInGracePeriod: false,
                graceDays: $graceDays,
                graceDaysRemaining: 0,
                daysRemaining: $this->ceilDaysRemaining($now, $trialEndsAt),
                endsAt: $trialEndsAt,
                graceEndsAt: null,
            );
        }

        // 3. Past paid/active subscription: check if within system grace period
        $pastSub = $tenant->subscriptions()
            ->whereIn('status', ['active', 'expired'])
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', $now)
            ->orderByDesc('ends_at')
            ->first();

        if ($pastSub && $pastSub->ends_at) {
            $endedAt = Carbon::parse($pastSub->ends_at);
            $graceEndsAt = $endedAt->copy()->addDays($graceDays);

            if ($graceDays > 0 && $graceEndsAt->isAfter($now) && $tenant->status !== 'readonly') {
                return $this->buildStatePayload(
                    state: self::STATE_GRACE_PERIOD,
                    canOperate: true,
                    isOnTrial: false,
                    isInGracePeriod: true,
                    graceDays: $graceDays,
                    graceDaysRemaining: (int) $this->ceilDaysRemaining($now, $graceEndsAt),
                    daysRemaining: 0,
                    endsAt: $endedAt,
                    graceEndsAt: $graceEndsAt,
                );
            }

            return $this->buildStatePayload(
                state: self::STATE_EXPIRED,
                canOperate: false,
                isOnTrial: false,
                isInGracePeriod: false,
                graceDays: $graceDays,
                graceDaysRemaining: 0,
                daysRemaining: 0,
                endsAt: $endedAt,
                graceEndsAt: $graceEndsAt,
            );
        }

        // 4. Expired trial or explicit expired/readonly tenant status
        if ($trialEndsAt && $trialEndsAt->isBefore($now)) {
            return $this->buildStatePayload(
                state: self::STATE_EXPIRED,
                canOperate: false,
                isOnTrial: false,
                isInGracePeriod: false,
                graceDays: $graceDays,
                graceDaysRemaining: 0,
                daysRemaining: 0,
                endsAt: $trialEndsAt,
                graceEndsAt: null,
            );
        }

        if (in_array($tenant->status, ['expired', 'readonly'], true)) {
            return $this->buildStatePayload(
                state: self::STATE_EXPIRED,
                canOperate: false,
                isOnTrial: false,
                isInGracePeriod: false,
                graceDays: $graceDays,
                graceDaysRemaining: 0,
                daysRemaining: 0,
                endsAt: $tenant->trial_ends_at ? Carbon::parse($tenant->trial_ends_at) : null,
                graceEndsAt: null,
            );
        }

        // 5. Fallback for active tenants with no subscription records (e.g., platform default/seeded/tests)
        return $this->buildStatePayload(
            state: self::STATE_ACTIVE,
            canOperate: true,
            isOnTrial: false,
            isInGracePeriod: false,
            graceDays: $graceDays,
            graceDaysRemaining: 0,
            daysRemaining: null,
            endsAt: null,
            graceEndsAt: null,
        );
    }

    /**
     * Calculate renewal start & end dates per business rules:
     * - If the tenant still has remaining time on an active subscription (`ends_at > now()`),
     *   preserve and extend from that `ends_at`.
     * - If the subscription has already expired (including grace period or no prior active subscription),
     *   start from the current subscription/activation date (`now()`).
     *
     * @return array{starts_at: Carbon, ends_at: Carbon, carried_over_from: Carbon|null}
     */
    public function calculateRenewalDates(
        Tenant $tenant,
        string $billingCycle = 'yearly',
        ?int $excludeSubscriptionId = null
    ): array {
        $now = Carbon::now();

        $activeWithRemaining = Subscription::where('tenant_id', $tenant->id)
            ->when($excludeSubscriptionId, fn ($q) => $q->where('id', '!=', $excludeSubscriptionId))
            ->where('status', 'active')
            ->whereNotNull('ends_at')
            ->where('ends_at', '>', $now)
            ->orderByDesc('ends_at')
            ->first();

        $carriedFrom = ($activeWithRemaining && $activeWithRemaining->ends_at)
            ? Carbon::parse($activeWithRemaining->ends_at)
            : null;

        $baseEndDate = $carriedFrom ?? $now;

        $endsAt = $billingCycle === 'monthly'
            ? $baseEndDate->copy()->addMonth()
            : $baseEndDate->copy()->addYear();

        return [
            'starts_at' => $now,
            'ends_at' => $endsAt,
            'carried_over_from' => $carriedFrom,
        ];
    }

    /**
     * Atomically activate a subscription, calculating exact validity dates,
     * expiring older active/trial subscriptions, and restoring tenant status.
     */
    public function activateSubscription(Subscription $subscription): Subscription
    {
        return DB::transaction(function () use ($subscription) {
            $tenant = $subscription->tenant;
            $dates = $this->calculateRenewalDates(
                $tenant,
                $subscription->billing_cycle ?: 'yearly',
                $subscription->id
            );

            // Expire prior active/trial subscriptions so only one remains current
            Subscription::where('tenant_id', $subscription->tenant_id)
                ->where('id', '!=', $subscription->id)
                ->whereIn('status', ['active', 'trial', 'trialing'])
                ->update(['status' => 'expired']);

            $subscription->update([
                'status' => 'active',
                'starts_at' => $dates['starts_at'],
                'ends_at' => $dates['ends_at'],
                'cancelled_at' => null,
            ]);

            if ($tenant) {
                $tenant->update([
                    'status' => 'active',
                    'suspended_at' => null,
                    'auto_suspended_at' => null,
                    'suspension_reason' => null,
                ]);
            }

            return $subscription->fresh();
        });
    }

    /**
     * Atomically extend a subscription by N days.
     * If already expired (or null ends_at), starts from now() so the extension takes effect immediately.
     * Restores active/trialing state on both subscription and tenant and clears suspension fields.
     */
    public function extendSubscription(Subscription $subscription, int $days): Subscription
    {
        return DB::transaction(function () use ($subscription, $days) {
            $subscription->loadMissing(['tenant', 'plan']);
            $now = Carbon::now();

            $currentEnd = $subscription->ends_at ? Carbon::parse($subscription->ends_at) : null;
            $baseEnd = ($currentEnd && $currentEnd->isFuture()) ? $currentEnd->copy() : $now->copy();
            $newEndsAt = $baseEnd->addDays($days);

            $isTrial = in_array($subscription->status, ['trial', 'trialing'], true)
                || $subscription->plan?->slug === 'trial';

            if (! $isTrial) {
                Subscription::where('tenant_id', $subscription->tenant_id)
                    ->where('id', '!=', $subscription->id)
                    ->whereIn('status', ['active', 'trial', 'trialing'])
                    ->update(['status' => 'expired']);
            }

            $subUpdates = [
                'status' => $isTrial ? 'trialing' : 'active',
                'ends_at' => $newEndsAt,
                'cancelled_at' => null,
            ];

            if ($isTrial) {
                $subUpdates['trial_ends_at'] = $newEndsAt;
            }

            $subscription->update($subUpdates);

            if ($subscription->tenant) {
                $tenantUpdates = [
                    'status' => $isTrial ? 'trial' : 'active',
                    'suspended_at' => null,
                    'auto_suspended_at' => null,
                    'suspension_reason' => null,
                ];

                if ($isTrial) {
                    $tenantUpdates['trial_ends_at'] = $newEndsAt;
                }

                $subscription->tenant->update($tenantUpdates);
            }

            return $subscription->fresh();
        });
    }

    /**
     * Atomically extend (or adjust) a tenant's trial period without touching historical paid subscriptions.
     */
    public function extendTenantTrial(Tenant $tenant, int $days): Tenant
    {
        return DB::transaction(function () use ($tenant, $days) {
            $now = Carbon::now();
            $currentEnd = $tenant->trial_ends_at ? Carbon::parse($tenant->trial_ends_at) : null;

            if ($days >= 0) {
                $base = ($currentEnd && $currentEnd->isFuture()) ? $currentEnd->copy() : $now->copy();
            } else {
                $base = $currentEnd ? $currentEnd->copy() : $now->copy();
            }

            $newEndDate = $base->addDays($days);
            $isFuture = $newEndDate->isAfter($now);

            $trialSub = $tenant->subscriptions()
                ->where(function ($q) {
                    $q->whereIn('status', ['trial', 'trialing'])
                        ->orWhereHas('plan', fn ($pq) => $pq->where('slug', 'trial'));
                })
                ->latest('id')
                ->first();

            if ($trialSub) {
                $trialSub->update([
                    'status' => $isFuture ? 'trialing' : 'expired',
                    'ends_at' => $newEndDate,
                    'trial_ends_at' => $newEndDate,
                ]);
            }

            $tenantUpdates = [
                'trial_ends_at' => $newEndDate,
                'status' => $isFuture ? 'trial' : 'expired',
            ];

            if ($isFuture) {
                $tenantUpdates['suspended_at'] = null;
                $tenantUpdates['auto_suspended_at'] = null;
                $tenantUpdates['suspension_reason'] = null;
            }

            $tenant->update($tenantUpdates);

            return $tenant->fresh();
        });
    }

    /**
     * Lift suspension and restore the tenant's real status from its subscription/trial state.
     */
    public function reactivateTenant(Tenant $tenant): Tenant
    {
        return DB::transaction(function () use ($tenant) {
            $now = Carbon::now();

            $hasActivePaid = $tenant->subscriptions()
                ->where('status', 'active')
                ->where(function ($q) use ($now) {
                    $q->whereNull('ends_at')->orWhere('ends_at', '>', $now);
                })
                ->exists();

            $hasActiveTrialSub = $tenant->subscriptions()
                ->whereIn('status', ['trial', 'trialing'])
                ->where(function ($q) use ($now) {
                    $q->where('trial_ends_at', '>', $now)
                        ->orWhere(function ($sq) use ($now) {
                            $sq->whereNull('trial_ends_at')->where('ends_at', '>', $now);
                        });
                })
                ->exists();

            $hasFutureTrial = $hasActiveTrialSub
                || ($tenant->trial_ends_at && Carbon::parse($tenant->trial_ends_at)->isAfter($now));

            $tenantUpdates = [
                'suspended_at' => null,
                'auto_suspended_at' => null,
                'suspension_reason' => null,
            ];

            if ($hasActivePaid) {
                $tenantUpdates['status'] = 'active';
            } elseif ($hasFutureTrial) {
                $tenantUpdates['status'] = 'trial';
            } else {
                // Temporarily unset suspension so resolveTenantState can detect grace period
                $tenant->forceFill($tenantUpdates + ['status' => 'active']);

                // The tenant's status was just changed IN MEMORY, so the
                // memoized lifecycle no longer describes it. The flush is
                // mandatory here — without it the resolution below would
                // return the cached state and read the wrong branch.
                $this->flushResolvedState((int) $tenant->getKey());

                $resolved = $this->resolveTenantState($tenant);

                if ($resolved['state'] === self::STATE_GRACE_PERIOD) {
                    $tenantUpdates['status'] = 'active';
                } elseif (! $tenant->subscriptions()->exists() && ! $tenant->trial_ends_at) {
                    // Fresh tenant with no trial date configured yet
                    $tenantUpdates['status'] = 'trial';
                    $tenantUpdates['trial_ends_at'] = $now->copy()->addDays($this->getDefaultTrialDays());
                } else {
                    $tenantUpdates['status'] = 'expired';
                }
            }

            $tenant->forceFill($tenantUpdates)->save();

            // Verify all unverified users' emails of this tenant upon activation
            $tenant->users()->whereNull('email_verified_at')->update([
                'email_verified_at' => $now,
            ]);

            return $tenant->fresh();
        });
    }

    private function ceilDaysRemaining(CarbonInterface $from, ?CarbonInterface $to): ?int
    {
        if (! $to) {
            return null;
        }

        if ($to->lessThanOrEqualTo($from)) {
            return 0;
        }

        // `diffInDays($to)` is the explicit form. `floatDiffInDays()` is
        // a Carbon ALIAS for exactly this call — the signed difference —
        // but it emits an E_USER_DEPRECATED notice on every invocation
        // and PHPStan cannot see it (it is resolved through Carbon's
        // `__call`).
        //
        // NOTE: the second argument of `diffInDays()` is the "absolute"
        // flag. `floatDiffInDays($to)` is signed, so it maps to
        // `diffInDays($to)` and NOT to `diffInDays($to, true)`.
        return (int) ceil($from->diffInDays($to));
    }

    private function buildStatePayload(
        string $state,
        bool $canOperate,
        bool $isOnTrial,
        bool $isInGracePeriod,
        int $graceDays,
        int $graceDaysRemaining,
        ?int $daysRemaining,
        ?Carbon $endsAt,
        ?Carbon $graceEndsAt,
    ): array {
        return [
            'state' => $state,
            'can_operate' => $canOperate,
            'is_on_trial' => $isOnTrial,
            'is_in_grace_period' => $isInGracePeriod,
            'grace_period_days' => $graceDays,
            'grace_days_remaining' => $graceDaysRemaining,
            'days_remaining' => $daysRemaining,
            'ends_at' => $endsAt,
            'grace_ends_at' => $graceEndsAt,
        ];
    }
}
