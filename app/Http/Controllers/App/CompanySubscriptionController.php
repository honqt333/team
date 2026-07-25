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
                'id' => 'trial-virtual',
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

        // Subscription history
        $subscriptionHistory = $tenant->subscriptions()
            ->with(['plan', 'invoices.payments'])
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
            return response()->json([
                'error' => __('company_profile.subscription.already_pending', [
                    'plan' => $existingPending->plan?->name_ar ?? $existingPending->plan?->name_en,
                ]),
            ], 409);
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

        // Calculate subscription dates
        $startDate = now();
        $endDate = $validated['billing_cycle'] === 'yearly'
            ? $startDate->copy()->addYear()
            : $startDate->copy()->addMonth();

        DB::beginTransaction();

        try {
            // Cancel/expire existing active subscriptions
            Subscription::where('tenant_id', $tenant->id)
                ->whereIn('status', ['active', 'trial', 'trialing'])
                ->update(['status' => 'expired']);

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
                'vat_rate' => $vatRate ?? 0,
                'vat_amount' => $vatAmount,
                'discount' => $discount,
                'total' => $total,
                'status' => 'pending',
                'due_date' => now()->addDays(7),
            ]);

            // Handle bank transfer (no gateway call)
            if ($validated['gateway'] === 'bank_transfer') {
                DB::commit();

                return response()->json([
                    'subscription' => $subscription->fresh()->load('plan'),
                    'invoice' => $invoice,
                    'bank_transfer' => true,
                    'message' => __('company_profile.subscription.bank_transfer_pending'),
                ]);
            }

            // Create pending payment record
            $payment = SubscriptionPayment::create([
                'tenant_id' => $tenant->id,
                'subscription_id' => $subscription->id,
                'subscription_invoice_id' => $invoice->id,
                'amount' => $total,
                'currency' => 'SAR',
                'gateway' => $validated['gateway'],
                'status' => 'pending',
            ]);

            // Initiate with payment gateway
            $formData = $this->paymentManager->initiate([
                'amount' => $total,
                'currency' => 'SAR',
                'description' => "اشتراك {$plan->name_ar} - {$tenant->trade_name}",
                'callback_url' => route('app.subscription.callback', ['payment' => $payment->id]),
                'metadata' => [
                    'payment_id' => $payment->id,
                    'subscription_id' => $subscription->id,
                    'tenant_id' => $tenant->id,
                    'plan_id' => $plan->id,
                ],
            ], $validated['gateway']);

            // Persist the gateway-side reference (e.g. Tap charge id, Tabby
            // session id, Tamara order id) so we can reconcile even if the
            // callback never arrives. Without this, gateway_payment_id stays
            // NULL until callback and the payment is effectively orphaned.
            $gatewayPaymentId = $formData['payment_id']
                ?? $formData['session_id']
                ?? $formData['order_id']
                ?? $formData['checkout_id']
                ?? null;

            if ($gatewayPaymentId) {
                $payment->update(['gateway_payment_id' => (string) $gatewayPaymentId]);
            }

            DB::commit();

            return response()->json([
                'subscription' => $subscription->fresh()->load('plan'),
                'invoice' => $invoice,
                'payment' => $payment,
                'form_data' => $formData,
                'gateway' => $validated['gateway'],
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
            'sender_account_name' => 'required|string|max:255',
            'sender_account_number' => 'nullable|string|max:255',
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
                    'sender_account_name' => $validated['sender_account_name'],
                    'sender_account_number' => $validated['sender_account_number'] ?? null,
                    'receipt_path' => $path,
                    'receipt_filename' => $file->getClientOriginalName(),
                    'receipt_mime_type' => $file->getMimeType(),
                    'receipt_size_bytes' => $file->getSize(),
                    'status' => 'pending',
                    'review_notes' => $validated['notes'] ?? null,
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
