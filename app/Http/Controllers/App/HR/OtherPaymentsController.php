<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\Employee;
use App\Models\HR\OtherPayment;
use App\Support\Permissions;
use App\Support\TenancyContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OtherPaymentsController extends Controller
{
    /**
     * Display a listing of other payments.
     */
    public function index(Request $request)
    {
        $this->authorize(Permissions::HR_PAYMENTS_VIEW);

        $user = auth()->user();

        // Get center from query param - empty string or null means show all for super_admin
        $centerId = $request->get('center_id');

        if (! $user->hasRole('super_admin')) {
            $centerId = TenancyContext::centerId() ?? $user->current_center_id;
        }

        $payments = OtherPayment::with(['employee', 'createdBy'])
            ->where('tenant_id', $user->tenant_id)
            ->when(
                ! $user->hasRole('super_admin') && ! $centerId,
                fn ($q) => $q->whereRaw('1 = 0'),
                fn ($q) => $q->when($centerId && $centerId !== '', fn ($sub) => $sub->where('center_id', $centerId))
            )
            ->latest()
            ->paginate(15);

        return response()->json($payments);
    }

    /**
     * Store a new other payment.
     */
    public function store(Request $request)
    {
        $this->authorize(Permissions::HR_PAYMENTS_CREATE);

        $validated = $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'title' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'nullable|date',
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);
        $this->ensureInScope((int) $employee->tenant_id, $employee->center_id !== null ? (int) $employee->center_id : null);

        // Use employee's center_id as it's more reliable
        $payment = OtherPayment::create([
            'tenant_id' => $employee->tenant_id,
            'center_id' => $employee->center_id,
            'created_by' => auth()->id(),
            ...$validated,
            'type' => 'other', // Default type is now 'other' (all payments are just payments)
            'status' => OtherPayment::STATUS_PENDING,
        ]);

        // Double-entry ledger mapping is handled via Billing/PaymentService/transactions under the hood

        return response()->json(['success' => true, 'payment' => $payment->load('employee')]);
    }

    /**
     * Update an other payment.
     */
    public function update(Request $request, OtherPayment $otherPayment)
    {
        $this->authorize(Permissions::HR_PAYMENTS_UPDATE);
        $this->ensurePaymentInScope($otherPayment);

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
        $this->authorize(Permissions::HR_PAYMENTS_DELETE);
        $this->ensurePaymentInScope($otherPayment);

        $otherPayment->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Approve an other payment.
     */
    public function approve(Request $request, OtherPayment $otherPayment)
    {
        $this->authorize(Permissions::HR_PAYMENTS_APPROVE);
        $this->ensurePaymentInScope($otherPayment);

        $result = DB::transaction(function () use ($otherPayment) {
            $lockedPayment = OtherPayment::query()
                ->whereKey($otherPayment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedPayment->status !== OtherPayment::STATUS_PENDING) {
                return null;
            }

            $lockedPayment->update([
                'status' => OtherPayment::STATUS_APPROVED,
                'approved_by' => auth()->id(),
            ]);

            return $lockedPayment->fresh()->load('employee');
        });

        if (! $result) {
            $message = __('hr.payment_must_be_pending_to_approve');

            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $message], 422)
                : back()->with('error', $message);
        }

        return response()->json(['success' => true, 'payment' => $result]);
    }

    /**
     * Mark an other payment as paid.
     */
    public function markAsPaid(Request $request, OtherPayment $otherPayment)
    {
        $this->authorize(Permissions::HR_PAYMENTS_APPROVE);
        $this->ensurePaymentInScope($otherPayment);

        $result = DB::transaction(function () use ($otherPayment) {
            $lockedPayment = OtherPayment::query()
                ->whereKey($otherPayment->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Enforce strict lifecycle: only approved payments can be marked as paid.
            if ($lockedPayment->status !== OtherPayment::STATUS_APPROVED) {
                return null;
            }

            $lockedPayment->update([
                'status' => OtherPayment::STATUS_PAID,
                'payment_date' => $lockedPayment->payment_date ?? now(),
            ]);

            return $lockedPayment->fresh()->load('employee');
        });

        if (! $result) {
            $message = __('hr.payment_must_be_approved_before_paid');

            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $message], 422)
                : back()->with('error', $message);
        }

        return response()->json(['success' => true, 'payment' => $result]);
    }

    private function ensurePaymentInScope(OtherPayment $otherPayment): void
    {
        $user = auth()->user();

        if ((int) $user->tenant_id !== (int) $otherPayment->tenant_id) {
            abort(403);
        }

        if ($user->hasRole('super_admin')) {
            return;
        }

        if ($otherPayment->center_id === null) {
            abort(403);
        }

        $this->ensureInScope(
            (int) $otherPayment->tenant_id,
            (int) $otherPayment->center_id
        );
    }

    private function ensureInScope(int $tenantId, ?int $centerId): void
    {
        $user = auth()->user();

        if ((int) $user->tenant_id !== $tenantId) {
            abort(403);
        }

        if ($user->hasRole('super_admin')) {
            return;
        }

        $activeCenterId = TenancyContext::centerId() ?? $user->current_center_id;

        if ($centerId !== null && $activeCenterId !== null && $centerId !== (int) $activeCenterId) {
            abort(403);
        }
    }
}
