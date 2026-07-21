<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\WorkOrders;

use App\Models\Service;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Models\WorkOrderItemPart;
use App\Services\Inventory\WorkOrderPartsService;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WorkOrderItemController
{
    use AuthorizesRequests;

    /**
     * Add a service item to a work order.
     */
    public function addItem(Request $request, WorkOrder $work_order): RedirectResponse
    {
        $this->authorize('update', $work_order);

        // Pre-normalize virtual 'packages' department_id to null
        if ($request->input('department_id') === 'packages') {
            $request->merge(['department_id' => null]);
        }

        $validated = $request->validate([
            'service_id' => 'nullable|exists:services,id',
            'department_id' => 'nullable|exists:departments,id',
            'title' => 'required_without:service_id|nullable|string|max:255',
            'qty' => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0',
            'discount_type' => 'nullable|string|in:none,fixed,percentage',
            'discount_value' => 'nullable|numeric|min:0',
            'duration_value' => 'nullable|integer|min:0',
            'duration_unit' => 'nullable|string|max:50',
            'warranty_value_snapshot' => 'nullable|integer|min:0',
            'warranty_unit_snapshot' => 'nullable|string|max:50',
            'is_warranty' => 'nullable|boolean',
            'started_at' => 'nullable|date',
            'completed_at' => 'nullable|date',
            'due_date' => 'nullable|date',
            'pending_parts' => ['nullable', 'array'],
            'pending_parts.*.source' => ['required_with:pending_parts', 'in:warehouse,external,customer'],
            'pending_parts.*.name' => ['required_with:pending_parts', 'string', 'max:255'],
            'pending_parts.*.part_id' => ['nullable', 'exists:parts,id'],
            'pending_parts.*.warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'pending_parts.*.qty' => ['required_with:pending_parts', 'numeric', 'min:0.01'],
            'pending_parts.*.unit_price' => ['required_with:pending_parts', 'numeric', 'min:0'],
            'pending_parts.*.discount' => ['nullable', 'numeric', 'min:0'],
            'pending_technicians' => ['nullable', 'array'],
            'pending_technicians.*.user_id' => ['required_with:pending_technicians', 'exists:users,id'],
            'pending_technicians.*.share' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'pending_notes' => ['nullable', 'array'],
            'pending_notes.*.content' => ['required_with:pending_notes', 'string'],
        ]);

        $service = $validated['service_id'] ? Service::find($validated['service_id']) : null;

        $line = $work_order->items()->create([
            'tenant_id' => $work_order->tenant_id,
            'center_id' => $work_order->center_id,
            'service_id' => $validated['service_id'],
            'department_id' => $validated['department_id'] ?? null,
            'title' => $validated['title'] ?? ($service ? $service->name_ar : 'أخرى'),
            'qty' => $validated['qty'],
            'unit_price' => $validated['unit_price'],
            'base_price_snapshot' => $service ? ($service->base_price ?? 0) : 0,
            'min_price_snapshot' => $service ? ($service->min_price ?? 0) : 0,
            'discount_type' => $validated['discount_type'] ?? 'none',
            'discount_value' => $validated['discount_value'] ?? 0,
            'duration_value' => $validated['duration_value'] ?? null,
            'duration_unit' => $validated['duration_unit'] ?? null,
            'warranty_value_snapshot' => $validated['warranty_value_snapshot'] ?? null,
            'warranty_unit_snapshot' => $validated['warranty_unit_snapshot'] ?? null,
            'is_warranty' => $validated['is_warranty'] ?? false,
            'started_at' => $validated['started_at'] ?? null,
            'completed_at' => $validated['completed_at'] ?? null,
            'due_date' => $validated['due_date'] ?? null,
        ]);

        // Auto-extend expected_end_date
        $maxItemDate = $line->due_date ?? $line->started_at ?? null;

        if ($maxItemDate) {
            $expectedEndDate = $work_order->expected_end_date ? Carbon::parse($work_order->expected_end_date) : null;

            if (! $expectedEndDate || Carbon::parse($maxItemDate)->gt($expectedEndDate)) {
                $work_order->update(['expected_end_date' => $maxItemDate]);
                $work_order->logActivity('expected_end_date_updated', 'تم تمديد تاريخ تسليم كرت العمل المتوقع إلى '.Carbon::parse($maxItemDate)->format('Y-m-d').' تلقائياً لتجاوزه بمدة الخدمة');
            }
        }

        // Save pending parts
        if (! empty($request->pending_parts)) {
            $partsService = app(WorkOrderPartsService::class);
            $allowNegative = auth()->user()->can('inventory.override_negative_stock');

            foreach ($request->pending_parts as $partData) {
                $partsService->addPart([
                    'work_order_id' => $work_order->id,
                    'work_order_item_id' => $line->id,
                    'tenant_id' => $work_order->tenant_id,
                    'center_id' => $work_order->center_id,
                    'part_id' => $partData['part_id'] ?? null,
                    'warehouse_id' => $partData['warehouse_id'] ?? null,
                    'source' => $partData['source'],
                    'name' => $partData['name'],
                    'part_number' => $partData['part_number'] ?? null,
                    'unit_id' => $partData['unit_id'] ?? null,
                    'notes' => $partData['notes'] ?? null,
                    'qty' => $partData['qty'],
                    'unit_price' => $partData['unit_price'],
                    'discount' => $partData['discount'] ?? 0,
                    'include_in_package' => $partData['include_in_package'] ?? true,
                    'hide_on_print' => $partData['hide_on_print'] ?? false,
                ], $allowNegative);
            }
        }

        // Save pending technicians
        if (! empty($request->pending_technicians)) {
            foreach ($request->pending_technicians as $tech) {
                $line->technicians()->attach($tech['user_id'], [
                    'assigned_at' => now(),
                    'share' => $tech['share'] ?? 100.00,
                ]);
            }
        }

        // Save pending notes
        if (! empty($request->pending_notes)) {
            foreach ($request->pending_notes as $note) {
                $line->itemNotes()->create([
                    'work_order_id' => $work_order->id,
                    'content' => $note['content'],
                    'user_id' => $request->user()->id,
                ]);
            }
        }

        $work_order->logActivity('item_added', __('work_orders.activities.actions.item_added', ['title' => $line->title]));

        return redirect()->back()->with('success', __('messages.service_added'));
    }

    /**
     * Update a service item.
     */
    public function updateItem(Request $request, WorkOrder $work_order, WorkOrderItem $item): RedirectResponse
    {
        $this->authorize('update', $work_order);

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'qty' => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0',
            'discount_type' => 'nullable|string|in:none,fixed,percentage',
            'discount_value' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:'.implode(',', WorkOrderItem::STATUSES),
            'duration_value' => 'nullable|integer|min:0',
            'duration_unit' => 'nullable|string|max:50',
            'warranty_value_snapshot' => 'nullable|integer|min:0',
            'warranty_unit_snapshot' => 'nullable|string|max:50',
            'is_warranty' => 'nullable|boolean',
            'started_at' => 'nullable|date',
            'completed_at' => 'nullable|date',
            'due_date' => 'nullable|date',
            'technicians' => 'nullable|array',
            'technicians.*.user_id' => 'required_with:technicians|exists:users,id',
            'technicians.*.share' => 'nullable|numeric|min:0|max:100',
        ]);

        $item->update(collect($validated)->except(['technicians'])->toArray());

        if ($request->has('technicians')) {
            $syncData = [];

            foreach ($request->input('technicians', []) as $tech) {
                $syncData[$tech['user_id']] = [
                    'assigned_at' => now(),
                    'share' => $tech['share'] ?? 100.00,
                ];
            }
            $item->technicians()->sync($syncData);
        }

        // Auto-extend expected_end_date
        $maxItemDate = $item->due_date ?? $item->started_at ?? null;

        if ($maxItemDate) {
            $expectedEndDate = $work_order->expected_end_date ? Carbon::parse($work_order->expected_end_date) : null;

            if (! $expectedEndDate || Carbon::parse($maxItemDate)->gt($expectedEndDate)) {
                $work_order->update(['expected_end_date' => $maxItemDate]);
                $work_order->logActivity('expected_end_date_updated', 'تم تمديد تاريخ تسليم كرت العمل المتوقع إلى '.Carbon::parse($maxItemDate)->format('Y-m-d').' تلقائياً لتجاوزه بمدة الخدمة');
            }
        }

        $work_order->logActivity('item_updated', __('work_orders.activities.actions.item_updated', ['title' => $item->title]));

        return redirect()->back()->with('success', __('messages.service_updated'));
    }

    /**
     * Delete (cancel) a service item.
     */
    public function deleteItem(WorkOrder $work_order, WorkOrderItem $item): RedirectResponse
    {
        $this->authorize('update', $work_order);

        $hasActiveParts = $item->parts()
            ->whereNotIn('status', [
                WorkOrderItemPart::STATUS_CANCELLED,
                WorkOrderItemPart::STATUS_REVERSED,
            ])
            ->exists();

        if ($item->technicians()->exists() || $hasActiveParts) {
            return redirect()->back()->with('error', __('messages.cannot_delete_item_has_parts_or_technicians'));
        }

        $item->update(['status' => WorkOrderItem::STATUS_CANCELLED]);

        $work_order->logActivity(
            'item_status_updated',
            __('work_orders.activities.actions.item_status_updated', [
                'title' => $item->title,
                'status' => __('work_orders.item_status.cancelled'),
            ])
        );

        return redirect()->back()->with('success', __('messages.item_status_updated'));
    }

    /**
     * Add a department to a work order.
     */
    public function addDepartment(Request $request, WorkOrder $work_order): RedirectResponse
    {
        $this->authorize('update', $work_order);

        $validated = $request->validate([
            'department_id' => 'required',
        ]);

        if ($validated['department_id'] === 'packages') {
            $work_order->update(['show_packages_section' => true]);

            return redirect()->back()->with('success', __('messages.department_added'));
        }

        $request->validate([
            'department_id' => 'exists:departments,id',
        ]);

        $alreadyAttached = $work_order->departments()
            ->where('department_id', $validated['department_id'])
            ->exists();

        if (! $alreadyAttached) {
            $work_order->departments()->attach($validated['department_id']);
        }

        return redirect()->back()->with('success', __('messages.department_added'));
    }

    /**
     * Remove a department from a work order.
     */
    public function removeDepartment(WorkOrder $work_order, string $department_id): RedirectResponse
    {
        $this->authorize('update', $work_order);

        if ($department_id === 'packages') {
            $hasPackages = $work_order->items()
                ->whereHas('service', fn ($q) => $q->where('type', Service::TYPE_PACKAGE))
                ->exists();

            if ($hasPackages) {
                return redirect()->back()->with('error', __('messages.cannot_remove_department_has_items'));
            }

            $work_order->update(['show_packages_section' => false]);

            return redirect()->back()->with('success', __('messages.department_removed'));
        }

        if (! is_numeric($department_id)) {
            abort(404);
        }
        $departmentId = (int) $department_id;

        $hasItems = $work_order->items()
            ->where('department_id', $departmentId)
            ->exists();

        if ($hasItems) {
            return redirect()->back()->with('error', __('messages.cannot_remove_department_has_items'));
        }

        $work_order->departments()->detach($departmentId);

        return redirect()->back()->with('success', __('messages.department_removed'));
    }
}
