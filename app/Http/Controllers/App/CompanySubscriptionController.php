<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Exceptions\BankTransferException;
use App\Exceptions\PaymentMismatchException;
use App\Http\Controllers\Controller;
use App\Models\BankTransferReceipt;
use App\Models\Billing\Plan;
use App\Models\Billing\Subscription;
use App\Models\Billing\SubscriptionInvoice;
use App\Models\Billing\SubscriptionPayment;
use App\Models\PaymentSettings;
use App\Services\Billing\BankTransferService;
use App\Services\Billing\PaymentCompletionService;
use App\Services\Billing\PromoCodeValidator;
use App\Services\Billing\SubscriptionLifecycleService;
use App\Services\Payment\PaymentManager;
use App\Support\Permissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Throwable;

class CompanySubscriptionController extends Controller
{
    public function __construct(
        protected PaymentManager $paymentManager,
        protected PaymentCompletionService $paymentCompletion,
        protected PromoCodeValidator $promoValidator,
        protected SubscriptionLifecycleService $lifecycle,
        protected BankTransferService $bankTransferService
    ) {}

    /**
     * Show the subscription management page.
     *
     * DEPRECATED — this index page has been replaced by BillingController
     * which serves the unified /app/settings/billing hub. This method
     * is kept only because the routes file still mounts a `GET /` on
     * the legacy `/settings/company/subscription` prefix; we just
     * redirect it to the new hub so any old bookmarks still land on
     * the right place.
     */
    public function index(Request $request)
    {
        $this->authorize(Permissions::COMPANY_MANAGE);

        return redirect()->route('app.billing.index');
    }

