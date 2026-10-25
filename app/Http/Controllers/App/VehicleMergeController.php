<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Actions\Vehicle\MergeVehicleAction;
use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class VehicleMergeController extends Controller
{
    use AuthorizesRequests;

    /**
     * Get merge data for a vehicle (source and potential target vehicles).
     */
    public function mergeData(Vehicle $vehicle): JsonResponse
    {
        $this->authorize('update', $vehicle);

        $vehicle->load(['customer', 'make', 'model']);

        $user = auth()->user();

        // Get other vehicles belonging to the same customer in this tenant
        $query = Vehicle::withoutGlobalScope('center_scoped')
            ->where('tenant_id', $user->tenant_id)
            ->where('customer_id', $vehicle->customer_id)
            ->where('id', '!=', $vehicle->id)
            ->with(['customer', 'make', 'model'])
            ->withCount(['workOrders', 'quotes'])
            ->latest();

        $otherVehicles = $query->limit(50)->get();

        return response()->json([
            'source' => [
                'id' => $vehicle->id,
                'plate_number' => $vehicle->plate_number,
                'make_name' => $vehicle->display_make,
                'model_name' => $vehicle->display_model,
                'year' => $vehicle->year,
                'vin' => $vehicle->vin,
                'odometer' => $vehicle->odometer,
                'customer_id' => $vehicle->customer_id,
                'customer_name' => $vehicle->customer?->name,
                'customer_phone' => $vehicle->customer?->phone,
                'work_orders_count' => $vehicle->workOrders()->withoutGlobalScope('center_scoped')->count(),
                'quotes_count' => $vehicle->quotes()->withoutGlobalScope('center_scoped')->count(),
            ],
            'targets' => $otherVehicles->map(fn ($v) => [
                'id' => $v->id,
                'plate_number' => $v->plate_number,
                'make_name' => $v->display_make,
                'model_name' => $v->display_model,
                'year' => $v->year,
                'vin' => $v->vin,
                'odometer' => $v->odometer,
                'customer_id' => $v->customer_id,
                'customer_name' => $v->customer?->name,
                'customer_phone' => $v->customer?->phone,
                'work_orders_count' => $v->work_orders_count,
                'quotes_count' => $v->quotes_count,
                'is_same_customer' => $v->customer_id === $vehicle->customer_id,
                'is_same_make' => $this->areSameMake($vehicle, $v),
            ]),
        ]);
    }

    /**
     * Execute merge of two vehicles.
     */
    public function executeMerge(Vehicle $source, Vehicle $target, MergeVehicleAction $action): RedirectResponse
    {
        $this->authorize('update', $source);
        $this->authorize('update', $target);

        if ($source->id === $target->id) {
            return redirect()->back()->with('error', __('vehicles.merge.cannot_merge_self'));
        }

        if ($source->tenant_id !== $target->tenant_id) {
            abort(403);
        }

        if ($source->customer_id !== $target->customer_id) {
            return redirect()->back()->with('error', __('vehicles.merge.different_customers_error'));
        }

        if (! $this->areSameMake($source, $target)) {
            return redirect()->back()->with('error', __('vehicles.merge.different_makes_error'));
        }

        $action->execute($source, $target);

        return redirect()->route('vehicles.show', $target)->with('success', __('vehicles.merge.success'));
    }

    /**
     * Determine if two vehicles belong to the same make/brand.
     */
    public function areSameMake(Vehicle $source, Vehicle $target): bool
    {
        if ($source->make_id && $target->make_id) {
            return (int) $source->make_id === (int) $target->make_id;
        }

        $sourceMake = mb_strtolower(trim((string) ($source->display_make ?: $source->make_other ?: '')));
        $targetMake = mb_strtolower(trim((string) ($target->display_make ?: $target->make_other ?: '')));

        if ($sourceMake !== '' && $targetMake !== '') {
            return $sourceMake === $targetMake;
        }

        return $sourceMake === '' && $targetMake === '';
    }
}
