<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\Billing\Subscription;
use App\Models\Billing\SubscriptionPayment;
use App\Services\Payment\PaymentManager;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    protected PaymentManager $paymentManager;

    public function __construct(PaymentManager $paymentManager)
    {
        $this->paymentManager = $paymentManager;
    }

    /**
     * Show checkout page for subscription payment.
     */
    public function checkout(Subscription $subscription): Response
    {
        $subscription->load(['tenant', 'plan']);
        
        // Get available gateways
        $gateways = $this->paymentManager->getAvailableGateways();
        
        // Create pending payment record
        $payment = SubscriptionPayment::create([
            'tenant_id' => $subscription->tenant_id,
            'subscription_id' => $subscription->id,
            'amount' => $subscription->price,
            'currency' => 'SAR',
            'gateway' => config('payment.default'),
            'status' => 'pending',
        ]);
        
        // Get payment form data
        $formData = $this->paymentManager->initiate([
            'amount' => $subscription->price,
            'currency' => 'SAR',
            'description' => "اشتراك {$subscription->plan->name_ar} - {$subscription->tenant->trade_name}",
            'callback_url' => route('system.payment.callback', ['payment' => $payment->id]),
            'metadata' => [
                'payment_id' => $payment->id,
                'subscription_id' => $subscription->id,
                'tenant_id' => $subscription->tenant_id,
            ],
        ]);
        
        return Inertia::render('System/Payment/Checkout', [
            'subscription' => $subscription,
            'payment' => $payment,
            'formData' => $formData,
            'gateways' => $gateways,
        ]);
    }

    /**
     * Handle payment callback from gateway.
     */
    public function callback(Request $request, SubscriptionPayment $payment)
    {
        // Verify payment with gateway
        $result = $this->paymentManager->verify(
            $request->all(),
            $payment->gateway
        );
        
        if ($result['success']) {
            // Mark payment as successful
            $payment->markAsPaid($result['raw_response'] ?? []);
            $payment->update([
                'gateway_payment_id' => $result['payment_id'] ?? null,
                'payment_method' => $result['payment_method'] ?? null,
            ]);
            
            // Activate subscription
            $subscription = $payment->subscription;
            if ($subscription) {
                $subscription->update([
                    'status' => 'active',
                ]);
                
                // Update tenant status
                $subscription->tenant->update([
                    'status' => 'active',
                ]);
            }
            
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
