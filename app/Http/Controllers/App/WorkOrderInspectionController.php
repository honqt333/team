<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\VehicleConditionItem;
use App\Models\WorkOrder;
use App\Models\WorkOrderInspection;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WorkOrderInspectionController extends Controller
{
    /**
     * Get available inspection templates.
     */
    public function getTemplates(WorkOrder $workOrder)
    {
        $items = VehicleConditionItem::where('is_active', true)
            ->orderedBySource()
            ->ordered()
            ->get();

        return response()->json($items);
    }

    /**
     * Store a new inspection.
     */
    public function store(Request $request, WorkOrder $workOrder)
    {
        $validated = $request->validate([
            'results' => 'required|array',
            'results.*.item_id' => 'required|exists:vehicle_condition_items,id',
            'results.*.status' => 'required|in:good,warning,danger,na',
            'results.*.notes' => 'nullable|string|max:500',
        ]);

        $inspection = WorkOrderInspection::create([
            'tenant_id' => $workOrder->tenant_id,
            'center_id' => $workOrder->center_id,
            'work_order_id' => $workOrder->id,
            'performed_by' => auth()->id(),
            'performed_at' => now(),
            'results' => $validated['results'],
        ]);

        $workOrder->logActivity('inspection_completed', __('work_orders.activities.actions.inspection_completed', [
            'template' => __('services_management.inspections')
        ]));

        return back()->with('success', __('messages.inspection_saved'));
    }

    /**
     * Display the specified inspection.
     */
    public function show(WorkOrder $workOrder, WorkOrderInspection $inspection)
    {
        $inspection->load(['performedBy']);
        // We also need the current condition items so we can show names
        $items = VehicleConditionItem::all();
        $inspection->items = $items;
        
        return response()->json($inspection);
    }
}
