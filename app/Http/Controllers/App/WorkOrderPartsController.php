<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Part;
use App\Models\Warehouse;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Models\WorkOrderItemPart;
use App\Services\Inventory\InventoryService;
use App\Services\Inventory\WorkOrderPartsService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WorkOrderPartsController extends Controller
{
    public function __construct(
        protected WorkOrderPartsService $partsService,
        protected InventoryService $inventoryService
    ) {}

    /**
     * Add a part to a work order.
     */
    public function store(Request $request, WorkOrder $workOrder)
    {
        $this->authorize('update', $workOrder);

        $validated = $request->validate([
            'work_order_item_id' => 'nullable|exists:work_order_items,id',
            'part_id' => 'nullable|exists:parts,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'name' => 'required|string|max:255',
            'part_number' => 'nullable|string|max:100',
            'source' => 'required|in:warehouse,external,customer',
            'qty' => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        // If warehouse source, require part_id and warehouse_id
        if ($validated['source'] === 'warehouse') {
            if (empty($validated['part_id']) || empty($validated['warehouse_id'])) {
                throw ValidationException::withMessages([
                    'part_id' => ['Part and warehouse are required for warehouse source.'],
                ]);
            }
        }

        // Check stock availability for warehouse source
        $allowNegative = auth()->user()->can('inventory.override_negative_stock');
        
        if ($validated['source'] === 'warehouse' && !$allowNegative) {
            $stock = $this->partsService->checkStock(
                $validated['warehouse_id'],
                $validated['part_id'],
                $validated['qty']
            );
            
            if (!$stock['sufficient']) {
                return back()->with('error', __('inventory.stock.insufficient', [
                    'available' => $stock['on_hand'],
                    'requested' => $validated['qty'],
                ]));
            }
        }

        try {
            $validated['work_order_id'] = $workOrder->id;
            $validated['tenant_id'] = $workOrder->tenant_id;
            $validated['center_id'] = $workOrder->center_id;

            $partLine = $this->partsService->addPart($validated, $allowNegative);

            return back()->with('success', __('inventory.moves.part_issued'));
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->with('error', $e->getMessage());
        }
    }

    /**
     * Update part quantity/price.
     */
    public function update(Request $request, WorkOrderItemPart $workOrderPart)
    {
        $workOrder = $workOrderPart->workOrderItem?->workOrder;
        if ($workOrder) {
            $this->authorize('update', $workOrder);
        }

        $validated = $request->validate([
            'qty' => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $allowNegative = auth()->user()->can('inventory.override_negative_stock');

        try {
            $this->partsService->updatePart($workOrderPart, $validated, $allowNegative);
            return back()->with('success', __('common.updated'));
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->with('error', $e->getMessage());
        }
    }

    /**
     * Remove/cancel a part line.
     */
    public function destroy(WorkOrderItemPart $workOrderPart)
    {
        $workOrder = $workOrderPart->workOrderItem?->workOrder;
        if ($workOrder) {
            $this->authorize('update', $workOrder);
        }

        try {
            $this->partsService->removePart($workOrderPart, auth()->id());
            return back()->with('success', __('common.deleted'));
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->with('error', $e->getMessage());
        }
    }

    /**
     * Manually reverse a part issue.
     */
    public function reverse(WorkOrderItemPart $workOrderPart)
    {
        $this->authorize('reverse', $workOrderPart);

        if (!$workOrderPart->canBeReversed()) {
            return back()->with('error', __('inventory.moves.cannot_reverse'));
        }

        try {
            $this->partsService->reversePartIssue($workOrderPart, auth()->id());
            return back()->with('success', __('inventory.moves.reversed'));
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->with('error', $e->getMessage());
        }
    }

    /**
     * API: Check stock availability.
     */
    public function checkStock(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'part_id' => 'required|exists:parts,id',
            'qty' => 'required|numeric|min:0',
        ]);

        $stock = $this->partsService->checkStock(
            $validated['warehouse_id'],
            $validated['part_id'],
            $validated['qty']
        );

        return response()->json($stock);
    }
}
