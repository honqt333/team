<?php

namespace App\Services;

use App\Models\InternalNotification;
use App\Models\User;

class NotificationService
{
    /**
     * Send a notification to a specific user.
     */
    public static function notify(
        int $tenantId,
        int $userId,
        string $type,
        string $title,
        ?string $body = null,
        ?string $actionUrl = null,
        ?int $actorId = null,
        ?string $icon = null,
        ?array $data = null,
    ): InternalNotification {
        return InternalNotification::create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'actor_id' => $actorId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'icon' => $icon ?? self::getIconForType($type),
            'action_url' => $actionUrl,
            'data' => $data,
        ]);
    }

    /**
     * Send a notification to multiple users.
     */
    public static function notifyMany(
        int $tenantId,
        array $userIds,
        string $type,
        string $title,
        ?string $body = null,
        ?string $actionUrl = null,
        ?int $actorId = null,
        ?string $icon = null,
        ?array $data = null,
    ): void {
        foreach ($userIds as $userId) {
            self::notify(
                tenantId: $tenantId,
                userId: $userId,
                type: $type,
                title: $title,
                body: $body,
                actionUrl: $actionUrl,
                actorId: $actorId,
                icon: $icon,
                data: $data,
            );
        }
    }

    /**
     * Notify all users with a specific role in a tenant.
     */
    public static function notifyRole(
        int $tenantId,
        string $roleName,
        string $type,
        string $title,
        ?string $body = null,
        ?string $actionUrl = null,
        ?int $actorId = null,
        ?string $icon = null,
        ?array $data = null,
    ): void {
        $userIds = User::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->role($roleName)
            ->pluck('id')
            ->toArray();

        self::notifyMany(
            tenantId: $tenantId,
            userIds: $userIds,
            type: $type,
            title: $title,
            body: $body,
            actionUrl: $actionUrl,
            actorId: $actorId,
            icon: $icon,
            data: $data,
        );
    }

    /**
     * Notify the tenant owner (super_admin).
     */
    public static function notifyOwner(
        int $tenantId,
        string $type,
        string $title,
        ?string $body = null,
        ?string $actionUrl = null,
        ?int $actorId = null,
        ?string $icon = null,
        ?array $data = null,
    ): void {
        self::notifyRole(
            tenantId: $tenantId,
            roleName: 'super_admin',
            type: $type,
            title: $title,
            body: $body,
            actionUrl: $actionUrl,
            actorId: $actorId,
            icon: $icon,
            data: $data,
        );
    }

    /**
     * Get default icon for notification type.
     */
    private static function getIconForType(string $type): string
    {
        return match (true) {
            str_starts_with($type, 'work_order') => 'clipboard',
            str_starts_with($type, 'invoice') => 'receipt',
            str_starts_with($type, 'quote') => 'document',
            str_starts_with($type, 'payment') => 'cash',
            str_starts_with($type, 'customer') => 'users',
            default => 'bell',
        };
    }
}
