<?php

namespace App\Http\Controllers\App;

use App\Actions\WorkOrder\CreateWorkOrderAction;
use App\Actions\WorkOrder\UpdateWorkOrderAction;
use App\Http\Requests\WorkOrderStoreRequest;
use App\Http\Requests\WorkOrderUpdateRequest;
use App\Models\Customer;
use App\Models\Service;
use App\Models\VehicleMake;
use App\Models\WorkOrder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkOrderController
{
    use AuthorizesRequests;

    /**
     * Hub page with quick-access cards
     */
    public function hub(): Response
    {
        $this->authorize("viewAny", WorkOrder::class);

        // Count open work orders (open, in_progress, draft)
        $openCount = WorkOrder::whereIn('status', ['open', 'in_progress', 'draft'])->count();
        
        // Count completed work orders (done but not closed)
        $completedCount = WorkOrder::where('status', 'done')->count();
        
        // Count closed work orders (closed, cancelled)
        $closedCount = WorkOrder::whereIn('status', ['closed', 'cancelled'])->count();

        $customers = Customer::select("id", "name", "phone")->get();
        $makes = VehicleMake::ordered()->get();
        $colors = \App\Models\VehicleColor::active()->ordered()->get();
        $departments = \App\Models\Department::active()->ordered()->get();
        
        $modelsByMake = \App\Models\VehicleModel::query()
            ->select('id', 'make_id', 'name_ar', 'name_en')
            ->get()
            ->groupBy('make_id');

        return Inertia::render("WorkOrders/Hub", [
            "openCount" => $openCount,
            "completedCount" => $completedCount,
            "closedCount" => $closedCount,
            "customers" => $customers,
            "makes" => $makes,
            "colors" => $colors,
            "modelsByMake" => $modelsByMake,
            "departments" => $departments,
        ]);
    }

    public function apiIndex(): JsonResponse
    {
        $this->authorize("viewAny", WorkOrder::class);

        $status = request("status");
        $subFilter = request("sub_filter");

        $workOrders = WorkOrder::with(["customer", "vehicle.make", "items"])
            ->when($status === 'open', function ($query) use ($subFilter) {
                if ($subFilter === 'overdue') {
                    $query->whereIn('status', ['open', 'in_progress'])
                        ->whereNotNull('expected_end_date')
                        ->where('expected_end_date', '<', now()->startOfDay());
                } elseif ($subFilter === 'pending_payment') {
                    // TODO: Filter by pending payment when columns exist
                    $query->whereIn('status', ['open', 'in_progress', 'done']);
                } elseif ($subFilter === 'draft') {
                    $query->where('status', 'draft');
                } elseif ($subFilter === 'in_progress') {
                    $query->whereIn('status', ['open', 'in_progress']);
                } else {
                    // Default: all open statuses
                    $query->whereIn('status', ['open', 'in_progress', 'draft']);
                }
            })
            ->when($status === 'closed', function ($query) {
                $query->whereIn('status', ['done', 'cancelled', 'closed']);
            })
            ->when(request("search"), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where("id", "like", "%{$search}%")
                      ->orWhereHas("customer", fn($c) => $c->where("name", "like", "%{$search}%"))
                      ->orWhereHas("vehicle", fn($v) => $v->where("plate_number", "like", "%{$search}%"));
                });
            })
            ->orderByDesc("created_at")
            ->paginate(15)
            ->withQueryString();

        return response()->json($workOrders);
    }

    public function index(): Response
    {
        $this->authorize("viewAny", WorkOrder::class);

        $status = request("status");
        $subFilter = request("sub_filter"); // New sub-filter for tabs

        // Base query for counting
        $baseQuery = WorkOrder::query();
        
        // Calculate counts for filter tabs (only for open status)
        $filterCounts = [];
        if ($status === 'open') {
            $filterCounts = [
                'open' => WorkOrder::whereIn('status', ['open', 'in_progress'])->count(),
                'draft' => WorkOrder::where('status', 'draft')->count(),
                'overdue' => WorkOrder::whereIn('status', ['open', 'in_progress'])
                    ->whereNotNull('expected_end_date')
                    ->where('expected_end_date', '<', now()->startOfDay())
                    ->count(),
                'pending_payment' => 0, // TODO: Add when payment columns exist
            ];
        }

        $workOrders = WorkOrder::with(["customer", "vehicle.make", "items"])
            ->when($status === 'open', function ($query) use ($subFilter) {
                if ($subFilter === 'overdue') {
                    $query->whereIn('status', ['open', 'in_progress'])
                        ->whereNotNull('expected_end_date')
                        ->where('expected_end_date', '<', now()->startOfDay());
                } elseif ($subFilter === 'pending_payment') {
                    // TODO: Filter by pending payment when columns exist
                    $query->whereIn('status', ['open', 'in_progress', 'done']);
                } elseif ($subFilter === 'draft') {
                    $query->where('status', 'draft');
                } elseif ($subFilter === 'in_progress') {
                    $query->whereIn('status', ['open', 'in_progress']);
                } else {
                    // Default: all open statuses
                    $query->whereIn('status', ['open', 'in_progress', 'draft']);
                }
            })
            ->when($status === 'closed', function ($query) {
                $query->whereIn('status', ['done', 'cancelled']);
            })
            ->when(request("search"), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where("id", "like", "%{$search}%")
                      ->orWhereHas("customer", fn($c) => $c->where("name", "like", "%{$search}%"))
                      ->orWhereHas("vehicle", fn($v) => $v->where("plate_number", "like", "%{$search}%"));
                });
            })
            ->orderByDesc("created_at")
            ->paginate(15)
            ->withQueryString();

        $customers = Customer::select("id", "name", "phone")->get();
        $makes = VehicleMake::ordered()->get();
        $colors = \App\Models\VehicleColor::active()->ordered()->get();
        $departments = \App\Models\Department::active()->ordered()->get();
        
        $modelsByMake = \App\Models\VehicleModel::query()
            ->select('id', 'make_id', 'name_ar', 'name_en')
            ->get()
            ->groupBy('make_id');
        
        // Get services grouped by department
        $services = Service::active()
            ->orderBy("department_id")
            ->orderBy("sort_order")
            ->get()
            ->groupBy("department_id");

        return Inertia::render("WorkOrders/Index", [
            "workOrders" => $workOrders,
            "customers" => $customers,
            "makes" => $makes,
            "colors" => $colors,
            "modelsByMake" => $modelsByMake,
            "departments" => $departments,
            "services" => $services,
            "filters" => request()->only(["search", "status", "sub_filter"]),
            "statusFilter" => $status,
            "subFilter" => $subFilter,
            "filterCounts" => $filterCounts,
        ]);
    }
    
    public function store(WorkOrderStoreRequest $request, CreateWorkOrderAction $createWorkOrder): \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $this->authorize('create', WorkOrder::class);

        // Files are included in validated() if rules exist for them
        $workOrder = $createWorkOrder->execute($request->user(), $request->validated());

        // Return JSON for API requests, redirect for web requests
        if ($request->expectsJson()) {
            // Refresh to get items (created within transaction)
            $workOrder->refresh();
            $workOrder->load('items');
            return response()->json($workOrder, 201);
        }

        // Redirect to the newly created work order's show page
        return redirect()->route('work-orders.show', $workOrder)->with('success', __('messages.work_order_created'));
    }

    public function show(WorkOrder $workOrder): Response
    {
        $this->authorize('view', $workOrder);

        $workOrder->load([
            'customer', 
            'vehicle.make', 
            'vehicle.customer', 
            'items.service.department',
            'items.technicians',
            'items.parts',
            'items.itemNotes.user',
            'damageMarks', 
            'photos', 
            'departments'
        ]);

        // Group items by department_id for accordion display
        $itemsByDepartment = $workOrder->items->groupBy(function ($item) {
            return $item->service?->department_id ?? 0;
        });

        $customers = \App\Models\Customer::select('id', 'name', 'phone')->get();
        $makes = \App\Models\VehicleMake::ordered()->get();
        $colors = \App\Models\VehicleColor::active()->ordered()->get();
        $departments = \App\Models\Department::active()->ordered()->get();
        $services = \App\Models\Service::active()->ordered()->get();
        
        // Get technicians (users strictly belonging to the work order's center)
        $technicians = \App\Models\User::whereHas('centers', function ($q) use ($workOrder) {
                $q->where('centers.id', $workOrder->center_id);
            })
            ->select('users.id', 'users.name')
            ->get();
        
        $modelsByMake = \App\Models\VehicleModel::query()
            ->select('id', 'make_id', 'name_ar', 'name_en')
            ->get()
            ->groupBy('make_id');

        // Get warehouses for addPartModal
        $warehouses = \App\Models\Warehouse::where('center_id', $workOrder->center_id)
            ->orWhereNull('center_id') // Global warehouses if any
            ->select('id', 'name')
            ->get();

        // Get parts for addPartModal
        $inventoryParts = \App\Models\Part::where('tenant_id', $workOrder->tenant_id)
            ->where('is_active', true)
            ->select('id', 'sku', 'name_ar', 'name_en')
            ->get();

        return Inertia::render('WorkOrders/Show', [
            'workOrder' => $workOrder,
            'itemsByDepartment' => $itemsByDepartment,
            'customers' => $customers,
            'makes' => $makes,
            'colors' => $colors,
            'modelsByMake' => $modelsByMake,
            'departments' => $departments,
            'services' => $services,
            'technicians' => $technicians,
            'warehouses' => $warehouses,
            'inventoryParts' => $inventoryParts,
        ]);
    }

    public function update(WorkOrderUpdateRequest $request, WorkOrder $workOrder, UpdateWorkOrderAction $updateWorkOrder): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $workOrder);

        $updateWorkOrder->execute($workOrder, $request->user(), $request->validated());

        return redirect()->back()->with('success', __('messages.work_order_updated'));
    }

    public function destroy(WorkOrder $workOrder): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('delete', $workOrder);

        // Delete photos from disk
        foreach ($workOrder->photos as $photo) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($photo->path);
        }

        $workOrder->delete();

        return redirect()->route('work-orders.index')->with('success', __('messages.work_order_deleted'));
    }

    // ─────────────────────────────────────────────────────────────
    // Work Order Items (Services)
    // ─────────────────────────────────────────────────────────────

    public function addItem(Request $request, WorkOrder $work_order): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $work_order);

        $validated = $request->validate([
            'service_id' => 'required|exists:services,id',
            'title' => 'nullable|string|max:255',
            'qty' => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0',
            'discount_type' => 'nullable|string|in:none,fixed,percentage',
            'discount_value' => 'nullable|numeric|min:0',
        ]);

        $service = \App\Models\Service::find($validated['service_id']);

        $work_order->items()->create([
            'tenant_id' => $work_order->tenant_id,
            'center_id' => $work_order->center_id,
            'service_id' => $validated['service_id'],
            'title' => $validated['title'] ?? $service->name_ar,
            'qty' => $validated['qty'],
            'unit_price' => $validated['unit_price'],
            'base_price_snapshot' => $service->base_price ?? 0,
            'min_price_snapshot' => $service->min_price ?? 0,
            'discount_type' => $validated['discount_type'] ?? 'none',
            'discount_value' => $validated['discount_value'] ?? 0,
        ]);

        return redirect()->back()->with('success', __('messages.service_added'));
    }

    public function updateItem(Request $request, WorkOrder $work_order, \App\Models\WorkOrderItem $item): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $work_order);

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'qty' => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0',
            'discount_type' => 'nullable|string|in:none,fixed,percentage',
            'discount_value' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:' . implode(',', \App\Models\WorkOrderItem::STATUSES),
        ]);

        $item->update($validated);

        return redirect()->back()->with('success', __('messages.service_updated'));
    }

    public function deleteItem(WorkOrder $work_order, \App\Models\WorkOrderItem $item): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $work_order);

        $item->delete();

        return redirect()->back()->with('success', __('messages.service_deleted'));
    }

    // ─────────────────────────────────────────────────────────────
    // Work Order Departments
    // ─────────────────────────────────────────────────────────────

    public function addDepartment(Request $request, WorkOrder $work_order): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $work_order);

        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
        ]);

        // Sync without detaching
        $work_order->departments()->syncWithoutDetaching([$validated['department_id']]);

        return redirect()->back()->with('success', __('messages.department_added'));
    }

    public function removeDepartment(WorkOrder $work_order, \App\Models\Department $department): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $work_order);

        // Rule R11: Can only remove department if no items belong to it
        $hasItems = $work_order->items()
            ->whereHas('service', fn($q) => $q->where('department_id', $department->id))
            ->exists();

        if ($hasItems) {
            return redirect()->back()->with('error', __('messages.cannot_remove_department_has_items'));
        }

        $work_order->departments()->detach($department->id);

        return redirect()->back()->with('success', __('messages.department_removed'));
    }

    // ─────────────────────────────────────────────────────────────
    // Work Order Status Management
    // ─────────────────────────────────────────────────────────────

    /**
     * Put work order on hold.
     * Rule R7: Suspends all items.
     */
    public function putOnHold(WorkOrder $work_order): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $work_order);

        if (!$work_order->canBeOnHold()) {
            return redirect()->back()->with('error', __('messages.cannot_put_on_hold'));
        }

        $work_order->putOnHold();

        return redirect()->back()->with('success', __('messages.work_order_on_hold'));
    }

    /**
     * Resume work order from hold.
     */
    public function resume(WorkOrder $work_order): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $work_order);

        if (!$work_order->resume()) {
            return redirect()->back()->with('error', __('messages.cannot_resume'));
        }

        return redirect()->back()->with('success', __('messages.work_order_resumed'));
    }

    /**
     * Cancel work order.
     * Rules R5, R6: Cannot cancel if items have technicians or parts.
     */
    public function cancel(WorkOrder $work_order): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $work_order);

        if (!$work_order->canBeCancelled()) {
            return redirect()->back()->with('error', __('messages.cannot_cancel_has_technicians_or_parts'));
        }

        $work_order->update(['status' => WorkOrder::STATUS_CANCELLED]);

        return redirect()->back()->with('success', __('messages.work_order_cancelled'));
    }

    /**
     * Mark as completed (vehicle exit).
     * Rule R8: Only when all items completed.
     */
    public function complete(WorkOrder $work_order): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $work_order);

        if (!$work_order->markAsCompleted()) {
            return redirect()->back()->with('error', __('messages.cannot_complete_items_pending'));
        }

        return redirect()->back()->with('success', __('messages.work_order_completed'));
    }

    // ─────────────────────────────────────────────────────────────
    // Work Order Item Status Management
    // ─────────────────────────────────────────────────────────────

    /**
     * Update item status.
     * Rules R1, R2: Cannot cancel if has technicians or parts.
     */
    public function updateItemStatus(Request $request, WorkOrder $work_order, \App\Models\WorkOrderItem $item): \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $this->authorize('update', $work_order);

        $validated = $request->validate([
            'status' => 'required|in:' . implode(',', \App\Models\WorkOrderItem::STATUSES),
        ]);

        $newStatus = $validated['status'];

        // Check business rules
        if (!$item->canChangeStatusTo($newStatus)) {
            $message = __('messages.cannot_change_item_status');
            return $request->expectsJson()
                ? response()->json(['error' => $message], 422)
                : redirect()->back()->with('error', $message);
        }

        $item->update(['status' => $newStatus]);

        $message = __('messages.item_status_updated');
        return $request->expectsJson()
            ? response()->json(['success' => $message, 'item' => $item->fresh()])
            : redirect()->back()->with('success', $message);
    }

    // ─────────────────────────────────────────────────────────────
    // Work Order Item Technicians
    // ─────────────────────────────────────────────────────────────

    /**
     * Assign technician to item.
     */
    public function assignTechnician(Request $request, WorkOrder $work_order, \App\Models\WorkOrderItem $item): \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $this->authorize('update', $work_order);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'notes' => 'nullable|string|max:500',
        ]);

        // Security Check: Technician must belong to the Work Order's center
        $techExistsInCenter = \App\Models\User::where('id', $validated['user_id'])
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

        // Attach technician (ignore if already assigned)
        $item->technicians()->syncWithoutDetaching([
            $validated['user_id'] => [
                'assigned_at' => now(),
                'notes' => $validated['notes'] ?? null,
            ]
        ]);

        $message = __('messages.technician_assigned');
        return $request->expectsJson()
            ? response()->json(['success' => $message, 'technicians' => $item->technicians])
            : redirect()->back()->with('success', $message);
    }

    /**
     * Remove technician from item.
     */
    public function removeTechnician(WorkOrder $work_order, \App\Models\WorkOrderItem $item, \App\Models\User $user): \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $this->authorize('update', $work_order);

        $item->technicians()->detach($user->id);

        $message = __('messages.technician_removed');
        return request()->expectsJson()
            ? response()->json(['success' => $message])
            : redirect()->back()->with('success', $message);
    }

    // ─────────────────────────────────────────────────────────────
    // Work Order Item Parts
    // ─────────────────────────────────────────────────────────────

    /**
     * Add part to item.
     */
    public function addPart(Request $request, WorkOrder $work_order, \App\Models\WorkOrderItem $item): \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $this->authorize('update', $work_order);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'part_number' => 'nullable|string|max:100',
            'source' => 'required|in:' . implode(',', \App\Models\WorkOrderItemPart::SOURCES),
            'qty' => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $part = $item->parts()->create([
            'tenant_id' => $work_order->tenant_id,
            'center_id' => $work_order->center_id,
            ...$validated,
        ]);

        $message = __('messages.part_added');
        return $request->expectsJson()
            ? response()->json(['success' => $message, 'part' => $part])
            : redirect()->back()->with('success', $message);
    }

    /**
     * Update part.
     */
    public function updatePart(Request $request, WorkOrder $work_order, \App\Models\WorkOrderItem $item, \App\Models\WorkOrderItemPart $part): \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $this->authorize('update', $work_order);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'part_number' => 'nullable|string|max:100',
            'source' => 'required|in:' . implode(',', \App\Models\WorkOrderItemPart::SOURCES),
            'qty' => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $part->update($validated);

        $message = __('messages.part_updated');
        return $request->expectsJson()
            ? response()->json(['success' => $message, 'part' => $part->fresh()])
            : redirect()->back()->with('success', $message);
    }

    /**
     * Delete part.
     */
    public function deletePart(WorkOrder $work_order, \App\Models\WorkOrderItem $item, \App\Models\WorkOrderItemPart $part): \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $this->authorize('update', $work_order);

        $part->delete();

        $message = __('messages.part_deleted');
        return request()->expectsJson()
            ? response()->json(['success' => $message])
            : redirect()->back()->with('success', $message);
    }

    // ─────────────────────────────────────────────────────────────
    // Work Order Item Notes
    // ─────────────────────────────────────────────────────────────

    /**
     * Add note to item.
     */
    public function addNote(Request $request, WorkOrder $work_order, \App\Models\WorkOrderItem $item): \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $this->authorize('update', $work_order);

        $validated = $request->validate([
            'content' => 'required|string|max:2000',
        ]);

        $note = $item->itemNotes()->create([
            'user_id' => $request->user()->id,
            'content' => $validated['content'],
        ]);

        $note->load('user');

        $message = __('messages.note_added');
        return $request->expectsJson()
            ? response()->json(['success' => $message, 'note' => $note])
            : redirect()->back()->with('success', $message);
    }

    /**
     * Delete note.
     */
    public function deleteNote(WorkOrder $work_order, \App\Models\WorkOrderItem $item, \App\Models\WorkOrderItemNote $note): \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $this->authorize('update', $work_order);

        $note->delete();

        $message = __('messages.note_deleted');
        return request()->expectsJson()
            ? response()->json(['success' => $message])
            : redirect()->back()->with('success', $message);
    }
}
