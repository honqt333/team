<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Mail\SubscriptionActivatedMail;
use App\Models\AdminActivityLog;
use App\Models\Billing\Subscription;
use App\Models\Billing\SubscriptionPayment;
use App\Services\Payment\PaymentManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Centralized handler for what happens AFTER a subscription payment succeeds.
 *
 * One place to:
 * - Activate the subscription and tenant
 * - Mark the invoice paid
 * - Log the activity (audit trail)
 * - Send the activation email
 * - Stay idempotent (re-runs of the same callback do nothing harmful)
 */
class PaymentCompletionService
{
    public function __construct(protected PaymentManager $paymentManager) {}

    /**
     * Process a successful payment callback.
     *
     * Idempotent: if the payment is already 'paid', this is a no-op so a
     * duplicate gateway callback doesn't re-activate things or double-send
     * emails.
     */
    public function completePayment(
        SubscriptionPayment $payment,
        array $verifyResult,
        ?int $adminUserId = null
    ): bool {
        // Idempotency guard: if already paid, just return the existing result.
        if ($payment->status === 'paid') {
            Log::info('PaymentCompletionService: payment already paid, skipping', [
                'payment_id' => $payment->id,
                'subscription_id' => $payment->subscription_id,
            ]);

            return true;
        }

        try {
            return DB::transaction(function () use ($payment, $verifyResult, $adminUserId) {
                // 1. Mark the payment as paid
                $payment->markAsPaid($verifyResult['raw_response'] ?? []);
                $payment->update([
                    'gateway_payment_id' => $verifyResult['payment_id']
                        ?? $payment->gateway_payment_id,
                    'payment_method' => $verifyResult['payment_method']
                        ?? $payment->payment_method,
                ]);

                // 2. Activate the subscription
                $subscription = $payment->subscription;

                if (! $subscription) {
                    Log::warning('PaymentCompletionService: payment has no subscription', [
                        'payment_id' => $payment->id,
                    ]);

                    return false;
                }

                // Preserve remaining active days from previous subscription if active
                $previousActive = Subscription::where('tenant_id', $subscription->tenant_id)
                    ->where('id', '!=', $subscription->id)
                    ->where('status', 'active')
                    ->where('ends_at', '>', now())
                    ->orderByDesc('ends_at')
                    ->first();

                $baseEndDate = ($previousActive && $previousActive->ends_at)
                    ? $previousActive->ends_at
                    : now();

                $calculatedEndsAt = $subscription->billing_cycle === 'yearly'
                    ? $baseEndDate->copy()->addYear()
                    : $baseEndDate->copy()->addMonth();

                // Expire any existing active/trial subscriptions atomically when new subscription is activated
                Subscription::where('tenant_id', $subscription->tenant_id)
                    ->where('id', '!=', $subscription->id)
                    ->whereIn('status', ['active', 'trial', 'trialing'])
                    ->update(['status' => 'expired']);

                $subscription->update([
                    'status' => 'active',
                    'starts_at' => now(),
                    'ends_at' => $calculatedEndsAt,
                ]);

                // 3. Mark the invoice paid (if linked)
                if ($payment->invoice) {
                    $payment->invoice->update([
                        'status' => 'paid',
                        'paid_at' => now(),
                    ]);
                }

                // 4. Activate the tenant
                if ($subscription->tenant) {
                    $subscription->tenant->update(['status' => 'active']);
                }

                // 5. Audit log (skip if no admin context — tenant-initiated
                //    payments from /app/* don't have an admin in the request)
                if ($adminUserId) {
                    AdminActivityLog::create([
                        'admin_user_id' => $adminUserId,
                        'action' => 'subscription.payment_completed',
                        'model_type' => SubscriptionPayment::class,
                        'model_id' => $payment->id,
                        'changes' => [
                            'subscription_id' => $subscription->id,
                            'tenant_id' => $subscription->tenant_id,
                            'amount' => $payment->amount,
                            'gateway' => $payment->gateway,
                            'gateway_payment_id' => $payment->gateway_payment_id,
                            'payment_method' => $payment->payment_method,
                        ],
                    ]);
                }

                // 6. Notify the tenant's users (best-effort, outside the txn would
                //    be safer but this matches the rest of the app's pattern)
                $this->notifyTenant($subscription, $payment);

                return true;
            });
        } catch (Throwable $e) {
            Log::error('PaymentCompletionService: failed to complete payment', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Notify tenant users that the subscription is active.
     * Best-effort: if email fails, we still consider the payment complete.
     */
    protected function notifyTenant(Subscription $subscription, SubscriptionPayment $payment): void
    {
        $tenant = $subscription->tenant;

        if (! $tenant) {
            return;
        }

        try {
            // Get all tenant users with valid email addresses. The tenant
            // typically has a small number of staff (admins, accountants,
            // technicians) so sending to all is fine and keeps the logic
            // simple. Spatie roles can be checked per-recipient in the mail
            // class if we want role-based content later.
            $recipients = $tenant->users()
                ->whereNotNull('email')
                ->get();

            if ($recipients->isEmpty()) {
                return;
            }

            foreach ($recipients as $user) {
                Mail::to($user->email)->queue(
                    new SubscriptionActivatedMail($subscription, $payment, $user)
                );
            }
        } catch (Throwable $e) {
            // Don't fail the whole flow if email queueing fails
            Log::warning('PaymentCompletionService: failed to queue activation email', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