    /**
     * Calculate subscription price (with promo code discount).
     * Used by frontend to display dynamic pricing.
     */
    public function calculatePrice(Request $request)
    {
        $this->authorize(Permissions::COMPANY_MANAGE);

        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'billing_cycle' => 'nullable|in:yearly',
            'promo_code' => 'nullable|string',
        ]);

        $plan = Plan::findOrFail($validated['plan_id']);
        $billingCycle = 'yearly';
        $basePrice = (float) $plan->price_yearly;

        $discount = 0;
        $promo = null;

        if (! empty($validated['promo_code'])) {
            // One set of rules for every place a promo code is used, so
            // the price shown here is the price that is charged.
            $check = $this->promoValidator->check($validated['promo_code'], auth()->user()->tenant_id);
            $promo = $check->promo;
            $discount = $this->promoValidator->discountFor($check, $basePrice);
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
                'billing_cycle' => $billingCycle,
            ],
            'base_price' => round($basePrice, 2),
            'discount' => round($discount, 2),
            'subtotal' => round($subtotal, 2),
            'vat_enabled' => (bool) $paymentSettings?->subscription_vat_enabled,
            'vat_rate' => $vatRate,
            'vat_amount' => round($vatAmount, 2),
            'total' => round($total, 2),
            'promo_code' => $promo?->code,
            'currency' => PaymentSettings::getPlatformCurrency(),
        ]);
    }

    /**
     * Validate promo code without committing to subscription.
     */
    public function validatePromo(Request $request)
    {
        $this->authorize(Permissions::COMPANY_MANAGE);

        $validated = $request->validate([
            'code' => 'required|string',
        ]);

        $check = $this->promoValidator->check($validated['code'], auth()->user()->tenant_id);

        if (! $check->isValid()) {
            return response()->json([
                'valid' => false,
                'message' => $this->promoValidator->messageFor($check),
            ], 422);
        }

        $promo = $check->promo;

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
        $this->authorize(Permissions::COMPANY_MANAGE);

        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'billing_cycle' => 'nullable|in:yearly',
            'gateway' => 'required|in:moyasar,tap,tabby,tamara,bank_transfer,paytabs',
            'promo_code' => 'nullable|string',
        ]);

        $tenant = auth()->user()->tenant;
        $plan = Plan::findOrFail($validated['plan_id']);
        $billingCycle = 'yearly';
        $basePrice = (float) $plan->price_yearly;

        $existingPending = $tenant->subscriptions()
            ->whereIn('status', ['pending', 'pending_payment'])
            ->latest()
            ->first();

        if ($existingPending) {
            $hasPendingReceipt = $existingPending->receipts()
                ->where('status', BankTransferReceipt::STATUS_PENDING)
                ->exists();

            if ($hasPendingReceipt) {
                $planName = $existingPending->plan?->name_ar ?? $existingPending->plan?->name_en ?? 'المختارة';

                return response()->json([
                    'error' => __('company_profile.subscription.receipt_under_review', ['plan' => $planName]),
                ], 409);
            }

            $isSamePlanBankTransfer = $validated['gateway'] === 'bank_transfer'
                && (int) $existingPending->plan_id === (int) $plan->id;

            if (! $isSamePlanBankTransfer) {
                try {
                    $this->bankTransferService->cancelUnpaidPendingSubscription(
                        $existingPending,
                        'إلغاء تلقائي لبدء محاولة اشتراك أو وسيلة دفع جديدة'
                    );
                } catch (BankTransferException $e) {
                    return response()->json(['error' => $e->getMessage()], $e->statusCode);
                }
            }
        }

        // Apply promo
        $discount = 0;
        $promo = null;

        if (! empty($validated['promo_code'])) {
            $check = $this->promoValidator->check($validated['promo_code'], $tenant->id);
            $promo = $check->promo;
            $discount = $this->promoValidator->discountFor($check, $basePrice);
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

        // Handle bank transfer BEFORE opening any DB transaction: no orphan
        // Subscription/Invoice rows are created until the tenant actually
        // submits the bank transfer receipt.
        if ($validated['gateway'] === 'bank_transfer') {
            $policy = $this->bankTransferService->getPolicyConfig($paymentSettings);

            if ($paymentSettings && ! $paymentSettings->bank_transfer_enabled) {
                return response()->json([
                    'error' => __('company_profile.subscription.bank_transfer_disabled', [
                        'default' => 'خدمة التحويل البنكي غير متاحة حالياً.',
                    ]),
                ], 422);
            }

            if ($policy['min_amount_halalas'] > 0 && $this->bankTransferService->toHalalas($total) < $policy['min_amount_halalas']) {
                return response()->json([
                    'error' => __('company_profile.subscription.bank_transfer_min_amount_error', [
                        'min' => $this->bankTransferService->fromHalalas($policy['min_amount_halalas']),
                        'default' => 'الحد الأدنى لمبلغ التحويل البنكي هو :min.',
                    ]),
                ], 422);
            }

            $pendingInvoices = SubscriptionInvoice::query()
                ->where('tenant_id', $tenant->id)
                ->whereIn('status', ['pending', 'pending_payment', 'overdue'])
                ->orderBy('due_at')
                ->get(['id', 'invoice_number', 'total', 'due_at', 'status'])
                ->map(fn (SubscriptionInvoice $inv): array => [
                    'id' => $inv->id,
                    'invoice_number' => $inv->invoice_number,
                    'amount' => (float) $inv->total,
                    'remaining_amount' => (float) $this->bankTransferService->fromHalalas(
                        $this->bankTransferService->getRemainingHalalas($inv)
                    ),
                    'due_date' => $inv->due_at?->toDateString(),
                    'status' => $inv->status,
                ])
                ->values()
                ->all();

            return response()->json([
                'bank_transfer_setup' => true,
                'plan' => $plan,
                'billing_cycle' => $billingCycle,
                'promo_code' => $promo?->code,
                'subtotal' => $subtotal,
                'vat' => $vatAmount,
                'total' => $total,
                'bank_accounts' => $paymentSettings->bank_accounts ?? [],
                'bank_instructions' => $paymentSettings->bank_transfer_instructions,
                'bank_transfer_min_amount' => (float) $this->bankTransferService->fromHalalas($policy['min_amount_halalas']),
                'bank_transfer_max_days' => $policy['max_days'],
                'bank_transfer_require_receipt' => $policy['require_receipt'],
                'pending_invoices' => $pendingInvoices,
                'message' => __('company_profile.subscription.bank_transfer_setup_ready'),
            ]);
        }

        $dates = $this->lifecycle->calculateRenewalDates($tenant, $billingCycle);
        $startDate = $dates['starts_at'];
        $endDate = $dates['ends_at'];

        DB::beginTransaction();

        try {
            // Create new subscription (pending until payment)
            $subscription = Subscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'status' => 'pending',
                'billing_cycle' => 'yearly',
                'starts_at' => $startDate,
                'ends_at' => $endDate,
                'price' => $basePrice,
                'discount_amount' => $discount,
                'promo_code' => $promo?->code,
                'auto_renew' => true,
            ]);

            if ($promo) {
                // The use of the code is recorded with the subscription,
                // so the limits on the code (how many times it may be
                // used, how many times one tenant may use it) actually
                // count. Without this row they never did.
                $this->promoValidator->recordUsage($promo, $tenant->id, $subscription->id, $discount);
            }

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

            // Initiate with payment gateway FIRST. For hosted gateways
            // (Tap, Tabby, Tamara) the gateway creates a charge on their
            // side that references our callback URL. If we created
            // Subscription/Invoice/Payment first and the gateway call
            // failed, we'd have orphan records the admin has to clean
            // up. By initiating first and rolling back on gateway error,
            // the tenant never sees a "pending" subscription unless the
            // gateway actually accepted the payment request.
            try {
                $platformCurrency = PaymentSettings::getPlatformCurrency();
                $formData = $this->paymentManager->initiate([
                    'amount' => $total,
                    'currency' => $platformCurrency,
                    'country_code' => $tenant->country,
                    'customer_phone' => $tenant->phone,
                    'customer_email' => $tenant->email,
                    'customer_name' => $tenant->trade_name ?: $tenant->name,
                    'description' => "اشتراك {$plan->name_ar} - {$tenant->trade_name}",
                    'callback_url' => route('app.billing.callback', ['payment' => '__PAYMENT_ID__']),
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
            if (empty($formData)) {
                DB::rollBack();

                return response()->json([
                    'error' => __('company_profile.subscription.gateway_unavailable', [
                        'default' => 'بوابة الدفع لم ترد، حاول مرة أخرى',
                    ]),
                ], 502);
            }

            // CRITICAL: Tap/Tabby/Tamara return {success: false, message: "..."}
            // when their API rejects the request (e.g. 401 because the admin
            // forgot to save the keys in /system/settings/payment). Without
            // this check, the controller would silently create Subscription +
            // Payment records in 'pending' state, then return a form with
            // `success: false` to the frontend — the tenant would click
            // "Pay" and get a blank Tap page. Detect this here and surface
            // the real gateway error to the user.
            if (isset($formData['success']) && $formData['success'] === false) {
                DB::rollBack();

                return response()->json([
                    'error' => $formData['message']
                        ?? $formData['rejection']
                        ?? __('company_profile.subscription.gateway_rejected', [
                            'default' => 'تم رفض طلب الدفع من البوابة',
                        ]),
                ], 402);
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
                'currency' => PaymentSettings::getPlatformCurrency(),
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
            try {
                $this->paymentCompletion->completePayment($payment, $result);
            } catch (PaymentMismatchException $e) {
                // The gateway says the transaction succeeded, but it is
                // not the transaction this payment is waiting for. The
                // subscription stays inactive.
                Log::warning('Subscription payment callback rejected', [
                    'payment_id' => $payment->id,
                    'tenant_id' => $payment->tenant_id,
                    'gateway' => $payment->gateway,
                    'reason' => $e->getMessage(),
                ]);

                $payment->markAsRejected($e->getMessage(), $result['raw_response'] ?? []);

                return Inertia::render('Settings/Company/SubscriptionFailed', [
                    'payment' => $payment->fresh(),
                    'reason' => $e->getMessage(),
                ]);
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
     * Cancel an in-flight pending subscription / payment (e.g. when tenant closes modal or abandons checkout).
     */
    public function cancelPending(Request $request)
    {
        $this->authorize(Permissions::COMPANY_MANAGE);

        $tenant = auth()->user()->tenant;

        $pendingSub = Subscription::where('tenant_id', $tenant->id)
            ->whereIn('status', ['pending', 'pending_payment'])
            ->latest()
            ->first();

        if ($pendingSub) {
            try {
                $this->bankTransferService->cancelUnpaidPendingSubscription(
                    $pendingSub,
                    'ملغي من المستخدم'
                );
            } catch (BankTransferException $e) {
                return response()->json(['error' => $e->getMessage()], $e->statusCode);
            }
        }

        return response()->json([
            'success' => true,
            'message' => __('company_profile.subscription.cancelled'),
        ]);
    }

    /**
     * Cancel current subscription.
     *
     * For paid active subscriptions that still have remaining days, we cancel
     * auto-renewal cleanly so the tenant keeps using what they already paid for
     * until `ends_at`. Only expired/trial/unpaid subscriptions are terminated immediately.
     */
    public function cancel(Request $request, Subscription $subscription)
    {
        $this->authorize(Permissions::COMPANY_MANAGE);

        if ($subscription->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $result = DB::transaction(function () use ($subscription, $validated): string {
                $lockedSub = Subscription::query()
                    ->whereKey($subscription->id)
                    ->where('tenant_id', $subscription->tenant_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (in_array($lockedSub->status, ['pending', 'pending_payment'], true)) {
                    $this->bankTransferService->cancelUnpaidPendingSubscription(
                        $lockedSub,
                        $validated['reason'] ?? 'ملغي من المستخدم'
                    );

                    return 'cancelled';
                }

                $isPaidActiveWithRemaining = $lockedSub->status === 'active'
                    && $lockedSub->ends_at
                    && $lockedSub->ends_at->isFuture();

                if ($isPaidActiveWithRemaining) {
                    $lockedSub->update([
                        'auto_renew' => false,
                        'cancelled_at' => now(),
                    ]);

                    return 'auto_renew_cancelled';
                }

                $lockedSub->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'auto_renew' => false,
                ]);

                $hasActive = Subscription::where('tenant_id', $lockedSub->tenant_id)
                    ->whereIn('status', ['active', 'trial', 'trialing'])
                    ->where(function ($q) {
                        $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
                    })
                    ->exists();

                if (! $hasActive) {
                    $lockedSub->tenant->update(['status' => 'expired']);
                }

                return 'cancelled';
            });
        } catch (BankTransferException $e) {
            if ($request->expectsJson()) {
                return response()->json(['error' => $e->getMessage()], $e->statusCode);
            }

            return back()->with('error', $e->getMessage());
        }

        $message = $result === 'auto_renew_cancelled'
            ? __('company_profile.subscription.auto_renew_cancelled')
            : __('company_profile.subscription.cancelled');

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Toggle auto-renew.
     */
    public function toggleAutoRenew(Request $request, Subscription $subscription)
    {
        $this->authorize(Permissions::COMPANY_MANAGE);

        if ($subscription->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $nextAutoRenew = ! $subscription->auto_renew;

        $subscription->update([
            'auto_renew' => $nextAutoRenew,
            'cancelled_at' => $nextAutoRenew ? null : $subscription->cancelled_at,
        ]);

        return back()->with('success', __('common.saved_success'));
    }

    /**
     * Resume a cancelled subscription (either cancelled status or scheduled cancellation at period end).
     */
    public function resume(Request $request, Subscription $subscription)
    {
        $this->authorize(Permissions::COMPANY_MANAGE);

        if ($subscription->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        // Case 1: Active subscription with scheduled cancellation (`cancelled_at` set)
        if ($subscription->status === 'active' && $subscription->cancelled_at !== null) {
            $subscription->update([
                'cancelled_at' => null,
                'auto_renew' => true,
            ]);

            return back()->with('success', __('company_profile.subscription.resumed'));
        }

        if ($subscription->status !== 'cancelled') {
            return back()->with('error', __('company_profile.subscription.resume_invalid'));
        }

        // Must have paid or approved receipt (or paid invoice), and ends_at in future
        $hasPaid = $subscription->payments()->where('status', 'paid')->exists()
            || $subscription->receipts()->where('status', 'approved')->exists()
            || $subscription->invoices()->where('status', 'paid')->exists();

        if (! $hasPaid || ($subscription->ends_at && $subscription->ends_at->isPast())) {
            return back()->with('error', __('company_profile.subscription.resume_invalid'));
        }

        $subscription->update([
            'status' => 'active',
            'cancelled_at' => null,
            'auto_renew' => true,
        ]);

        $subscription->tenant->update([
            'status' => 'active',
            'suspended_at' => null,
            'auto_suspended_at' => null,
            'suspension_reason' => null,
        ]);

        return back()->with('success', __('company_profile.subscription.resumed'));
    }

    /**
     * Upload a bank transfer receipt for a subscription payment.
     * Creates a BankTransferReceipt in 'pending' status for admin review.
     */
    public function uploadReceipt(Request $request, Subscription $subscription)
    {
        $this->authorize(Permissions::COMPANY_MANAGE);

        // Verify the subscription belongs to this tenant
        if ($subscription->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $tenant = auth()->user()->tenant;

        if (! $tenant) {
            abort(403);
        }

        $policy = $this->bankTransferService->getPolicyConfig();
        $idempotencyKey = $request->header('Idempotency-Key') ?: $request->input('idempotency_key');
        $normalizedKey = is_string($idempotencyKey) ? $idempotencyKey : null;
        $validated = $request->validate(
            $this->bankTransferService->buildValidationRules(
                (int) $subscription->tenant_id,
                false,
                $policy['require_receipt'],
                $normalizedKey
            )
        );

        try {
            $this->bankTransferService->submitReceipt(
                $tenant,
                $validated,
                $request->file('receipt'),
                $normalizedKey,
                $subscription
            );

            return back()->with('success', __('company_profile.subscription.receipt_uploaded'));
        } catch (BankTransferException $e) {
            return back()->withErrors(['receipt' => $e->getMessage()]);
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
        $this->authorize(Permissions::COMPANY_MANAGE);

        $tenant = auth()->user()->tenant;

        if (! $tenant) {
            return response()->json(['error' => 'لا يوجد مستأجر مرتبط بحسابك'], 403);
        }

        $policy = $this->bankTransferService->getPolicyConfig();
        $requirePlan = ! $request->filled('subscription_invoice_id');
        $idempotencyKey = $request->header('Idempotency-Key') ?: $request->input('idempotency_key');
        $normalizedKey = is_string($idempotencyKey) ? $idempotencyKey : null;
        $validated = $request->validate(
            $this->bankTransferService->buildValidationRules(
                (int) $tenant->id,
                $requirePlan,
                $policy['require_receipt'],
                $normalizedKey
            )
        );

        try {
            $result = $this->bankTransferService->submitReceipt(
                $tenant,
                $validated,
                $request->file('receipt'),
                $normalizedKey
            );

            return response()->json([
                'success' => true,
                'idempotent_replay' => $result['idempotent_replay'],
                'subscription' => $result['subscription'],
                'invoice' => $result['invoice'],
                'payment' => $result['payment'],
                'receipt' => $result['receipt'],
                'message' => __('company_profile.subscription.receipt_uploaded'),
            ]);
        } catch (BankTransferException $e) {
            return response()->json(array_merge([
                'error' => $e->getMessage(),
            ], $e->context), $e->statusCode);
        } catch (Throwable $e) {
            Log::error('Bank transfer receipt+subscription creation failed', [
                'tenant_id' => $tenant->id,
                'plan_id' => $validated['plan_id'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => __('company_profile.subscription.receipt_upload_failed'),
            ], 500);
        }
    }

    /**
     * Real-time check used by the upload form: does this tenant already
     * have a PENDING receipt with this transfer reference? The form
     * calls this on every keystroke (debounced) so the user sees the
     * "duplicate reference" error before they hit Submit.
     *
     * Returns JSON: { exists: bool, receipt_id?: int }
     */
    public function checkTransferReference(Request $request)
    {
        $this->authorize(Permissions::COMPANY_MANAGE);

        $validated = $request->validate([
            'ref' => 'required|string|max:255',
        ]);

        $tenant = auth()->user()->tenant;

        if (! $tenant) {
            return response()->json(['exists' => false]);
        }

        $existing = BankTransferReceipt::where('tenant_id', $tenant->id)
            ->where('transfer_reference', $validated['ref'])
            ->where('status', BankTransferReceipt::STATUS_PENDING)
            ->first(['id', 'created_at']);

        return response()->json([
            'exists' => (bool) $existing,
            'receipt_id' => $existing?->id,
        ]);
    }

    /**
     * List the current tenant's bank transfer receipts.
     *
     * DEPRECATED — receipts are now shown inline in the Billing hub
     * (`/app/settings/billing`). The legacy GET route is kept mounted
     * so bookmarks/external links still resolve, but they just redirect
     * to the new unified page.
     */
    public function myReceipts(Request $request)
    {
        $this->authorize(Permissions::COMPANY_MANAGE);

        return redirect()->route('app.billing.index');
    }

    /**
     * Update the tenant's dunning preferences — opt-out flag and
     * preferred language for collection reminders. Phase 6.
     *
     * This is the tenant's last line of control: even if the admin
     * has dunning globally enabled, a tenant who toggles
     * dunning_opted_out = true will not receive any reminders
     * (DunningService::findCandidates() short-circuits on it).
     */
    public function updateDunningPreferences(Request $request)
    {
        $this->authorize(Permissions::COMPANY_MANAGE);

        $validated = $request->validate([
            'dunning_opted_out' => ['required', 'boolean'],
            'dunning_language' => ['nullable', 'in:ar,en'],
        ]);

        $tenant = $request->user()->tenant;

        if (! $tenant) {
            return back()->withErrors(['dunning_opted_out' => __('No tenant context.')]);
        }

        $tenant->update([
            'dunning_opted_out' => $validated['dunning_opted_out'],
            'dunning_language' => $validated['dunning_language'] ?? null,
        ]);

        return back()->with('success', __('Dunning preferences updated.'));
    }
}
