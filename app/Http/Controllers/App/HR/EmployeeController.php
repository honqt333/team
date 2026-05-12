<?php

namespace App\Http\Controllers\App\HR;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\HR\Allowance;
use App\Models\HR\Deduction;
use App\Models\HR\Employee;
use App\Models\HR\EmployeeType;
use App\Models\HR\JobTitle;
use App\Models\User;
use App\Support\TenancyContext;
use Illuminate\Http\Request;
use Inertia\Inertia;

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

        $employees = Employee::where('tenant_id', $tenantId)
            ->when(!$isSuperAdmin, fn($q) => $q->where('center_id', $centerId))
            ->when($isSuperAdmin && $request->center_id, fn($q) => $q->where('center_id', $request->center_id))
            ->when($status === 'active', fn($q) => $q->active())
            ->when($status === 'inactive', fn($q) => $q->where('status', '!=', 'active'))
            ->with(['jobTitle:id,name_ar,name_en', 'department:id,name_ar,name_en', 'employeeType:id,name_ar,name_en', 'center:id,name'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name_ar', 'like', "%{$search}%")
                        ->orWhere('name_en', 'like', "%{$search}%")
                        ->orWhere('employee_number', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->orderBy('name_ar')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('HR/Employees/Index', [
            'employees' => $employees,
            'filters' => array_merge($request->only(['search', 'center_id']), ['status' => $status]),
            'counts' => [
                'active' => Employee::where('tenant_id', $tenantId)
                    ->when(!$isSuperAdmin, fn($q) => $q->where('center_id', $centerId))
                    ->when($isSuperAdmin && $request->center_id, fn($q) => $q->where('center_id', $request->center_id))
                    ->active()
                    ->count(),
                'inactive' => Employee::where('tenant_id', $tenantId)
                    ->when(!$isSuperAdmin, fn($q) => $q->where('center_id', $centerId))
                    ->when($isSuperAdmin && $request->center_id, fn($q) => $q->where('center_id', $request->center_id))
                    ->where('status', '!=', 'active')
                    ->count(),
            ],
            // For create modal
            'jobTitles' => JobTitle::where('tenant_id', $tenantId)->active()->get(['id', 'name_ar', 'name_en']),
            'employeeTypes' => EmployeeType::where('tenant_id', $tenantId)->active()->get(['id', 'name_ar', 'name_en']),
            'departments' => Department::where('tenant_id', $tenantId)->where('is_active', true)->get(['id', 'name_ar', 'name_en']),
            'centers' => $isSuperAdmin 
                ? \App\Models\Center::where('tenant_id', $tenantId)->get(['id', 'name'])
                : \App\Models\Center::where('id', $centerId)->get(['id', 'name']),
            'nationalities' => \App\Models\Nationality::active()->orderBy('name_ar')->get(['id', 'name_ar', 'name_en']),
            'users' => User::where('tenant_id', $tenantId)
                ->whereDoesntHave('employee')
                ->get(['id', 'name', 'email']),
            'shifts' => \App\Models\HR\Shift::where('tenant_id', $tenantId)->active()->get(['id', 'name_ar', 'name_en']),
        ]);
    }

    /**
     * Print employees list.
     */
    public function print(Request $request)
    {
        $this->authorize('viewAny', Employee::class);
        
        $tenantId = TenancyContext::tenantId();
        $centerId = TenancyContext::centerId();
        $isSuperAdmin = auth()->user()->hasRole('super_admin');
        $status = $request->get('status', 'active');

        $employees = Employee::where('tenant_id', $tenantId)
            ->when($request->id, fn($q) => $q->where('id', $request->id))
            ->when(!$request->id, function($query) use ($isSuperAdmin, $centerId, $request, $status) {
                $query->when(!$isSuperAdmin, fn($q) => $q->where('center_id', $centerId))
                    ->when($isSuperAdmin && $request->center_id, fn($q) => $q->where('center_id', $request->center_id))
                    ->when($status === 'active', fn($q) => $q->active())
                    ->when($status === 'inactive', fn($q) => $q->where('status', '!=', 'active'))
                    ->when($request->search, function ($q, $search) {
                        $q->where(function ($sq) use ($search) {
                            $sq->where('name_ar', 'like', "%{$search}%")
                                ->orWhere('name_en', 'like', "%{$search}%")
                                ->orWhere('employee_number', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        });
                    });
            })
            ->with(['jobTitle:id,name_ar,name_en', 'department:id,name_ar,name_en', 'center:id,name'])
            ->orderBy('name_ar')
            ->get();

        // Group statistics
        $byDepartment = $employees->groupBy(fn($e) => $e->department?->name_ar ?? 'بدون قسم')->map->count();
        $byJobTitle = $employees->groupBy(fn($e) => $e->jobTitle?->name_ar ?? 'بدون مسمى')->map->count();

        return Inertia::render('HR/Employees/Print', [
            'employees' => $employees,
            'tenant' => auth()->user()->tenant,
            'center' => \App\Models\Center::find($request->center_id ?: $centerId),
            'stats' => [
                'by_department' => $byDepartment,
                'by_job_title' => $byJobTitle,
            ]
        ]);
    }

    /**
     * Store new employee.
     */
    public function store(Request $request)
    {
        \Log::info('Employee store initiated', $request->all());
        $this->authorize('create', Employee::class);
        
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:hr_employees,phone,NULL,id,tenant_id,' . TenancyContext::tenantId(),
            'email' => 'required|email|max:255|unique:hr_employees,email,NULL,id,tenant_id,' . TenancyContext::tenantId(),
            'gender' => 'required|in:male,female',
            'nationality_id' => 'required|exists:nationalities,id',
            'job_title_id' => 'required|exists:hr_job_titles,id',
            'center_id' => 'nullable|exists:centers,id',
            'default_shift_id' => 'nullable|exists:hr_shifts,id',
        ]);

        // Security Check: Restrict "Management" department to Super Admin
        if (isset($validated['department_id'])) {
            $this->checkManagementRestriction($validated['department_id']);
        }

        // Check if user has permission to management if center_id is null
        if (empty($validated['center_id']) && !auth()->user()->hasRole('super_admin')) {
             $validated['center_id'] = TenancyContext::centerId();
             \Log::info('Auto-assigned center_id', ['center_id' => $validated['center_id']]);
        }

        try {
            $employee = new Employee();
            $employee->fill([
                'tenant_id' => TenancyContext::tenantId(),
                'center_id' => array_key_exists('center_id', $validated) ? $validated['center_id'] : TenancyContext::centerId(),
                'status' => 'active',
                ...$validated,
            ]);
            
            $employee->save();

            \Log::info('Employee created', ['id' => $employee->id]);
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error('Database Error creating employee: ' . $e->getMessage());
            if ($e->errorInfo[1] == 1062) { // Duplicate entry
                return back()->with('error', __('messages.duplicate_entry_error') . ' - ' . __('hr.employees.employee_number_exists'));
            }
            return back()->with('error', __('messages.error_occurred'));
        } catch (\Exception $e) {
            \Log::error('Error creating employee: ' . $e->getMessage());
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
            'leaves' => fn($q) => $q->orderBy('start_date', 'desc'),
            'defaultShift',
            'employeeShifts.shift',
            'documents' => fn($q) => $q->orderBy('created_at', 'desc'),
            'contracts' => fn($q) => $q->orderBy('start_date', 'desc'),
        ]);

        // Get weekly schedule (day_of_week based)
        $weeklySchedule = $employee->employeeShifts
            ->whereNull('date')
            ->whereNotNull('day_of_week')
            ->keyBy('day_of_week')
            ->map(fn($s) => $s->shift_id);

        return Inertia::render('HR/Employees/Show', [
            'employee' => $employee,
            'jobTitles' => JobTitle::where('tenant_id', $tenantId)->active()->get(['id', 'name_ar', 'name_en']),
            'employeeTypes' => EmployeeType::where('tenant_id', $tenantId)->active()->get(['id', 'name_ar', 'name_en']),
            'departments' => Department::where('tenant_id', $tenantId)->where('is_active', true)->get(['id', 'name_ar', 'name_en']),
            'nationalities' => \App\Models\Nationality::active()->orderBy('name_ar')->get(['id', 'name_ar', 'name_en']),
            'centers' => \App\Models\Center::where('tenant_id', $tenantId)->get(['id', 'name']),
            'allAllowances' => Allowance::where('tenant_id', $tenantId)->active()->get(),
            'allDeductions' => Deduction::where('tenant_id', $tenantId)->active()->get(),
            'users' => User::where('tenant_id', $tenantId)
                ->where(function ($q) use ($employee) {
                    $q->whereDoesntHave('employee')
                        ->orWhere('id', $employee->user_id);
                })
                ->get(['id', 'name', 'email']),
            'shifts' => \App\Models\HR\Shift::where('tenant_id', $tenantId)->active()->get(['id', 'name_ar', 'name_en', 'start_time', 'end_time', 'color']),
            'weeklySchedule' => $weeklySchedule,
            // Roles for assignment
            'availableRoles' => \App\Models\Role::where('tenant_id', $tenantId)
                ->whereNotIn('name', ['super_admin']) // Exclude super_admin from assignment
                ->get(['id', 'name', 'label_ar', 'label_en']),
            'userRoles' => $employee->user ? $employee->user->roles->pluck('name') : [],
            // Financial Data
            'payrollItems' => $employee->payrollItems()
                ->with(['payrollRun', 'createdBy:id,name'])
                ->orderByDesc('created_at')
                ->take(12)
                ->get(),
            'otherPayments' => $employee->otherPayments()
                ->with(['createdBy:id,name', 'approvedBy:id,name'])
                ->orderByDesc('created_at')
                ->take(20)
                ->get(),
        ]);
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
            'name_en' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20|unique:hr_employees,phone,' . $employee->id . ',id,tenant_id,' . TenancyContext::tenantId(),
            'email' => 'required|email|max:255|unique:hr_employees,email,' . $employee->id . ',id,tenant_id,' . TenancyContext::tenantId(),
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

        // Security Check: Restrict "Management" department to Super Admin if changed
        if (isset($validated['department_id']) && $validated['department_id'] != $employee->getOriginal('department_id')) {
            $this->checkManagementRestriction($validated['department_id']);
        }

        return back()->with('success', __('messages.updated_successfully'));
    }

    /**
     * Update employee allowances.
     */
    public function updateAllowances(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'allowances' => 'array',
            'allowances.*.id' => 'required|exists:hr_allowances,id',
            'allowances.*.custom_amount' => 'nullable|numeric|min:0',
            'allowances.*.period_type' => 'required|in:one_time,fixed_period,indefinite',
            'allowances.*.start_date' => 'nullable|date',
            'allowances.*.end_date' => 'nullable|date|after_or_equal:allowances.*.start_date',
        ]);

        // Sync allowances
        $syncData = [];
        foreach ($validated['allowances'] ?? [] as $allowance) {
            $syncData[$allowance['id']] = [
                'custom_amount' => $allowance['custom_amount'] ?? null,
                'period_type' => $allowance['period_type'] ?? 'indefinite',
                'start_date' => $allowance['start_date'] ?? null,
                'end_date' => $allowance['end_date'] ?? null,
                'is_active' => true,
            ];
        }
        $employee->allowances()->sync($syncData);

        return back()->with('success', __('messages.updated_successfully'));
    }

    /**
     * Update employee deductions.
     */
    public function updateDeductions(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'deductions' => 'array',
            'deductions.*.id' => 'required|exists:hr_deductions,id',
            'deductions.*.custom_amount' => 'nullable|numeric|min:0',
            'deductions.*.period_type' => 'required|in:one_time,fixed_period,indefinite',
            'deductions.*.start_date' => 'nullable|date',
            'deductions.*.end_date' => 'nullable|date|after_or_equal:deductions.*.start_date',
        ]);

        // Sync deductions
        $syncData = [];
        foreach ($validated['deductions'] ?? [] as $deduction) {
            $syncData[$deduction['id']] = [
                'custom_amount' => $deduction['custom_amount'] ?? null,
                'period_type' => $deduction['period_type'] ?? 'indefinite',
                'start_date' => $deduction['start_date'] ?? null,
                'end_date' => $deduction['end_date'] ?? null,
                'is_active' => true,
            ];
        }
        $employee->deductions()->sync($syncData);

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
        if ($employee->photo_path && \Storage::disk('public')->exists($employee->photo_path)) {
            \Storage::disk('public')->delete($employee->photo_path);
        }

        // Store new photo
        $path = $request->file('photo')->store('employees/photos', 'public');
        
        $employee->update(['photo_path' => $path]);

        return back()->with('success', __('messages.updated_successfully'));
    }

    /**
     * Update employee bank info.
     */
    public function updateBankInfo(Request $request, Employee $employee)
    {
        $this->authorize('update', $employee);
        
        $validated = $request->validate([
            'bank_name' => 'nullable|string|max:100',
            'bank_iban' => 'nullable|string|max:34',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_notes' => 'nullable|string',
        ]);

        $employee->update($validated);

        return back()->with('success', __('messages.updated_successfully'));
    }

    /**
     * Update employee financial info (salary, GOSI rate).
     */
    public function updateFinancialInfo(Request $request, Employee $employee)
    {
        $this->authorize('update', $employee);
        
        $validated = $request->validate([
            'base_salary' => 'required|numeric|min:0',
            'gosi_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        $employee->update($validated);

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
     * Check if the selected department is "Management" and restrict it to Super Admin.
     */
    private function checkManagementRestriction($departmentId)
    {
        if (auth()->user()->hasRole('super_admin')) {
            return;
        }

        $department = Department::find($departmentId);
        if (!$department) return;

        // Check if department is "Management" / "الإدارة"
        $isManagement = str_contains($department->name_en, 'Management') || 
                        str_contains($department->name_ar, 'الإدارة');

        if ($isManagement) {
            abort(403, 'إضافة موظفين لقسم الإدارة متاح فقط للمدير العام (Super Admin).');
        }
    }

    /**
     * Update employee's user roles.
     */
    public function updateRoles(Request $request, Employee $employee)
    {
        $this->authorize('update', $employee);
        
        // Ensure employee has a linked user
        if (!$employee->user) {
            return back()->with('error', __('hr.employees.no_user_linked'));
        }

        $validated = $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'string|exists:roles,name',
        ]);

        // Get tenant ID for permission context
        $tenantId = $employee->tenant_id;
        
        // Set team context for role operations
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($tenantId);
        
        // Get allowed roles for this tenant (exclude super_admin)
        $allowedRoles = \App\Models\Role::where('tenant_id', $tenantId)
            ->whereNotIn('name', ['super_admin'])
            ->pluck('name')
            ->toArray();
        
        // Filter to only allowed roles and always include 'employee' role
        $rolesToSync = array_intersect($validated['roles'], $allowedRoles);
        if (!in_array('employee', $rolesToSync)) {
            $rolesToSync[] = 'employee'; // Employee role is always required
        }
        
        // Sync roles
        $employee->user->syncRoles($rolesToSync);
        
        \Log::info("Updated roles for employee {$employee->id}", ['roles' => $rolesToSync]);
        
        return back()->with('success', __('hr.employees.roles_updated'));
    }
}
