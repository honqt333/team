<?php

namespace App\Http\Controllers\App\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\Attendance;
use App\Models\HR\Employee;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->input('date') ? Carbon::parse($request->input('date')) : Carbon::today();
        
        // Get all active employees
        $employees = Employee::active()
            ->with(['attendance' => function ($query) use ($date) {
                $query->whereDate('date', $date->format('Y-m-d'));
            }])
            ->get()
            ->map(function ($employee) use ($date) {
                // Attach attendance if exists, otherwise null
                $attendance = $employee->attendance->first();
                
                return [
                    'id' => $employee->id,
                    'name_ar' => $employee->name_ar,
                    'name_en' => $employee->name_en,
                    'employee_number' => $employee->employee_number,
                    'photo_url' => $employee->photo_path ? asset('storage/' . $employee->photo_path) : null,
                    'shift_start' => $employee->shift_start,
                    'shift_end' => $employee->shift_end,
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
                ];
            });

        // Calculate stats
        $totalLateMinutes = $employees->sum(fn($e) => $e['attendance']['late_minutes'] ?? 0);
        $lateCount = $employees->filter(fn($e) => ($e['attendance']['late_minutes'] ?? 0) > 0)->count();

        return Inertia::render('HR/Attendance/Index', [
            'employees' => $employees,
            'filters' => [
                'date' => $date->format('Y-m-d'),
            ],
            'stats' => [
                'total' => $employees->count(),
                'present' => $employees->filter(fn($e) => $e['attendance'] && $e['attendance']['status'] === 'present')->count(),
                'absent' => $employees->filter(fn($e) => !$e['attendance'] || $e['attendance']['status'] === 'absent')->count(),
                'leave' => $employees->filter(fn($e) => $e['attendance'] && $e['attendance']['status'] === 'leave')->count(),
                'late_count' => $lateCount,
                'total_late_minutes' => $totalLateMinutes,
            ]
        ]);
    }

    public function print(Request $request)
    {
        $date = $request->input('date') ? Carbon::parse($request->input('date')) : Carbon::today();
        
        $employees = Employee::active()
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
        // Ensure same tenant
        if ($attendance->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

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
        if ($attendance->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $attendance->delete();

        return back();
    }
}
