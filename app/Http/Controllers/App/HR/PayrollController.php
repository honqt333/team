<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\HR;

use App\Http\Controllers\Controller;
use App\Models\Center;
use App\Models\HR\Attendance;
use App\Models\HR\AttendanceSettings;
use App\Models\HR\Employee;
use App\Models\HR\Leave;
use App\Models\HR\PayrollItem;
use App\Models\HR\PayrollRun;
use App\Services\HR\PayrollAccountingService;
use App\Services\HR\TechnicianCommissionService;
use App\Support\TenancyContext;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $query = PayrollRun::where('tenant_id', $tenantId)
            ->when($centerId, function ($q) use ($centerId) {
                $q->where(function ($sub) use ($centerId) {
                    $sub->where('center_id', $centerId)
                        ->orWhereNull('center_id');
                });
            })
            ->with(['createdBy:id,name', 'approvedBy:id,name'])
            ->withCount('items')
            ->withSum('items', 'net_salary')
            ->when($request->input('status'), function ($q, $status) {
                $q->where('status', $status);
            })
            ->when($request->input('search'), function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('period_start', 'like', "%{$search}%")
                        ->orWhere('period_end', 'like', "%{$search}%")
                        ->orWhere('payment_date', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%");
                });
            });

        $allowedSorts = ['period_start', 'period_end', 'payment_date', 'items_count', 'items_sum_net_salary', 'status', 'created_at'];
        $sort = in_array($request->input('sort'), $allowedSorts, true) ? $request->input('sort') : 'period_start';
        $direction = strtolower((string) ($request->input('direction') ?? $request->input('order', ''))) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sort, $direction);

        $payrollRuns = $query->paginate(12)->withQueryString();

        // Get ALL employees for managers (not filtered by center)
        $employees = Employee::where('tenant_id', $tenantId)
            ->active()
            ->select('id', 'name_ar', 'center_id')
            ->orderBy('name_ar')
            ->get();

        // Get centers list for filter dropdown
        $centers = Center::where('tenant_id', $tenantId)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('HR/Payroll/Index', [
            'payrollRuns' => $payrollRuns,
            'employees' => $employees,
            'centers' => $centers,
            'filters' => array_merge($request->only(['search', 'status']), [
                'sort' => $sort,
                'direction' => $direction,
            ]),
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
            'companyTransaction:id,title,amount,total_amount,status,transaction_date,center_id',
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

        return DB::transaction(function () use ($payrollRun, $payrollItem) {
            $locked = PayrollRun::query()->lockForUpdate()->findOrFail($payrollRun->id);
            $this->authorize('update', $locked);
            $item = $locked->items()->lockForUpdate()->findOrFail($payrollItem->id);

            if ($locked->status !== 'draft') {
                return back()->with('error', __('hr.payroll.cannot_delete_item'));
            }
            $item->delete();
            $locked->logActivity('item_deleted', ['payroll_item_id' => $item->id]);

            return back()->with('success', __('common.deleted_success'));
        });
    }

    /**
     * Update an individual payroll item in a draft payroll run.
     */
    public function updateItem(Request $request, PayrollRun $payrollRun, PayrollItem $payrollItem)
    {
        $this->authorize('update', $payrollRun);

        $validated = $request->validate([
            'base_salary' => 'required|numeric|min:0',
            'total_allowances' => 'required|numeric|min:0',
            'total_deductions' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        return DB::transaction(function () use ($payrollRun, $payrollItem, $validated) {
            $locked = PayrollRun::query()->lockForUpdate()->findOrFail($payrollRun->id);
            $this->authorize('update', $locked);

            if ($locked->status !== 'draft') {
                return back()->with('error', __('hr.payroll.cannot_edit_item'));
            }

            $item = $locked->items()->lockForUpdate()->findOrFail($payrollItem->id);

            $settings = AttendanceSettings::getForCenter($locked->center_id);
            $maxCapPct = (float) ($settings->max_deduction_percentage ?? 50.0);
            $grossWage = (float) $validated['base_salary'] + (float) $validated['total_allowances'];

            if ($maxCapPct > 0 && $maxCapPct < 100 && $grossWage > 0) {
                $maxAllowed = round($grossWage * ($maxCapPct / 100), 2);

                if ((float) $validated['total_deductions'] > $maxAllowed) {
                    return back()->with('error', __('hr.payroll.deduction_exceeds_statutory_cap', ['percent' => $maxCapPct]));
                }
            }

            $netSalary = max(0, $grossWage - (float) $validated['total_deductions']);

            $item->update([
                'base_salary' => $validated['base_salary'],
                'total_allowances' => $validated['total_allowances'],
                'total_deductions' => $validated['total_deductions'],
                'net_salary' => $netSalary,
                'notes' => $validated['notes'] ?? $item->notes,
            ]);

            $locked->logActivity('item_updated', [
                'payroll_item_id' => $item->id,
                'base_salary' => $validated['base_salary'],
                'total_allowances' => $validated['total_allowances'],
                'total_deductions' => $validated['total_deductions'],
                'net_salary' => $netSalary,
            ]);

            return back()->with('success', __('messages.updated_successfully'));
        });
    }

    /**
     * Process and create payroll items for a run.
     */
    private function processPayrollItems(PayrollRun $payrollRun)
    {
        $settings = AttendanceSettings::getForCenter($payrollRun->center_id);
        $commissionService = app(TechnicianCommissionService::class);

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

            // Calculate technician commission & productivity KPIs
            $commissionData = $commissionService->calculateForPeriod(
                $employee,
                $payrollRun->period_start,
                $payrollRun->period_end
            );
            $commissionAmount = $commissionData['commission_amount'];

            // Calculate base values
            $baseSalary = $employee->base_salary ?? 0;
            $gosiAmount = $employee->calculateGosiAmount();
            $regularAllowances = $employee->calculateTotalAllowances();
            $totalAllowances = $regularAllowances + $overtimePayment + $commissionAmount;
            $grossWage = $baseSalary + $totalAllowances;

            $regularDeductions = $employee->calculateTotalDeductions();
            $rawTotalDeductions = round($regularDeductions + $gosiAmount + $attendanceDeduction, 2);
            $maxCapPct = (float) ($settings->max_deduction_percentage ?? 50.0);

            $totalDeductions = $rawTotalDeductions;
            $cappedAmount = 0.0;

            if ($maxCapPct > 0 && $maxCapPct < 100 && $grossWage > 0) {
                $maxAllowed = round($grossWage * ($maxCapPct / 100), 2);

                if ($rawTotalDeductions > $maxAllowed) {
                    // GOSI is statutory; cap discretionary/attendance deductions so total stays within statutory limit
                    $totalDeductions = max($gosiAmount, $maxAllowed);
                    $cappedAmount = round($rawTotalDeductions - $totalDeductions, 2);
                }
            }

            $netSalary = $grossWage - $totalDeductions;

            $transferredCenterIds = Attendance::withoutGlobalScope('center_scoped')
                ->where('tenant_id', $employee->tenant_id)
                ->where('employee_id', $employee->id)
                ->whereBetween('date', [$payrollRun->period_start->toDateString(), $payrollRun->period_end->toDateString()])
                ->whereNotNull('center_id')
                ->pluck('center_id')
                ->unique()
                ->filter(fn ($attCenterId) => (int) $attCenterId !== (int) $employee->center_id)
                ->values();

            $transferNote = $transferredCenterIds->isNotEmpty()
                ? __('hr.payroll.transferred_attendance_note', ['centers' => $transferredCenterIds->implode(', ')])
                : null;

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
                    'regular' => $regularAllowances,
                    'overtime' => $overtimePayment,
                    'commission' => $commissionAmount,
                    'productivity' => [
                        'billable_items' => $commissionData['billable_items_count'],
                        'warranty_items' => $commissionData['warranty_items_count'],
                        'non_warranty_share' => $commissionData['non_warranty_share'],
                        'efficiency_rate' => $commissionData['efficiency_rate'],
                    ],
                ],
                'deductions_breakdown' => [
                    'regular' => $regularDeductions,
                    'gosi' => $gosiAmount,
                    'attendance' => $attendanceDeduction,
                    'raw_total' => $rawTotalDeductions,
                    'cap_percentage' => $maxCapPct,
                    'capped_amount' => $cappedAmount,
                    'is_capped' => $cappedAmount > 0,
                    'transferred_from_center_ids' => $transferredCenterIds->all(),
                ],
                'notes' => $transferNote,
                'created_by' => auth()->id(),
            ]);
        }
    }

    /**
     * Calculate deductions based on attendance (late, absence) and approved leaves.
     */
    private function calculateAttendanceDeductions(Employee $employee, Carbon $start, Carbon $end, AttendanceSettings $settings): float
    {
        $attendances = Attendance::withoutGlobalScope('center_scoped')
            ->where('tenant_id', $employee->tenant_id)
            ->where('employee_id', $employee->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get();

        // Get approved leaves for this employee overlapping with the period
        $approvedLeaves = Leave::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start->toDateString(), $end->toDateString()])
                    ->orWhereBetween('end_date', [$start->toDateString(), $end->toDateString()])
                    ->orWhere(function ($sub) use ($start, $end) {
                        $sub->where('start_date', '<=', $start->toDateString())
                            ->where('end_date', '>=', $end->toDateString());
                    });
            })
            ->get();

        // Count late minutes deduction
        $totalLateMinutes = $attendances->sum('late_minutes');
        $lateDeduction = $totalLateMinutes * (float) ($settings->late_deduction_per_minute ?? 0);

        // Daily wage calculation (Saudi Labor Law standard: monthly wage / 30)
        $dailySalary = ($employee->base_salary + $employee->calculateTotalAllowances()) / 30;

        $unexcusedAbsentDays = 0;
        $unpaidLeaveDays = 0;

        $currentDate = $start->copy();

        while ($currentDate->lte($end)) {
            // Only working days are subject to absence/attendance checks
            if ($settings->isWorkingDay($currentDate->dayOfWeek)) {
                $dateStr = $currentDate->toDateString();

                // Check if employee has an approved leave covering this day
                $activeLeave = $approvedLeaves->first(function ($leave) use ($currentDate) {
                    $leaveStart = Carbon::parse($leave->start_date)->startOfDay();
                    $leaveEnd = Carbon::parse($leave->end_date)->endOfDay();

                    return $currentDate->gte($leaveStart) && $currentDate->lte($leaveEnd);
                });

                if ($activeLeave) {
                    if ($activeLeave->type === 'unpaid') {
                        $unpaidLeaveDays++;
                    }
                    // Paid leaves (annual, sick, emergency, etc.) are protected from absence deduction
                } else {
                    // Check attendance record
                    $att = $attendances->first(fn ($a) => Carbon::parse($a->date)->toDateString() === $dateStr);

                    if (! $att || $att->status === 'absent') {
                        $unexcusedAbsentDays++;
                    }
                }
            }

            $currentDate->addDay();
        }

        // Calculate unexcused absence deduction based on setting
        if ($settings->absence_deduction_type === 'percentage') {
            $absenceDeduction = $unexcusedAbsentDays * $dailySalary * ((float) ($settings->absence_deduction_value ?? 100) / 100);
        } else {
            $absenceDeduction = $unexcusedAbsentDays * (float) ($settings->absence_deduction_value ?? 0);
        }

        // Unpaid leave deduction (exact daily wage per day without absence penalty)
        $unpaidLeaveDeduction = $unpaidLeaveDays * $dailySalary;

        return round($lateDeduction + $absenceDeduction + $unpaidLeaveDeduction, 2);
    }

    /**
     * Calculate overtime payment.
     */
    private function calculateOvertimePayment(Employee $employee, Carbon $start, Carbon $end, AttendanceSettings $settings): float
    {
        if (! $settings->overtime_enabled) {
            return 0;
        }

        $totalOvertimeMinutes = Attendance::withoutGlobalScope('center_scoped')
            ->where('tenant_id', $employee->tenant_id)
            ->where('employee_id', $employee->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->sum('overtime_minutes');

        $hours = $totalOvertimeMinutes / 60;

        return round($hours * $settings->overtime_rate_per_hour, 2);
    }

    /**
     * Approve a payroll run.
     */
    public function approve(PayrollRun $payrollRun, PayrollAccountingService $accountingService)
    {
        $this->authorize('update', $payrollRun);

        if ($payrollRun->status !== 'draft') {
            return back()->with('error', __('hr.payroll.cannot_approve'));
        }

        DB::transaction(function () use ($payrollRun, $accountingService): void {
            $payrollRun->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
            ]);

            $payrollRun->logActivity('approved', [
                'status' => 'approved',
                'approved_by' => auth()->id(),
            ]);

            $accountingService->postPayrollRunExpense($payrollRun);
        });

        return back()->with('success', __('hr.payroll.approved_successfully'));
    }

    /**
     * Mark payroll as paid.
     */
    public function markPaid(Request $request, PayrollRun $payrollRun, PayrollAccountingService $accountingService)
    {
        $this->authorize('update', $payrollRun);

        if ($payrollRun->status !== 'approved') {
            return back()->with('error', __('hr.payroll.must_approve_first'));
        }

        $validated = $request->validate([
            'payment_date' => 'nullable|date',
            'payment_method' => 'nullable|string|max:50',
            'reference_number' => 'nullable|string|max:100',
        ]);

        $paymentDate = ! empty($validated['payment_date']) ? Carbon::parse($validated['payment_date']) : now();

        DB::transaction(function () use ($payrollRun, $accountingService, $paymentDate, $validated): void {
            $payrollRun->update([
                'status' => 'paid',
                'payment_date' => $paymentDate,
            ]);

            $payrollRun->logActivity('paid', [
                'status' => 'paid',
                'payment_date' => $paymentDate->toDateString(),
                'payment_method' => $validated['payment_method'] ?? 'bank_transfer',
                'reference_number' => $validated['reference_number'] ?? null,
            ]);

            $accountingService->postPayrollRunExpense($payrollRun, $paymentDate);

            // Mark associated Other Payments as paid
            $payrollRun->load('items');

            foreach ($payrollRun->items as $item) {
                $item->employee->otherPayments()
                    ->approved()
                    ->whereBetween('payment_date', [$payrollRun->period_start, $payrollRun->period_end])
                    ->update(['status' => 'paid']);
            }
        });

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
     * Export WPS / Bank-compatible CSV file for a payroll run.
     */
    public function exportWps(PayrollRun $payrollRun)
    {
        $this->authorize('view', $payrollRun);

        $payrollRun->load([
            'items.employee',
            'center',
        ]);

        $currency = auth()->user()?->tenant?->currency ?? 'SAR';
        $periodStart = $payrollRun->period_start?->format('Y-m-d') ?? (string) $payrollRun->period_start;
        $periodEnd = $payrollRun->period_end?->format('Y-m-d') ?? (string) $payrollRun->period_end;
        $filename = "wps-payroll-{$periodStart}-to-{$periodEnd}.csv";

        $sanitize = static function (?string $value): string {
            $str = (string) ($value ?? '');

            if ($str !== '' && in_array($str[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
                return "'".$str;
            }

            return $str;
        };

        return response()->streamDownload(function () use ($payrollRun, $currency, $periodStart, $periodEnd, $sanitize): void {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM for Excel / local banking portals
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Employee Number',
                'Employee Name (AR)',
                'Employee Name (EN)',
                'National ID / Iqama',
                'Bank Name',
                'IBAN',
                'Account Number',
                'Base Salary',
                'Total Allowances',
                'GOSI Deduction',
                'Total Deductions',
                'Net Salary',
                'Currency',
                'Period Start',
                'Period End',
            ]);

            foreach ($payrollRun->items as $item) {
                $emp = $item->employee;
                fputcsv($handle, [
                    $sanitize($emp?->employee_number),
                    $sanitize($emp?->name_ar),
                    $sanitize($emp?->name_en),
                    $sanitize($emp?->national_id),
                    $sanitize($emp?->bank_name),
                    $sanitize($emp?->bank_iban),
                    $sanitize($emp?->bank_account_number),
                    number_format((float) $item->base_salary, 2, '.', ''),
                    number_format((float) $item->total_allowances, 2, '.', ''),
                    number_format((float) $item->gosi_amount, 2, '.', ''),
                    number_format((float) $item->total_deductions, 2, '.', ''),
                    number_format((float) $item->net_salary, 2, '.', ''),
                    $currency,
                    $periodStart,
                    $periodEnd,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Get payroll items for a specific employee.
     */
    public function employeePayroll(Employee $employee)
    {
        $this->authorize('view', $employee);

        $payrollItems = $employee->payrollItems()
            ->with(['payrollRun:id,period_start,period_end,status', 'createdBy:id,name'])
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
