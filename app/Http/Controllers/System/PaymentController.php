<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\Billing\Subscription;
use App\Models\Billing\SubscriptionPayment;
use App\Models\PaymentSettings;
use App\Services\Billing\PaymentCompletionService;
use App\Services\Payment\PaymentManager;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    protected PaymentManager $paymentManager;

    protected PaymentCompletionService $paymentCompletion;

    public function __construct(
        PaymentManager $paymentManager,
        PaymentCompletionService $paymentCompletion
    ) {
        $this->paymentManager = $paymentManager;
        $this->paymentCompletion = $paymentCompletion;
    }

    /**
     * Show checkout page for subscription payment.
     */
    public function checkout(Subscription $subscription): Response
    {
        $subscription->load(['tenant', 'plan']);

        // Get available gateways (already filtered by admin-enabled flag)
        $gateways = $this->paymentManager->getAvailableGateways();

        // Read default gateway from DB (admin-managed)
        $paymentSettings = PaymentSettings::getSettings();
        $defaultGateway = $paymentSettings->default_gateway ?? config('payment.default');

        // Compute total with VAT so the gateway charges the same amount the
        // tenant saw in the subscription page. Without this, gateway form
        // amounts would mismatch the invoice total.
        $vatCalc = $paymentSettings->calculateVat((float) $subscription->price);
        $totalAmount = $vatCalc['total'];

        // Create pending payment record
        $payment = SubscriptionPayment::create([
            'tenant_id' => $subscription->tenant_id,
            'subscription_id' => $subscription->id,
            'amount' => $totalAmount,
            'currency' => 'SAR',
            'gateway' => $defaultGateway,
            'status' => 'pending',
        ]);

        // Get payment form data
        $formData = $this->paymentManager->initiate([
            'amount' => $totalAmount,
            'currency' => 'SAR',
            'description' => "اشتراك {$subscription->plan->name_ar} - {$subscription->tenant->trade_name}",
            'callback_url' => route('system.payment.callback', ['payment' => $payment->id]),
            'metadata' => [
                'payment_id' => $payment->id,
                'subscription_id' => $subscription->id,
                'tenant_id' => $subscription->tenant_id,
            ],
        ], $defaultGateway);

        return Inertia::render('System/Payment/Checkout', [
            'subscription' => $subscription,
            'payment' => $payment,
            'formData' => $formData,
            'gateways' => $gateways,
        ]);
    }

    /**
     * Handle payment callback from gateway.
     *
     * Delegates to PaymentCompletionService for the post-payment side effects
     * (invoice mark-paid, tenant activation, audit log, activation email).
     * The service is idempotent so re-callbacks are safe.
     */
    public function callback(Request $request, SubscriptionPayment $payment)
    {
        // Verify payment with gateway
        $result = $this->paymentManager->verify(
            $request->all(),
            $payment->gateway
        );

        if ($result['success']) {
            // System-initiated payments are recorded against a tenant
            // (typically from the admin re-charging a tenant). Pass the
            // current admin user id for the audit log so we can trace who
            // triggered the payment on the system side.
            $adminUserId = auth('admin')->id();
            $this->paymentCompletion->completePayment($payment, $result, $adminUserId);

            return redirect()->route('system.payment.success', ['payment' => $payment->id]);
        }

        // Mark payment as failed
        $payment->markAsFailed(
            $result['message'] ?? 'فشل الدفع',
            $result['raw_response'] ?? []
        );

        return redirect()->route('system.payment.failed', ['payment' => $payment->id]);
    }

    /**
     * Payment success page.
     */
    public function success(SubscriptionPayment $payment): Response
    {
        $payment->load(['subscription.plan', 'tenant']);

        return Inertia::render('System/Payment/Success', [
            'payment' => $payment,
        ]);
    }

    /**
     * Payment failed page.
     */
    public function failed(SubscriptionPayment $payment): Response
    {
        $payment->load(['subscription.plan', 'tenant']);

        return Inertia::render('System/Payment/Failed', [
            'payment' => $payment,
        ]);
    }

    /**
     * Retry a failed payment.
     */
    public function retry(SubscriptionPayment $payment)
    {
        if ($payment->status !== 'failed') {
            return back()->with('error', 'لا يمكن إعادة المحاولة لهذه الدفعة');
        }

        // Create new payment attempt
        $newPayment = SubscriptionPayment::create([
            'tenant_id' => $payment->tenant_id,
            'subscription_id' => $payment->subscription_id,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'gateway' => $payment->gateway,
            'status' => 'pending',
        ]);

        return redirect()->route('system.payment.checkout', ['subscription' => $payment->subscription_id]);
    }
}
