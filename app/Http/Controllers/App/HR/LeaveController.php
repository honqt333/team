<?php

namespace App\Http\Controllers\App\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\Employee;
use App\Models\HR\Leave;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaveController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
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

    public function destroy(Leave $leave)
    {
        if ($leave->status !== 'pending') {
            return back()->with('error', __('common.cannot_delete_processed_leave'));
        }

        $leave->delete();

        return back()->with('success', __('common.deleted_success'));
    }

    public function updateStatus(Request $request, Leave $leave)
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'notes' => 'nullable|string',
        ]);

        $leave->update([
            'status' => $validated['status'],
            'notes' => $request->notes,
            'approved_by' => auth()->id(),
        ]);

        return back()->with('success', __('common.saved_success'));
    }
}
