<?php

namespace App\Http\Controllers\App\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\Attendance;
use App\Models\HR\AttendanceSettings;
use App\Models\HR\Employee;
use App\Models\HR\Leave;
use App\Support\TenancyContext;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Attendance::class);
        
        $tenantId = TenancyContext::tenantId();
        $centerId = TenancyContext::centerId();
        $date = $request->input('date') ? Carbon::parse($request->input('date')) : Carbon::today();
        
        // Get center attendance settings
        $settings = AttendanceSettings::getForCenter($centerId);
        
        // Get all active employees for this tenant and center
        $employees = Employee::where('tenant_id', $tenantId)
            ->where('center_id', $centerId)
            ->active()
            ->with(['attendance' => function ($query) use ($date) {
                $query->whereDate('date', $date->format('Y-m-d'));
            }])
            ->get()
            ->map(function ($employee) use ($date, $tenantId) {
                // Attach attendance if exists, otherwise null
                $attendance = $employee->attendance->first();
                
                // Check if employee is on approved leave
                $isOnLeave = Leave::where('employee_id', $employee->id)
                    ->where('status', 'approved')
                    ->where('start_date', '<=', $date->toDateString())
                    ->where('end_date', '>=', $date->toDateString())
                    ->exists();
                
                // Get shift for this date
                $shift = $employee->getShiftForDate($date);
                
                // Determine status
                $status = null;
                if ($attendance) {
                    $status = $attendance->status;
                } elseif ($isOnLeave) {
                    $status = 'leave';
                }
                
                return [
                    'id' => $employee->id,
                    'name_ar' => $employee->name_ar,
                    'name_en' => $employee->name_en,
                    'employee_number' => $employee->employee_number,
                    'photo_url' => $employee->photo_path ? asset('storage/' . $employee->photo_path) : null,
                    'shift_start' => $shift?->start_time,
                    'shift_end' => $shift?->end_time,
                    'shift_name' => $shift?->name_ar,
                    'is_on_leave' => $isOnLeave,
                    'attendance' => $attendance ? [
                        'id' => $attendance->id,
                        'status' => $attendance->status,
                        'check_in' => $attendance->check_in ? Carbon::parse($attendance->check_in)->format('H:i') : null,
                        'check_out' => $attendance->check_out ? Carbon::parse($attendance->check_out)->format('H:i') : null,
                        'late_minutes' => $attendance->late_minutes ?? 0,
                        'early_leave_minutes' => $attendance->early_leave_minutes ?? 0,
                        'overtime_minutes' => $attendance->overtime_minutes ?? 0,
                        'notes' => $attendance->notes,
                    ] : null,
                    'display_status' => $status,
                ];
            });

        // Calculate stats
        $totalLateMinutes = $employees->sum(fn($e) => $e['attendance']['late_minutes'] ?? 0);
        $lateCount = $employees->filter(fn($e) => ($e['attendance']['late_minutes'] ?? 0) > 0)->count();
        $onLeaveCount = $employees->filter(fn($e) => $e['is_on_leave'])->count();

        return Inertia::render('HR/Attendance/Index', [
            'employees' => $employees,
            'filters' => [
                'date' => $date->format('Y-m-d'),
            ],
            'stats' => [
                'total' => $employees->count(),
                'present' => $employees->filter(fn($e) => $e['attendance'] && $e['attendance']['status'] === 'present')->count(),
                'absent' => $employees->filter(fn($e) => !$e['attendance'] && !$e['is_on_leave'])->count(),
                'leave' => $onLeaveCount,
                'late_count' => $lateCount,
                'total_late_minutes' => $totalLateMinutes,
            ],
            'settings' => [
                'grace_period_minutes' => $settings->grace_period_minutes,
                'overtime_enabled' => $settings->overtime_enabled,
            ]
        ]);
    }

    public function print(Request $request)
    {
        $this->authorize('viewAny', Attendance::class);
        
        $tenantId = TenancyContext::tenantId();
        $centerId = TenancyContext::centerId();
        $date = $request->input('date') ? Carbon::parse($request->input('date')) : Carbon::today();
        
        $employees = Employee::where('tenant_id', $tenantId)
            ->where('center_id', $centerId)
            ->active()
            ->with(['attendance' => function ($query) use ($date) {
                $query->whereDate('date', $date->format('Y-m-d'));
            }, 'jobTitle', 'department'])
            ->get()
            ->map(function ($employee) use ($date) {
                $attendance = $employee->attendance->first();
                return [
                    'id' => $employee->id,
                    'name_ar' => $employee->name_ar,
                    'name_en' => $employee->name_en,
                    'employee_number' => $employee->employee_number,
                    'job_title' => $employee->jobTitle?->name_ar,
                    'department' => $employee->department?->name,
                    'shift_start' => $employee->shift_start ? Carbon::parse($employee->shift_start)->format('H:i') : null,
                    'shift_end' => $employee->shift_end ? Carbon::parse($employee->shift_end)->format('H:i') : null,
                    'attendance' => $attendance ? [
                        'status' => $attendance->status,
                        'check_in' => $attendance->check_in ? Carbon::parse($attendance->check_in)->format('H:i') : null,
                        'check_out' => $attendance->check_out ? Carbon::parse($attendance->check_out)->format('H:i') : null,
                        'notes' => $attendance->notes,
                    ] : null,
                ];
            });

        return Inertia::render('HR/Attendance/Print', [
            'employees' => $employees,
            'date' => $date->format('Y-m-d'),
            'tenant' => auth()->user()->tenant,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Attendance::class);
        
        $validated = $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'date' => 'required|date',
            'status' => 'required|in:present,absent,late,leave,holiday',
            'check_in' => 'nullable|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i',
            'notes' => 'nullable|string',
        ]);

        $attendance = Attendance::create([
            'tenant_id' => auth()->user()->tenant_id,
            'center_id' => auth()->user()->current_center_id,
            'employee_id' => $validated['employee_id'],
            'date' => $validated['date'],
            'status' => $validated['status'],
            'check_in' => $validated['check_in'] ?? null,
            'check_out' => $validated['check_out'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        // حساب التأخير والانصراف المبكر تلقائياً
        $attendance->calculateTimes();
        $attendance->save();

        return back()->with('success', __('messages.saved_successfully'));
    }

    public function update(Request $request, Attendance $attendance)
    {
        $this->authorize('update', $attendance);

        $validated = $request->validate([
            'status' => 'required|in:present,absent,late,leave,holiday',
            'check_in' => 'nullable|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i',
            'notes' => 'nullable|string',
        ]);

        $attendance->update($validated);

        // إعادة حساب التأخير والانصراف المبكر
        $attendance->calculateTimes();
        $attendance->save();

        return back()->with('success', __('messages.saved_successfully'));
    }

    public function destroy(Attendance $attendance)
    {
        $this->authorize('delete', $attendance);

        $attendance->delete();

        return back();
    }
}
