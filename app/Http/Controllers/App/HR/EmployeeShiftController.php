<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\Employee;
use App\Models\HR\EmployeeShift;
use App\Support\TenancyContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeShiftController extends Controller
{
    /**
     * تحديث الوردية الافتراضية للموظف
     */
    public function updateDefaultShift(Request $request, Employee $employee)
    {
        $this->authorize('update', $employee);

        $validated = $request->validate([
            'default_shift_id' => [
                'nullable',
                Rule::exists('hr_shifts', 'id')->where('tenant_id', $employee->tenant_id),
            ],
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
        $this->authorize('update', $employee);

        $validated = $request->validate([
            'schedule' => 'required|array',
            'schedule.*' => [
                'nullable',
                Rule::exists('hr_shifts', 'id')->where('tenant_id', $employee->tenant_id),
            ],
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
