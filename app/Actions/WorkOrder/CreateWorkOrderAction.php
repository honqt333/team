<?php

declare(strict_types=1);

namespace App\Actions\WorkOrder;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleMileageLog;
use App\Models\WorkOrder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class CreateWorkOrderAction
{
    public function execute(User $user, array $data): WorkOrder
    {
        return DB::transaction(function () use ($user, $data) {
            // Generate sequential code with locking
            $code = WorkOrder::generateCode($user->tenant_id, $user->current_center_id);

            // Create work order
            $workOrder = WorkOrder::create([
                'tenant_id' => $user->tenant_id,
                'center_id' => $user->current_center_id,
                'customer_id' => $data['customer_id'],
                'vehicle_id' => $data['vehicle_id'],
                'code' => $code,
                'status' => $data['status'] ?? WorkOrder::STATUS_OPEN,
                'notes' => $data['notes'] ?? null,
                'opened_at' => now(),
                // New Fields
                'customer_complaint' => $data['customer_complaint'] ?? null,
                'initial_assessment' => $data['initial_assessment'] ?? null,
                'odometer' => $data['odometer'] ?? null,
                'contact_name' => $data['contact_name'] ?? null,
                'contact_phone' => $data['contact_phone'] ?? null,
                'entry_date' => $data['entry_date'] ?? now()->toDateString(),
                'expected_end_date' => $data['expected_end_date'] ?? null,
            ]);

            // Create items if they exist
            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $itemData) {
                    $workOrder->items()->create([
                        'tenant_id' => $user->tenant_id,
                        'center_id' => $user->current_center_id,
                        'title' => $itemData['title'],
                        'qty' => $itemData['qty'],
                        'unit_price' => $itemData['unit_price'],
                    ]);
                }
            }

            // Attach departments (syncWithoutDetaching prevents duplicates)
            if (! empty($data['departments'])) {
                $departments = $data['departments'];
                $showPackages = false;

                if (($key = array_search('packages', $departments)) !== false) {
                    $showPackages = true;
                    unset($departments[$key]);
                    $departments = array_values($departments);
                }

                if ($showPackages) {
                    $workOrder->update(['show_packages_section' => true]);
                }

                $uniqueDepts = array_values(array_unique($departments));

                if (! empty($uniqueDepts)) {
                    $workOrder->departments()->syncWithoutDetaching($uniqueDepts);
                }
            }

            // Create damage marks
            if (! empty($data['damage_marks'])) {
                foreach ($data['damage_marks'] as $mark) {
                    $workOrder->damageMarks()->create([
                        'x' => $mark['x'],
                        'y' => $mark['y'],
                        'color' => $mark['color'] ?? 'red',
                        'description' => $mark['description'] ?? null,
                    ]);
                }
            }

            // Handle photos
            if (isset($data['photos'])) {
                $this->syncPhotos($workOrder, $data['photos']);
            }

            // Sync Odometer to Vehicle History
            if (isset($data['odometer']) && $data['odometer'] !== null) {
                $vehicle = Vehicle::find($data['vehicle_id']);

                if ($vehicle) {
                    $shouldUpdate = true;

                    // If new odometer is lower, only update if explicitly allowed
                    if ($vehicle->odometer && $data['odometer'] < $vehicle->odometer) {
                        if (! isset($data['allow_lower_odometer']) || ! $data['allow_lower_odometer']) {
                            $shouldUpdate = false;
                        }
                    }

                    if ($shouldUpdate) {
                        // Log history
                        VehicleMileageLog::create([
                            'vehicle_id' => $vehicle->id,
                            'tenant_id' => $user->tenant_id,
                            'center_id' => $user->current_center_id,
                            'reference_type' => WorkOrder::class,
                            'reference_id' => $workOrder->id,
                            'mileage' => $data['odometer'],
                            'previous_mileage' => $vehicle->odometer,
                            'difference' => $data['odometer'] - ($vehicle->odometer ?? 0),
                            'created_by' => $user->id,
                            'reference_code' => $workOrder->code,
                            'recorded_at' => now(),
                        ]);

                        // Update vehicle odometer
                        $vehicle->update(['odometer' => $data['odometer']]);
                    }
                }
            }

            $workOrder->logActivity('created', __('work_orders.activities.actions.created'));

            $workOrder->refresh();

            return $workOrder;
        });
    }

    private function syncPhotos(WorkOrder $workOrder, array $photos): void
    {
        foreach ($photos as $photoData) {
            $file = $photoData['file'] ?? null;

            if ($file instanceof UploadedFile) {
                $path = $file->store('work-orders/'.$workOrder->id, 'public');

                $workOrder->photos()->create([
                    'path' => $path,
                    'type' => $photoData['type'] ?? 'general',
                    'caption' => $photoData['caption'] ?? null,
                ]);
            }
        }
    }
}
