<?php

namespace App\Http\Controllers\App\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\Employee;
use Illuminate\Http\Request;

class EmployeeFinancialsController extends Controller
{
    /**
     * Update employee allowances.
     */
    public function updateAllowances(Request $request, Employee $employee)
    {
        $this->authorize('update', $employee);

        $validated = $request->validate([
            'allowances' => 'array',
            'allowances.*.id' => 'required|exists:hr_allowances,id',
            'allowances.*.custom_amount' => 'nullable|numeric|min:0',
            'allowances.*.period_type' => 'required|in:one_time,fixed_period,indefinite',
            'allowances.*.start_date' => 'nullable|date',
            'allowances.*.end_date' => 'nullable|date|after_or_equal:allowances.*.start_date',
        ]);

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
        $this->authorize('update', $employee);

        $validated = $request->validate([
            'deductions' => 'array',
            'deductions.*.id' => 'required|exists:hr_deductions,id',
            'deductions.*.custom_amount' => 'nullable|numeric|min:0',
            'deductions.*.period_type' => 'required|in:one_time,fixed_period,indefinite',
            'deductions.*.start_date' => 'nullable|date',
            'deductions.*.end_date' => 'nullable|date|after_or_equal:deductions.*.start_date',
        ]);

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
}
