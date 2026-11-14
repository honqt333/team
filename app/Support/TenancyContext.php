<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;

class TenancyContext
{
    private static ?int $overrideTenantId = null;

    private static ?int $overrideCenterId = null;

    private static bool $hasOverride = false;

    /**
     * Explicitly set tenant and center context (e.g. inside queued jobs, console commands, or tests).
     */
    public static function set(?int $tenantId, ?int $centerId = null): void
    {
        self::$overrideTenantId = $tenantId;
        self::$overrideCenterId = $centerId;
        self::$hasOverride = true;
    }

    /**
     * Clear any manual tenant/center context override.
     */
    public static function clear(): void
    {
        self::$overrideTenantId = null;
        self::$overrideCenterId = null;
        self::$hasOverride = false;
    }

    /**
     * Execute a callback within an explicit tenant/center context and restore the previous context afterwards.
     *
     * @template T
     *
     * @param callable(): T $callback
     * @return T
     */
    public static function runForTenant(?int $tenantId, ?int $centerId, callable $callback): mixed
    {
        $prevHasOverride = self::$hasOverride;
        $prevTenant = self::$overrideTenantId;
        $prevCenter = self::$overrideCenterId;

        self::set($tenantId, $centerId);

        try {
            return $callback();
        } finally {
            self::$hasOverride = $prevHasOverride;
            self::$overrideTenantId = $prevTenant;
            self::$overrideCenterId = $prevCenter;
        }
    }

    /**
     * Whether the current request is targeting the system administration panel or public routes.
     */
    public static function isSystemOrPublicContext(): bool
    {
        if (auth('admin')->check()) {
            return true;
        }

        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return true;
        }

        if (! app()->bound('request')) {
            return false;
        }

        $request = request();

        return $request->is('system/*')
            || $request->is('system')
            || $request->is('portal/*')
            || $request->is('site/*');
    }

    /**
     * Get the currently authenticated user.
     */
    public static function user(): ?User
    {
        return auth()->user();
    }

    /**
     * Get the current tenant ID from the explicit override or authenticated user.
     */
    public static function tenantId(): ?int
    {
        if (self::$hasOverride) {
            return self::$overrideTenantId;
        }

        if (request()->is('system/*') || request()->is('system')) {
            return null;
        }

        $user = self::user();

        return $user?->tenant_id;
    }

    /**
     * Get the current center ID from the explicit override or authenticated user.
     */
    public static function centerId(): ?int
    {
        if (self::$hasOverride) {
            return self::$overrideCenterId;
        }

        $user = self::user();

        return $user?->current_center_id;
    }
}
