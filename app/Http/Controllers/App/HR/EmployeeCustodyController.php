<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\Employee;
use App\Models\HR\EmployeeAssetCustody;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeCustodyController extends Controller
{
    use AuthorizesRequests;

    /**
     * Issue a new asset/tool custody item to an employee.
     */
    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('update', $employee);

        $validated = $request->validate([
            'asset_name' => 'required|string|max:255',
            'category' => ['required', Rule::in(EmployeeAssetCustody::CATEGORIES)],
            'serial_number' => 'nullable|string|max:100',
            'estimated_value' => 'nullable|numeric|min:0|max:9999999.99',
            'condition_on_issue' => 'required|in:new,good,fair',
            'issued_date' => 'required|date',
            'issue_signature' => 'nullable|string|max:500000',
            'notes' => 'nullable|string|max:1000',
        ]);

        $employee->assetCustodies()->create([
            'tenant_id' => $employee->tenant_id,
            'center_id' => $employee->center_id,
            'asset_name' => $validated['asset_name'],
            'category' => $validated['category'],
            'serial_number' => $validated['serial_number'] ?? null,
            'estimated_value' => $validated['estimated_value'] ?? 0,
            'condition_on_issue' => $validated['condition_on_issue'],
            'issued_date' => $validated['issued_date'],
            'status' => EmployeeAssetCustody::STATUS_ISSUED,
            'issue_signature' => $validated['issue_signature'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'created_by' => $request->user()?->id,
        ]);

        return back()->with('success', __('common.saved_success'));
    }

    /**
     * Record return (or damage/loss) of an issued custody item.
     */
    public function returnAsset(Request $request, Employee $employee, EmployeeAssetCustody $custody): RedirectResponse
    {
        $this->authorize('update', $employee);
        abort_if((int) $custody->employee_id !== (int) $employee->id, 404);

        $validated = $request->validate([
            'status' => 'required|in:returned,damaged,lost',
            'returned_date' => 'required|date',
            'condition_on_return' => 'nullable|in:good,fair,damaged,lost',
            'return_signature' => 'nullable|string|max:500000',
            'notes' => 'nullable|string|max:1000',
        ]);

        $custody->update([
            'status' => $validated['status'],
            'returned_date' => $validated['returned_date'],
            'condition_on_return' => $validated['condition_on_return'] ?? $validated['status'],
            'return_signature' => $validated['return_signature'] ?? null,
            'notes' => $validated['notes'] ?? $custody->notes,
            'returned_by' => $request->user()?->id,
        ]);

        return back()->with('success', __('common.saved_success'));
    }

    /**
     * Soft-delete a custody record.
     */
    public function destroy(Employee $employee, EmployeeAssetCustody $custody): RedirectResponse
    {
        $this->authorize('update', $employee);
        abort_if((int) $custody->employee_id !== (int) $employee->id, 404);

        $custody->delete();

        return back()->with('success', __('common.deleted_success'));
    }
}
