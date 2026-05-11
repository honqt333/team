<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ContactMessageController extends Controller
{
    /**
     * Display a listing of the contact messages.
     */
    public function index(Request $request)
    {
        $messages = ContactMessage::query()
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('subject', 'like', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('System/ContactMessages/Index', [
            'messages' => $messages,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Mark a message as read.
     */
    public function markAsRead(ContactMessage $contactMessage)
    {
        $contactMessage->update(['is_read' => true]);
        
        return back()->with('success', 'تم تحديث حالة الرسالة بنجاح.');
    }

    /**
     * Remove the specified message from storage.
     */
    public function destroy(ContactMessage $contactMessage)
    {
        $contactMessage->delete();

        return back()->with('success', 'تم حذف الرسالة بنجاح.');
    }
}
