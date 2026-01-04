<?php

namespace App\Http\Controllers\App\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\Employee;
use App\Models\HR\OtherPayment;
use App\Support\TenancyContext;
use Illuminate\Http\Request;

class OtherPaymentsController extends Controller
{

    /**
     * Display a listing of other payments.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Employee::class);

        $user = auth()->user();
        
        // Get center from query param - empty string or null means show all
        $centerId = $request->get('center_id');
        
        $payments = OtherPayment::with(['employee', 'createdBy'])
            ->where('tenant_id', $user->tenant_id)
            ->when($centerId && $centerId !== '', fn($q) => $q->where('center_id', $centerId))
            ->latest()
            ->paginate(15);

        return response()->json($payments);
    }

    /**
     * Store a new other payment.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'title' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'nullable|date',
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);
        $this->authorize('update', $employee);

        // Use employee's center_id as it's more reliable
        $payment = OtherPayment::create([
            'tenant_id' => $employee->tenant_id,
            'center_id' => $employee->center_id,
            'created_by' => auth()->id(),
            ...$validated,
            'type' => 'other', // Default type is now 'other' (all payments are just payments)
            'status' => OtherPayment::STATUS_PENDING,
        ]);

        return response()->json(['success' => true, 'payment' => $payment->load('employee')]);
    }

    /**
     * Update an other payment.
     */
    public function update(Request $request, OtherPayment $otherPayment)
    {
        $this->authorize('update', $otherPayment->employee);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'nullable|date',
        ]);

        $otherPayment->update($validated);

        return response()->json(['success' => true, 'payment' => $otherPayment->fresh()->load('employee')]);
    }

    /**
     * Delete an other payment.
     */
    public function destroy(OtherPayment $otherPayment)
    {
        $this->authorize('update', $otherPayment->employee);

        $otherPayment->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Approve an other payment.
     */
    public function approve(OtherPayment $otherPayment)
    {
        $this->authorize('update', $otherPayment->employee);

        $otherPayment->update([
            'status' => OtherPayment::STATUS_APPROVED,
            'approved_by' => auth()->id(),
        ]);

        return response()->json(['success' => true, 'payment' => $otherPayment->fresh()->load('employee')]);
    }

    /**
     * Mark an other payment as paid.
     */
    public function markAsPaid(OtherPayment $otherPayment)
    {
        $this->authorize('update', $otherPayment->employee);

        $otherPayment->update([
            'status' => OtherPayment::STATUS_PAID,
            'payment_date' => $otherPayment->payment_date ?? now(),
        ]);

        return response()->json(['success' => true, 'payment' => $otherPayment->fresh()->load('employee')]);
    }
}
