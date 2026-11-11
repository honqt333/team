<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\InternalNotification;
use App\Models\Tenant;
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
    ): ?InternalNotification {
        // Respect tenant in-app notification preference if configured (bypass for test notifications)
        if ($type !== 'system_test') {
            $tenant = Tenant::find($tenantId);

            if ($tenant) {
                $settings = $tenant->getEffectiveNotificationSettings();

                if (isset($settings['channels']['in_app']) && ! $settings['channels']['in_app']) {
                    return null;
                }

                // The notification TYPE (`work_order.created`) and the settings
                // EVENT KEY (`work_order_created`) live in two different
                // namespaces. Tenant::notificationEventKeysFor() maps between
                // them, so a per-event toggle on the settings page actually
                // applies to the notifications this service writes.
                foreach (Tenant::notificationEventKeysFor($type) as $eventKey) {
                    if (isset($settings['events'][$eventKey]['in_app']) && ! $settings['events'][$eventKey]['in_app']) {
                        return null;
                    }
                }
            }
        }

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
        // Use raw query instead of ->role() to avoid RoleDoesNotExist
        // when the role exists but the spatie permission cache is stale
        // or the team/guard context isn't set.
        $userIds = User::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereHas('roles', function ($q) use ($roleName) {
                $q->where('name', $roleName)->where('guard_name', 'web');
            })
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
