<?php

namespace App\Http\Controllers\App\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\Allowance;
use App\Models\HR\Deduction;
use App\Support\TenancyContext;
use Illuminate\Http\Request;

class HRPayrollSettingsController extends Controller
{
    /**
     * Store new allowance.
     */
    public function storeAllowance(Request $request)
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'is_flexible' => 'boolean',
            'type' => 'required_if:is_flexible,false|in:fixed,percentage',
            'amount' => 'required_if:is_flexible,false|numeric|min:0.01',
            'calculation_base' => 'nullable|in:base_salary,monthly_contribution',
            'is_active' => 'boolean',
        ]);

        if ($request->boolean('is_flexible')) {
            $validated['type'] = 'fixed';
            $validated['amount'] = 0;
        }

        Allowance::create([
            'tenant_id' => TenancyContext::tenantId(),
            'updated_by' => auth()->id(),
            ...$validated,
        ]);

        return back()->with('success', __('messages.created_successfully'));
    }

    /**
     * Update allowance.
     */
    public function updateAllowance(Request $request, Allowance $allowance)
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'sometimes|required|string|max:255',
            'is_flexible' => 'boolean',
            'type' => 'required_if:is_flexible,false|in:fixed,percentage',
            'amount' => 'required_if:is_flexible,false|numeric|min:0.01',
            'calculation_base' => 'nullable|in:base_salary,monthly_contribution',
            'is_active' => 'boolean',
        ]);

        if ($request->boolean('is_flexible')) {
            $validated['type'] = 'fixed';
            $validated['amount'] = 0;
        }

        $allowance->update([
            'updated_by' => auth()->id(),
            ...$validated,
        ]);

        return back()->with('success', __('messages.updated_successfully'));
    }

    /**
     * Delete allowance.
     */
    public function destroyAllowance(Allowance $allowance)
    {
        $allowance->delete();
        return back()->with('success', __('messages.deleted_successfully'));
    }

    /**
     * Store new deduction.
     */
    public function storeDeduction(Request $request)
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'is_flexible' => 'boolean',
            'type' => 'required_if:is_flexible,false|in:fixed,percentage',
            'amount' => 'required_if:is_flexible,false|numeric|min:0.01',
            'calculation_base' => 'nullable|in:base_salary,monthly_contribution',
            'is_active' => 'boolean',
        ]);

        if ($request->boolean('is_flexible')) {
            $validated['type'] = 'fixed';
            $validated['amount'] = 0;
        }

        Deduction::create([
            'tenant_id' => TenancyContext::tenantId(),
            'updated_by' => auth()->id(),
            ...$validated,
        ]);

        return back()->with('success', __('messages.created_successfully'));
    }

    /**
     * Update deduction.
     */
    public function updateDeduction(Request $request, Deduction $deduction)
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'sometimes|required|string|max:255',
            'is_flexible' => 'boolean',
            'type' => 'required_if:is_flexible,false|in:fixed,percentage',
            'amount' => 'required_if:is_flexible,false|numeric|min:0.01',
            'calculation_base' => 'nullable|in:base_salary,monthly_contribution',
            'is_active' => 'boolean',
        ]);

        if ($request->boolean('is_flexible')) {
            $validated['type'] = 'fixed';
            $validated['amount'] = 0;
        }

        $deduction->update([
            'updated_by' => auth()->id(),
            ...$validated,
        ]);

        return back()->with('success', __('messages.updated_successfully'));
    }

    /**
     * Delete deduction.
     */
    public function destroyDeduction(Deduction $deduction)
    {
        $deduction->delete();
        return back()->with('success', __('messages.deleted_successfully'));
    }
}
