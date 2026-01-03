<?php

namespace App\Http\Controllers\App\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\Employee;
use App\Models\HR\EmployeeShift;
use App\Support\TenancyContext;
use Illuminate\Http\Request;

class EmployeeShiftController extends Controller
{
    /**
     * تحديث الوردية الافتراضية للموظف
     */
    public function updateDefaultShift(Request $request, Employee $employee)
    {
        if ($employee->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $validated = $request->validate([
            'default_shift_id' => 'nullable|exists:hr_shifts,id',
        ]);

        $employee->update([
            'default_shift_id' => $validated['default_shift_id'],
        ]);

        return back()->with('success', __('messages.saved_successfully'));
    }

    /**
     * تحديث الجدولة الأسبوعية للموظف
     */
    public function updateWeeklySchedule(Request $request, Employee $employee)
    {
        if ($employee->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $validated = $request->validate([
            'schedule' => 'required|array',
            'schedule.*' => 'nullable|exists:hr_shifts,id',
        ]);

        $tenantId = TenancyContext::tenantId();

        // حذف الجدولة الأسبوعية الحالية
        EmployeeShift::where('employee_id', $employee->id)
            ->whereNull('date')
            ->whereNotNull('day_of_week')
            ->delete();

        // إضافة الجدولة الجديدة
        foreach ($validated['schedule'] as $dayOfWeek => $shiftId) {
            if ($shiftId) {
                EmployeeShift::create([
                    'tenant_id' => $tenantId,
                    'employee_id' => $employee->id,
                    'shift_id' => $shiftId,
                    'day_of_week' => $dayOfWeek,
                    'date' => null,
                ]);
            }
        }

        return back()->with('success', __('messages.saved_successfully'));
    }
}
