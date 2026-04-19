<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VehicleMileageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, Vehicle $vehicle)
    {
        $this->authorize('view', $vehicle);

        $logs = $vehicle->mileageLogs()
            ->with(['creator:id,name', 'reference'])
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->paginate(20);

        // Transform collection to add readable reference info if needed
        $logs->through(function ($log) {
            return [
                'id' => $log->id,
                'recorded_at' => $log->recorded_at,
                'mileage' => $log->mileage,
                'difference' => $log->difference,
                'reference_code' => $log->reference_code,
                'creator' => $log->creator,
                'reference_type' => class_basename($log->reference_type),
                'reference_id' => $log->reference_id,
            ];
        });

        if ($request->wantsJson()) {
            return response()->json($logs);
        }

        return back()->with('mileageLogs', $logs);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Vehicle $vehicle, $logId)
    {
        $this->authorize('update', $vehicle);

        $log = $vehicle->mileageLogs()->findOrFail($logId);

        $log->delete();

        return response()->json([
            'message' => 'Mileage log deleted successfully',
            'odometer' => $vehicle->odometer
        ]);
    }
}
