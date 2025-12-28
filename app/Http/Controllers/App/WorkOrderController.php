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

        $workOrder->load(['customer', 'vehicle.make', 'vehicle.customer', 'items.service.department', 'damageMarks', 'photos', 'departments']);

        // Group items by department_id for accordion display
        $itemsByDepartment = $workOrder->items->groupBy(function ($item) {
            return $item->service?->department_id ?? 0;
        });

        $customers = \App\Models\Customer::select('id', 'name', 'phone')->get();
        $makes = \App\Models\VehicleMake::ordered()->get();
        $colors = \App\Models\VehicleColor::active()->ordered()->get();
        $departments = \App\Models\Department::active()->ordered()->get();
        $services = \App\Models\Service::active()->ordered()->get();
        
        $modelsByMake = \App\Models\VehicleModel::query()
            ->select('id', 'make_id', 'name_ar', 'name_en')
            ->get()
            ->groupBy('make_id');

        return Inertia::render('WorkOrders/Show', [
            'workOrder' => $workOrder,
            'itemsByDepartment' => $itemsByDepartment,
            'customers' => $customers,
            'makes' => $makes,
            'colors' => $colors,
            'modelsByMake' => $modelsByMake,
            'departments' => $departments,
            'services' => $services,
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

        $work_order->departments()->detach($department->id);

        return redirect()->back()->with('success', __('messages.department_removed'));
    }
}
