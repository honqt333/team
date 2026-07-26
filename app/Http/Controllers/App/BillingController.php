<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\BankTransferReceipt;
use App\Models\Billing\Plan;
use App\Models\Billing\Subscription;
use App\Models\Billing\SubscriptionPayment;
use App\Models\PaymentSettings;
use App\Services\Payment\PaymentManager;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Unified billing hub for the tenant — replaces the old
 * /app/settings/company/subscription page with a single Stripe-style
 * destination that surfaces:
 *
 *   - Current subscription card
 *   - Available plans (upgrade / downgrade)
 *   - Subscription history (cancelled / expired / refunded)
 *   - Pending bank transfer receipts (awaiting admin approval)
 *   - Recent payment attempts (last 10)
 *   - Active payment methods (read-only — System admin configures them)
 *
 * All data is computed in one place so the Vue hub page does not have
 * to fetch each piece separately. The page mirrors the data the old
 * CompanySubscriptionController::index used to return, plus the
 * enabled_gateways and payment_settings slices that CompanyProfileController
 * already sent to Settings/Company/Index.vue (which now lives in its
 * own dedicated section so it does not collide with the billing hub).
 *
 * The old /app/settings/company/subscription routes are preserved as
 * aliases — the controller still exposes the same POST endpoints
 * (subscribe / cancel / resume / receipt upload), and the GET routes
 * redirect to the new hub. See routes/web.php.
 */
class BillingController extends Controller
{
    public function __construct(
        protected PaymentManager $paymentManager
    ) {}

    public function index(Request $request): Response
    {
        $tenant = $request->user()->tenant;
        $tenant->load(['taxSettings', 'address', 'zatcaSettings']);

        $paymentSettings = PaymentSettings::first() ?? new PaymentSettings;
        $availableGateways = $this->paymentManager->getAvailableGateways();

        // Mirror the filter CompanyProfileController applies: only show
        // gateways that the System admin has enabled in /system/settings/payment.
        $enabledGateways = collect($availableGateways)->filter(function ($gateway) use ($paymentSettings) {
            if ($gateway['name'] === 'bank_transfer') {
                return (bool) $paymentSettings->bank_transfer_enabled;
            }

            $config = match ($gateway['name']) {
                'moyasar' => $paymentSettings->moyasar,
                'tap' => $paymentSettings->tap,
                'tabby' => $paymentSettings->tabby,
                'tamara' => $paymentSettings->tamara,
                default => null,
            };

            return $config && ($config['enabled'] ?? false);
        })->values()->all();

        // Current subscription — include pending/pending_payment so the
        // tenant sees their in-flight subscription instead of an empty card.
        $currentSubscription = $tenant->subscriptions()
            ->whereIn('status', ['active', 'trial', 'trialing', 'cancelled', 'expired', 'pending', 'pending_payment'])
            ->with('plan')
            ->orderByDesc('created_at')
            ->first();

        // Build virtual trial if no real subscription
        if (! $currentSubscription && $tenant->created_at) {
            $trialStarts = $tenant->created_at;
            $trialEnds = $tenant->trial_ends_at ?: $trialStarts->copy()->addDays(14);
            $currentSubscription = [
                'kind' => 'virtual_trial',
                'id' => 'trial-virtual',
                'plan_id' => null,
                'status' => $trialEnds->isFuture() ? 'trial' : 'expired',
                'billing_cycle' => 'yearly',
                'starts_at' => $trialStarts->toIso8601String(),
                'ends_at' => $trialEnds->toIso8601String(),
                'price' => 0,
                'auto_renew' => false,
                'plan' => [
                    'name_ar' => "فترة تجريبية {$trialEnds->diffInDays($trialStarts)} يوم",
                    'name_en' => "{$trialEnds->diffInDays($trialStarts)} days Trial",
                ],
            ];
        }

        // Plans
        $plans = Plan::where('is_active', true)
            ->orderBy('price_monthly', 'asc')
            ->get()
            ->map(function ($plan) {
                $plan->features_ar = $plan->features_ar ?? [];
                $plan->features_en = $plan->features_en ?? [];

                return $plan;
            });

        // Subscription history — past subscriptions only. The current
        // subscription is shown separately in the SubscriptionCard, so
        // we exclude it from the history list.
        $currentSubId = $currentSubscription && ! is_array($currentSubscription)
            ? $currentSubscription->id
            : null;

        $subscriptionHistory = $tenant->subscriptions()
            ->with(['plan', 'invoices.payments'])
            ->when($currentSubId, fn ($q) => $q->where('id', '!=', $currentSubId))
            ->whereIn('status', ['cancelled', 'expired', 'refunded'])
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        // Recent payment attempts
        $recentPayments = SubscriptionPayment::where('tenant_id', $tenant->id)
            ->with('subscription.plan')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // Pending bank transfer receipts (awaiting admin review)
        $pendingReceipts = BankTransferReceipt::where('tenant_id', $tenant->id)
            ->where('status', 'pending')
            ->when($currentSubId, function ($q) use ($currentSubId) {
                $q->where('subscription_id', '!=', $currentSubId);
            })
            ->with('subscription.plan')
            ->orderByDesc('created_at')
            ->get();

        // All my receipts (the dedicated /settings/company/subscription/receipts
        // page is now folded into the hub — we paginate the full list here
        // and the Vue side renders the same MyReceipts table inline).
        $allReceipts = BankTransferReceipt::where('tenant_id', $tenant->id)
            ->with(['subscription.plan', 'payment'])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return Inertia::render('Settings/Billing/Index', [
            'current_subscription' => $currentSubscription,
            'available_plans' => $plans,
            'subscription_history' => $subscriptionHistory,
            'recent_payments' => $recentPayments,
            'pending_receipts' => $pendingReceipts,
            'all_receipts' => $allReceipts,
            'enabled_gateways' => $enabledGateways,
            'payment_settings' => [
                'default_gateway' => $paymentSettings->default_gateway,
                'bank_transfer_enabled' => $paymentSettings->bank_transfer_enabled,
                'bank_accounts' => $paymentSettings->bank_accounts ?? [],
                'bank_transfer_instructions' => $paymentSettings->bank_transfer_instructions,
                'mada_enabled' => $paymentSettings->mada_enabled,
                'visa_enabled' => $paymentSettings->visa_enabled,
                'mastercard_enabled' => $paymentSettings->mastercard_enabled,
                'applepay_enabled' => $paymentSettings->applepay_enabled,
                'stcpay_enabled' => $paymentSettings->stcpay_enabled,
                'subscription_vat_enabled' => (bool) $paymentSettings->subscription_vat_enabled,
                'subscription_vat_rate' => (float) $paymentSettings->getSubscriptionVatRate(),
                'subscription_vat_inclusive' => (bool) $paymentSettings->subscription_vat_inclusive,
            ],
            'company_info' => $paymentSettings->getCompanyInfo(),
            'vat_settings' => [
                'enabled' => (bool) $paymentSettings->subscription_vat_enabled,
                'rate' => (float) $paymentSettings->getSubscriptionVatRate(),
                'inclusive' => (bool) $paymentSettings->subscription_vat_inclusive,
            ],
        ]);
    }
}
