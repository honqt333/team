<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\VehicleConditionItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VehicleConditionItemController extends Controller
{
    /**
     * Display a listing of condition items.
     * Note: This likely won't be accessed directly if we use SystemSettingsController for the main view,
     * but we keep it IF we want to return a specific Inertia view or JSON.
     * Based on SystemSettingsController, it loads everything there.
     * However, for searching/pagination specific to this tab, we might route here.
     */
    public function index(Request $request): Response
    {
        $query = VehicleConditionItem::with('updatedBy')->orderedBySource()->ordered();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name_ar', 'like', "%{$search}%")
                  ->orWhere('name_en', 'like', "%{$search}%");
            });
        }

        $items = $query->paginate(20)->withQueryString();

        return Inertia::render('Settings/System/Index', [
            'condition_items' => $items,
            'activeSection' => 'condition-items',
            'filters' => $request->only('search'),
        ]);
    }

    /**
     * Store a newly created item.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'category_id' => 'required|exists:vehicle_condition_categories,id',
            'is_active' => 'boolean',
        ]);

        $validated['source'] = 'center';
        $validated['updated_by'] = auth()->id();

        VehicleConditionItem::create($validated);

        return redirect()->back()->with('success', __('common.created_success'));
    }

    /**
     * Update the specified item.
     */
    public function update(Request $request, VehicleConditionItem $conditionItem): RedirectResponse
    {
        if ($conditionItem->source === 'system') {
            abort(403, __('common.cannot_modify_system_data'));
        }

        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'category_id' => 'required|exists:vehicle_condition_categories,id',
            'is_active' => 'boolean',
        ]);

        $validated['updated_by'] = auth()->id();
        $conditionItem->update($validated);

        return redirect()->back()->with('success', __('common.updated_success'));
    }

    /**
     * Remove the specified item.
     */
    public function destroy(VehicleConditionItem $conditionItem): RedirectResponse
    {
        if ($conditionItem->source === 'system') {
            abort(403, __('common.cannot_delete_system_data'));
        }

        $conditionItem->delete();

        return redirect()->back()->with('success', __('common.deleted_success'));
    }

    /**
     * Toggle active status.
     */
    public function toggleActive(VehicleConditionItem $conditionItem): RedirectResponse
    {
        if ($conditionItem->source === 'system') {
            abort(403, __('common.cannot_modify_system_data'));
        }

        $conditionItem->update(['is_active' => !$conditionItem->is_active]);

        return redirect()->back();
    }
}
