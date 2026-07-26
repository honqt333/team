<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\BankTransferReceipt;
use App\Models\Billing\Plan;
use App\Models\Billing\PromoCode;
use App\Models\Billing\Subscription;
use App\Models\Billing\SubscriptionInvoice;
use App\Models\Billing\SubscriptionPayment;
use App\Models\PaymentSettings;
use App\Services\Billing\PaymentCompletionService;
use App\Services\Payment\PaymentManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class CompanySubscriptionController extends Controller
{
    public function __construct(
        protected PaymentManager $paymentManager,
        protected PaymentCompletionService $paymentCompletion
    ) {}

    /**
     * Show the subscription management page.
     * Re-uses CompanyProfileController data + adds payment settings.
     */
    public function index(Request $request): Response
    {
        $tenant = auth()->user()->tenant;
        $tenant->load(['taxSettings', 'address', 'zatcaSettings']);

        // Load payment settings (which gateways are enabled)
        $paymentSettings = PaymentSettings::first() ?? new PaymentSettings;
        $availableGateways = $this->paymentManager->getAvailableGateways();

        // Filter gateways that are admin-enabled
        $enabledGateways = collect($availableGateways)->filter(function ($gateway) use ($paymentSettings) {
            // bank_transfer is configured as a single boolean toggle on
            // PaymentSettings, not as a per-key array like Moyasar/Tap.
            // Treat it as its own case so it surfaces in the
            // enabled_gateways list and the SubscribeModal renders it.
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
                // Discriminator: lets the frontend tell a real Subscription
                // model apart from this virtual placeholder without relying
                // on missing field conventions (e.g. plan_id == null).
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
        // subscription (whatever its status) is shown separately in
        // SubscriptionCard so the history list should not include it
        // again. We treat all non-final statuses as "current" (active,
        // trial, trialing, pending, pending_payment) and only show
        // cancelled / expired / refunded here.
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

        // Pending bank transfer receipts (awaiting admin review).
        // Exclude receipts attached to the current in-flight subscription
        // so we don't show the same receipt twice — once in the
        // SubscriptionCard (status=pending_payment) and again here.
        $pendingReceipts = BankTransferReceipt::where('tenant_id', $tenant->id)
            ->where('status', 'pending')
            ->when($currentSubId, function ($q) use ($currentSubId) {
                $q->where('subscription_id', '!=', $currentSubId);
            })
            ->with('subscription.plan')
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Settings/Company/Subscription', [
            'current_subscription' => $currentSubscription,
            'available_plans' => $plans,
            'subscription_history' => $subscriptionHistory,
            'recent_payments' => $recentPayments,
            'pending_receipts' => $pendingReceipts,
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
            ],
            'company_info' => $paymentSettings->getCompanyInfo(),
            'vat_settings' => [
                'enabled' => (bool) $paymentSettings->subscription_vat_enabled,
                'rate' => (float) $paymentSettings->getSubscriptionVatRate(),
                'inclusive' => (bool) $paymentSettings->subscription_vat_inclusive,
            ],
        ]);
    }

    /**
     * Calculate subscription price (with promo code discount).
     * Used by frontend to display dynamic pricing.
     */
    public function calculatePrice(Request $request)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
            'promo_code' => 'nullable|string',
        ]);

        $plan = Plan::findOrFail($validated['plan_id']);
        $basePrice = $validated['billing_cycle'] === 'yearly'
            ? (float) $plan->price_yearly
            : (float) $plan->price_monthly;

        $discount = 0;
        $promo = null;

        if (! empty($validated['promo_code'])) {
            $promo = PromoCode::where('code', $validated['promo_code'])
                ->where('is_active', true)
                ->first();

            if ($promo) {
                $usage = $promo->usages()->where('tenant_id', auth()->user()->tenant_id)->count();

                if ($usage < ($promo->max_uses_per_tenant ?? 999)) {
                    $discount = $promo->discount_type === 'percentage'
                        ? $basePrice * ((float) $promo->discount_value / 100)
                        : (float) $promo->discount_value;
                    $discount = min($discount, $basePrice);
                }
            }
        }

        $subtotal = $basePrice - $discount;
        $paymentSettings = PaymentSettings::first();
        $vatCalc = $paymentSettings?->calculateVat($subtotal) ?? [
            'exclusive' => $subtotal,
            'vat' => 0,
            'total' => $subtotal,
        ];
        $vatRate = $paymentSettings?->getSubscriptionVatRate() ?? 0;
        $vatAmount = $vatCalc['vat'];
        $total = $vatCalc['total'];

        return response()->json([
            'plan' => [
                'id' => $plan->id,
                'name_ar' => $plan->name_ar,
                'name_en' => $plan->name_en,
                'billing_cycle' => $validated['billing_cycle'],
            ],
            'base_price' => round($basePrice, 2),
            'discount' => round($discount, 2),
            'subtotal' => round($subtotal, 2),
            'vat_enabled' => (bool) $paymentSettings?->subscription_vat_enabled,
            'vat_rate' => $vatRate,
            'vat_amount' => round($vatAmount, 2),
            'total' => round($total, 2),
            'promo_code' => $promo?->code,
            'currency' => 'SAR',
        ]);
    }

    /**
     * Validate promo code without committing to subscription.
     */
    public function validatePromo(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string',
        ]);

        $promo = PromoCode::where('code', $validated['code'])
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->first();

        if (! $promo) {
            return response()->json([
                'valid' => false,
                'message' => __('company_profile.subscription.promo_invalid'),
            ], 422);
        }

        $usage = $promo->usages()->where('tenant_id', auth()->user()->tenant_id)->count();

        if ($usage >= ($promo->max_uses_per_tenant ?? 999)) {
            return response()->json([
                'valid' => false,
                'message' => __('company_profile.subscription.promo_exhausted'),
            ], 422);
        }

        return response()->json([
            'valid' => true,
            'code' => $promo->code,
            'type' => $promo->discount_type,
            'value' => (float) $promo->discount_value,
            'description' => $promo->discount_type === 'percentage'
                ? "خصم {$promo->discount_value}%"
                : 'خصم '.number_format($promo->discount_value, 2).' ريال',
        ]);
    }

    /**
     * Subscribe to a plan. Creates a pending payment and returns gateway form data.
     */
    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
            'gateway' => 'required|in:moyasar,tap,tabby,tamara,bank_transfer,paytabs',
            'promo_code' => 'nullable|string',
            'use_installments' => 'boolean',
            'installment_count' => 'nullable|integer|in:2,3,4,6,12',
        ]);

        $tenant = auth()->user()->tenant;
        $plan = Plan::findOrFail($validated['plan_id']);
        $basePrice = $validated['billing_cycle'] === 'yearly'
            ? (float) $plan->price_yearly
            : (float) $plan->price_monthly;

        // Guard: reject if the tenant already has an in-flight subscription.
        // Without this, every click on "Subscribe now" creates a new pending
        // subscription, payment, and (for bank transfer) receipt — so the
        // history table fills with duplicate "pending" rows for the same
        // tenant until they realize nothing's working.
        $existingPending = $tenant->subscriptions()
            ->whereIn('status', ['pending', 'pending_payment'])
            ->latest()
            ->first();

        if ($existingPending) {
            // If the pending request has an uploaded bank receipt, it's under admin review
            $hasReceipt = $existingPending->receipts()->exists();

            if (! $hasReceipt) {
                // Online payment attempt or abandoned request: auto-cancel it so payment is never hung
                $existingPending->update(['status' => 'cancelled']);
                SubscriptionPayment::where('subscription_id', $existingPending->id)
                    ->where('status', 'pending')
                    ->update(['status' => 'failed', 'failure_reason' => 'إلغاء تلقائي لبدء محاولة سداد جديدة']);
                SubscriptionInvoice::where('subscription_id', $existingPending->id)
                    ->where('status', 'pending')
                    ->update(['status' => 'cancelled']);
            } else {
                $planName = $existingPending->plan?->name_ar ?? $existingPending->plan?->name_en ?? 'المختارة';

                return response()->json([
                    'error' => __('company_profile.subscription.receipt_under_review', ['plan' => $planName]),
                ], 409);
            }
        }

        // Apply promo
        $discount = 0;
        $promo = null;

        if (! empty($validated['promo_code'])) {
            $promo = PromoCode::where('code', $validated['promo_code'])
                ->where('is_active', true)
                ->first();

            if ($promo) {
                $usage = $promo->usages()->where('tenant_id', $tenant->id)->count();

                if ($usage < ($promo->max_uses_per_tenant ?? 999)) {
                    $discount = $promo->discount_type === 'percentage'
                        ? $basePrice * ((float) $promo->discount_value / 100)
                        : (float) $promo->discount_value;
                    $discount = min($discount, $basePrice);
                }
            }
        }

        $subtotal = $basePrice - $discount;
        $paymentSettings = PaymentSettings::first();
        $vatCalc = $paymentSettings?->calculateVat($subtotal) ?? [
            'exclusive' => $subtotal,
            'vat' => 0,
            'total' => $subtotal,
        ];
        $vatRate = $paymentSettings?->getSubscriptionVatRate() ?? 0;
        $vatAmount = $vatCalc['vat'];
        $total = $vatCalc['total'];

        // Calculate subscription dates (preserving any remaining active days)
        $previousActive = Subscription::where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->orderByDesc('ends_at')
            ->first();

        $startDate = now();
        $baseEndDate = ($previousActive && $previousActive->ends_at) ? $previousActive->ends_at : $startDate;
        $endDate = $validated['billing_cycle'] === 'yearly'
            ? $baseEndDate->copy()->addYear()
            : $baseEndDate->copy()->addMonth();

        DB::beginTransaction();

        try {
            // Create new subscription (pending until payment)
            $subscription = Subscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'status' => 'pending',
                'billing_cycle' => $validated['billing_cycle'],
                'starts_at' => $startDate,
                'ends_at' => $endDate,
                'price' => $basePrice,
                'discount_amount' => $discount,
                'promo_code' => $promo?->code,
                'auto_renew' => true,
            ]);

            // Create subscription invoice
            $invoice = SubscriptionInvoice::create([
                'subscription_id' => $subscription->id,
                'tenant_id' => $tenant->id,
                'invoice_number' => 'SUB-'.str_pad((string) $subscription->id, 6, '0', STR_PAD_LEFT).'-'.now()->format('Ymd'),
                'subtotal' => $subtotal,
                'vat' => $vatAmount, // single combined VAT column in DB
                'discount' => $discount,
                'total' => $total,
                'status' => 'pending',
                'issued_at' => now(),
                'due_at' => now()->addDays(7),
            ]);

            // Handle bank transfer: do NOT create the subscription/invoice
            // yet. The tenant has to actually upload a receipt proving they
            // transferred the money before anything is recorded. Otherwise
            // a tenant who clicks "Bank transfer" by accident creates a
            // pending subscription that the admin then has to clean up.
            // Instead, return the data the upload form needs to compute
            // price + VAT + bank account list. The receipt POST will
            // create the subscription + invoice + receipt atomically.
            if ($validated['gateway'] === 'bank_transfer') {
                DB::rollBack();

                return response()->json([
                    'bank_transfer_setup' => true,
                    'plan' => $plan,
                    'billing_cycle' => $validated['billing_cycle'],
                    'promo_code' => $promo?->code,
                    'subtotal' => $subtotal,
                    'vat' => $vatAmount,
                    'total' => $total,
                    'bank_accounts' => $paymentSettings->bank_accounts ?? [],
                    'bank_instructions' => $paymentSettings->bank_transfer_instructions,
                    'message' => __('company_profile.subscription.bank_transfer_setup_ready'),
                ]);
            }

            // Initiate with payment gateway FIRST. For hosted gateways
            // (Tap, Tabby, Tamara) the gateway creates a charge on their
            // side that references our callback URL. If we created
            // Subscription/Invoice/Payment first and the gateway call
            // failed, we'd have orphan records the admin has to clean
            // up. By initiating first and rolling back on gateway error,
            // the tenant never sees a "pending" subscription unless the
            // gateway actually accepted the payment request.
            try {
                $formData = $this->paymentManager->initiate([
                    'amount' => $total,
                    'currency' => 'SAR',
                    'description' => "اشتراك {$plan->name_ar} - {$tenant->trade_name}",
                    'callback_url' => route('app.subscription.callback', ['payment' => '__PAYMENT_ID__']),
                    'metadata' => [
                        'plan_id' => $plan->id,
                        'tenant_id' => $tenant->id,
                    ],
                ], $validated['gateway']);
            } catch (Throwable $e) {
                // Gateway call threw — roll back the orphan subscription
                // and invoice we just created so the tenant doesn't see
                // a "pending" sub the admin has to clean up.
                DB::rollBack();
                Log::error('Payment gateway initiate threw', [
                    'tenant_id' => $tenant->id,
                    'plan_id' => $plan->id,
                    'gateway' => $validated['gateway'],
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'error' => __('company_profile.subscription.gateway_unavailable', [
                        'default' => 'بوابة الدفع غير متاحة حالياً، حاول مرة أخرى',
                    ]),
                ], 502);
            }

            // Some gateways (e.g. Tabby, Tamara) return a "soft" failure
            // without throwing — they return form_data with status=rejected
            // or no payment_id at all. Treat that as a failure too.
            if (! is_array($formData) || empty($formData)) {
                DB::rollBack();

                return response()->json([
                    'error' => __('company_profile.subscription.gateway_unavailable', [
                        'default' => 'بوابة الدفع لم ترد، حاول مرة أخرى',
                    ]),
                ], 502);
            }

            $gatewayStatus = $formData['status'] ?? null;
            $allowedGatewayStatuses = ['initiated', 'pending', 'created', 'open', null];

            if ($gatewayStatus !== null && ! in_array($gatewayStatus, $allowedGatewayStatuses, true)) {
                DB::rollBack();

                return response()->json([
                    'error' => $formData['rejection']
                        ?? $formData['message']
                        ?? __('company_profile.subscription.gateway_rejected', [
                            'default' => 'تم رفض طلب الدفع من البوابة',
                        ]),
                ], 402);
            }

            // Gateway accepted the payment request. Now atomically
            // create Subscription + Invoice + Payment. The placeholder
            // payment_id in the callback URL is replaced with the real
            // one below.
            $payment = SubscriptionPayment::create([
                'tenant_id' => $tenant->id,
                'subscription_id' => $subscription->id,
                'subscription_invoice_id' => $invoice->id,
                'amount' => $total,
                'currency' => 'SAR',
                'gateway' => $validated['gateway'],
                'status' => 'pending',
            ]);

            // Persist the gateway-side reference (e.g. Tap charge id, Tabby
            // session id, Tamara order id) so we can reconcile even if the
            // callback never arrives.
            $gatewayPaymentId = $formData['payment_id']
                ?? $formData['session_id']
                ?? $formData['order_id']
                ?? $formData['checkout_id']
                ?? null;

            if ($gatewayPaymentId) {
                $payment->update(['gateway_payment_id' => (string) $gatewayPaymentId]);
            }

            // Replace the __PAYMENT_ID__ placeholder with the real id so
            // the gateway redirects back to the right callback route.
            // We do this by intercepting the form_data before returning
            // it to the frontend — the callback URL is fixed at this
            // point so we can safely swap in the real id.
            if (isset($formData['callback_url'])) {
                $formData['callback_url'] = str_replace(
                    '__PAYMENT_ID__',
                    (string) $payment->id,
                    $formData['callback_url']
                );
            }

            DB::commit();

            return response()->json([
                'subscription' => $subscription->fresh()->load('plan'),
                'invoice' => $invoice,
                'payment' => $payment,
                'form_data' => $formData,
                'gateway' => $validated['gateway'],
                'initiated' => true,
                'message' => __('company_profile.subscription.payment_initiated'),
            ]);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Subscription creation failed', [
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => __('company_profile.subscription.create_failed'),
            ], 500);
        }
    }

    /**
     * Handle payment gateway callback (for tenant-initiated payments).
     *
     * Delegates to PaymentCompletionService which:
     * - Marks the payment paid
     * - Activates the subscription + tenant
     * - Marks the invoice paid
     * - Logs an audit trail
     * - Sends the activation email
     * - Is idempotent (re-callbacks are no-ops)
     */
    public function callback(Request $request, SubscriptionPayment $payment)
    {
        $result = $this->paymentManager->verify($request->all(), $payment->gateway);

        if ($result['success']) {
            $this->paymentCompletion->completePayment($payment, $result);

            return Inertia::render('Settings/Company/SubscriptionSuccess', [
                'payment' => $payment->fresh()->load('subscription.plan'),
            ]);
        }

        $payment->markAsFailed($result['message'] ?? 'فشل الدفع', $result['raw_response'] ?? []);

        return Inertia::render('Settings/Company/SubscriptionFailed', [
            'payment' => $payment,
            'reason' => $result['message'] ?? null,
        ]);
    }

    /**
     * Cancel an in-flight pending subscription / payment (e.g. when tenant closes modal or abandons checkout).
     */
    public function cancelPending(Request $request)
    {
        $tenant = auth()->user()->tenant;

        $pendingSub = Subscription::where('tenant_id', $tenant->id)
            ->whereIn('status', ['pending', 'pending_payment'])
            ->latest()
            ->first();

        if ($pendingSub) {
            $pendingSub->update(['status' => 'cancelled']);

            SubscriptionPayment::where('subscription_id', $pendingSub->id)
                ->where('status', 'pending')
                ->update(['status' => 'failed', 'failure_reason' => 'ملغي من المستخدم']);

            SubscriptionInvoice::where('subscription_id', $pendingSub->id)
                ->where('status', 'pending')
                ->update(['status' => 'cancelled']);
        }

        return response()->json([
            'success' => true,
            'message' => __('company_profile.subscription.cancelled'),
        ]);
    }

    /**
     * Cancel current subscription.
     */
    public function cancel(Request $request, Subscription $subscription)
    {
        // Verify the subscription belongs to this tenant
        if ($subscription->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        // If no other active subscription, suspend tenant
        $hasActive = Subscription::where('tenant_id', $subscription->tenant_id)
            ->whereIn('status', ['active', 'trialing'])
            ->exists();

        if (! $hasActive) {
            $subscription->tenant->update(['status' => 'suspended']);
        }

        return back()->with('success', __('company_profile.subscription.cancelled'));
    }

    /**
     * Toggle auto-renew.
     */
    public function toggleAutoRenew(Request $request, Subscription $subscription)
    {
        if ($subscription->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $subscription->update([
            'auto_renew' => ! $subscription->auto_renew,
        ]);

        return back()->with('success', __('common.saved_success'));
    }

    /**
     * Resume a cancelled subscription (within grace period).
     */
    public function resume(Request $request, Subscription $subscription)
    {
        if ($subscription->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        if ($subscription->status !== 'cancelled') {
            return back()->with('error', __('company_profile.subscription.resume_invalid'));
        }

        $subscription->update([
            'status' => 'active',
            'cancelled_at' => null,
        ]);

        $subscription->tenant->update(['status' => 'active']);

        return back()->with('success', __('company_profile.subscription.resumed'));
    }

    /**
     * Upload a bank transfer receipt for a subscription payment.
     * Creates a BankTransferReceipt in 'pending' status for admin review.
     */
    public function uploadReceipt(Request $request, Subscription $subscription)
    {
        // Verify the subscription belongs to this tenant
        if ($subscription->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $validated = $request->validate([
            'transfer_amount' => 'required|numeric|min:0.01',
            'transfer_reference' => 'required|string|max:255',
            'transfer_date' => 'required|date',
            'bank_name' => 'required|string|max:255',
            'account_name' => 'required|string|max:255',
            'receipt' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10MB
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            return DB::transaction(function () use ($request, $subscription, $validated) {
                $file = $request->file('receipt');
                $path = $file->store("bank-transfer-receipts/{$subscription->tenant_id}", 'public');

                // Find the latest pending payment for this subscription, if any
                $payment = $subscription->payments()
                    ->whereIn('status', ['pending', 'pending_payment'])
                    ->latest()
                    ->first();

                $receipt = BankTransferReceipt::create([
                    'tenant_id' => $subscription->tenant_id,
                    'subscription_id' => $subscription->id,
                    'subscription_payment_id' => $payment?->id,
                    'transfer_amount' => $validated['transfer_amount'],
                    'transfer_reference' => $validated['transfer_reference'],
                    'transfer_date' => $validated['transfer_date'],
                    'bank_name' => $validated['bank_name'],
                    'account_name' => $validated['account_name'],
                    'receipt_path' => $path,
                    'status' => 'pending',
                    'notes' => $validated['notes'] ?? null,
                ]);

                // Move the subscription to pending_payment while admin reviews
                if ($subscription->status !== 'pending_payment') {
                    $subscription->update(['status' => 'pending_payment']);
                }

                return back()->with('success', __('company_profile.subscription.receipt_uploaded'));
            });
        } catch (Throwable $e) {
            Log::error('Bank transfer receipt upload failed', [
                'subscription_id' => $subscription->id,
                'tenant_id' => $subscription->tenant_id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['receipt' => __('company_profile.subscription.receipt_upload_failed')]);
        }
    }

    /**
     * Create a new subscription + invoice + payment + bank transfer
     * receipt atomically from one upload.
     *
     * This is the correct flow for bank-transfer payments:
     *   - The tenant picks a plan
     *   - The subscribe() controller returns a "setup" payload with
     *     plan/price/bank accounts but creates NO records
     *   - The tenant fills in the receipt upload form
     *   - This method creates Subscription + Invoice + Payment +
     *     Receipt in a single transaction (rollback on any failure)
     *
     * The previous flow (subscribe() then upload to a route param
     * subscription) created a "pending" subscription the moment the
     * tenant clicked "Bank transfer" — even before they actually
     * transferred any money. This was confusing and required the
     * admin to clean up stale pending records.
     */
    public function uploadBankTransferReceipt(Request $request)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
            'promo_code' => 'nullable|string|max:50',
            'transfer_amount' => 'required|numeric|min:0.01',
            'transfer_reference' => 'required|string|max:255',
            'transfer_date' => 'required|date',
            'bank_name' => 'required|string|max:255',
            'account_name' => 'required|string|max:255',
            'receipt' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10MB
            'notes' => 'nullable|string|max:1000',
        ]);

        $tenant = auth()->user()->tenant;
        $plan = Plan::findOrFail($validated['plan_id']);
        $basePrice = $validated['billing_cycle'] === 'yearly'
            ? (float) $plan->price_yearly
            : (float) $plan->price_monthly;

        // Guard: don't allow creating a second pending subscription.
        $existingPending = $tenant->subscriptions()
            ->whereIn('status', ['pending', 'pending_payment'])
            ->latest()
            ->first();

        if ($existingPending) {
            // Same UX as subscribe(): distinguish between `pending`
            // (payment in flight) and `pending_payment` (receipt
            // awaiting admin approval).
            $message = $existingPending->status === 'pending_payment'
                ? __('company_profile.subscription.receipt_under_review', [
                    'plan' => $existingPending->plan?->name_ar ?? $existingPending->plan?->name_en,
                    'default' => 'إيصال تحويل بنكي لـ ":plan" قيد المراجعة من الإدارة. انتظر الموافقة أو ألغِ الطلب الحالي.',
                ])
                : __('company_profile.subscription.payment_in_progress', [
                    'plan' => $existingPending->plan?->name_ar ?? $existingPending->plan?->name_en,
                    'default' => 'لديك طلب دفع جارٍ لـ ":plan" — أكمل الدفع أو ألغِه قبل رفع إيصال جديد.',
                ]);

            return response()->json([
                'error' => $message,
                'pending_status' => $existingPending->status,
                'pending_subscription_id' => $existingPending->id,
            ], 409);
        }

        // Apply promo (mirrors subscribe() logic)
        $discount = 0;

        if (! empty($validated['promo_code'])) {
            $promo = PromoCode::where('code', $validated['promo_code'])
                ->where('is_active', true)
                ->first();

            if ($promo) {
                $usage = $promo->usages()->where('tenant_id', $tenant->id)->count();

                if ($usage < ($promo->max_uses_per_tenant ?? 999)) {
                    $discount = $promo->discount_type === 'percentage'
                        ? $basePrice * ((float) $promo->discount_value / 100)
                        : (float) $promo->discount_value;
                    $discount = min($discount, $basePrice);
                }
            }
        }

        $subtotal = $basePrice - $discount;
        $paymentSettings = PaymentSettings::first();
        $vatCalc = $paymentSettings?->calculateVat($subtotal) ?? [
            'exclusive' => $subtotal,
            'vat' => 0,
            'total' => $subtotal,
        ];
        $total = $vatCalc['total'];

        try {
            return DB::transaction(function () use ($request, $tenant, $plan, $validated, $basePrice, $discount, $subtotal, $total) {
                // 1. Subscription (status=pending_payment, awaiting admin
                //    approval of the receipt)
                $previousActive = Subscription::where('tenant_id', $tenant->id)
                    ->where('status', 'active')
                    ->where('ends_at', '>', now())
                    ->orderByDesc('ends_at')
                    ->first();

                $startDate = now();
                $baseEndDate = ($previousActive && $previousActive->ends_at) ? $previousActive->ends_at : $startDate;
                $endDate = $validated['billing_cycle'] === 'yearly'
                    ? $baseEndDate->copy()->addYear()
                    : $baseEndDate->copy()->addMonth();

                $subscription = Subscription::create([
                    'tenant_id' => $tenant->id,
                    'plan_id' => $plan->id,
                    'status' => 'pending_payment',
                    'billing_cycle' => $validated['billing_cycle'],
                    'starts_at' => $startDate,
                    'ends_at' => $endDate,
                    'price' => $basePrice,
                    'discount_amount' => $discount,
                    'promo_code' => $validated['promo_code'] ?? null,
                    'auto_renew' => true,
                ]);

                // 2. Invoice
                $invoice = SubscriptionInvoice::create([
                    'subscription_id' => $subscription->id,
                    'tenant_id' => $tenant->id,
                    'invoice_number' => 'SUB-'.str_pad((string) $subscription->id, 6, '0', STR_PAD_LEFT).'-'.now()->format('Ymd'),
                    'subtotal' => $subtotal,
                    'vat' => $total - $subtotal,
                    'discount' => $discount,
                    'total' => $total,
                    'status' => 'pending',
                    'issued_at' => now(),
                    'due_at' => now()->addDays(7),
                ]);

                // 3. Payment (status=pending until admin approves receipt)
                $payment = SubscriptionPayment::create([
                    'tenant_id' => $tenant->id,
                    'subscription_id' => $subscription->id,
                    'subscription_invoice_id' => $invoice->id,
                    'amount' => $total,
                    'currency' => 'SAR',
                    'gateway' => 'bank_transfer',
                    'status' => 'pending',
                ]);

                // 4. Receipt — the only thing the tenant actively uploads.
                $file = $request->file('receipt');
                $path = $file->store("bank-transfer-receipts/{$tenant->id}", 'public');

                $receipt = BankTransferReceipt::create([
                    'tenant_id' => $tenant->id,
                    'subscription_id' => $subscription->id,
                    'subscription_payment_id' => $payment->id,
                    'transfer_amount' => $validated['transfer_amount'],
                    'transfer_reference' => $validated['transfer_reference'],
                    'transfer_date' => $validated['transfer_date'],
                    'bank_name' => $validated['bank_name'],
                    'account_name' => $validated['account_name'],
                    'receipt_path' => $path,
                    'status' => 'pending',
                    'notes' => $validated['notes'] ?? null,
                ]);

                return response()->json([
                    'success' => true,
                    'subscription' => $subscription->fresh()->load('plan'),
                    'invoice' => $invoice,
                    'payment' => $payment,
                    'receipt' => $receipt,
                    'message' => __('company_profile.subscription.receipt_uploaded'),
                ]);
            });
        } catch (Throwable $e) {
            Log::error('Bank transfer receipt+subscription creation failed', [
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => __('company_profile.subscription.receipt_upload_failed'),
            ], 500);
        }
    }

    /**
     * List the current tenant's bank transfer receipts.
     */
    public function myReceipts(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $receipts = BankTransferReceipt::where('tenant_id', $tenantId)
            ->with(['subscription.plan', 'payment'])
            ->latest()
            ->paginate(20);

        return Inertia::render('Settings/Company/MyReceipts', [
            'receipts' => $receipts,
        ]);
    }
}
