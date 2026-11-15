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
        if (! self::isInAppAllowed($tenantId, $type)) {
            return null;
        }

        return self::createNotification(
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
        $uniqueUserIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));

        if ($uniqueUserIds === [] || ! self::isInAppAllowed($tenantId, $type)) {
            return;
        }

        foreach ($uniqueUserIds as $userId) {
            self::createNotification(
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
     * Notify all active users in a tenant (optionally scoped to a center plus super_admins).
     */
    public static function notifyTenantUsers(
        int $tenantId,
        string $type,
        string $title,
        ?string $body = null,
        ?string $actionUrl = null,
        ?int $actorId = null,
        ?string $icon = null,
        ?array $data = null,
        ?int $centerId = null,
    ): void {
        if (! self::isInAppAllowed($tenantId, $type)) {
            return;
        }

        $query = User::where('tenant_id', $tenantId)
            ->where('is_active', true);

        if ($centerId !== null) {
            $query->where(function ($q) use ($centerId) {
                $q->whereHas('roles', fn ($r) => $r->where('name', 'super_admin')->where('guard_name', 'web'))
                    ->orWhere('current_center_id', $centerId)
                    ->orWhereHas('centers', fn ($c) => $c->where('centers.id', $centerId))
                    ->orWhere(function ($unassigned) {
                        $unassigned->whereNull('current_center_id')
                            ->whereDoesntHave('centers');
                    });
            });
        }

        $userIds = $query->pluck('id')->all();

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

    private static function isInAppAllowed(int $tenantId, string $type): bool
    {
        if ($type === 'system_test') {
            return true;
        }

        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            return true;
        }

        $settings = $tenant->getEffectiveNotificationSettings();

        if (isset($settings['channels']['in_app']) && ! $settings['channels']['in_app']) {
            return false;
        }

        foreach (Tenant::notificationEventKeysFor($type) as $eventKey) {
            if (isset($settings['events'][$eventKey]['in_app']) && ! $settings['events'][$eventKey]['in_app']) {
                return false;
            }
        }

        return true;
    }

    private static function createNotification(
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
