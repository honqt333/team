<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentsController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Record a new payment
     */
    public function store(Request $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $invoice->balance,
            'payment_method' => 'required|in:cash,card,transfer,credit',
            'payment_date' => 'nullable|date',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        $validated['received_by'] = auth()->id();

        try {
            $this->paymentService->recordPayment($invoice, $validated);

            return back()->with('success', __('payments.recorded'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Delete a payment
     */
    public function destroy(Payment $payment)
    {
        $this->authorize('update', $payment->invoice);

        try {
            $this->paymentService->deletePayment($payment);

            return back()->with('success', __('payments.deleted'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Record full payment (convenience endpoint)
     */
    public function payFull(Request $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        $validated = $request->validate([
            'payment_method' => 'required|in:cash,card,transfer,credit',
            'reference' => 'nullable|string|max:100',
        ]);

        try {
            $this->paymentService->recordFullPayment(
                $invoice,
                $validated['payment_method'],
                $validated['reference'] ?? null
            );

            return back()->with('success', __('payments.recorded'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
