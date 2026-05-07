<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\InspectionTemplate;
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
        $templates = InspectionTemplate::where('is_active', true)
            ->with('items')
            ->get();

        return response()->json($templates);
    }

    /**
     * Store a new inspection.
     */
    public function store(Request $request, WorkOrder $workOrder)
    {
        $validated = $request->validate([
            'template_id' => 'required|exists:inspection_templates,id',
            'results' => 'required|array',
            'results.*.item_id' => 'required|exists:inspection_items,id',
            'results.*.status' => 'required|in:good,warning,danger,na',
            'results.*.notes' => 'nullable|string|max:500',
        ]);

        $inspection = WorkOrderInspection::create([
            'tenant_id' => $workOrder->tenant_id,
            'center_id' => $workOrder->center_id,
            'work_order_id' => $workOrder->id,
            'template_id' => $validated['template_id'],
            'performed_by' => auth()->id(),
            'performed_at' => now(),
            'results' => $validated['results'],
        ]);

        $workOrder->logActivity('inspection_completed', __('work_orders.activities.actions.inspection_completed', [
            'template' => $inspection->template->name[app()->getLocale()] ?? $inspection->template->name['ar']
        ]));

        return back()->with('success', __('messages.inspection_saved'));
    }

    /**
     * Display the specified inspection.
     */
    public function show(WorkOrder $workOrder, WorkOrderInspection $inspection)
    {
        $inspection->load(['template.items', 'performedBy']);
        
        return response()->json($inspection);
    }
}
