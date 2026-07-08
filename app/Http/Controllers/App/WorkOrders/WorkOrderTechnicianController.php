<?php

namespace App\Http\Controllers\App\WorkOrders;

use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WorkOrderTechnicianController
{
    use AuthorizesRequests;

    /**
     * Update item status.
     */
    public function updateItemStatus(Request $request, WorkOrder $work_order, WorkOrderItem $item): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $work_order);

        $validated = $request->validate([
            'status' => 'required|in:' . implode(',', WorkOrderItem::STATUSES),
        ]);

        $newStatus = $validated['status'];

        if (!$item->canChangeStatusTo($newStatus)) {
            $message = __('messages.cannot_change_item_status');
            return $request->expectsJson()
                ? response()->json(['error' => $message], 422)
                : redirect()->back()->with('error', $message);
        }

        $item->update(['status' => $newStatus]);

        $work_order->logActivity(
            'item_status_updated',
            __('work_orders.activities.actions.item_status_updated', [
                'title' => $item->title,
                'status' => __('work_orders.item_status.' . $newStatus)
            ])
        );

        $message = __('messages.item_status_updated');
        return $request->expectsJson()
            ? response()->json(['success' => $message, 'item' => $item->fresh()])
            : redirect()->back()->with('success', $message);
    }

    /**
     * Assign technician to item.
     */
    public function assignTechnician(Request $request, WorkOrder $work_order, WorkOrderItem $item): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $work_order);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'notes' => 'nullable|string|max:500',
        ]);

        // Security Check: Technician must belong to the Work Order's center
        $techExistsInCenter = User::where('id', $validated['user_id'])
            ->whereHas('centers', function ($q) use ($work_order) {
                $q->where('centers.id', $work_order->center_id);
            })
            ->exists();

        if (!$techExistsInCenter) {
            $message = __('messages.technician_not_belong_to_center');
            return $request->expectsJson()
                ? response()->json(['error' => $message], 403)
                : redirect()->back()->with('error', $message);
        }

        $item->technicians()->syncWithoutDetaching([
            $validated['user_id'] => [
                'assigned_at' => now(),
                'notes'       => $validated['notes'] ?? null,
                'share'       => 0,
            ]
        ]);

        $this->rebalanceTechnicianShares($item);

        $work_order->logActivity('technician_assigned', __('work_orders.activities.actions.technician_assigned', [
            'name'    => User::find($validated['user_id'])->name,
            'service' => $item->title
        ]));

        $message = __('messages.technician_assigned');
        return $request->expectsJson()
            ? response()->json(['success' => $message, 'technicians' => $item->technicians()->withPivot(['assigned_at', 'completed_at', 'notes', 'share'])->get()])
            : redirect()->back()->with('success', $message);
    }

    /**
     * Remove technician from item.
     */
    public function removeTechnician(WorkOrder $work_order, WorkOrderItem $item, User $user): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $work_order);

        $name = $user->name;
        $item->technicians()->detach($user->id);

        $this->rebalanceTechnicianShares($item);

        $work_order->logActivity('technician_removed', __('work_orders.activities.actions.technician_removed', [
            'name'    => $name,
            'service' => $item->title
        ]));

        $message = __('messages.technician_removed');
        return request()->expectsJson()
            ? response()->json(['success' => $message])
            : redirect()->back()->with('success', $message);
    }

    /**
     * Distribute shares equally among all technicians assigned to a work order item.
     */
    private function rebalanceTechnicianShares(WorkOrderItem $item): void
    {
        $techIds = $item->technicians()->pluck('users.id')->toArray();
        $count   = count($techIds);

        if ($count === 0) return;

        if ($count === 1) {
            $item->technicians()->updateExistingPivot($techIds[0], ['share' => 100.00]);
            return;
        }

        $base      = round(100 / $count, 2);
        $allocated = $base * ($count - 1);
        $last      = round(100 - $allocated, 2);

        foreach ($techIds as $index => $techId) {
            $share = ($index === $count - 1) ? $last : $base;
            $item->technicians()->updateExistingPivot($techId, ['share' => $share]);
        }
    }
}
