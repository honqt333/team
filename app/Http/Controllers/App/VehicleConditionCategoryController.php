<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\VehicleConditionCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VehicleConditionCategoryController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $validated['source'] = 'center';
        $validated['updated_by'] = auth()->id();

        VehicleConditionCategory::create($validated);

        return redirect()->back()->with('success', __('common.created_success'));
    }

    public function update(Request $request, VehicleConditionCategory $conditionCategory): RedirectResponse
    {
        if ($conditionCategory->source === 'system') {
            abort(403, __('common.cannot_modify_system_data'));
        }

        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $validated['updated_by'] = auth()->id();
        $conditionCategory->update($validated);

        return redirect()->back()->with('success', __('common.updated_success'));
    }

    public function destroy(VehicleConditionCategory $conditionCategory): RedirectResponse
    {
        if ($conditionCategory->source === 'system') {
            abort(403, __('common.cannot_delete_system_data'));
        }

        if ($conditionCategory->items()->exists()) {
            return redirect()->back()->with('error', 'لا يمكن حذف القسم لوجود فحوصات مرتبطة به.');
        }

        $conditionCategory->delete();

        return redirect()->back()->with('success', __('common.deleted_success'));
    }

    public function toggleActive(VehicleConditionCategory $conditionCategory): RedirectResponse
    {
        if ($conditionCategory->source === 'system') {
            abort(403, __('common.cannot_modify_system_data'));
        }

        $conditionCategory->update(['is_active' => !$conditionCategory->is_active]);

        return redirect()->back();
    }
}
