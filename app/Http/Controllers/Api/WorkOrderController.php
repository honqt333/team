<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkOrder;
use App\Models\Customer;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class WorkOrderController extends Controller
{
    use AuthorizesRequests;

    public function index(): JsonResponse
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
    public function customerSearch(Request $request): JsonResponse
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
    public function customerVehicles(Request $request): JsonResponse
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

    /**
     * Search vehicles by plate number, customer name, or phone (for work order creation).
     */
    public function vehicleSearch(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $vehicles = Vehicle::with(['customer', 'make', 'model'])
            ->where(function ($q) use ($query) {
                $q->where('plate_number', 'like', "%{$query}%")
                  ->orWhereHas('customer', function ($customerQuery) use ($query) {
                      $customerQuery->where('name', 'like', "%{$query}%")
                                    ->orWhere('phone', 'like', "%{$query}%");
                  });
            })
            ->limit(10)
            ->get();

        // Check for open work orders and attach info
        $vehicles->each(function ($vehicle) {
            $openWorkOrder = WorkOrder::where('vehicle_id', $vehicle->id)
                ->whereIn('status', ['draft', 'open', 'in_progress'])
                ->first(['id', 'code', 'status']);
            
            $vehicle->has_open_work_order = $openWorkOrder !== null;
            $vehicle->open_work_order = $openWorkOrder;
        });

        return response()->json($vehicles);
    }
}
