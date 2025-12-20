<?php

namespace App\Support;

use App\Models\User;

class TenancyContext
{
    /**
     * Get the currently authenticated user.
     */
    public static function user(): ?User
    {
        return auth()->user();
    }

    /**
     * Get the current tenant ID from the authenticated user.
     */
    public static function tenantId(): ?int
    {
        $user = self::user();

        return $user?->tenant_id;
    }

    /**
     * Get the current center ID from the authenticated user.
     */
    public static function centerId(): ?int
    {
        $user = self::user();

        return $user?->current_center_id;
    }
}
