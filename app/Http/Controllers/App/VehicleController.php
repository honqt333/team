<?php

namespace App\Http\Controllers\App;

use App\Http\Requests\VehicleStoreRequest;
use App\Http\Requests\VehicleUpdateRequest;
use App\Models\Customer;
use App\Models\Vehicle;
use App\Models\VehicleMake;
use App\Models\VehicleColor;
use App\Models\WorkOrder;
use App\Models\Quote;
use App\Models\Department;
use App\Models\Service;
use App\Models\VehicleModel;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VehicleController
{
    use AuthorizesRequests;

    /**
     * Export vehicles to Excel.
     */
    public function export()
    {
        $this->authorize('viewAny', Vehicle::class);
        
        $search = request('search');
        $filename = 'vehicles_' . date('Y-m-d_His') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\VehiclesExport($search),
            $filename
        );
    }

    /**
     * Print vehicles list (Full Page).
     */
    public function print()
    {
        $this->authorize('viewAny', Vehicle::class);

        $user = auth()->user();
        $search = request('search');

    }


    public function apiIndex(Request $request)
    {
        $this->authorize('viewAny', Vehicle::class);

        $user = $request->user();
        
        $vehicles = Vehicle::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('center_id', $user->current_center_id)
            ->with(['customer', 'make', 'model'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('plate_number', 'like', "%{$search}%")
                      ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->make_id, fn ($q, $makeId) => $q->where('make_id', $makeId))
            ->when($request->date_from, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($request->date_to, fn ($q, $date) => $q->whereDate('created_at', '<=', $date))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return response()->json($vehicles);
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Vehicle::class);

        $user = $request->user();
        
        $vehicles = Vehicle::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('center_id', $user->current_center_id)
            ->with(['customer', 'make', 'model'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('plate_number', 'like', "%{$search}%")
                      ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->make_id, fn ($q, $makeId) => $q->where('make_id', $makeId))
            ->when($request->date_from, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($request->date_to, fn ($q, $date) => $q->whereDate('created_at', '<=', $date))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        // Get customers for the dropdown
        $customers = Customer::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('center_id', $user->current_center_id)
            ->select('id', 'name', 'phone', 'whatsapp')
            ->orderBy('name')
            ->get();



        // Get all makes
        $makes = VehicleMake::ordered()->get(['id', 'name_ar', 'name_en']);
        
        // Get active colors
        $colors = VehicleColor::active()->ordered()->get(['id', 'name_ar', 'name_en', 'hex_code']);

        // Build modelsByMake map
        $modelsByMake = [];
        foreach ($makes as $make) {
            $modelsByMake[$make->id] = $make->models()->ordered()->get(['id', 'name_ar', 'name_en']);
        }

        return Inertia::render('Vehicles/Index', [
            'vehicles' => $vehicles,
            'customers' => $customers,
            'makes' => $makes,
            'colors' => $colors,
            'modelsByMake' => $modelsByMake,
            'filters' => $request->only(['search', 'make_id', 'date_from', 'date_to']),
        ]);
    }

    public function store(VehicleStoreRequest $request)
    {
        $this->authorize('create', Vehicle::class);

        $user = $request->user();
        
        $data = $request->validated();
        $data['tenant_id'] = $user->tenant_id;
        $data['center_id'] = $user->current_center_id;

        // Handle "other" values
        if ($request->make_id === '__other__' || $request->make_id === null) {
            $data['make_id'] = null;
        }
        if ($request->model_id === '__other__' || $request->model_id === null) {
            $data['model_id'] = null;
        }

        $vehicle = Vehicle::create($data);
        
        // Record initial mileage log if provided
        if ($request->odometer > 0) {
            $vehicle->mileageLogs()->create([
                'mileage' => $request->odometer,
                'previous_mileage' => 0,
                'difference' => $request->odometer,
                'recorded_at' => now(),
                'created_by' => $user->id,
            ]);
        }

        // Load customer relationship for frontend
        $vehicle->load('customer');

        return back()->with('vehicle', $vehicle);
    }

    public function show(Vehicle $vehicle)
    {
        $this->authorize('view', $vehicle);

        // Load relationships
        $vehicle->load(['customer', 'make', 'model']);

        // Get all related data
        $workOrders = $vehicle->workOrders()->with(['vehicle.make', 'vehicle.model'])->latest()->get();
        $quotes = $vehicle->quotes()->with(['vehicle.make', 'vehicle.model'])->latest()->get();

        // Count related data
        $counts = [
            'workOrders' => $workOrders->count(),
            'quotes' => $quotes->count(),
            'mileageLogs' => $vehicle->mileageLogs()->count(),
            'invoices' => 0, // Placeholder
            'payments' => 0, // Placeholder
        ];

        // Check if can be deleted (protected if has history)
        $canDelete = $counts['quotes'] === 0 && $counts['workOrders'] === 0;

        // Get form data for modals (reusing logic from Index/CustomerController)
        $makes = VehicleMake::ordered()->get(['id', 'name_ar', 'name_en']);
        $colors = VehicleColor::active()->ordered()->get(['id', 'name_ar', 'name_en', 'hex_code']);
        $departments = Department::where('is_active', true)->orderBy('sort_order')->get();
        $services = Service::where('is_active', true)->with('department')->orderBy('sort_order')->get();
        
        // Build modelsByMake map
        $modelsByMake = [];
        foreach ($makes as $make) {
            $modelsByMake[$make->id] = $make->models()->ordered()->get(['id', 'name_ar', 'name_en']);
        }

        return Inertia::render('Vehicles/Show', [
            'vehicle' => $vehicle,
            'customer' => $vehicle->customer, // Pass explicitly for convenience
            'counts' => $counts,
            'canDelete' => $canDelete,
            'workOrders' => $workOrders,
            'quotes' => $quotes,
            'makes' => $makes,
            'colors' => $colors,
            'modelsByMake' => $modelsByMake,
            'departments' => $departments,
            'services' => $services,
        ]);
    }

    public function update(VehicleUpdateRequest $request, Vehicle $vehicle)
    {
        $this->authorize('update', $vehicle);

        $data = $request->validated();

        // Handle "other" values
        if ($request->make_id === '__other__' || $request->make_id === null) {
            $data['make_id'] = null;
        }
        if ($request->model_id === '__other__' || $request->model_id === null) {
            $data['model_id'] = null;
        }

        $oldOdometer = (int)$vehicle->odometer;
        $vehicle->update($data);

        // Record mileage log if odometer changed manually
        $newOdometer = (int)$request->odometer;
        if ($newOdometer !== $oldOdometer && $newOdometer > 0) {
            $vehicle->mileageLogs()->create([
                'mileage' => $newOdometer,
                'previous_mileage' => $oldOdometer,
                'difference' => $newOdometer - $oldOdometer,
                'recorded_at' => now(),
                'created_by' => auth()->id(),
            ]);
        }

        return back()->with('success', 'Vehicle updated successfully.');
    }

    public function destroy(Vehicle $vehicle)
    {
        $this->authorize('delete', $vehicle);

        $vehicle->delete();

        return back()->with('success', 'Vehicle deleted successfully.');
    }
}
