<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\InternalNotification;
use App\Models\SystemAnnouncement;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationController extends Controller
{
    /**
     * Full page listing of all notifications.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $notifications = InternalNotification::query()
            ->forUser($user->id)
            ->where('tenant_id', $user->tenant_id)
            ->with('actor:id,name,photo_path')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
        ]);
    }

    /**
     * API: Get recent notifications for the dropdown bell.
     */
    public function apiIndex(Request $request)
    {
        $user = $request->user();

        $notifications = InternalNotification::query()
            ->forUser($user->id)
            ->where('tenant_id', $user->tenant_id)
            ->with('actor:id,name,photo_path')
            ->latest()
            ->limit(15)
            ->get();

        // Also fetch system announcements to mix into the bell dropdown
        $systemAnnouncements = SystemAnnouncement::forTenant($user->tenant_id)
            ->whereDoesntHave('reads', fn($q) => $q->where('tenant_id', $user->tenant_id))
            ->latest('published_at')
            ->limit(5)
            ->get()
            ->map(function ($ann) {
                return [
                    'id' => $ann->id,
                    'is_system' => true,
                    'title' => $ann->title,
                    'body' => strip_tags($ann->content),
                    'icon' => 'information-circle',
                    'read_at' => null,
                    'created_at' => $ann->published_at,
                    'action_url' => null,
                ];
            });

        // Merge and sort
        $all = collect($notifications)->concat($systemAnnouncements)
            ->sortByDesc('created_at')
            ->values()
            ->take(15);

        return response()->json([
            'notifications' => $all,
        ]);
    }

    /**
     * API: Get the unread notification count (used by polling).
     */
    public function unreadCount(Request $request)
    {
        $user = $request->user();

        $internalCount = InternalNotification::query()
            ->forUser($user->id)
            ->where('tenant_id', $user->tenant_id)
            ->unread()
            ->count();

        $systemCount = SystemAnnouncement::forTenant($user->tenant_id)
            ->whereDoesntHave('reads', fn($q) => $q->where('tenant_id', $user->tenant_id))
            ->count();

        return response()->json(['count' => $internalCount + $systemCount]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(Request $request, string $id)
    {
        $user = $request->user();

        $notification = InternalNotification::query()
            ->forUser($user->id)
            ->where('tenant_id', $user->tenant_id)
            ->findOrFail($id);

        $notification->markAsRead();

        // If there's an action URL, redirect to it
        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        if ($notification->action_url) {
            return redirect($notification->action_url);
        }

        return back();
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request)
    {
        $user = $request->user();

        InternalNotification::query()
            ->forUser($user->id)
            ->where('tenant_id', $user->tenant_id)
            ->unread()
            ->update(['read_at' => now()]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back();
    }

    /**
     * Dismiss a system announcement (mark as read for the tenant).
     */
    public function dismissAnnouncement(Request $request, int $announcement)
    {
        $user = $request->user();
        $tenantId = $user->tenant_id;

        $ann = SystemAnnouncement::findOrFail($announcement);
        $ann->markAsReadBy($tenantId);

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back();
    }
}
