<?php

namespace App\Http\Controllers\App;

use App\Http\Requests\VehicleStoreRequest;
use App\Http\Requests\VehicleUpdateRequest;
use App\Models\Customer;
use App\Models\Vehicle;
use App\Models\VehicleMake;
use App\Models\VehicleColor;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VehicleController
{
    use AuthorizesRequests;

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
        
        // Load customer relationship for frontend
        $vehicle->load('customer');

        return back()->with('vehicle', $vehicle);
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

        $vehicle->update($data);

        return back()->with('success', 'Vehicle updated successfully.');
    }

    public function destroy(Vehicle $vehicle)
    {
        $this->authorize('delete', $vehicle);

        $vehicle->delete();

        return back()->with('success', 'Vehicle deleted successfully.');
    }
}
