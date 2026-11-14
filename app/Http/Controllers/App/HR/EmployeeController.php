<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\HR;

use App\Http\Controllers\Controller;
use App\Models\Center;
use App\Models\Department;
use App\Models\HR\Allowance;
use App\Models\HR\Deduction;
use App\Models\HR\Employee;
use App\Models\HR\EmployeeType;
use App\Models\HR\JobTitle;
use App\Models\HR\OtherPayment;
use App\Models\HR\Shift;
use App\Models\Nationality;
use App\Models\Role;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Models\WorkOrderLaborEntry;
use App\Services\HR\TechnicianCommissionService;
use App\Support\TenancyContext;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Log;
use Spatie\Permission\PermissionRegistrar;
use Storage;

class EmployeeController extends Controller
{
    /**
     * Employees list with tabs: active / inactive.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Employee::class);

        $tenantId = TenancyContext::tenantId();
        $centerId = TenancyContext::centerId();
        $status = $request->get('status', 'active');
        $isSuperAdmin = auth()->user()->hasRole('super_admin');

        $allowedSorts = ['employee_number', 'name_ar', 'name_en', 'base_salary', 'created_at'];
        $sort = in_array($request->input('sort'), $allowedSorts, true) ? $request->input('sort') : 'name_ar';
        $direction = strtolower((string) ($request->input('direction') ?? $request->input('order', ''))) === 'desc' ? 'desc' : 'asc';

        $employees = Employee::where('tenant_id', $tenantId)
            ->when(! $isSuperAdmin, fn ($q) => $q->where(fn ($sub) => $sub->where('center_id', $centerId)->orWhereNull('center_id')))
            ->when($isSuperAdmin && $request->center_id, fn ($q) => $q->where('center_id', $request->center_id))
            ->when($status === 'active', fn ($q) => $q->active())
            ->when($status === 'inactive', fn ($q) => $q->where('status', '!=', 'active'))
            ->with([
                'jobTitle:id,name_ar,name_en',
                'department:id,name_ar,name_en',
                'employeeType:id,name_ar,name_en',
                'center:id,name',
                'documents' => fn ($q) => $q->whereIn('type', ['id', 'iqama', 'national_id'])->whereNotNull('expiry_date'),
                'user:id,name,email,is_system_admin',
                'user.roles:id,name',
                'user.roles.permissions:id,name',
                'user.permissions:id,name',
            ])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name_ar', 'like', "%{$search}%")
                        ->orWhere('name_en', 'like', "%{$search}%")
                        ->orWhere('employee_number', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->orderBy($sort, $direction)
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('HR/Employees/Index', [
            'employees' => $employees,
            'filters' => array_merge($request->only(['search', 'center_id']), [
                'status' => $status,
                'sort' => $sort,
                'direction' => $direction,
            ]),
            'counts' => [
                'active' => Employee::where('tenant_id', $tenantId)
                    ->when(! $isSuperAdmin, fn ($q) => $q->where(fn ($sub) => $sub->where('center_id', $centerId)->orWhereNull('center_id')))
                    ->when($isSuperAdmin && $request->center_id, fn ($q) => $q->where('center_id', $request->center_id))
                    ->active()
                    ->count(),
                'inactive' => Employee::where('tenant_id', $tenantId)
                    ->when(! $isSuperAdmin, fn ($q) => $q->where(fn ($sub) => $sub->where('center_id', $centerId)->orWhereNull('center_id')))
                    ->when($isSuperAdmin && $request->center_id, fn ($q) => $q->where('center_id', $request->center_id))
                    ->where('status', '!=', 'active')
                    ->count(),
            ],
            // For create modal
            'jobTitles' => JobTitle::where('tenant_id', $tenantId)->active()->get(['id', 'name_ar', 'name_en']),
            'employeeTypes' => EmployeeType::where('tenant_id', $tenantId)->active()->get(['id', 'name_ar', 'name_en']),
            'departments' => Department::where('tenant_id', $tenantId)->where('is_active', true)->get(['id', 'name_ar', 'name_en']),
            'centers' => $isSuperAdmin
                ? Center::where('tenant_id', $tenantId)->get(['id', 'name'])
                : Center::where('id', $centerId)->get(['id', 'name']),
            'nationalities' => Nationality::active()->orderBy('name_ar')->get(['id', 'name_ar', 'name_en']),
            'users' => User::where('tenant_id', $tenantId)
                ->whereDoesntHave('employee')
                ->get(['id', 'name', 'email']),
            'shifts' => Shift::where('tenant_id', $tenantId)->active()->get(['id', 'name_ar', 'name_en']),
        ]);
    }

    /**
     * Print employees list.
     */
    public function print()
    {
        $this->authorize('viewAny', Employee::class);

        $tenantId = TenancyContext::tenantId();
        $centerId = TenancyContext::centerId();
        $isSuperAdmin = auth()->user()->hasRole('super_admin');

        $employees = Employee::where('tenant_id', $tenantId)
            ->when(! $isSuperAdmin, fn ($q) => $q->where(fn ($sub) => $sub->where('center_id', $centerId)->orWhereNull('center_id')))
            ->active()
            ->with(['jobTitle:id,name_ar,name_en', 'department:id,name_ar,name_en'])
            ->orderBy('name_ar')
            ->get();

        // Group statistics
        $byDepartment = $employees->groupBy(fn ($e) => $e->department?->name_ar ?? 'بدون قسم')->map->count();
        $byJobTitle = $employees->groupBy(fn ($e) => $e->jobTitle?->name_ar ?? 'بدون مسمى')->map->count();

        return Inertia::render('HR/Employees/Print', [
            'employees' => $employees,
            'tenant' => auth()->user()->tenant,
            'center' => Center::find($centerId),
            'stats' => [
                'by_department' => $byDepartment,
                'by_job_title' => $byJobTitle,
            ],
        ]);
    }

