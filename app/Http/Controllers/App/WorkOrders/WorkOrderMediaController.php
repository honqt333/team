<?php

namespace App\Http\Controllers\App\WorkOrders;

use App\Models\WorkOrder;
use App\Models\WorkOrderAttachment;
use App\Models\WorkOrderPhoto;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class WorkOrderMediaController
{
    use AuthorizesRequests;

    /**
     * Upload photos to work order.
     */
    public function uploadPhotos(Request $request, WorkOrder $workOrder): mixed
    {
        $this->authorize('update', $workOrder);

        $request->validate([
            'photos' => 'required|array',
            'photos.*.file' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
            'photos.*.type' => 'nullable|string|in:general,before,after,damage',
            'photos.*.caption' => 'nullable|string|max:255',
        ]);

        foreach ($request->file('photos') as $index => $photoFile) {
            $file = $photoFile['file'];
            $type = $request->input("photos.$index.type") ?? 'general';
            $caption = $request->input("photos.$index.caption");

            $path = $file->store('work-orders/' . $workOrder->id . '/photos', 'public');

            $workOrder->photos()->create([
                'path' => $path,
                'type' => $type,
                'caption' => $caption,
            ]);
        }

        $workOrder->logActivity('photos_uploaded', __('work_orders.activities.actions.photos_uploaded'));

        return back()->with('success', __('messages.photos_uploaded_successfully'));
    }

    /**
     * Delete a photo from work order.
     */
    public function deletePhoto(WorkOrder $workOrder, WorkOrderPhoto $photo): mixed
    {
        $this->authorize('update', $workOrder);

        if ($photo->work_order_id !== $workOrder->id) {
            abort(403);
        }

        Storage::disk('public')->delete($photo->path);
        $photo->delete();

        return back()->with('success', __('messages.photo_deleted'));
    }

    /**
     * Upload attachments to work order.
     */
    public function uploadAttachments(Request $request, WorkOrder $workOrder): mixed
    {
        $this->authorize('update', $workOrder);

        $request->validate([
            'attachments' => 'required|array',
            'attachments.*' => 'required|file|mimes:pdf,jpg,png|max:1024',
        ]);

        foreach ($request->file('attachments') as $file) {
            $path = $file->store('work-orders/' . $workOrder->id . '/attachments', 'public');

            $workOrder->attachments()->create([
                'tenant_id' => $workOrder->tenant_id,
                'file_name' => $file->getClientOriginalName(),
                'path' => $path,
                'file_type' => $file->getClientOriginalExtension(),
                'file_size' => $file->getSize(),
                'user_id' => auth()->id(),
            ]);
        }

        $workOrder->logActivity('attachments_uploaded', __('work_orders.activities.actions.attachments_uploaded'));

        return back()->with('success', __('messages.attachments_uploaded_successfully'));
    }

    /**
     * Delete an attachment.
     */
    public function destroyAttachment(WorkOrder $workOrder, WorkOrderAttachment $attachment): mixed
    {
        $this->authorize('update', $workOrder);

        if ($attachment->work_order_id !== $workOrder->id) {
            abort(403);
        }

        Storage::disk('public')->delete($attachment->path);
        $attachment->delete();

        return back()->with('success', __('messages.attachment_deleted'));
    }
}
