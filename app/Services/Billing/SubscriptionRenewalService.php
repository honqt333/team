<?php

namespace App\Services\Billing;

use App\Mail\SubscriptionInvoiceMail;
use App\Mail\SubscriptionRenewalReminderMail;
use App\Mail\SubscriptionExpiredMail;
use App\Models\Billing\Subscription;
use App\Models\Billing\SubscriptionInvoice;
use App\Services\Invoice\SubscriptionInvoiceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SubscriptionRenewalService
{
    protected SubscriptionInvoiceService $invoiceService;

    public function __construct(SubscriptionInvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
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
            } catch (\Exception $e) {
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
        // Calculate new dates
        $startsAt = Carbon::now();
        $endsAt = $subscription->billing_cycle === 'yearly'
            ? $startsAt->copy()->addYear()
            : $startsAt->copy()->addMonth();

        // Update subscription dates
        $subscription->update([
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ]);

        // Create new invoice
        $invoice = $this->invoiceService->createForSubscription($subscription);

        // Send invoice email
        if ($subscription->tenant->email) {
            try {
                Mail::to($subscription->tenant->email)->send(new SubscriptionInvoiceMail($invoice));
            } catch (\Exception $e) {
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
                    } catch (\Exception $e) {
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
     * Expire subscriptions past their end date.
     */
    protected function expireSubscriptions(array &$results): void
    {
        // Grace period in days
        $gracePeriod = config('billing.grace_period', 3);
        $expireDate = now()->subDays($gracePeriod);

        $subscriptions = Subscription::where('status', 'active')
            ->where('auto_renew', false)
            ->whereDate('ends_at', '<', $expireDate)
            ->with(['tenant', 'plan'])
            ->get();

        foreach ($subscriptions as $subscription) {
            try {
                $subscription->update(['status' => 'expired']);
                $subscription->tenant->update(['status' => 'suspended']);

                // Send expiration notice
                if ($subscription->tenant->email) {
                    Mail::to($subscription->tenant->email)->send(
                        new SubscriptionExpiredMail($subscription)
                    );
                }

                $results['expired']++;

                Log::info('Subscription expired', [
                    'subscription_id' => $subscription->id,
                    'tenant' => $subscription->tenant->trade_name,
                ]);
            } catch (\Exception $e) {
                Log::error('Subscription expiration failed', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
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
            'auto_renew' => !$subscription->auto_renew,
        ]);

        return $subscription->auto_renew;
    }
}
