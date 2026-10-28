<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\WorkOrders;

use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Models\WorkOrderItemPart;
use App\Services\Inventory\WorkOrderPartsService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WorkOrderPartsController
{
    use AuthorizesRequests;

    public function __construct(protected WorkOrderPartsService $partsService) {}

    /**
     * Add part to item.
     */
    public function addPart(Request $request, WorkOrder $work_order, WorkOrderItem $item): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $work_order);

        // The line has to belong to the work order in the URL.
        $item = $work_order->scopedItem($item->id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'part_id' => 'nullable|exists:parts,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'part_number' => 'nullable|string|max:100',
            'source' => 'required|in:'.implode(',', WorkOrderItemPart::SOURCES),
            'unit_id' => 'nullable|exists:inventory_units,id',
            'qty' => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'include_in_package' => 'boolean',
            'hide_on_print' => 'boolean',
            'notes' => 'nullable|string|max:500',
        ]);

        $allowNegative = auth()->user()->can('inventory.override_negative_stock');
        $part = $this->partsService->addPart([
            'work_order_id' => $work_order->id,
            'work_order_item_id' => $item->id,
            'tenant_id' => $work_order->tenant_id,
            'center_id' => $work_order->center_id,
            ...$validated,
        ], $allowNegative);

        $work_order->logActivity('part_added', __('work_orders.activities.actions.part_added', ['name' => $validated['name']]));

        $message = __('messages.part_added');

        return $request->expectsJson()
            ? response()->json(['success' => $message, 'part' => $part])
            : redirect()->back()->with('success', $message);
    }

    /**
     * Update part.
     *
     * The change goes through WorkOrderPartsService, which applies the
     * stock delta and reverses the issue move. It used to write
     * straight onto the record, so the stock balance and the quantity
     * shown on the work order could drift apart.
     */
    public function updatePart(Request $request, WorkOrder $work_order, WorkOrderItem $item, WorkOrderItemPart $part): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $work_order);

        // The line and the part both have to belong to the work order
        // in the URL.
        $item = $work_order->scopedItem($item->id);
        $part = $item->scopedPart($part->id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'part_id' => 'nullable|exists:parts,id',
            'part_number' => 'nullable|string|max:100',
            'source' => 'required|in:'.implode(',', WorkOrderItemPart::SOURCES),
            'unit_id' => 'nullable|exists:inventory_units,id',
            'qty' => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'include_in_package' => 'boolean',
            'hide_on_print' => 'boolean',
            'notes' => 'nullable|string|max:500',
        ]);

        $allowNegative = auth()->user()->can('inventory.override_negative_stock');

        try {
            $this->partsService->updatePart($part, $validated, $allowNegative);
        } catch (ValidationException $e) {
            return $request->expectsJson()
                ? response()->json(['error' => $e->getMessage()], 422)
                : redirect()->back()->withErrors($e->errors())->with('error', $e->getMessage());
        }

        $work_order->logActivity('part_updated', __('work_orders.activities.actions.part_updated', ['name' => $validated['name']]));

        $message = __('messages.part_updated');

        return $request->expectsJson()
            ? response()->json(['success' => $message, 'part' => $part->fresh()])
            : redirect()->back()->with('success', $message);
    }

    /**
     * Delete part.
     *
     * The removal goes through WorkOrderPartsService, which reverses
     * the issue move before cancelling the line. It used to delete
     * the record directly, which left the issued stock out of the
     * warehouse balance.
     */
    public function deletePart(WorkOrder $work_order, WorkOrderItem $item, WorkOrderItemPart $part): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $work_order);

        // The line and the part both have to belong to the work order
        // in the URL.
        $item = $work_order->scopedItem($item->id);
        $part = $item->scopedPart($part->id);

        $name = $part->name;

        try {
            $this->partsService->removePart($part, auth()->id());
        } catch (ValidationException $e) {
            return request()->expectsJson()
                ? response()->json(['error' => $e->getMessage()], 422)
                : redirect()->back()->withErrors($e->errors())->with('error', $e->getMessage());
        }

        $work_order->logActivity('part_deleted', __('work_orders.activities.actions.part_deleted', ['name' => $name]));

        $message = __('messages.part_deleted');

        return request()->expectsJson()
            ? response()->json(['success' => $message])
            : redirect()->back()->with('success', $message);
    }
}
