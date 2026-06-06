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
        $this->authorize('create', EmployeeContract::class);

        $validated = $request->validate([
            'contract_number' => 'required|string|unique:hr_employee_contracts,contract_number',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:draft,active,expired,cancelled',
            'content' => 'nullable|string',
            'salary_details' => 'nullable|array',
        ]);

        $employee->contracts()->create([
            'tenant_id' => $employee->tenant_id,
            'contract_number' => $validated['contract_number'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? null,
            'status' => $validated['status'],
            'salary_details' => $validated['salary_details'] ?? null,
            'content' => $validated['content'] ?? null,
        ]);

        return back()->with('success', __('common.saved_success'));
    }

    public function update(Request $request, EmployeeContract $contract)
    {
        $this->authorize('update', $contract);

        $validated = $request->validate([
            'contract_number' => 'required|string|unique:hr_employee_contracts,contract_number,' . $contract->id,
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:draft,active,expired,cancelled',
            'content' => 'nullable|string',
            'salary_details' => 'nullable|array',
        ]);

        // SECURITY: Use $validated (only the fields the request actually validated)
        // instead of $request->all() to prevent mass-assignment of unrelated columns
        // like tenant_id, employee_id, signed_at that should be controlled server-side.
        $contract->update($validated);

        return back()->with('success', __('common.saved_success'));
    }

    public function destroy(EmployeeContract $contract)
    {
        $this->authorize('delete', $contract);

        $contract->delete();
        return back()->with('success', __('common.deleted_success'));
    }
}
