<?php

namespace App\Http\Controllers\App\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\Attendance;
use App\Models\HR\AttendanceSettings;
use App\Models\HR\Employee;
use App\Models\HR\PayrollItem;
use App\Models\HR\PayrollRun;
use App\Support\TenancyContext;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PayrollController extends Controller
{
    /**
     * Payroll runs index page.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', PayrollRun::class);
        
        $tenantId = TenancyContext::tenantId();
        $centerId = TenancyContext::centerId();

        $payrollRuns = PayrollRun::where('tenant_id', $tenantId)
            ->where('center_id', $centerId)
            ->with(['createdBy:id,name', 'approvedBy:id,name'])
            ->withCount('items')
            ->withSum('items', 'net_salary')
            ->orderByDesc('period_start')
            ->paginate(12);

        // Get ALL employees for managers (not filtered by center)
        $employees = Employee::where('tenant_id', $tenantId)
            ->active()
            ->select('id', 'name_ar', 'center_id')
            ->orderBy('name_ar')
            ->get();

        // Get centers list for filter dropdown
        $centers = \App\Models\Center::where('tenant_id', $tenantId)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('HR/Payroll/Index', [
            'payrollRuns' => $payrollRuns,
            'employees' => $employees,
            'centers' => $centers,
        ]);
    }

    /**
     * Show payroll run details.
     */
    public function show(PayrollRun $payrollRun)
    {
        $this->authorize('view', $payrollRun);

        $payrollRun->load([
            'items.employee.jobTitle',
            'items.employee.department',
            'createdBy:id,name',
            'approvedBy:id,name',
        ]);

        return Inertia::render('HR/Payroll/Show', [
            'payrollRun' => $payrollRun,
        ]);
    }

    /**
     * Generate a new payroll run for a specific period.
     */
    public function generate(Request $request)
    {
        $this->authorize('create', PayrollRun::class);

        $validated = $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'payment_date' => 'nullable|date',
        ]);

        $tenantId = TenancyContext::tenantId();
        $centerId = TenancyContext::centerId();
        
        $periodStart = Carbon::parse($validated['period_start']);
        $periodEnd = Carbon::parse($validated['period_end']);

        // Check if payroll already exists for this period
        $exists = PayrollRun::where('tenant_id', $tenantId)
            ->where('center_id', $centerId)
            ->where('period_start', $periodStart->toDateString())
            ->exists();

        if ($exists) {
            return back()->with('error', __('hr.payroll.already_exists'));
        }

        // Create payroll run
        $payrollRun = PayrollRun::create([
            'tenant_id' => $tenantId,
            'center_id' => $centerId,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'payment_date' => $validated['payment_date'] ?? null,
            'status' => 'draft',
            'created_by' => auth()->id(),
        ]);

        $this->processPayrollItems($payrollRun);

        return redirect()->route('app.hr.payroll.show', $payrollRun)
            ->with('success', __('hr.payroll.generated_successfully'));
    }

    /**
     * Regenerate an existing draft payroll run.
     */
    public function regenerate(PayrollRun $payrollRun)
    {
        $this->authorize('update', $payrollRun);

        if ($payrollRun->status !== 'draft') {
            return back()->with('error', __('hr.payroll.cannot_regenerate'));
        }

        // Delete existing items
        $payrollRun->items()->delete();

        // Process items again
        $this->processPayrollItems($payrollRun);

        return back()->with('success', __('hr.payroll.regenerated_successfully'));
    }

    /**
     * Remove an item (employee) from the payroll run.
     */
    public function destroyItem(PayrollRun $payrollRun, PayrollItem $payrollItem)
    {
        $this->authorize('update', $payrollRun);

        if ($payrollRun->status !== 'draft') {
            return back()->with('error', __('hr.payroll.cannot_delete_item'));
        }

        $payrollItem->delete();

        return back()->with('success', __('common.deleted_success'));
    }

    /**
     * Process and create payroll items for a run.
     */
    private function processPayrollItems(PayrollRun $payrollRun)
    {
        $settings = AttendanceSettings::getForCenter($payrollRun->center_id);
        
        // Get all active employees in this center
        $employees = Employee::where('tenant_id', $payrollRun->tenant_id)
            ->where('center_id', $payrollRun->center_id)
            ->active()
            ->with(['allowances', 'deductions'])
            ->get();

        foreach ($employees as $employee) {
            // Calculate attendance-based deductions
            $attendanceDeduction = $this->calculateAttendanceDeductions(
                $employee, 
                $payrollRun->period_start, 
                $payrollRun->period_end,
                $settings
            );

            // Calculate overtime payment
            $overtimePayment = $this->calculateOvertimePayment(
                $employee,
                $payrollRun->period_start,
                $payrollRun->period_end,
                $settings
            );

            // Calculate base values
            $baseSalary = $employee->base_salary ?? 0;
            $gosiAmount = $employee->calculateGosiAmount();
            $totalAllowances = $employee->calculateTotalAllowances() + $overtimePayment;
            $totalDeductions = $employee->calculateTotalDeductions() + $gosiAmount + $attendanceDeduction;
            $netSalary = $baseSalary + $totalAllowances - $totalDeductions;

            // Create payroll item
            PayrollItem::create([
                'payroll_run_id' => $payrollRun->id,
                'employee_id' => $employee->id,
                'base_salary' => $baseSalary,
                'gosi_rate' => $employee->gosi_employee_rate ?? 9.75,
                'gosi_amount' => $gosiAmount,
                'total_allowances' => $totalAllowances,
                'total_deductions' => $totalDeductions,
                'net_salary' => max(0, $netSalary),
                'allowances_breakdown' => [
                    'regular' => $employee->calculateTotalAllowances(),
                    'overtime' => $overtimePayment,
                ],
                'deductions_breakdown' => [
                    'regular' => $employee->calculateTotalDeductions(),
                    'gosi' => $gosiAmount,
                    'attendance' => $attendanceDeduction,
                ],
                'created_by' => auth()->id(),
            ]);
        }
    }

    /**
     * Calculate deductions based on attendance (late, absence).
     */
    private function calculateAttendanceDeductions(Employee $employee, Carbon $start, Carbon $end, AttendanceSettings $settings): float
    {
        $attendances = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get();

        $lateDeduction = 0;
        $absenceDeduction = 0;

        // Count late minutes
        $totalLateMinutes = $attendances->sum('late_minutes');
        $lateDeduction = $totalLateMinutes * $settings->late_deduction_per_minute;

        // Count absences
        $expectedWorkDays = 0;
        $currentDate = $start->copy();
        while ($currentDate->lte($end)) {
            if ($settings->isWorkingDay($currentDate->dayOfWeek)) {
                $expectedWorkDays++;
            }
            $currentDate->addDay();
        }

        $presentDays = $attendances->where('status', '!=', 'absent')->count();
        $absentDays = max(0, $expectedWorkDays - $presentDays);
        
        // Calculate absence deduction based on type
        if ($settings->absence_deduction_type === 'percentage') {
            // Daily Salary = (Base + Allowances) / 30
            $dailySalary = ($employee->base_salary + $employee->calculateTotalAllowances()) / 30;
            // Deduction = Days * Daily Salary * Percentage
            // Value is stored as 100 for 100%, 50 for 50%, etc.
            $absenceDeduction = $absentDays * $dailySalary * ($settings->absence_deduction_value / 100);
        } else {
            // Fixed amount per day
            $absenceDeduction = $absentDays * $settings->absence_deduction_value;
        }

        return round($lateDeduction + $absenceDeduction, 2);
    }
    
    /**
     * Calculate overtime payment.
     */
    private function calculateOvertimePayment(Employee $employee, Carbon $start, Carbon $end, AttendanceSettings $settings): float
    {
        if (!$settings->overtime_enabled) {
            return 0;
        }

        $totalOvertimeMinutes = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->sum('overtime_minutes');

        $hours = $totalOvertimeMinutes / 60;
        return round($hours * $settings->overtime_rate_per_hour, 2);
    }

    /**
     * Approve a payroll run.
     */
    public function approve(PayrollRun $payrollRun)
    {
        $this->authorize('update', $payrollRun);

        if ($payrollRun->status !== 'draft') {
            return back()->with('error', __('hr.payroll.cannot_approve'));
        }

        $payrollRun->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
        ]);

        return back()->with('success', __('hr.payroll.approved_successfully'));
    }

    /**
     * Mark payroll as paid.
     */
    public function markPaid(PayrollRun $payrollRun)
    {
        $this->authorize('update', $payrollRun);

        if ($payrollRun->status !== 'approved') {
            return back()->with('error', __('hr.payroll.must_approve_first'));
        }

        $payrollRun->update([
            'status' => 'paid',
            'payment_date' => now(),
        ]);

        // Mark associated Other Payments as paid
        // Note: Ideally we would link them to the payroll item, but for now we find them by date/employee
        $payrollRun->load('items');
        foreach ($payrollRun->items as $item) {
            $item->employee->otherPayments()
                ->approved()
                ->whereBetween('payment_date', [$payrollRun->period_start, $payrollRun->period_end])
                ->update(['status' => 'paid']);
        }

        return back()->with('success', __('hr.payroll.marked_paid'));
    }

    /**
     * Print payroll summary.
     */
    public function print(PayrollRun $payrollRun)
    {
        $this->authorize('view', $payrollRun);

        $payrollRun->load([
            'items.employee.jobTitle',
            'items.employee.department',
            'center',
        ]);

        return Inertia::render('HR/Payroll/Print', [
            'payrollRun' => $payrollRun,
            'tenant' => auth()->user()->tenant,
        ]);
    }

    /**
     * Get payroll items for a specific employee.
     */
    public function employeePayroll(Employee $employee)
    {
        $this->authorize('view', $employee);

        $payrollItems = $employee->payrollItems()
            ->with(['payrollRun', 'createdBy:id,name'])
            ->orderByDesc('created_at')
            ->paginate(12);

        return response()->json([
            'payrollItems' => $payrollItems,
        ]);
    }

    /**
     * Print a pay slip.
     */
    public function printPaySlip(PayrollItem $payrollItem)
    {
        $this->authorize('view', $payrollItem->employee);

        $payrollItem->load([
            'employee.jobTitle',
            'employee.department',
            'employee.center',
            'payrollRun',
            'createdBy:id,name',
        ]);

        return Inertia::render('HR/Payroll/PrintPaySlip', [
            'payrollItem' => $payrollItem,
        ]);
    }
}

