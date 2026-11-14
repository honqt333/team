<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\Leave;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeaveController extends Controller
{
    private function ensureSameTenant(Request $request, Leave $leave): void
    {
        if ((int) $leave->tenant_id !== (int) $request->user()?->tenant_id) {
            abort(404);
        }
    }

    public function store(Request $request)
    {
        $this->authorize('create', Leave::class);
        $tenantId = (int) $request->user()->tenant_id;

        $validated = $request->validate([
            'employee_id' => [
                'required',
                Rule::exists('hr_employees', 'id')->where('tenant_id', $tenantId),
            ],
            'type' => 'required|string|in:annual,sick,unpaid,emergency,other',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string',
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        $days = $startDate->diffInDays($endDate) + 1;

        Leave::create([
            'tenant_id' => auth()->user()->tenant_id,
            'employee_id' => $validated['employee_id'],
            'type' => $validated['type'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'days' => $days,
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        return back()->with('success', __('common.saved_success'));
    }

    public function update(Request $request, Leave $leave)
    {
        $this->ensureSameTenant($request, $leave);
        $this->authorize('update', $leave);

        $validated = $request->validate([
            'type' => 'required|string|in:annual,sick,unpaid,emergency,other',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string',
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        $days = $startDate->diffInDays($endDate) + 1;

        $leave->update([
            'type' => $validated['type'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'days' => $days,
            'reason' => $request->reason,
        ]);

        return back()->with('success', __('common.saved_success'));
    }

    public function destroy(Request $request, Leave $leave)
    {
        $this->ensureSameTenant($request, $leave);
        $this->authorize('delete', $leave);

        if ($leave->status !== 'pending') {
            return back()->with('error', __('common.cannot_delete_processed_leave'));
        }

        $leave->delete();

        return back()->with('success', __('common.deleted_success'));
    }

    public function updateStatus(Request $request, Leave $leave)
    {
        $this->ensureSameTenant($request, $leave);

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'notes' => 'nullable|string',
        ]);

        // Enforce self-approval guard even for system admins
        if ($leave->employee_id !== null && (int) $leave->employee_id === (int) $request->user()?->employee?->id) {
            abort(403);
        }

        $ability = $validated['status'] === 'approved' ? 'approve' : 'reject';
        $this->authorize($ability, $leave);

        $leave->update([
            'status' => $validated['status'],
            'notes' => $request->notes,
            'approved_by' => auth()->id(),
        ]);

        return back()->with('success', __('common.saved_success'));
    }
}
