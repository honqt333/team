<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\WorkOrders;

use App\Models\Payment;
use App\Models\WorkOrder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class WorkOrderPaymentController
{
    use AuthorizesRequests;

    /**
     * Store a new payment for a work order.
     */
    public function storePayment(Request $request, WorkOrder $workOrder): mixed
    {
        $this->authorize('update', $workOrder);

        $validated = $request->validate([
            'payment_method' => 'required|in:'.implode(',', Payment::METHODS),
            'type' => 'required|in:'.implode(',', Payment::TYPES),
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validated['type'] === 'refund' && $workOrder->status === WorkOrder::STATUS_DONE) {
            return back()->with('error', __('payments.refund_not_allowed_for_exited'));
        }

        $linkedInvoiceId = $workOrder->invoice?->id;

        $workOrder->payments()->create([
            'tenant_id' => $workOrder->tenant_id,
            'center_id' => $workOrder->center_id,
            'invoice_id' => $linkedInvoiceId,
            'type' => $validated['type'],
            'payment_method' => $validated['payment_method'],
            'amount' => $validated['amount'],
            'payment_date' => $validated['payment_date'],
            'reference' => $validated['reference'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'received_by' => auth()->id(),
        ]);

        if ($linkedInvoiceId) {
            $workOrder->invoice->updatePaymentStatus();
        }

        $workOrder->logActivity('payment_added', __('work_orders.activities.actions.payment_added', ['amount' => $validated['amount']]));

        return back()->with('success', __('payments.saved_successfully'));
    }

    /**
     * Update an existing payment.
     */
    public function updatePayment(Request $request, WorkOrder $workOrder, Payment $payment): mixed
    {
        $this->authorize('update', $workOrder);

        if ($payment->work_order_id !== $workOrder->id) {
            abort(403);
        }

        $validated = $request->validate([
            'payment_method' => 'required|in:'.implode(',', Payment::METHODS),
            'type' => 'required|in:'.implode(',', Payment::TYPES),
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validated['type'] === 'refund' && $workOrder->status === WorkOrder::STATUS_DONE) {
            return back()->with('error', __('payments.refund_not_allowed_for_exited'));
        }

        $payment->update($validated);

        return back()->with('success', __('payments.updated_successfully'));
    }

    /**
     * Delete a payment.
     */
    public function destroyPayment(WorkOrder $workOrder, Payment $payment): mixed
    {
        $this->authorize('update', $workOrder);

        if ($payment->work_order_id !== $workOrder->id) {
            abort(403);
        }

        $payment->delete();

        return back()->with('success', __('payments.deleted_successfully'));
    }
}
