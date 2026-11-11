<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Mail\SubscriptionExpiredMail;
use App\Mail\SubscriptionInvoiceMail;
use App\Mail\SubscriptionRenewalReminderMail;
use App\Models\Billing\Subscription;
use App\Models\Billing\SubscriptionInvoice;
use App\Models\Tenant;
use App\Services\Invoice\SubscriptionInvoiceService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SubscriptionRenewalService
{
    protected SubscriptionInvoiceService $invoiceService;

    protected SubscriptionLifecycleService $lifecycle;

    public function __construct(
        SubscriptionInvoiceService $invoiceService,
        ?SubscriptionLifecycleService $lifecycle = null
    ) {
        $this->invoiceService = $invoiceService;
        $this->lifecycle = $lifecycle ?? app(SubscriptionLifecycleService::class);
    }

    /**
     * Process all pending renewals.
     */
    public function processRenewals(): array
    {
        $results = [
            'renewed' => 0,
            'reminders_sent' => 0,
            'expired' => 0,
            'errors' => [],
        ];

        // 1. Auto-renew subscriptions that are due today
        $this->processAutoRenewals($results);

        // 2. Send reminders for upcoming expirations
        $this->sendRenewalReminders($results);

        // 3. Expire subscriptions that are past due
        $this->expireSubscriptions($results);

        return $results;
    }

    /**
     * Auto-renew subscriptions that are due today.
     */
    protected function processAutoRenewals(array &$results): void
    {
        $subscriptions = Subscription::where('status', 'active')
            ->where('auto_renew', true)
            ->whereDate('ends_at', '<=', now())
            ->with(['tenant', 'plan'])
            ->get();

        foreach ($subscriptions as $subscription) {
            try {
                $this->renewSubscription($subscription);
                $results['renewed']++;
            } catch (Exception $e) {
                Log::error('Subscription renewal failed', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                ]);
                $results['errors'][] = [
                    'subscription_id' => $subscription->id,
                    'message' => $e->getMessage(),
                ];
            }
        }
    }

    /**
     * Renew a single subscription.
     */
    public function renewSubscription(Subscription $subscription): SubscriptionInvoice
    {
        // Preserve remaining time if active, otherwise start from current activation date
        $dates = $this->lifecycle->calculateRenewalDates(
            $subscription->tenant,
            $subscription->billing_cycle ?: 'yearly'
        );
        $startsAt = $dates['starts_at'];
        $endsAt = $dates['ends_at'];

        // Update subscription dates & reactivate
        $subscription->update([
            'status' => 'active',
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ]);

        // Create new invoice
        $invoice = $this->invoiceService->createForSubscription($subscription);

        // Send invoice email
        if ($subscription->tenant->email) {
            try {
                Mail::to($subscription->tenant->email)->send(new SubscriptionInvoiceMail($invoice));
            } catch (Exception $e) {
                Log::warning('Failed to send renewal invoice email', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Subscription renewed', [
            'subscription_id' => $subscription->id,
            'tenant' => $subscription->tenant->trade_name,
            'ends_at' => $endsAt->toDateString(),
        ]);

        return $invoice;
    }

    /**
     * Send renewal reminders.
     */
    protected function sendRenewalReminders(array &$results): void
    {
        // Reminder days before expiration
        $reminderDays = [7, 3, 1];

        foreach ($reminderDays as $days) {
            $expirationDate = now()->addDays($days)->toDateString();

            $subscriptions = Subscription::where('status', 'active')
                ->whereDate('ends_at', $expirationDate)
                ->with(['tenant', 'plan'])
                ->get();

            foreach ($subscriptions as $subscription) {
                if ($subscription->tenant->email) {
                    try {
                        Mail::to($subscription->tenant->email)->send(
                            new SubscriptionRenewalReminderMail($subscription, $days)
                        );
                        $results['reminders_sent']++;
                    } catch (Exception $e) {
                        Log::warning('Failed to send renewal reminder', [
                            'subscription_id' => $subscription->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Expire subscriptions past their configured grace period,
     * expire ended trial periods, and auto-suspend long-unactivated tenants.
     */
    protected function expireSubscriptions(array &$results): void
    {
        $gracePeriod = $this->lifecycle->getGracePeriodDays();
        $expireDate = now()->subDays($gracePeriod);

        // 1. Expire active paid subscriptions whose grace period has elapsed
        $subscriptions = Subscription::where('status', 'active')
            ->where('auto_renew', false)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', $expireDate)
            ->with(['tenant', 'plan'])
            ->get();

        foreach ($subscriptions as $subscription) {
            try {
                $subscription->update(['status' => 'expired']);

                if ($subscription->tenant && ! $subscription->tenant->subscriptions()->where('status', 'active')->where('ends_at', '>', now())->exists()) {
                    $subscription->tenant->update(['status' => 'expired']);
                }

                if ($subscription->tenant?->email) {
                    Mail::to($subscription->tenant->email)->send(
                        new SubscriptionExpiredMail($subscription)
                    );
                }

                $results['expired']++;

                Log::info('Subscription expired', [
                    'subscription_id' => $subscription->id,
                    'tenant' => $subscription->tenant?->trade_name,
                ]);
            } catch (Exception $e) {
                Log::error('Subscription expiration failed', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 2. Expire trial subscriptions once trial end date passes
        $expiredTrials = Subscription::whereIn('status', ['trial', 'trialing'])
            ->where(function ($query) {
                $query->where('trial_ends_at', '<', now())
                    ->orWhere(function ($q) {
                        $q->whereNull('trial_ends_at')->where('ends_at', '<', now());
                    });
            })
            ->with('tenant')
            ->get();

        foreach ($expiredTrials as $trialSub) {
            try {
                $trialSub->update(['status' => 'expired']);

                if ($trialSub->tenant && ! $trialSub->tenant->hasPaidSubscription()) {
                    $trialSub->tenant->update(['status' => 'expired']);
                }
                $results['expired']++;
            } catch (Exception $e) {
                Log::error('Trial expiration failed', [
                    'subscription_id' => $trialSub->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 3. Auto-suspend expired tenants once past existing DunningConfig auto_suspend_after_days
        $staleTenants = Tenant::where('status', 'expired')
            ->whereDoesntHave('subscriptions', function ($query) {
                $query->whereIn('status', ['active', 'trial', 'trialing'])
                    ->where(function ($q) {
                        $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
                    });
            })
            ->get();

        foreach ($staleTenants as $staleTenant) {
            $autoSuspendDays = $this->lifecycle->getAutoSuspendDays($staleTenant->id);

            if ($autoSuspendDays === null || $autoSuspendDays <= 0) {
                continue;
            }

            $cutoff = now()->subDays($autoSuspendDays);
            $latestSubEnd = $staleTenant->subscriptions()->max('ends_at');
            $expiredAt = $latestSubEnd ? Carbon::parse($latestSubEnd) : $staleTenant->trial_ends_at;

            if ($expiredAt && $expiredAt->lessThan($cutoff)) {
                $staleTenant->update([
                    'status' => 'suspended',
                    'suspended_at' => now(),
                    'auto_suspended_at' => now(),
                    'suspension_reason' => 'unactivated_expired_auto_suspend',
                ]);
            }
        }
    }

    /**
     * Check if subscription can be renewed.
     */
    public function canRenew(Subscription $subscription): bool
    {
        return $subscription->status === 'active'
            && $subscription->auto_renew
            && $subscription->plan;
    }

    /**
     * Toggle auto-renewal for a subscription.
     */
    public function toggleAutoRenew(Subscription $subscription): bool
    {
        $subscription->update([
            'auto_renew' => ! $subscription->auto_renew,
        ]);

        return $subscription->auto_renew;
    }
}
