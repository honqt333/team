<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Billing\Plan;
use App\Models\Billing\PromoCode;
use App\Models\Billing\Subscription;
use App\Models\Billing\SubscriptionInvoice;
use App\Models\Billing\SubscriptionPayment;
use App\Models\PaymentSettings;
use App\Services\Payment\PaymentManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class CompanySubscriptionController extends Controller
{
    public function __construct(protected PaymentManager $paymentManager) {}

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

        // Current subscription
        $currentSubscription = $tenant->subscriptions()
            ->whereIn('status', ['active', 'trial', 'trialing', 'cancelled', 'expired'])
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

        return Inertia::render('Settings/Company/Subscription', [
            'current_subscription' => $currentSubscription,
            'available_plans' => $plans,
            'subscription_history' => $subscriptionHistory,
            'recent_payments' => $recentPayments,
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
        $vatRate = 15; // SA
        $vatAmount = $subtotal * ($vatRate / 100);
        $total = $subtotal + $vatAmount;

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
        $vatAmount = $subtotal * 0.15;
        $total = $subtotal + $vatAmount;

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
                'vat_rate' => 15,
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
     */
    public function callback(Request $request, SubscriptionPayment $payment)
    {
        $result = $this->paymentManager->verify($request->all(), $payment->gateway);

        if ($result['success']) {
            $payment->markAsPaid($result['raw_response'] ?? []);
            $payment->update([
                'gateway_payment_id' => $result['payment_id'] ?? null,
                'payment_method' => $result['payment_method'] ?? null,
            ]);

            $subscription = $payment->subscription;

            if ($subscription) {
                $subscription->update(['status' => 'active']);
                $payment->invoice?->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);
                $subscription->tenant->update(['status' => 'active']);
            }

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
}
