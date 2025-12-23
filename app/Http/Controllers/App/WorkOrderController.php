<?php

namespace App\Http\Controllers\App;

use App\Http\Requests\WorkOrderStoreRequest;
use App\Http\Requests\WorkOrderUpdateRequest;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Vehicle;
use App\Models\VehicleMake;
use App\Models\WorkOrder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class WorkOrderController
{
    use AuthorizesRequests;

    public function index(): Response
    {
        $this->authorize('viewAny', WorkOrder::class);

        $workOrders = WorkOrder::with(['customer', 'vehicle.make', 'items', 'damageMarks', 'photos'])
            ->orderByDesc('created_at')
            ->paginate(15);

        $customers = Customer::select('id', 'name', 'phone')->get();
        $makes = \App\Models\VehicleMake::ordered()->get();
        $colors = \App\Models\VehicleColor::active()->ordered()->get();
        $departments = \App\Models\Department::active()->ordered()->get();
        
        // Group models by make
        $modelsByMake = \App\Models\VehicleModel::query()
            ->select('id', 'make_id', 'name_ar', 'name_en')
            ->get()
            ->groupBy('make_id');

        return Inertia::render('WorkOrders/Index', [
            'workOrders' => $workOrders,
            'customers' => $customers,
            'makes' => $makes,
            'colors' => $colors,
            'modelsByMake' => $modelsByMake,
            'departments' => $departments,
        ]);
    }

    public function apiIndex(): JsonResponse
    {
        $this->authorize('viewAny', WorkOrder::class);

        $workOrders = WorkOrder::with(['customer', 'vehicle.make', 'items'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json($workOrders);
    }

    /**
     * Search customers by name or phone (for autocomplete).
     */
    public function apiCustomerSearch(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $customers = Customer::where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('phone', 'like', "%{$query}%");
            })
            ->select('id', 'name', 'phone', 'type')
            ->limit(10)
            ->get();

        return response()->json($customers);
    }

    /**
     * Get vehicles for a specific customer (for modal filtering).
     */
    public function apiVehicles(Request $request): JsonResponse
    {
        $customerId = $request->input('customer_id');

        if (!$customerId) {
            return response()->json([]);
        }

        $vehicles = Vehicle::where('customer_id', $customerId)
            ->with(['make', 'model'])
            ->get(['id', 'plate_number', 'make_id', 'model_id', 'make_other', 'model_other', 'year']);

        return response()->json($vehicles);
    }

    public function store(WorkOrderStoreRequest $request): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('create', WorkOrder::class);

        $user = $request->user();
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $user) {
            // Generate sequential code with locking
            $code = WorkOrder::generateCode($user->tenant_id, $user->current_center_id);

            // Create work order
            $workOrder = WorkOrder::create([
                'customer_id' => $validated['customer_id'],
                'vehicle_id' => $validated['vehicle_id'],
                'code' => $code,
                'status' => $validated['status'] ?? WorkOrder::STATUS_DRAFT,
                'notes' => $validated['notes'] ?? null,
                'customer_complaint' => $validated['customer_complaint'] ?? null,
                'initial_assessment' => $validated['initial_assessment'] ?? null,
                'mileage' => $validated['mileage'] ?? null,
                'contact_name' => $validated['contact_name'] ?? null,
                'contact_phone' => $validated['contact_phone'] ?? null,
                'entry_date' => $validated['entry_date'] ?? now()->toDateString(),
                'expected_end_date' => $validated['expected_end_date'] ?? null,
                'opened_at' => now(),
            ]);

            // Attach departments
            if (!empty($validated['departments'])) {
                $workOrder->departments()->attach($validated['departments']);
            }

            // Create damage marks
            if (!empty($validated['damage_marks'])) {
                foreach ($validated['damage_marks'] as $mark) {
                    $workOrder->damageMarks()->create([
                        'x' => $mark['x'],
                        'y' => $mark['y'],
                        'color' => $mark['color'] ?? 'red',
                        'description' => $mark['description'] ?? null,
                    ]);
                }
            }

            // TODO: Handle photos upload separately via dedicated endpoint
        });

        return redirect()->back();
    }

    public function show(WorkOrder $workOrder): JsonResponse
    {
        $this->authorize('view', $workOrder);

        $workOrder->load(['customer', 'vehicle', 'items']);

        return response()->json($workOrder);
    }

    public function update(WorkOrderUpdateRequest $request, WorkOrder $workOrder): JsonResponse
    {
        $this->authorize('update', $workOrder);

        $validated = $request->validated();
        $user = $request->user();

        DB::transaction(function () use ($validated, $workOrder, $user) {
            // Update main work order fields
            $workOrder->update([
                'customer_id' => $validated['customer_id'] ?? $workOrder->customer_id,
                'vehicle_id' => $validated['vehicle_id'] ?? $workOrder->vehicle_id,
                'status' => $validated['status'] ?? $workOrder->status,
                'notes' => $validated['notes'] ?? $workOrder->notes,
            ]);

            // Handle status transitions
            if (isset($validated['status'])) {
                if ($validated['status'] === WorkOrder::STATUS_DONE && !$workOrder->closed_at) {
                    $workOrder->update(['closed_at' => now()]);
                }
            }

            // Update items if provided
            if (isset($validated['items'])) {
                // Delete existing items and recreate
                $workOrder->items()->delete();

                foreach ($validated['items'] as $itemData) {
                    $workOrder->items()->create([
                        'tenant_id' => $user->tenant_id,
                        'center_id' => $user->current_center_id,
                        'title' => $itemData['title'],
                        'qty' => $itemData['qty'],
                        'unit_price' => $itemData['unit_price'],
                    ]);
                }
            }
        });

        $workOrder->load(['customer', 'vehicle', 'items']);

        return response()->json($workOrder);
    }

    public function destroy(WorkOrder $workOrder): JsonResponse
    {
        $this->authorize('delete', $workOrder);

        $workOrder->delete();

        return response()->json(null, 204);
    }
}
