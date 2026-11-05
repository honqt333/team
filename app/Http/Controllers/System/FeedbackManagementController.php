<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class FeedbackManagementController extends Controller
{
    /**
     * Display a listing of all tenant feedbacks for system admins.
     */
    public function index(Request $request): Response
    {
        $feedbacks = Feedback::withoutGlobalScopes()
            ->with([
                'tenant:id,name,trade_name',
                'user:id,name,email,phone',
                'adminUser:id,name',
            ])
            ->when($request->search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('comments', 'like', "%{$search}%")
                        ->orWhere('admin_reply', 'like', "%{$search}%")
                        ->orWhereHas('tenant', function ($t) use ($search) {
                            $t->where('name', 'like', "%{$search}%")
                                ->orWhere('trade_name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->type, fn ($q, $type) => $q->where('type', $type))
            ->when($request->module, fn ($q, $module) => $q->where('module', $module))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total' => Feedback::withoutGlobalScopes()->count(),
            'pending' => Feedback::withoutGlobalScopes()->where('status', Feedback::STATUS_PENDING)->count(),
            'in_progress' => Feedback::withoutGlobalScopes()->where('status', Feedback::STATUS_IN_PROGRESS)->count(),
            'resolved' => Feedback::withoutGlobalScopes()->where('status', Feedback::STATUS_RESOLVED)->count(),
            'bugs' => Feedback::withoutGlobalScopes()->where('type', Feedback::TYPE_BUG)->count(),
        ];

        return Inertia::render('System/Feedbacks/Index', [
            'feedbacks' => $feedbacks,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status', 'type', 'module']),
        ]);
    }

    /**
     * Reply to a feedback with admin comments and optional response video.
     */
    public function reply(Request $request, Feedback $feedback): RedirectResponse
    {
        $validated = $request->validate([
            'admin_reply' => ['required', 'string', 'max:2000'],
            'status' => ['required', 'string', 'in:pending,in_progress,resolved,closed'],
            'reply_video' => ['nullable', 'file', 'mimes:webm,mp4,ogg,mkv', 'max:51200'],
        ]);

        $replyVideoPath = $feedback->admin_reply_video_path;

        if ($request->hasFile('reply_video')) {
            if ($replyVideoPath && Storage::disk('public')->exists($replyVideoPath)) {
                Storage::disk('public')->delete($replyVideoPath);
            }

            $file = $request->file('reply_video');
            $extension = $file->getClientOriginalExtension() ?: 'webm';
            $fileName = 'reply_'.Str::uuid().'.'.$extension;
            $replyVideoPath = $file->storeAs("system/feedbacks/{$feedback->id}", $fileName, 'public');
        }

        $feedback->update([
            'admin_reply' => $validated['admin_reply'],
            'status' => $validated['status'],
            'admin_reply_video_path' => $replyVideoPath,
            'admin_replied_at' => now(),
            'admin_user_id' => $request->user()->id,
            'has_user_seen_reply' => false,
        ]);

        return back()->with('success', __('messages.system.feedbacks.replied'));
    }

    /**
     * Quick status update.
     */
    public function updateStatus(Request $request, Feedback $feedback): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:pending,in_progress,resolved,closed'],
        ]);

        $feedback->update(['status' => $validated['status']]);

        return back()->with('success', __('messages.system.feedbacks.status_updated'));
    }

    /**
     * Delete feedback from system administration.
     */
    public function destroy(Feedback $feedback): RedirectResponse
    {
        if ($feedback->video_path && Storage::disk('public')->exists($feedback->video_path)) {
            Storage::disk('public')->delete($feedback->video_path);
        }

        if ($feedback->admin_reply_video_path && Storage::disk('public')->exists($feedback->admin_reply_video_path)) {
            Storage::disk('public')->delete($feedback->admin_reply_video_path);
        }

        $feedback->delete();

        return back()->with('success', __('messages.system.feedbacks.deleted_success'));
    }
}
