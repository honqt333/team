<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\HR;

use App\Http\Controllers\Controller;
use App\Models\Center;
use App\Models\Department;
use App\Models\HR\Allowance;
use App\Models\HR\AttendanceSettings;
use App\Models\HR\BiometricDevice;
use App\Models\HR\Deduction;
use App\Models\HR\EmployeeType;
use App\Models\HR\HRRegulation;
use App\Models\HR\JobTitle;
use App\Models\HR\Shift;
use App\Support\TenancyContext;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingsController extends Controller
{
    /**
     * HR Settings page with tabs.
     */
    public function index()
    {
        $tenantId = TenancyContext::tenantId();
        $centerId = TenancyContext::centerId();

        return Inertia::render('HR/Settings', [
            'employeeTypes' => EmployeeType::where('tenant_id', $tenantId)
                ->with('updatedBy:id,name')
                ->orderBy('name_ar')
                ->get(),
            'jobTitles' => JobTitle::where('tenant_id', $tenantId)
                ->with(['department:id,name_ar,name_en', 'updatedBy:id,name'])
                ->orderBy('name_ar')
                ->get(),
            'allowances' => Allowance::where('tenant_id', $tenantId)
                ->with('updatedBy:id,name')
                ->orderBy('name_ar')
                ->get(),
            'deductions' => Deduction::where('tenant_id', $tenantId)
                ->with('updatedBy:id,name')
                ->orderBy('name_ar')
                ->get(),
            'departments' => Department::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->orderBy('name_ar')
                ->get(['id', 'name_ar', 'name_en']),
            'biometricDevices' => BiometricDevice::where('tenant_id', $tenantId)
                ->with('center:id,name')
                ->orderBy('name')
                ->get(),
            'centers' => Center::where('tenant_id', $tenantId)
                ->orderBy('name')
                ->get(['id', 'name']),
            'shifts' => Shift::where('tenant_id', $tenantId)
                ->orderBy('name_ar')
                ->get(),
            'regulations' => HRRegulation::where('tenant_id', $tenantId)
                ->with(['updatedBy:id,name'])
                ->orderBy('category')
                ->orderBy('title_ar')
                ->get(),
            'attendanceSettings' => AttendanceSettings::getForCenter($centerId),
        ]);
    }

    // ========================
    // Employee Types CRUD
    // ========================

    public function storeEmployeeType(Request $request)
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);

        EmployeeType::create([
            'tenant_id' => TenancyContext::tenantId(),
            'updated_by' => auth()->id(),
            ...$validated,
        ]);

        return back()->with('success', __('messages.created_successfully'));
    }

    public function updateEmployeeType(Request $request, EmployeeType $employeeType)
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'sometimes|required|string|max:255',
            'is_active' => 'boolean',
        ]);

        $employeeType->update([
            'updated_by' => auth()->id(),
            ...$validated,
        ]);

        return back()->with('success', __('messages.updated_successfully'));
    }

    public function destroyEmployeeType(EmployeeType $employeeType)
    {
        $employeeType->delete();

        return back()->with('success', __('messages.deleted_successfully'));
    }

    // ========================
    // Job Titles CRUD
    // ========================

    public function storeJobTitle(Request $request)
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'department_id' => 'nullable|exists:departments,id',
            'is_active' => 'boolean',
        ]);

        JobTitle::create([
            'tenant_id' => TenancyContext::tenantId(),
            'updated_by' => auth()->id(),
            ...$validated,
        ]);

        return back()->with('success', __('messages.created_successfully'));
    }

    public function updateJobTitle(Request $request, JobTitle $jobTitle)
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'sometimes|required|string|max:255',
            'department_id' => 'nullable|exists:departments,id',
            'is_active' => 'boolean',
        ]);

        $jobTitle->update([
            'updated_by' => auth()->id(),
            ...$validated,
        ]);

        return back()->with('success', __('messages.updated_successfully'));
    }

    public function destroyJobTitle(JobTitle $jobTitle)
    {
        $jobTitle->delete();

        return back()->with('success', __('messages.deleted_successfully'));
    }

    // ========================
    // Attendance Settings
    // ========================

    public function updateAttendanceSettings(Request $request)
    {
        $centerId = TenancyContext::centerId();

        $validated = $request->validate([
            'grace_period_minutes' => 'required|integer|min:0|max:60',
            'late_deduction_per_minute' => 'required|numeric|min:0',
            'absence_deduction_value' => 'required|numeric|min:0',
            'absence_deduction_type' => 'required|in:fixed,percentage',
            'overtime_rate_per_hour' => 'required|numeric|min:0',
            'overtime_enabled' => 'boolean',
            'working_days' => 'array',
            'working_days.*' => 'integer|min:0|max:6',
        ]);

        $settings = AttendanceSettings::getForCenter($centerId);
        $settings->update($validated);

        return back()->with('success', __('messages.saved_successfully'));
    }
}
