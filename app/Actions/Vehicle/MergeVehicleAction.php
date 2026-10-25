<?php

declare(strict_types=1);

namespace App\Actions\Vehicle;

use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;

class MergeVehicleAction
{
    /**
     * Merge source vehicle into target vehicle.
     * All related data from source will be transferred to target, then source is deleted.
     *
     * @param Vehicle $source The vehicle to merge FROM (will be deleted)
     * @param Vehicle $target The vehicle to merge TO (will remain)
     * @return Vehicle The target vehicle with merged data
     */
    public function execute(Vehicle $source, Vehicle $target): Vehicle
    {
        return DB::transaction(function () use ($source, $target) {
            // Transfer work orders
            DB::table('work_orders')
                ->where('vehicle_id', $source->id)
                ->update(['vehicle_id' => $target->id]);

            // Transfer quotes
            DB::table('quotes')
                ->where('vehicle_id', $source->id)
                ->update(['vehicle_id' => $target->id]);

            // Transfer mileage logs
            DB::table('vehicle_mileage_logs')
                ->where('vehicle_id', $source->id)
                ->update(['vehicle_id' => $target->id]);

            // Transfer appointments if table exists
            if (DB::getSchemaBuilder()->hasTable('appointments')) {
                DB::table('appointments')
                    ->where('vehicle_id', $source->id)
                    ->update(['vehicle_id' => $target->id]);
            }

            // Keep the higher odometer reading
            if (($source->odometer ?? 0) > ($target->odometer ?? 0)) {
                $target->odometer = $source->odometer;
            }

            // If target VIN is empty but source has one, copy it
            if (empty($target->vin) && ! empty($source->vin)) {
                $target->vin = $source->vin;
            }

            // If target year is empty but source has one, copy it
            if (empty($target->year) && ! empty($source->year)) {
                $target->year = $source->year;
            }

            // If target color is empty but source has one, copy it
            if (empty($target->color) && ! empty($source->color)) {
                $target->color = $source->color;
            }

            $target->save();

            // Soft-delete source vehicle
            $source->delete();

            // Refresh target to get updated counts
            $target->refresh();

            return $target;
        });
    }
}
