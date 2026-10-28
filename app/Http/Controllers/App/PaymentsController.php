<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PaymentService;
use Exception;
use Illuminate\Http\Request;

class PaymentsController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function store(Request $request, Invoice $invoice)
    {
        // The invoice `update` ability is the DRAFT-EDIT ability: it
        // requires status === 'draft'. An issued invoice (status
        // `valid`) could therefore never take a payment — the request
        // was rejected even though the invoice was unpaid and the
        // customer was standing at the counter.
        //
        // `recordPayment` is the ability written for this: it checks
        // the payment permission, the tenant, and that the invoice is
        // not already settled.
        $this->authorize('recordPayment', $invoice);

        $validated = $request->validate([
            'type' => 'nullable|in:'.implode(',', Payment::TYPES),
            'amount' => 'required|numeric|min:0.01|max:'.$invoice->balance,
            'payment_method' => 'required_unless:type,bad_debt|nullable|in:'.implode(',', Payment::METHODS),
            'payment_date' => 'nullable|date',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        $validated['type'] = $validated['type'] ?? 'payment';

        if ($validated['type'] === 'bad_debt') {
            $validated['payment_method'] = $validated['payment_method'] ?? 'cash';
        }
        $validated['received_by'] = auth()->id();

        try {
            $this->paymentService->recordPayment($invoice, $validated);

            return back()->with('success', __('payments.recorded'));
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Delete a payment
     */
    public function destroy(Payment $payment)
    {
        // Deleting a payment is a payment operation, not a draft edit.
        // Authorizing against the invoice's `update` ability meant a
        // payment recorded against an ISSUED invoice could never be
        // removed — a wrong entry on a real invoice was permanent.
        // PaymentPolicy::delete is the gate written for it.
        $this->authorize('delete', $payment);

        try {
            $this->paymentService->deletePayment($payment);

            return back()->with('success', __('payments.deleted'));
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Record full payment (convenience endpoint)
     */
    public function payFull(Request $request, Invoice $invoice)
    {
        // Same reasoning as store(): the draft-edit ability must not
        // gate a payment on an issued invoice.
        $this->authorize('recordPayment', $invoice);

        $validated = $request->validate([
            'payment_method' => 'required|in:'.implode(',', Payment::METHODS),
            'reference' => 'nullable|string|max:100',
        ]);

        try {
            $this->paymentService->recordFullPayment(
                $invoice,
                $validated['payment_method'],
                $validated['reference'] ?? null
            );

            return back()->with('success', __('payments.recorded'));
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
