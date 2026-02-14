<?php

namespace App\Http\Controllers\App\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\Employee;
use App\Models\HR\EmployeeContract;
use Illuminate\Http\Request;

class EmployeeContractsController extends Controller
{
    public function store(Request $request, Employee $employee)
    {
        $request->validate([
            'contract_number' => 'required|string|unique:hr_employee_contracts,contract_number',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:draft,active,expired,cancelled',
            'content' => 'nullable|string',
            'salary_details' => 'nullable|array',
        ]);

        $employee->contracts()->create([
            'tenant_id' => $employee->tenant_id,
            'contract_number' => $request->contract_number,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => $request->status,
            'content' => $request->content,
            'salary_details' => $request->salary_details,
        ]);

        return back()->with('success', __('common.saved_success'));
    }

    public function update(Request $request, EmployeeContract $contract)
    {
        $request->validate([
            'contract_number' => 'required|string|unique:hr_employee_contracts,contract_number,' . $contract->id,
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:draft,active,expired,cancelled',
            'content' => 'nullable|string',
            'salary_details' => 'nullable|array',
        ]);

        $contract->update($request->all());

        return back()->with('success', __('common.saved_success'));
    }

    public function destroy(EmployeeContract $contract)
    {
        $contract->delete();
        return back()->with('success', __('common.deleted_success'));
    }
}
