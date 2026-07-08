<?php

namespace App\Http\Controllers\App\WorkOrders;

use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Models\WorkOrderItemNote;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkOrderNotesController
{
    use AuthorizesRequests;

    /**
     * Add note to item.
     */
    public function addNote(Request $request, WorkOrder $work_order, WorkOrderItem $item): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $work_order);

        $validated = $request->validate([
            'content' => 'required|string|max:2000',
        ]);

        $note = $item->itemNotes()->create([
            'user_id' => $request->user()->id,
            'content' => $validated['content'],
            'work_order_id' => $work_order->id,
        ]);

        $note->load('user');

        $message = __('messages.note_added');
        
        $notes = $item->itemNotes()->with('user.roles')->latest()->get();
        
        return $request->wantsJson() && !$request->hasHeader('X-Inertia')
            ? response()->json(['success' => $message, 'notes' => $notes])
            : redirect()->back()->with('success', $message);
    }

    /**
     * Delete note from item.
     */
    public function deleteNote(WorkOrder $work_order, WorkOrderItem $item, WorkOrderItemNote $note): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $work_order);

        $note->delete();

        $message = __('messages.note_deleted');
        
        $notes = $item->itemNotes()->with('user.roles')->latest()->get();
        
        return request()->wantsJson() && !request()->hasHeader('X-Inertia')
            ? response()->json(['success' => $message, 'notes' => $notes])
            : redirect()->back()->with('success', $message);
    }

    /**
     * Add general note to work order.
     */
    public function addGeneralNote(Request $request, WorkOrder $work_order): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $work_order);

        $validated = $request->validate([
            'content' => 'required|string|max:2000',
        ]);

        $note = $work_order->generalNotes()->create([
            'user_id' => $request->user()->id,
            'content' => $validated['content'],
            'work_order_item_id' => null,
        ]);

        $note->load('user');

        $message = __('messages.note_added');
        return $request->wantsJson() && !$request->hasHeader('X-Inertia')
            ? response()->json(['success' => $message, 'note' => $note])
            : redirect()->back()->with('success', $message);
    }

    /**
     * Delete general note.
     */
    public function deleteGeneralNote(WorkOrder $work_order, WorkOrderItemNote $note): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $work_order);

        $note->delete();

        $message = __('messages.note_deleted');
        return request()->wantsJson() && !request()->hasHeader('X-Inertia')
            ? response()->json(['success' => $message])
            : redirect()->back()->with('success', $message);
    }
}
