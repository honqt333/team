<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\Attendance;
use App\Models\HR\AttendanceCorrectionRequest;
use App\Models\HR\AttendanceSettings;
use App\Models\HR\Employee;
use App\Models\HR\Leave;
use App\Support\TenancyContext;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
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
        $settings = $centerId ? AttendanceSettings::getForCenter((int) $centerId) : null;

        // Get all active employees for this tenant and center (including HQ/management employees)
        $employeesQuery = Employee::where('tenant_id', $tenantId)->active();

        if ($centerId) {
            $employeesQuery->where(function ($q) use ($centerId) {
                $q->where('center_id', $centerId)
                    ->orWhereNull('center_id');
            });
        }

        $employees = $employeesQuery
            ->with(['attendance' => function ($query) use ($date) {
                $query->whereDate('date', $date->format('Y-m-d'));
            }])
            ->get()
            ->map(function ($employee) use ($date) {
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
                    'photo_url' => $employee->photo_path ? asset('storage/'.$employee->photo_path) : null,
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

        // Calculate stats on all employees
        $totalLateMinutes = $employees->sum(fn ($e) => $e['attendance']['late_minutes'] ?? 0);
        $lateCount = $employees->filter(fn ($e) => ($e['attendance']['late_minutes'] ?? 0) > 0)->count();
        $onLeaveCount = $employees->filter(fn ($e) => $e['is_on_leave'])->count();
        $presentCount = $employees->filter(fn ($e) => $e['attendance'] && $e['attendance']['status'] === 'present')->count();
        $absentCount = $employees->filter(fn ($e) => (! $e['attendance'] && ! $e['is_on_leave']) || ($e['attendance'] && $e['attendance']['status'] === 'absent'))->count();

        $search = $request->input('search');
        $statusFilter = $request->input('status');

        $filteredEmployees = $employees
            ->when($search, function ($collection, $search) {
                $search = mb_strtolower((string) $search);

                return $collection->filter(function ($e) use ($search) {
                    return str_contains(mb_strtolower((string) ($e['name_ar'] ?? '')), $search)
                        || str_contains(mb_strtolower((string) ($e['name_en'] ?? '')), $search)
                        || str_contains(mb_strtolower((string) ($e['employee_number'] ?? '')), $search);
                });
            })
            ->when($statusFilter, function ($collection, $statusFilter) {
                return $collection->filter(function ($e) use ($statusFilter) {
                    if ($statusFilter === 'present') {
                        return $e['attendance'] && $e['attendance']['status'] === 'present';
                    }

                    if ($statusFilter === 'absent') {
                        return (! $e['attendance'] && ! $e['is_on_leave']) || ($e['attendance'] && $e['attendance']['status'] === 'absent');
                    }

                    if ($statusFilter === 'late') {
                        return ($e['attendance']['late_minutes'] ?? 0) > 0;
                    }

                    if ($statusFilter === 'leave') {
                        return $e['is_on_leave'] || ($e['attendance'] && $e['attendance']['status'] === 'leave');
                    }

                    return true;
                });
            })
            ->values();

        return Inertia::render('HR/Attendance/Index', [
            'employees' => $filteredEmployees,
            'filters' => [
                'date' => $date->format('Y-m-d'),
                'search' => $search ?? '',
                'status' => $statusFilter ?? '',
            ],
            'stats' => [
                'total' => $employees->count(),
                'present' => $presentCount,
                'absent' => $absentCount,
                'leave' => $onLeaveCount,
                'late_count' => $lateCount,
                'total_late_minutes' => $totalLateMinutes,
            ],
            'settings' => [
                'grace_period_minutes' => $settings?->grace_period_minutes ?? 10,
                'overtime_enabled' => $settings?->overtime_enabled ?? true,
            ],
            'pendingCorrections' => AttendanceCorrectionRequest::query()
                ->with(['employee:id,name_ar,name_en,employee_number'])
                ->where('status', AttendanceCorrectionRequest::STATUS_PENDING)
                ->orderByDesc('created_at')
                ->take(30)
                ->get(),
        ]);
    }

    public function print(Request $request)
    {
        $this->authorize('viewAny', Attendance::class);

        $tenantId = TenancyContext::tenantId();
        $centerId = TenancyContext::centerId();
        $date = $request->input('date') ? Carbon::parse($request->input('date')) : Carbon::today();

        $employeesQuery = Employee::where('tenant_id', $tenantId)->active();

        if ($centerId) {
            $employeesQuery->where(function ($q) use ($centerId) {
                $q->where('center_id', $centerId)
                    ->orWhereNull('center_id');
            });
        }

        $employees = $employeesQuery
            ->with(['attendance' => function ($query) use ($date) {
                $query->whereDate('date', $date->format('Y-m-d'));
            }, 'jobTitle', 'department'])
            ->get()
            ->map(function ($employee) {
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

        $user = auth()->user();

        // The employee has to be one the recorder may manage. The
        // employee list is centre-scoped for everybody except the
        // tenant super admin (EmployeeController::index), and the
        // attendance row is what the payroll reads — so a record for
        // an employee of another branch used to land on that branch's
        // salary.
        $isSuperAdmin = $user->hasRole('super_admin');

        $validated = $request->validate([
            'employee_id' => [
                'required',
                Rule::exists('hr_employees', 'id')
                    ->where('tenant_id', $user->tenant_id)
                    ->when(! $isSuperAdmin && $user->current_center_id !== null,
                        fn (Exists $rule) => $rule->where('center_id', $user->current_center_id)),
            ],
            'date' => 'required|date',
            'status' => 'required|in:present,absent,late,leave,holiday',
            'check_in' => 'nullable|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i',
            'notes' => 'nullable|string',
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);

        $attendance = Attendance::create([
            'tenant_id' => $employee->tenant_id,
            // The record belongs to the employee's branch, not to
            // whoever happened to be logged in.
            'center_id' => $employee->center_id,
            'employee_id' => $employee->id,
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

        return back()->with('success', __('messages.deleted_successfully'));
    }

    public function approveCorrection(Request $request, AttendanceCorrectionRequest $correctionRequest)
    {
        $this->authorize('create', Attendance::class);

        if ($correctionRequest->status !== AttendanceCorrectionRequest::STATUS_PENDING) {
            return back()->with('error', __('hr.attendance.correction_already_reviewed'));
        }

        $validated = $request->validate([
            'review_notes' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($request, $correctionRequest, $validated): void {
            $attendance = Attendance::firstOrNew([
                'tenant_id' => $correctionRequest->tenant_id,
                'center_id' => $correctionRequest->center_id,
                'employee_id' => $correctionRequest->employee_id,
                'date' => $correctionRequest->date->format('Y-m-d'),
            ]);

            if ($correctionRequest->requested_check_in) {
                $attendance->check_in = $correctionRequest->requested_check_in;
            }

            if ($correctionRequest->requested_check_out) {
                $attendance->check_out = $correctionRequest->requested_check_out;
            }

            $attendance->status = 'present';
            $attendance->calculateTimes();
            $attendance->save();

            $correctionRequest->forceFill([
                'attendance_id' => $attendance->id,
                'status' => AttendanceCorrectionRequest::STATUS_APPROVED,
                'reviewed_by' => $request->user()?->id,
                'reviewed_at' => now(),
                'review_notes' => $validated['review_notes'] ?? null,
            ])->save();

            $correctionRequest->logActivity('approved', ['attendance_id' => $attendance->id]);
        });

        return back()->with('success', __('hr.attendance.correction_approved'));
    }

    public function rejectCorrection(Request $request, AttendanceCorrectionRequest $correctionRequest)
    {
        $this->authorize('create', Attendance::class);

        if ($correctionRequest->status !== AttendanceCorrectionRequest::STATUS_PENDING) {
            return back()->with('error', __('hr.attendance.correction_already_reviewed'));
        }

        $validated = $request->validate([
            'review_notes' => 'nullable|string|max:500',
        ]);

        $correctionRequest->forceFill([
            'status' => AttendanceCorrectionRequest::STATUS_REJECTED,
            'reviewed_by' => $request->user()?->id,
            'reviewed_at' => now(),
            'review_notes' => $validated['review_notes'] ?? null,
        ])->save();

        $correctionRequest->logActivity('rejected');

        return back()->with('success', __('hr.attendance.correction_rejected'));
    }
}
