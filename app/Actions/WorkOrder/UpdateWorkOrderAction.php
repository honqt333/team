<?php

namespace App\Actions\WorkOrder;

use App\Models\WorkOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UpdateWorkOrderAction
{
    public function execute(WorkOrder $workOrder, User $user, array $data): WorkOrder
    {
        return DB::transaction(function () use ($workOrder, $user, $data) {
            // Update main work order fields
            $workOrder->update([
                'customer_id' => $data['customer_id'] ?? $workOrder->customer_id,
                'vehicle_id' => $data['vehicle_id'] ?? $workOrder->vehicle_id,
                'status' => $data['status'] ?? $workOrder->status,
                'notes' => $data['notes'] ?? $workOrder->notes,
                // New Fields
                'customer_complaint' => $data['customer_complaint'] ?? $workOrder->customer_complaint,
                'initial_assessment' => $data['initial_assessment'] ?? $workOrder->initial_assessment,
                'mileage' => $data['mileage'] ?? $workOrder->mileage,
                'contact_name' => $data['contact_name'] ?? $workOrder->contact_name,
                'contact_phone' => $data['contact_phone'] ?? $workOrder->contact_phone,
                'entry_date' => $data['entry_date'] ?? $workOrder->entry_date,
                'expected_end_date' => $data['expected_end_date'] ?? $workOrder->expected_end_date,
            ]);

            // Handle status transitions
            if (isset($data['status'])) {
                if ($data['status'] === WorkOrder::STATUS_DONE && !$workOrder->closed_at) {
                    $workOrder->update(['closed_at' => now()]);
                }
            }
            
            // Sync departments
            if (isset($data['departments'])) {
                $workOrder->departments()->sync($data['departments']);
            }

            // Sync damage marks
            if (isset($data['damage_marks'])) {
                $workOrder->damageMarks()->delete();
                foreach ($data['damage_marks'] as $mark) {
                    $workOrder->damageMarks()->create([
                        'x' => $mark['x'],
                        'y' => $mark['y'],
                        'color' => $mark['color'] ?? 'red',
                        'description' => $mark['description'] ?? null,
                    ]);
                }
            }

            // Update items if provided
            if (isset($data['items'])) {
                // Delete existing items and recreate
                $workOrder->items()->delete();

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
            
            // Handle photos
            if (isset($data['photos'])) {
                $this->syncPhotos($workOrder, $data['photos']);
            }

            return $workOrder;
        });
    }
    
    private function syncPhotos(WorkOrder $workOrder, array $photos): void
    {
        // 1. Identify kept photos (those with 'id')
        $keepIds = [];
        foreach ($photos as $meta) {
            if (isset($meta['id'])) {
                $keepIds[] = $meta['id'];
                // Update caption/type if changed
                $photo = $workOrder->photos()->find($meta['id']);
                if ($photo) {
                    $photo->update([
                        'caption' => $meta['caption'] ?? null,
                        'type' => $meta['type'] ?? 'general',
                    ]);
                }
            }
        }

        // 2. Delete removed photos
        $photosToDelete = $workOrder->photos()->whereNotIn('id', $keepIds)->get();
        foreach ($photosToDelete as $photo) {
            Storage::disk('public')->delete($photo->path);
            $photo->delete();
        }

        // 3. Add new photos
        foreach ($photos as $photoData) {
            // Check for new file upload
            if (isset($photoData['file']) && $photoData['file'] instanceof \Illuminate\Http\UploadedFile) {
                $file = $photoData['file'];
                $path = $file->store('work-orders/' . $workOrder->id, 'public');
                
                $workOrder->photos()->create([
                    'path' => $path,
                    'type' => $photoData['type'] ?? 'general',
                    'caption' => $photoData['caption'] ?? null,
                ]);
            }
        }
    }
}
