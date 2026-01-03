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
        $tenantId = TenancyContext::tenantId();
        $status = $request->get('status', 'active');

        $employees = Employee::where('tenant_id', $tenantId)
            ->when($status === 'active', fn($q) => $q->active())
            ->when($status === 'inactive', fn($q) => $q->where('status', '!=', 'active'))
            ->with(['jobTitle:id,name_ar,name_en', 'department:id,name_ar,name_en', 'employeeType:id,name_ar,name_en'])
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
            'filters' => array_merge($request->only(['search']), ['status' => $status]),
            'counts' => [
                'active' => Employee::where('tenant_id', $tenantId)->active()->count(),
                'inactive' => Employee::where('tenant_id', $tenantId)->where('status', '!=', 'active')->count(),
            ],
            // For create modal
            'jobTitles' => JobTitle::where('tenant_id', $tenantId)->active()->get(['id', 'name_ar', 'name_en']),
            'employeeTypes' => EmployeeType::where('tenant_id', $tenantId)->active()->get(['id', 'name_ar', 'name_en']),
            'departments' => Department::where('tenant_id', $tenantId)->where('is_active', true)->get(['id', 'name_ar', 'name_en']),
            'centers' => \App\Models\Center::where('tenant_id', $tenantId)->get(['id', 'name']),
            'nationalities' => \App\Models\Nationality::active()->orderBy('name_ar')->get(['id', 'name_ar', 'name_en']),
            'users' => User::where('tenant_id', $tenantId)
                ->whereDoesntHave('employee')
                ->get(['id', 'name', 'email']),
            'shifts' => \App\Models\HR\Shift::where('tenant_id', $tenantId)->active()->get(['id', 'name_ar', 'name_en']),
        ]);
    }

    /**
     * Store new employee.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'gender' => 'required|in:male,female',
            'nationality_id' => 'required|exists:nationalities,id',
            'job_title_id' => 'required|exists:hr_job_titles,id',
            'center_id' => 'nullable|exists:centers,id',
            'shift_start' => 'nullable|date_format:H:i',
            'shift_end' => 'nullable|date_format:H:i',
            'default_shift_id' => 'nullable|exists:hr_shifts,id',
        ]);

        $employee = Employee::create([
            'tenant_id' => TenancyContext::tenantId(),
            'center_id' => array_key_exists('center_id', $validated) ? $validated['center_id'] : TenancyContext::centerId(),
            ...$validated,
        ]);

        return back()->with('success', __('messages.created_successfully'));
    }

    /**
     * Show employee profile (extensible with tabs).
     */
    public function show(Employee $employee)
    {
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
        ]);
    }

    /**
     * Update employee.
     */
    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            // Basic Info
            'name_ar' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
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
            'shift_start' => 'nullable|date_format:H:i',
            'shift_end' => 'nullable|date_format:H:i',
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

        $employee->update($validated);

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
        ]);

        // Sync allowances
        $syncData = [];
        foreach ($validated['allowances'] ?? [] as $allowance) {
            $syncData[$allowance['id']] = ['custom_amount' => $allowance['custom_amount'] ?? null];
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
        ]);

        // Sync deductions
        $syncData = [];
        foreach ($validated['deductions'] ?? [] as $deduction) {
            $syncData[$deduction['id']] = ['custom_amount' => $deduction['custom_amount'] ?? null];
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
     * Delete employee.
     */
    public function destroy(Employee $employee)
    {
        $employee->delete();
        return redirect()->route('app.hr.employees.index')
            ->with('success', __('messages.deleted_successfully'));
    }
}
