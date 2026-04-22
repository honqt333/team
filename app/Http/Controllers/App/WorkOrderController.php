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
use App\Services\NotificationService;
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
                      ->orWhere("code", "like", "%{$search}%")
                      ->orWhereHas("customer", fn($c) => $c->where("name", "like", "%{$search}%"))
                      ->orWhereHas("vehicle", fn($v) => $v->where("plate_number", "like", "%{$search}%"));
                });
            })
            ->when(request("date_from"), function ($query, $dateFrom) {
                $query->whereDate('created_at', '>=', $dateFrom);
            })
            ->when(request("date_to"), function ($query, $dateTo) {
                $query->whereDate('created_at', '<=', $dateTo);
            })
            ->when(request("customer_type"), function ($query, $customerType) {
                $query->whereHas('customer', function ($q) use ($customerType) {
                    $q->where('type', $customerType);
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
            "filters" => request()->only(["search", "status", "sub_filter", "date_from", "date_to", "customer_type"]),
            "statusFilter" => $status,
            "subFilter" => $subFilter,
            "filterCounts" => $filterCounts,
        ]);
    }

    /**
     * Export work orders to XLSX.
     */
    public function export()
    {
        $this->authorize('viewAny', WorkOrder::class);

        $filename = 'work_orders_' . date('Y-m-d_His') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\WorkOrdersExport(),
            $filename
        );
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

        // Send notification to owner
        $workOrder->load('customer');
        NotificationService::notifyOwner(
            tenantId: $request->user()->tenant_id,
            type: 'work_order.created',
            title: 'أمر عمل جديد #' . ($workOrder->code ?? $workOrder->id),
            body: 'تم إنشاء أمر عمل جديد' . ($workOrder->customer ? ' للعميل ' . $workOrder->customer->name : ''),
            actionUrl: '/app/work-orders/' . $workOrder->id,
            actorId: $request->user()->id,
        );

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
            'departments',
            'payments.receivedBy',
            'parts.part',
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
        
        // Get technicians (employees with linked users belonging to the work order's center)
        $technicians = \App\Models\HR\Employee::where('center_id', $workOrder->center_id)
            ->whereNotNull('user_id')
            ->active()
            ->get()
            ->map(function ($employee) {
                return [
                    'id' => $employee->user_id, // Use user_id as the compatible ID for assignment
                    'name' => $employee->display_name,
                ];
            });
        
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

        // Get units for addPartModal
        $inventoryUnits = \App\Models\InventoryUnit::where('is_active', true)
            ->select('id', 'name_ar', 'name_en')
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
            'inventoryUnits' => $inventoryUnits,
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

    /**
     * Update condition report (fuel level and damage marks).
     */
    public function updateCondition(Request $request, WorkOrder $workOrder)
    {
        $this->authorize('update', $workOrder);
        
        if (!$workOrder->canBeEdited()) {
            return back()->withErrors(['error' => __('messages.work_order_locked')]);
        }

        $validated = $request->validate([
            'fuel_level' => 'nullable|numeric|min:0|max:100',
            'damage_marks' => 'nullable|array',
        ]);

        $workOrder->update([
            'fuel_level' => $validated['fuel_level'] ?? $workOrder->fuel_level,
        ]);

        // Update damage marks through relationship
        if (isset($validated['damage_marks'])) {
            $workOrder->damageMarks()->delete();
            foreach ($validated['damage_marks'] as $mark) {
                $workOrder->damageMarks()->create([
                    'x' => $mark['x'] ?? 0,
                    'y' => $mark['y'] ?? 0,
                    'color' => $mark['color'] ?? 'red',
                    'description' => $mark['description'] ?? '',
                ]);
            }
        }

        return back();
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
            // Pending parts validation
            'pending_parts' => ['nullable', 'array'],
            'pending_parts.*.source' => ['required_with:pending_parts', 'in:warehouse,external,customer'],
            'pending_parts.*.name' => ['required_with:pending_parts', 'string', 'max:255'],
            'pending_parts.*.part_id' => ['nullable', 'exists:parts,id'],
            'pending_parts.*.warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'pending_parts.*.qty' => ['required_with:pending_parts', 'numeric', 'min:0.01'],
            'pending_parts.*.unit_price' => ['required_with:pending_parts', 'numeric', 'min:0'],
            'pending_parts.*.discount' => ['nullable', 'numeric', 'min:0'],
            // Pending technicians validation
            'pending_technicians' => ['nullable', 'array'],
            'pending_technicians.*.user_id' => ['required_with:pending_technicians', 'exists:users,id'],
            // Pending notes validation
            'pending_notes' => ['nullable', 'array'],
            'pending_notes.*.content' => ['required_with:pending_notes', 'string'],
        ]);

        $service = \App\Models\Service::find($validated['service_id']);

        $line = $work_order->items()->create([
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

        // Save pending parts linked to the new service item
        if (!empty($request->pending_parts)) {
            $partsService = app(\App\Services\Inventory\WorkOrderPartsService::class);
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
        if (!empty($request->pending_technicians)) {
            foreach ($request->pending_technicians as $tech) {
                $line->technicians()->attach($tech['user_id'], [
                    'assigned_at' => now()
                ]);
            }
        }

        // Save pending notes
        if (!empty($request->pending_notes)) {
            foreach ($request->pending_notes as $note) {
                $line->itemNotes()->create([
                    'tenant_id' => $work_order->tenant_id,
                    'center_id' => $work_order->center_id,
                    'content' => $note['content'],
                    'user_id' => $request->user()->id,
                ]);
            }
        }

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
            'part_id' => 'nullable|exists:parts,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'part_number' => 'nullable|string|max:100',
            'source' => 'required|in:' . implode(',', \App\Models\WorkOrderItemPart::SOURCES),
            'unit_id' => 'nullable|exists:inventory_units,id',
            'qty' => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'include_in_package' => 'boolean',
            'hide_on_print' => 'boolean',
            'notes' => 'nullable|string|max:500',
        ]);

        $partsService = app(\App\Services\Inventory\WorkOrderPartsService::class);
        $allowNegative = auth()->user()->can('inventory.override_negative_stock');
        $part = $partsService->addPart([
            'work_order_id' => $work_order->id,
            'work_order_item_id' => $item->id,
            'tenant_id' => $work_order->tenant_id,
            'center_id' => $work_order->center_id,
            ...$validated,
        ], $allowNegative);

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
            'part_id' => 'nullable|exists:parts,id',
            'part_number' => 'nullable|string|max:100',
            'source' => 'required|in:' . implode(',', \App\Models\WorkOrderItemPart::SOURCES),
            'unit_id' => 'nullable|exists:inventory_units,id',
            'qty' => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'include_in_package' => 'boolean',
            'hide_on_print' => 'boolean',
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

    // ─────────────────────────────────────────────────────────────
    // Print Views
    // ─────────────────────────────────────────────────────────────

    /**
     * Print vehicle condition report.
     */
    public function printCondition(WorkOrder $workOrder): Response
    {
        $this->authorize('view', $workOrder);

        $workOrder->load([
            'customer',
            'vehicle.make',
            'vehicle.model',
            'damageMarks',
            'photos',
            'center',
            'tenant',
        ]);

        return Inertia::render('WorkOrders/Print/Condition', [
            'workOrder' => $workOrder,
        ]);
    }

    /**
     * Print work order services list.
     */
    public function printServices(WorkOrder $workOrder): Response
    {
        $this->authorize('view', $workOrder);

        $workOrder->load([
            'customer',
            'vehicle.make',
            'vehicle.model',
            'items.service.department',
            'items.technicians',
            'center',
            'tenant',
        ]);

        // Group items by department
        $itemsByDepartment = $workOrder->items->groupBy(function ($item) {
            return $item->service?->department_id ?? 0;
        });

        $departments = \App\Models\Department::active()->ordered()->get()->keyBy('id');

        return Inertia::render('WorkOrders/Print/Services', [
            'workOrder' => $workOrder,
            'itemsByDepartment' => $itemsByDepartment,
            'departments' => $departments,
        ]);
    }

    /**
     * Print proforma invoice.
     */
    public function printProforma(WorkOrder $workOrder): Response
    {
        $this->authorize('view', $workOrder);

        $workOrder->load([
            'customer',
            'vehicle.make',
            'vehicle.model',
            'items.service.department',
            'items.parts',
            'center',
            'tenant',
        ]);

        // Calculate totals
        $servicesTotal = $workOrder->items->sum(function ($item) {
            return $item->line_total ?? ($item->qty * $item->unit_price);
        });

        $partsTotal = $workOrder->items->sum(function ($item) {
            return $item->parts->sum(function ($part) {
                return $part->qty * $part->unit_price;
            });
        });

        $totalPaid = $workOrder->total_paid ?? 0;
        $grandTotal = $servicesTotal + $partsTotal;
        $balance = $grandTotal - $totalPaid;

        // Group items by department
        $itemsByDepartment = $workOrder->items->groupBy(function ($item) {
            return $item->service?->department_id ?? 0;
        });

        // Collect all parts
        $allParts = $workOrder->items->flatMap(function ($item) {
            return $item->parts;
        });

        $departments = \App\Models\Department::active()->ordered()->get()->keyBy('id');

        // Get tax settings
        $taxSettings = \App\Models\TenantTaxSetting::where('tenant_id', $workOrder->tenant_id)->first();

        return Inertia::render('WorkOrders/Print/Proforma', [
            'workOrder' => $workOrder,
            'itemsByDepartment' => $itemsByDepartment,
            'allParts' => $allParts,
            'departments' => $departments,
            'servicesTotal' => $servicesTotal,
            'partsTotal' => $partsTotal,
            'grandTotal' => $grandTotal,
            'totalPaid' => $totalPaid,
            'balance' => $balance,
            'taxSettings' => $taxSettings,
        ]);
    }

    /**
     * Print payments receipt.
     */
    public function printPayments(WorkOrder $workOrder): Response
    {
        $this->authorize('view', $workOrder);

        $workOrder->load([
            'customer',
            'vehicle.make',
            'vehicle.model',
            'center',
            'tenant',
        ]);

        // Get payments related to this work order
        $payments = $workOrder->payments()->with('receivedBy')->get();

        // Calculate totals
        $servicesTotal = $workOrder->items->sum(function ($item) {
            return $item->line_total ?? ($item->qty * $item->unit_price);
        });

        $partsTotal = $workOrder->items->sum(function ($item) {
            return $item->parts?->sum(function ($part) {
                return $part->qty * $part->unit_price;
            }) ?? 0;
        });

        $grandTotal = $servicesTotal + $partsTotal;
        $totalPaid = $payments->sum('amount');
        $balance = $grandTotal - $totalPaid;

        return Inertia::render('WorkOrders/Print/Payments', [
            'workOrder' => $workOrder,
            'payments' => $payments,
            'grandTotal' => $grandTotal,
            'totalPaid' => $totalPaid,
            'balance' => $balance,
        ]);
    }

    // ==================== Payment Methods ====================

    /**
     * Store a new payment for a work order.
     */
    public function storePayment(Request $request, WorkOrder $workOrder)
    {
        $this->authorize('update', $workOrder);

        $validated = $request->validate([
            'payment_method' => 'required|in:' . implode(',', \App\Models\Payment::METHODS),
            'type' => 'required|in:' . implode(',', \App\Models\Payment::TYPES),
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $workOrder->payments()->create([
            'tenant_id' => $workOrder->tenant_id,
            'center_id' => $workOrder->center_id,
            'type' => $validated['type'],
            'payment_method' => $validated['payment_method'],
            'amount' => $validated['amount'],
            'payment_date' => $validated['payment_date'],
            'reference' => $validated['reference'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'received_by' => auth()->id(),
        ]);

        return back()->with('success', __('payments.saved_successfully'));
    }

    /**
     * Update an existing payment.
     */
    public function updatePayment(Request $request, WorkOrder $workOrder, \App\Models\Payment $payment)
    {
        $this->authorize('update', $workOrder);

        // Ensure payment belongs to this work order
        if ($payment->work_order_id !== $workOrder->id) {
            abort(403);
        }

        $validated = $request->validate([
            'payment_method' => 'required|in:' . implode(',', \App\Models\Payment::METHODS),
            'type' => 'required|in:' . implode(',', \App\Models\Payment::TYPES),
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $payment->update($validated);

        return back()->with('success', __('payments.updated_successfully'));
    }

    /**
     * Delete a payment.
     */
    public function destroyPayment(WorkOrder $workOrder, \App\Models\Payment $payment)
    {
        $this->authorize('update', $workOrder);

        // Ensure payment belongs to this work order
        if ($payment->work_order_id !== $workOrder->id) {
            abort(403);
        }

        $payment->delete();

        return back()->with('success', __('payments.deleted_successfully'));
    }

    /**
     * Delete a photo from work order.
     */
    public function deletePhoto(WorkOrder $workOrder, \App\Models\WorkOrderPhoto $photo)
    {
        $this->authorize('update', $workOrder);

        // Verify photo belongs to this work order
        if ($photo->work_order_id !== $workOrder->id) {
            abort(403);
        }

        // Delete file from storage
        \Illuminate\Support\Facades\Storage::disk('public')->delete($photo->path);
        
        // Delete record
        $photo->delete();

        return back()->with('success', __('messages.photo_deleted'));
    }
}
