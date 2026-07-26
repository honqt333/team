<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\InternalNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Admin-side notification endpoints — powers the bell icon in
 * SystemLayout.vue. Mirrors the App\NotificationController API surface
 * but reads from the admin user (no tenant context) instead of the
 * tenant user.
 *
 * Polling-only design: the front end calls /unread-count every 30s and
 * /recent on demand. If/when a real-time layer is needed (Laravel Echo
 * + Pusher), the endpoints stay the same and the client just switches
 * to a WebSocket subscription.
 *
 * The `admin_id` column on `internal_notifications` (added in
 * 2026_07_26_210000) lets us reuse the existing notifications table
 * for both tenants and admins — the user_id+tenant_id columns stay
 * required for tenant-side notifications while admin notifications
 * leave them NULL and identify the recipient via admin_id.
 */
class AdminNotificationController extends Controller
{
    /**
     * Full-page notification list (for the "View all" link from the
     * dropdown). Paginated for performance.
     */
    public function index(Request $request)
    {
        $adminId = auth('admin')->id();

        $notifications = InternalNotification::query()
            ->where('admin_id', $adminId)
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('System/Notifications/Index', [
            'notifications' => $notifications,
        ]);
    }

    /**
     * Returns the 20 most recent notifications for the authenticated
     * admin, used to populate the bell dropdown.
     */
    public function recent(Request $request): JsonResponse
    {
        $adminId = auth('admin')->id();

        $notifications = InternalNotification::query()
            ->where('admin_id', $adminId)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return response()->json([
            'notifications' => $notifications,
        ]);
    }

    /**
     * Returns just the unread count. Tiny payload — the front end
     * polls this every 30s for the bell badge.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $adminId = auth('admin')->id();

        $count = InternalNotification::query()
            ->where('admin_id', $adminId)
            ->whereNull('read_at')
            ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Mark a single notification as read. Idempotent — calling twice
     * is a no-op (the model checks read_at before updating).
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $adminId = auth('admin')->id();

        $notification = InternalNotification::query()
            ->where('admin_id', $adminId)
            ->where('id', $id)
            ->firstOrFail();

        $notification->markAsRead();

        return response()->json(['success' => true]);
    }

    /**
     * Mark every notification for the admin as read. Useful from the
     * "Mark all read" button in the dropdown header.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $adminId = auth('admin')->id();

        $count = InternalNotification::query()
            ->where('admin_id', $adminId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['success' => true, 'count' => $count]);
    }
}
