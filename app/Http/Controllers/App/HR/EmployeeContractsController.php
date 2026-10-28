<?php

declare(strict_types=1);

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

        // The employee has to be one the current user may manage.
        // The employee list is centre-scoped for everybody except the
        // tenant super admin (EmployeeController::index), so a
        // contract could otherwise be created for an employee of
        // another branch — and carry that branch's salary data.
        $this->assertEmployeeInScope($employee);

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

    /**
     * Is the employee inside the scope of the current user?
     *
     * The tenant super admin manages every branch of the tenant — the
     * employee list gives them the whole tenant and lets them filter
     * by centre. Everybody else only sees their own centre.
     */
    private function assertEmployeeInScope(Employee $employee): void
    {
        $user = auth()->user();

        if ($user->hasRole('super_admin')) {
            return;
        }

        if ($employee->center_id !== null && $user->current_center_id !== null
            && (int) $employee->center_id !== (int) $user->current_center_id) {
            abort(403, __('hr.employees.wrong_center'));
        }
    }

    public function update(Request $request, EmployeeContract $contract)
    {
        $this->authorize('update', $contract);

        $validated = $request->validate([
            'contract_number' => 'required|string|unique:hr_employee_contracts,contract_number,'.$contract->id,
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
