<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\BankTransferReceipt;
use App\Models\Billing\Plan;
use App\Models\Billing\Subscription;
use App\Models\Billing\SubscriptionInvoice;
use App\Models\Billing\SubscriptionPayment;
use App\Models\PaymentSettings;
use App\Models\Tenant;
use App\Services\Billing\PromoCodeValidator;
use App\Services\Billing\SubscriptionLifecycleService;
use App\Services\System\SystemAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionsController extends Controller
{
    public function __construct(
        protected SystemAuditService $audit,
        protected ?SubscriptionLifecycleService $lifecycle = null
    ) {
        $this->lifecycle ??= app(SubscriptionLifecycleService::class);
    }

    /**
     * Display a listing of subscriptions.
     */
    public function index(Request $request): Response
    {
        $query = Subscription::with(['tenant', 'plan'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->status && $request->status !== 'all') {
            if ($request->status === 'trial') {
                $query->whereIn('status', ['trial', 'trialing']);
            } else {
                $query->where('status', $request->status);
            }
        }

        // Filter by plan
        if ($request->plan_id) {
            $query->where('plan_id', $request->plan_id);
        }

        // Search
        if ($request->search) {
            $search = $request->search;
            $query->whereHas('tenant', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('trade_name', 'like', "%{$search}%");
            });
        }

        $subscriptions = $query->paginate(20)->withQueryString();

        return Inertia::render('System/Subscriptions/Index', [
            'subscriptions' => $subscriptions,
            'plans' => Plan::where('is_active', true)->get(['id', 'name_ar', 'name_en', 'slug', 'price_yearly', 'trial_days']),
            'filters' => $request->only(['search', 'status', 'plan_id']),
            'stats' => [
                'total' => Subscription::count(),
                'active' => Subscription::where('status', 'active')->count(),
                'trial' => Subscription::whereIn('status', ['trial', 'trialing'])->count(),
                'cancelled' => Subscription::where('status', 'cancelled')->count(),
                'expired' => Subscription::where('status', 'expired')->count(),
            ],
            'eligibleTenants' => Tenant::get(['id', 'name', 'trade_name']),
        ]);
    }

    /**
     * Show the form for creating a new subscription.
     *
     * Provides a dedicated page for creating a subscription (used when the
     * admin navigates to /system/subscriptions/create directly). The Index
     * page also has its own modal-based creation flow.
     */
    public function create(): Response
    {
        return Inertia::render('System/Subscriptions/Create', [
            'plans' => Plan::where('is_active', true)
                ->orderBy('sort_order')
                ->get(['id', 'name_ar', 'name_en', 'slug', 'price_yearly', 'trial_days', 'is_featured']),
            'tenants' => Tenant::orderBy('trade_name')
                ->get(['id', 'name', 'trade_name', 'status']),
            'eligibleTenants' => Tenant::orderBy('trade_name')
                ->get(['id', 'name', 'trade_name']),
        ]);
    }

    /**
     * Display the specified subscription.
     */
    public function show(Subscription $subscription): Response
    {
        $subscription->load(['tenant', 'plan', 'invoices']);

        return Inertia::render('System/Subscriptions/Show', [
            'subscription' => $subscription,
        ]);
    }

    /**
     * Store a newly created subscription.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'plan_id' => 'required|exists:plans,id',
            'billing_cycle' => 'nullable|in:yearly',
            'promo_code' => 'nullable|string|exists:promo_codes,code',
            'settlement_type' => 'nullable|in:paid_now,pending_invoice,complimentary',
            'payment_method' => 'nullable|in:cash,bank_transfer,pos,manual',
            'paid_amount' => 'nullable|numeric|min:0',
            'payment_reference' => 'nullable|string|max:255',
            'payment_notes' => 'nullable|string|max:1000',
        ]);

        $tenant = Tenant::findOrFail($validated['tenant_id']);
        $plan = Plan::findOrFail($validated['plan_id']);
        $billingCycle = 'yearly';
        $price = (float) $plan->price_yearly;

        $isTrial = $plan->slug === 'trial' || ($plan->trial_days > 0 && $price == 0.0);
        $settlementType = $validated['settlement_type'] ?? 'standard';
        $paymentMethod = $validated['payment_method'] ?? 'cash';

        $promoValidator = app(PromoCodeValidator::class);
        $promoCheck = null;
        $discount = 0.0;
        $effectivePrice = $price;

        if (! empty($validated['promo_code'])) {
            $promoCheck = $promoValidator->check($validated['promo_code'], (int) $tenant->id);

            if (! $promoCheck->isValid()) {
                return back()->withErrors([
                    'promo_code' => $promoValidator->messageFor($promoCheck),
                ])->withInput();
            }

            if (! $isTrial && $settlementType !== 'complimentary') {
                $discount = $promoValidator->discountFor($promoCheck, $price);
                $effectivePrice = max(0.0, $price - $discount);
            }
        }

        $subscription = DB::transaction(function () use ($request, $validated, $tenant, $plan, $billingCycle, $effectivePrice, $discount, $promoValidator, $promoCheck, $isTrial, $settlementType, $paymentMethod) {
            $startDate = now();

            if ($isTrial) {
                $trialDays = $plan->trial_days ?: $this->lifecycle->getDefaultTrialDays();
                $trialEndsAt = $startDate->copy()->addDays($trialDays);

                Subscription::where('tenant_id', $validated['tenant_id'])
                    ->whereIn('status', ['active', 'trial', 'trialing'])
                    ->update(['status' => 'expired']);

                $subscription = Subscription::create([
                    'tenant_id' => $validated['tenant_id'],
                    'plan_id' => $validated['plan_id'],
                    'status' => 'trialing',
                    'billing_cycle' => $billingCycle,
                    'starts_at' => $startDate,
                    'ends_at' => $trialEndsAt,
                    'trial_ends_at' => $trialEndsAt,
                    'price' => 0.0,
                    'auto_renew' => false,
                    'promo_code' => $validated['promo_code'] ?? null,
                ]);

                $subscription->tenant->update([
                    'status' => 'trial',
                    'trial_ends_at' => $trialEndsAt,
                    'suspended_at' => null,
                    'auto_suspended_at' => null,
                    'suspension_reason' => null,
                ]);

                return $subscription;
            }

            $dates = $this->lifecycle->calculateRenewalDates($tenant, $billingCycle);

            if ($settlementType === 'pending_invoice') {
                // Pending payment: do not cancel existing active sub yet or activate tenant until invoice is paid
                $subscription = Subscription::create([
                    'tenant_id' => $validated['tenant_id'],
                    'plan_id' => $validated['plan_id'],
                    'status' => 'pending_payment',
                    'billing_cycle' => $billingCycle,
                    'starts_at' => $dates['starts_at'],
                    'ends_at' => $dates['ends_at'],
                    'trial_ends_at' => null,
                    'price' => $effectivePrice,
                    'payment_method' => $paymentMethod,
                    'auto_renew' => true,
                    'promo_code' => $validated['promo_code'] ?? null,
                ]);

                if ($promoCheck && $promoCheck->isValid()) {
                    $promoValidator->recordUsage($promoCheck->promo, (int) $tenant->id, $subscription->id, $discount);
                }

                $paymentSettings = PaymentSettings::first();
                $vatCalc = $paymentSettings?->calculateVat($effectivePrice) ?? ['exclusive' => $effectivePrice, 'vat' => 0, 'total' => $effectivePrice];

                SubscriptionInvoice::create([
                    'subscription_id' => $subscription->id,
                    'tenant_id' => $tenant->id,
                    'invoice_number' => SubscriptionInvoice::generateNumber(),
                    'subtotal' => $vatCalc['exclusive'],
                    'vat' => $vatCalc['vat'],
                    'discount' => $discount,
                    'total' => $vatCalc['total'],
                    'status' => 'pending',
                    'issued_at' => now(),
                    'due_at' => now()->addDays(7),
                    'notes' => $validated['payment_notes'] ?? null,
                ]);

                return $subscription;
            }

            // paid_now or complimentary -> activate immediately and expire prior active/trial subs
            Subscription::where('tenant_id', $validated['tenant_id'])
                ->whereIn('status', ['active', 'trial', 'trialing'])
                ->update(['status' => 'expired']);

            $subscription = Subscription::create([
                'tenant_id' => $validated['tenant_id'],
                'plan_id' => $validated['plan_id'],
                'status' => 'active',
                'billing_cycle' => $billingCycle,
                'starts_at' => $dates['starts_at'],
                'ends_at' => $dates['ends_at'],
                'trial_ends_at' => null,
                'price' => $settlementType === 'complimentary' ? 0.0 : $effectivePrice,
                'payment_method' => match ($settlementType) {
                    'paid_now' => $paymentMethod,
                    'complimentary' => 'complimentary',
                    default => null,
                },
                'auto_renew' => true,
                'promo_code' => $validated['promo_code'] ?? null,
            ]);

            if ($promoCheck && $promoCheck->isValid()) {
                $promoValidator->recordUsage($promoCheck->promo, (int) $tenant->id, $subscription->id, $discount);
            }

            $subscription->tenant->update([
                'status' => 'active',
                'trial_ends_at' => null,
                'suspended_at' => null,
                'auto_suspended_at' => null,
                'suspension_reason' => null,
            ]);

            if ($settlementType === 'paid_now') {
                $paymentSettings = PaymentSettings::first();
                $vatCalc = $paymentSettings?->calculateVat($effectivePrice) ?? ['exclusive' => $effectivePrice, 'vat' => 0, 'total' => $effectivePrice];
                $collectedAmount = isset($validated['paid_amount']) && (float) $validated['paid_amount'] > 0
                    ? (float) $validated['paid_amount']
                    : (float) $vatCalc['total'];
                $ref = ! empty($validated['payment_reference'])
                    ? $validated['payment_reference']
                    : strtoupper($paymentMethod).'-'.time();
                $actor = $request->user();
                $recordedBy = $actor ? $actor::class.'#'.$actor->id : 'unknown';

                $paymentNotes = $validated['payment_notes'] ?? null;

                $invoice = SubscriptionInvoice::create([
                    'subscription_id' => $subscription->id,
                    'tenant_id' => $tenant->id,
                    'invoice_number' => SubscriptionInvoice::generateNumber(),
                    'subtotal' => $vatCalc['exclusive'],
                    'vat' => $vatCalc['vat'],
                    'discount' => $discount,
                    'total' => $collectedAmount,
                    'status' => 'paid',
                    'issued_at' => now(),
                    'due_at' => now(),
                    'paid_at' => now(),
                    'notes' => trim(($paymentNotes ? $paymentNotes."\n" : '')."[Manual {$paymentMethod}] ref={$ref}, recorded_by={$recordedBy}"),
                ]);

                SubscriptionPayment::create([
                    'tenant_id' => $tenant->id,
                    'subscription_id' => $subscription->id,
                    'subscription_invoice_id' => $invoice->id,
                    'amount' => $collectedAmount,
                    'currency' => PaymentSettings::getPlatformCurrency(),
                    'gateway' => $paymentMethod,
                    'payment_method' => $paymentMethod,
                    'gateway_ref' => $ref,
                    'status' => 'paid',
                    'paid_at' => now(),
                    'gateway_response' => [
                        'recorded_by' => $recordedBy,
                        'notes' => $validated['payment_notes'] ?? null,
                    ],
                ]);
            }

            return $subscription;
        });

        $this->audit->log('subscription_create', Subscription::class, $subscription->id, [
            'tenant_id' => $subscription->tenant_id,
            'plan_id' => $subscription->plan_id,
            'status' => $subscription->status,
            'billing_cycle' => $billingCycle,
            'settlement_type' => $settlementType,
            'payment_method' => $settlementType === 'paid_now' ? $paymentMethod : null,
            'price' => $subscription->price,
        ], $request);

        if (str_contains((string) url()->previous(), '/system/tenants/') || str_contains((string) $request->headers->get('referer', ''), '/system/tenants/')) {
            return back()->with('success', __('messages.system.subscriptions.linked_to_tenant'));
        }

        return redirect()->route('system.subscriptions.index')
            ->with('success', __('messages.system.subscriptions.created_success'));
    }

    /**
     * Cancel subscription.
     *
     * Also cascades to:
     *   - the open invoice (mark as cancelled so the tenant doesn't see
     *     a stale "pending" invoice on their subscription page)
     *   - any pending bank transfer receipts (mark as superseded so
     *     they fall off the "under review" list and an admin who
     *     accidentally approves one later won't activate a cancelled sub)
     *   - the tenant status (suspended — they keep their data but
     *     can't use the app until they re-subscribe)
     */
    public function cancel(Request $request, Subscription $subscription)
    {
        $previousStatus = $subscription->status;

        // `$request->user()` honours the guard activated for this
        // request by the `auth:web,admin` middleware, so it returns
        // the AdminUser for an admin session and the tenant User for a
        // tenant-session system admin. `auth('admin')` only ever looks
        // at the 'admin' guard, so for a tenant-session admin it
        // returned null and the superseded receipt lost its reviewer.
        $actor = $request->user();
        $reviewerId = $actor instanceof AdminUser
            ? $actor->id
            : ($actor ? AdminUser::where('user_id', $actor->id)->value('id') : null);

        DB::transaction(function () use ($subscription, $reviewerId) {
            $subscription->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            // Close any open invoices on this subscription. Without this the
            // tenant's subscription page would still show a "pending" invoice
            // even after the admin cancels — confusing for both sides.
            $subscription->invoices()
                ->whereIn('status', ['pending', 'pending_payment'])
                ->update(['status' => 'cancelled']);

            // Mark any pending bank transfer receipts as superseded so the
            // admin queue doesn't keep them and so any approval after the
            // cancel doesn't accidentally re-activate this subscription.
            BankTransferReceipt::where('subscription_id', $subscription->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'superseded',
                    'reviewed_by' => $reviewerId,
                    'reviewed_at' => now(),
                    'review_notes' => 'Subscription cancelled by admin — receipt superseded.',
                ]);
        });

        // NOTE: We deliberately do NOT change tenant.status here.
        // Cancelling a subscription shouldn't lock the tenant out of the
        // app — they may have multiple workspaces/subscriptions, or the
        // admin may have just clicked cancel by mistake and the tenant
        // should still be able to re-subscribe. Tenant suspension is a
        // separate, explicit action.

        $this->audit->log('subscription_cancel', Subscription::class, $subscription->id, [
            'tenant_id' => $subscription->tenant_id,
            'plan_id' => $subscription->plan_id,
            'previous_status' => $previousStatus,
        ], $request);

        return back()->with('success', __('messages.system.subscriptions.cancelled_success'));
    }

    /**
     * Activate subscription via the unified lifecycle service.
     */
    public function activate(Request $request, Subscription $subscription)
    {
        $activated = $this->lifecycle->activateSubscription($subscription);

        $this->audit->log('subscription_activate', Subscription::class, $activated->id, [
            'tenant_id' => $activated->tenant_id,
            'plan_id' => $activated->plan_id,
            'ends_at' => $activated->ends_at?->toDateString(),
        ], $request);

        return back()->with('success', __('messages.system.subscriptions.activated'));
    }

    /**
     * Extend subscription via the unified lifecycle service.
     */
    public function extend(Request $request, Subscription $subscription)
    {
        $validated = $request->validate([
            'days' => 'required|integer|min:1|max:365',
        ]);

        $extended = $this->lifecycle->extendSubscription($subscription, (int) $validated['days']);

        $this->audit->log('subscription_extend', Subscription::class, $extended->id, [
            'tenant_id' => $extended->tenant_id,
            'days' => $validated['days'],
            'status' => $extended->status,
            'ends_at' => $extended->ends_at?->toDateString(),
        ], $request);

        return back()->with('success', __('messages.system.subscriptions.extended', ['days' => $validated['days']]));
    }
}