    /**
     * Store new employee.
     */
    public function store(Request $request)
    {
        // Authorization comes first, before anything is written
        // anywhere.
        $this->authorize('create', Employee::class);

        // Operational logging without the request body.
        //
        // SECURITY: the previous line logged `$request->all()` —
        // the employee's name, phone, email, nationality and every
        // other field the client sent — and it did so BEFORE the
        // permission check, so a rejected request still produced a
        // full copy of the personal data in the log. Only
        // non-identifying context is recorded here.
        Log::info('Employee store initiated', [
            'tenant_id' => TenancyContext::tenantId(),
            'center_id' => TenancyContext::centerId(),
            'user_id' => auth()->id(),
        ]);

        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:hr_employees,phone,NULL,id,tenant_id,'.TenancyContext::tenantId(),
            'email' => 'required|email|max:255|unique:users,email|unique:hr_employees,email',
            'gender' => 'required|in:male,female',
            'nationality_id' => 'required|exists:nationalities,id',
            'job_title_id' => 'required|exists:hr_job_titles,id',
            'department_id' => 'nullable|exists:departments,id',
            'center_id' => 'nullable|exists:centers,id',
            'default_shift_id' => 'nullable|exists:hr_shifts,id',
        ]);

        try {
            $employee = new Employee;
            $employee->fill([
                'tenant_id' => TenancyContext::tenantId(),
                'center_id' => array_key_exists('center_id', $validated) ? $validated['center_id'] : TenancyContext::centerId(),
                'status' => 'active',
                ...$validated,
            ]);

            $employee->save();

            Log::info('Employee created', ['id' => $employee->id]);
        } catch (QueryException $e) {
            Log::error('Database Error creating employee: '.$e->getMessage());

            if ($e->errorInfo[1] == 1062) { // Duplicate entry
                return back()->with('error', __('messages.duplicate_entry_error').' - '.__('hr.employees.employee_number_exists'));
            }

            return back()->with('error', __('messages.error_occurred'));
        } catch (Exception $e) {
            Log::error('Error creating employee: '.$e->getMessage());

            return back()->with('error', __('messages.error_occurred'));
        }

        return back()->with('success', __('messages.created_successfully'));
    }

    /**
     * Show employee profile (extensible with tabs).
     */
    public function show(Employee $employee)
    {
        $this->authorize('view', $employee);

        $tenantId = TenancyContext::tenantId();

        $employee->load([
            'jobTitle',
            'employeeType',
            'department',
            'nationality',
            'center',
            'user:id,name,email',
            'allowances',
            'deductions',
            'leaves' => fn ($q) => $q->orderBy('start_date', 'desc'),
            'defaultShift',
            'employeeShifts.shift',
            'documents' => fn ($q) => $q->orderBy('created_at', 'desc'),
            'contracts' => fn ($q) => $q->orderBy('start_date', 'desc'),
            'assetCustodies' => fn ($q) => $q->orderByDesc('issued_date')->orderByDesc('id'),
        ]);

        // Get weekly schedule (day_of_week based)
        $weeklySchedule = $employee->employeeShifts
            ->whereNull('date')
            ->whereNotNull('day_of_week')
            ->keyBy('day_of_week')
            ->map(fn ($s) => $s->shift_id);

        return Inertia::render('HR/Employees/Show', [
            'employee' => $employee,
            'jobTitles' => JobTitle::where('tenant_id', $tenantId)->active()->get(['id', 'name_ar', 'name_en']),
            'employeeTypes' => EmployeeType::where('tenant_id', $tenantId)->active()->get(['id', 'name_ar', 'name_en']),
            'departments' => Department::where('tenant_id', $tenantId)->where('is_active', true)->get(['id', 'name_ar', 'name_en']),
            'nationalities' => Nationality::active()->orderBy('name_ar')->get(['id', 'name_ar', 'name_en']),
            'centers' => Center::where('tenant_id', $tenantId)->get(['id', 'name']),
            'allAllowances' => Allowance::where('tenant_id', $tenantId)->active()->get(),
            'allDeductions' => Deduction::where('tenant_id', $tenantId)->active()->get(),
            'users' => User::where('tenant_id', $tenantId)
                ->where(function ($q) use ($employee) {
                    $q->whereDoesntHave('employee')
                        ->orWhere('id', $employee->user_id);
                })
                ->get(['id', 'name', 'email']),
            'shifts' => Shift::where('tenant_id', $tenantId)->active()->get(['id', 'name_ar', 'name_en', 'start_time', 'end_time', 'color']),
            'weeklySchedule' => $weeklySchedule,
            // Roles for assignment
            'availableRoles' => Role::where('tenant_id', $tenantId)
                ->whereNotIn('name', ['super_admin']) // Exclude super_admin from assignment
                ->get(['id', 'name', 'label_ar', 'label_en']),
            'userRoles' => $employee->user ? $employee->user->roles->pluck('name') : [],
            // Financial Data
            'payrollItems' => $employee->payrollItems()
                ->with(['payrollRun:id,period_start,period_end,status', 'createdBy:id,name'])
                ->orderByDesc('created_at')
                ->take(12)
                ->get(),
            'otherPayments' => $employee->otherPayments()
                ->with(['createdBy:id,name', 'approvedBy:id,name'])
                ->orderByDesc('created_at')
                ->take(20)
                ->get(),
            // Attendance Data
            'recentAttendances' => $employee->attendance()
                ->orderByDesc('date')
                ->take(30)
                ->get(),
            'attendanceSummary' => $this->buildAttendanceSummary($employee),
            // Work Orders & Technician Productivity Data
            ...$this->buildWorkOrdersData($employee),
            // Offboarding & EOSB Clearance
            'latestOffboarding' => $employee->offboardings()
                ->with(['createdBy:id,name', 'finalizedBy:id,name'])
                ->latest('id')
                ->first(),
            'offboardingDefaults' => [
                'allowances_amount' => $employee->calculateTotalAllowances(),
                'unpaid_advances' => (float) $employee->otherPayments()
                    ->where('type', OtherPayment::TYPE_ADVANCE)
                    ->whereIn('status', [OtherPayment::STATUS_APPROVED, OtherPayment::STATUS_PAID])
                    ->sum('amount'),
            ],
        ]);
    }

    private function buildAttendanceSummary(Employee $employee): array
    {
        $summary = $employee->attendance()
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->selectRaw("
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
                COALESCE(SUM(overtime_minutes), 0) as overtime_minutes
            ")
            ->first();

        return [
            'present' => (int) ($summary?->present ?? 0),
            'late' => (int) ($summary?->late ?? 0),
            'absent' => (int) ($summary?->absent ?? 0),
            'overtime_minutes' => (int) ($summary?->overtime_minutes ?? 0),
        ];
    }

    private function buildWorkOrdersData(Employee $employee): array
    {
        $userId = $employee->user_id;

        if (! $userId) {
            return [
                'recentWorkOrders' => [],
                'workOrdersSummary' => [
                    'active_tasks' => 0,
                    'completed_tasks' => 0,
                    'labor_hours' => 0,
                ],
            ];
        }

        $activeTasks = WorkOrderItem::query()
            ->whereHas('workOrder', fn ($q) => $q->where('center_id', $employee->center_id))
            ->whereHas('technicians', fn ($q) => $q->where('users.id', $userId))
            ->whereIn('status', [
                WorkOrderItem::STATUS_PENDING,
                WorkOrderItem::STATUS_IN_PROGRESS,
                WorkOrderItem::STATUS_ON_HOLD,
                WorkOrderItem::STATUS_READY_FOR_QC,
            ])
            ->count();

        $completedTasks = WorkOrderItem::query()
            ->whereHas('workOrder', fn ($q) => $q->where('center_id', $employee->center_id))
            ->whereHas('technicians', fn ($q) => $q->where('users.id', $userId))
            ->where('status', WorkOrderItem::STATUS_COMPLETED)
            ->count();

        $laborSeconds = WorkOrderLaborEntry::query()
            ->where('center_id', $employee->center_id)
            ->where('user_id', $userId)
            ->whereMonth('started_at', now()->month)
            ->whereYear('started_at', now()->year)
            ->get()
            ->sum(fn ($entry) => $entry->effectiveDurationSeconds());

        $recentWorkOrders = WorkOrder::query()
            ->where('center_id', $employee->center_id)
            ->whereHas('items.technicians', fn ($q) => $q->where('users.id', $userId))
            ->with(['customer:id,name', 'vehicle.make:id,name_ar,name_en', 'vehicle.model:id,name_ar,name_en'])
            ->orderByDesc('created_at')
            ->take(15)
            ->get(['id', 'code', 'customer_id', 'vehicle_id', 'status', 'created_at']);

        $productivity = app(TechnicianCommissionService::class)->calculateForPeriod(
            $employee,
            now()->startOfMonth(),
            now()->endOfMonth()
        );

        return [
            'recentWorkOrders' => $recentWorkOrders,
            'workOrdersSummary' => [
                'active_tasks' => $activeTasks,
                'completed_tasks' => $completedTasks,
                'labor_hours' => round($laborSeconds / 3600, 1),
                'commission_amount' => $productivity['commission_amount'],
                'first_time_fix_rate' => $productivity['first_time_fix_rate'],
                'efficiency_rate' => $productivity['efficiency_rate'],
            ],
        ];
    }

    /**
     * Update employee.
     */
    public function update(Request $request, Employee $employee)
    {
        $this->authorize('update', $employee);

        $validated = $request->validate([
            // Basic Info
            'name_ar' => 'required|string|max:255',
            'name_en' => 'sometimes|required|string|max:255',
            'phone' => 'nullable|string|max:20|unique:hr_employees,phone,'.$employee->id.',id,tenant_id,'.TenancyContext::tenantId(),
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:hr_employees,email,'.$employee->id,
                'unique:users,email,'.($employee->user_id ?? 'NULL').',id',
            ],
            'gender' => 'nullable|in:male,female',
            'marital_status' => 'nullable|in:single,married',
            'birth_date' => 'nullable|date',
            // Address
            'city' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'street' => 'nullable|string|max:255',
            'building_number' => 'nullable|string|max:50',
            'postal_code' => 'nullable|string|max:20',
            // Job Info
            'job_title_id' => 'nullable|exists:hr_job_titles,id',
            'employee_type_id' => 'nullable|exists:hr_employee_types,id',
            'department_id' => 'nullable|exists:departments,id',
            'center_id' => 'nullable|exists:centers,id',
            'user_id' => 'nullable|exists:users,id',
            'hire_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date',
            'default_shift_id' => 'nullable|exists:hr_shifts,id',
            'status' => 'required|in:active,inactive,terminated',
            'notes' => 'nullable|string',
            // Identity
            'nationality_id' => 'nullable|exists:nationalities,id',
            'national_id' => 'nullable|string|max:20',
            'national_id_expiry' => 'nullable|date',
            'passport_number' => 'nullable|string|max:50',
            'passport_expiry' => 'nullable|date',
            'border_entry_number' => 'nullable|string|max:50',
            'border_port' => 'nullable|string|max:255',
            'sponsor_name' => 'nullable|string|max:255',
            'profession_on_id' => 'nullable|string|max:255',
            // Insurance
            'insurance_company' => 'nullable|string|max:255',
            'insurance_card_number' => 'nullable|string|max:50',
            'insurance_policy_number' => 'nullable|string|max:50',
            'insurance_expiry' => 'nullable|date',
            'insurance_classification' => 'nullable|string|max:100',
            'insurance_details' => 'nullable|string',
            // Salary (kept for compatibility)
            'base_salary' => 'nullable|numeric|min:0',
            'commission_enabled' => 'boolean',
            'commission_type' => 'nullable|in:fixed,percentage',
            'commission_rate' => 'nullable|numeric|min:0',
            'termination_date' => 'nullable|date',
            'termination_reason' => 'nullable|string',
        ]);

        $employee->fill($validated);
        $employee->save();

        return back()->with('success', __('messages.updated_successfully'));
    }

    /**
     * Upload employee photo.
     */
    public function uploadPhoto(Request $request, Employee $employee)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Delete old photo if exists
        if ($employee->photo_path && Storage::disk('public')->exists($employee->photo_path)) {
            Storage::disk('public')->delete($employee->photo_path);
        }

        // Store new photo
        $path = $request->file('photo')->store('employees/photos', 'public');

        $employee->update(['photo_path' => $path]);

        return back()->with('success', __('messages.updated_successfully'));
    }

    /**
     * Delete employee.
     */
    public function destroy(Employee $employee)
    {
        $this->authorize('delete', $employee);

        $employee->delete();

        return redirect()->route('app.hr.employees.index')
            ->with('success', __('messages.deleted_successfully'));
    }

    /**
     * Update employee's user roles.
     */
    public function updateRoles(Request $request, Employee $employee)
    {
        $this->authorize('update', $employee);

        if (! $employee->user) {
            return back()->with('error', __('hr.employees.no_user_linked'));
        }

        $validated = $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'string|exists:roles,name',
        ]);

        $tenantId = $employee->tenant_id;
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenantId);

        $allowedRoles = Role::where('tenant_id', $tenantId)
            ->whereNotIn('name', ['super_admin'])
            ->pluck('name')
            ->toArray();

        $rolesToSync = array_intersect($validated['roles'], $allowedRoles);

        if (! in_array('employee', $rolesToSync)) {
            $rolesToSync[] = 'employee';
        }

        $this->authorize('update', $employee->user);

        foreach (Role::where('tenant_id', $tenantId)->whereIn('name', $rolesToSync)->get() as $role) {
            $this->authorize('assignRole', [User::class, $role]);
        }
        $employee->user->syncRoles($rolesToSync);
        Log::info("Updated roles for employee {$employee->id}", ['roles' => $rolesToSync]);

        return back()->with('success', __('hr.employees.roles_updated'));
    }
}
