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
use App\Support\Permissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
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

    public function downloadReceipt(Request $request, BankTransferReceipt $receipt)
    {
        abort_unless((int) $receipt->tenant_id === (int) $request->user()->tenant_id, 403);
        $this->authorize(Permissions::COMPANY_MANAGE);
        $disk = Storage::disk(
            Storage::disk('local')->exists($receipt->receipt_path ?? '') ? 'local' : 'public'
        );
        abort_unless($receipt->receipt_path && $disk->exists($receipt->receipt_path), 404);

        return $disk->response($receipt->receipt_path, basename($receipt->receipt_path), ['Cache-Control' => 'private, no-store'], 'inline');
    }

    public function index(Request $request): Response
    {
        $tenant = $request->user()->tenant;
        $tenant->load(['taxSettings', 'address', 'zatcaSettings']);

        // Validate the filter / search / pagination inputs that drive
        // the receipts list (section #6 in the Vue page). Without
        // validation an attacker could pass `status=garbage` and
        // either match zero rows (silent) or trigger a SQL error.
        $filter = $request->validate([
            'receipt_status' => ['nullable', 'string', Rule::in(['all', 'pending', 'approved', 'rejected'])],
            'receipt_search' => ['nullable', 'string', 'max:100'],
            'receipt_per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

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

        // Prioritized selection for Current Subscription:
        // Priority 1: Valid active or trialing subscription (ends_at in future or null)
        $currentSubscription = $tenant->subscriptions()
            ->whereIn('status', ['active', 'trial', 'trialing'])
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->with('plan')
            ->orderByDesc('ends_at')
            ->first();

        // Priority 2: In-flight subscription with an uploaded bank transfer receipt awaiting review
        if (! $currentSubscription) {
            $currentSubscription = $tenant->subscriptions()
                ->whereIn('status', ['pending', 'pending_payment'])
                ->whereHas('receipts', function ($q) {
                    $q->where('status', 'pending');
                })
                ->with('plan')
                ->latest()
                ->first();
        }

        // Priority 3: Legitimate prior subscription (active/trial/paid) that expired or was cancelled
        if (! $currentSubscription) {
            $currentSubscription = $tenant->subscriptions()
                ->whereIn('status', ['active', 'trial', 'trialing', 'cancelled', 'expired'])
                ->with('plan')
                ->latest()
                ->first();
        }

        // Priority 4: Build virtual trial if no real subscription
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

        // Subscription history — legitimate past subscriptions except the
        // current one. Only subscriptions with paid payments, uploaded receipts,
        // or trials are included. Provisional unconfirmed checkout attempts
        // are excluded (they appear in recentPayments).
        $currentSubId = $currentSubscription && ! is_array($currentSubscription)
            ? $currentSubscription->id
            : null;

        $subscriptionHistory = $tenant->subscriptions()
            ->with(['plan', 'invoices.payments'])
            ->when($currentSubId, fn ($q) => $q->where('id', '!=', $currentSubId))
            ->where(function ($q) {
                $q->whereHas('payments', fn ($p) => $p->where('status', 'paid'))
                    ->orWhereHas('receipts')
                    ->orWhereIn('status', ['trial', 'trialing']);
            })
            ->orderByDesc('created_at')
            ->limit(50)
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
        // page is now folded into the hub). The list is filterable by
        // status (all / pending / approved / rejected) and searchable
        // by plan name / transfer reference / bank name — query params
        // are read from $filter (validated above). We also pass a
        // per-status count so the Vue filter chips can show counts
        // without a second round-trip.
        $allReceiptsQuery = BankTransferReceipt::where('tenant_id', $tenant->id)
            ->with(['subscription.plan', 'payment']);

        // Filter: status chip (default 'all' = no filter)
        if (! empty($filter['receipt_status']) && $filter['receipt_status'] !== 'all') {
            $allReceiptsQuery->where('status', $filter['receipt_status']);
        }

        // Search: match plan name_ar/name_en, transfer reference, or
        // bank name. The OR group is wrapped in a closure so the
        // surrounding query builder doesn't have to worry about
        // operator precedence when combined with the WHERE status.
        if (! empty($filter['receipt_search'])) {
            $needle = '%'.$filter['receipt_search'].'%';
            $allReceiptsQuery->where(function ($q) use ($needle) {
                $q->where('transfer_reference', 'like', $needle)
                    ->orWhere('bank_name', 'like', $needle)
                    ->orWhere('account_name', 'like', $needle)
                    ->orWhereHas('subscription.plan', function ($plan) use ($needle) {
                        $plan->where('name_ar', 'like', $needle)
                            ->orWhere('name_en', 'like', $needle);
                    });
            });
        }

        // Per-status counts for the chip badges. We use the base
        // query (no status filter applied) so the counts always
        // reflect the full distribution regardless of which
        // chip is active.
        $statusCounts = (clone $allReceiptsQuery)
            ->reorder()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
        $statusCounts['all'] = array_sum($statusCounts);

        $perPage = $filter['receipt_per_page'] ?? 20;
        $allReceipts = $allReceiptsQuery
            ->orderByDesc('created_at')
            ->limit($perPage)
            ->get();

        return Inertia::render('Settings/Billing/Index', [
            'current_subscription' => $currentSubscription,
            'storage_usage' => $tenant->storage_usage,
            'available_plans' => $plans,
            'subscription_history' => $subscriptionHistory,
            'recent_payments' => $recentPayments,
            'pending_receipts' => $pendingReceipts,
            'all_receipts' => $allReceipts,
            'receipt_status_counts' => $statusCounts,
            'receipt_filter' => [
                'status' => $filter['receipt_status'] ?? 'all',
                'search' => $filter['receipt_search'] ?? '',
                'per_page' => $perPage,
            ],
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
            // Phase 6: tenant's dunning preferences (opt-out + lang).
            // Used by DunningPreferencesCard in the Billing page.
            'tenant' => $tenant ? [
                'id' => $tenant->id,
                'dunning_opted_out' => (bool) $tenant->dunning_opted_out,
                'dunning_language' => $tenant->dunning_language,
            ] : null,
        ]);
    }
}
