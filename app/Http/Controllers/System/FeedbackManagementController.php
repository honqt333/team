<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FeedbackManagementController extends Controller
{
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
