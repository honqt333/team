<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\HR\Employee;
use App\Models\HR\Leave;
use App\Models\HR\Attendance;
use App\Models\HR\PayrollItem;
use App\Support\TenancyContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class EmployeePortalController extends Controller
{
    /**
     * Get the authenticated employee record.
     */
    protected function getEmployee()
    {
        $user = auth()->user();
        
        if (!$user->employee) {
            abort(403, __('messages.no_employee_linked'));
        }
        
        return $user->employee;
    }

    /**
     * Employee Dashboard - Overview of key info.
     */
    public function dashboard()
    {
        $this->authorize('viewOwn', Employee::class);
        
        $employee = $this->getEmployee();
        $employee->load(['jobTitle', 'department', 'center', 'defaultShift']);
        
        // Current month stats
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();
        
        // Attendance summary
        $attendanceStats = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->selectRaw('
                COUNT(*) as total_days,
                SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present_days,
                SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent_days,
                SUM(CASE WHEN status = "late" THEN 1 ELSE 0 END) as late_days,
                SUM(TIMESTAMPDIFF(MINUTE, check_in, check_out)) as total_minutes
            ')
            ->first();
        
        // Leave balance (simplified - would need proper leave balance calculation)
        $usedLeaves = Leave::where('employee_id', $employee->id)
            ->whereYear('start_date', $now->year)
            ->where('status', 'approved')
            ->sum('days');
        
        // Pending leave requests
        $pendingLeaves = Leave::where('employee_id', $employee->id)
            ->where('status', 'pending')
            ->count();
        
        // Recent payslips
        $recentPayslips = PayrollItem::where('employee_id', $employee->id)
            ->with('payrollRun:id,name,period_start,period_end')
            ->orderByDesc('created_at')
            ->take(3)
            ->get();
        
        return Inertia::render('EmployeePortal/Dashboard', [
            'employee' => $employee,
            'stats' => [
                'attendance' => [
                    'present' => $attendanceStats->present_days ?? 0,
                    'absent' => $attendanceStats->absent_days ?? 0,
                    'late' => $attendanceStats->late_days ?? 0,
                    'total_hours' => round(($attendanceStats->total_minutes ?? 0) / 60, 1),
                ],
                'leaves' => [
                    'used' => $usedLeaves,
                    'pending' => $pendingLeaves,
                    'annual_balance' => 21 - $usedLeaves, // Simplified - 21 days annual
                ],
            ],
            'recentPayslips' => $recentPayslips,
        ]);
    }

    /**
     * Employee Profile - Personal information.
     */
    public function profile()
    {
        $this->authorize('viewOwn', Employee::class);
        
        $employee = $this->getEmployee();
        $employee->load([
            'jobTitle', 
            'employeeType', 
            'department', 
            'nationality', 
            'center',
            'defaultShift',
        ]);
        
        return Inertia::render('EmployeePortal/Profile', [
            'employee' => $employee,
        ]);
    }

    /**
     * Employee Attendance - Personal attendance records.
     */
    public function attendance(Request $request)
    {
        $this->authorize('viewOwn', Employee::class);
        
        $employee = $this->getEmployee();
        
        // Default to current month
        $month = $request->get('month', Carbon::now()->format('Y-m'));
        $startDate = Carbon::parse($month)->startOfMonth();
        $endDate = Carbon::parse($month)->endOfMonth();
        
        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->with('shift:id,name_ar,name_en,start_time,end_time')
            ->orderBy('date')
            ->get();
        
        // Calculate monthly summary
        $summary = [
            'present' => $attendance->where('status', 'present')->count(),
            'absent' => $attendance->where('status', 'absent')->count(),
            'late' => $attendance->where('status', 'late')->count(),
            'early_leave' => $attendance->where('status', 'early_leave')->count(),
            'total_hours' => round($attendance->sum(function ($a) {
                if ($a->check_in && $a->check_out) {
                    return Carbon::parse($a->check_out)->diffInMinutes(Carbon::parse($a->check_in)) / 60;
                }
                return 0;
            }), 1),
        ];
        
        return Inertia::render('EmployeePortal/Attendance', [
            'attendance' => $attendance,
            'summary' => $summary,
            'month' => $month,
            'employee' => $employee->only(['id', 'name_ar', 'name_en']),
        ]);
    }

    /**
     * Employee Leaves - Leave requests and history.
     */
    public function leaves(Request $request)
    {
        $this->authorize('viewOwn', Employee::class);
        
        $employee = $this->getEmployee();
        
        $leaves = Leave::where('employee_id', $employee->id)
            ->with(['approvedBy:id,name'])
            ->orderByDesc('created_at')
            ->paginate(10);
        
        // Leave balance by type (simplified)
        $usedByType = Leave::where('employee_id', $employee->id)
            ->whereYear('start_date', Carbon::now()->year)
            ->where('status', 'approved')
            ->selectRaw('type, SUM(days) as used_days')
            ->groupBy('type')
            ->pluck('used_days', 'type');
        
        return Inertia::render('EmployeePortal/Leaves', [
            'leaves' => $leaves,
            'usedByType' => $usedByType,
            'employee' => $employee->only(['id', 'name_ar', 'name_en']),
        ]);
    }

    /**
     * Request a new leave.
     */
    public function requestLeave(Request $request)
    {
        $this->authorize('createOwn', Leave::class);
        
        $employee = $this->getEmployee();
        
        $validated = $request->validate([
            'type' => 'required|string|in:annual,sick,unpaid,emergency,maternity,paternity,other',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:500',
        ]);
        
        // Calculate days
        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        $days = $startDate->diffInDays($endDate) + 1;
        
        Leave::create([
            'tenant_id' => TenancyContext::tenantId(),
            'employee_id' => $employee->id,
            'type' => $validated['type'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'days' => $days,
            'reason' => $validated['reason'],
            'status' => 'pending',
            'requested_by' => auth()->id(),
        ]);
        
        return back()->with('success', __('hr.leaves.request_submitted'));
    }

    /**
     * Employee Payslips - Salary history.
     */
    public function payslips(Request $request)
    {
        $this->authorize('viewOwn', Employee::class);
        
        $employee = $this->getEmployee();
        
        $payslips = PayrollItem::where('employee_id', $employee->id)
            ->with(['payrollRun:id,name,period_start,period_end,status'])
            ->orderByDesc('created_at')
            ->paginate(12);
        
        return Inertia::render('EmployeePortal/Payslips', [
            'payslips' => $payslips,
            'employee' => $employee->only(['id', 'name_ar', 'name_en', 'base_salary']),
        ]);
    }

    /**
     * View single payslip details.
     */
    public function showPayslip(PayrollItem $payslip)
    {
        $this->authorize('viewOwn', Employee::class);
        
        $employee = $this->getEmployee();
        
        // Ensure this payslip belongs to the employee
        if ($payslip->employee_id !== $employee->id) {
            abort(403);
        }
        
        $payslip->load(['payrollRun', 'employee.jobTitle', 'employee.department']);
        
        return Inertia::render('EmployeePortal/PayslipShow', [
            'payslip' => $payslip,
        ]);
    }
}
