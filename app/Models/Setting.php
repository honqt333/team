<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// @bypass-tenancy-scanner - System-wide key/value store (smtp config etc.)
class Setting extends Model
{
    protected $fillable = ['tenant_id', 'key', 'value', 'group'];

    /**
     * Get setting value by key.
     *
     * This reads the platform-wide row only. A tenant-specific row
     * with the same key belongs to that tenant and must be read with
     * `forTenant()`.
     */
    public static function get(string $key, $default = null)
    {
        $setting = static::whereNull('tenant_id')->where('key', $key)->first();

        return $setting ? $setting->value : $default;
    }

    /**
     * Set setting value by key.
     *
     * This writes the platform-wide row only, so a tenant-specific row
     * with the same key is never overwritten.
     */
    public static function set(string $key, $value, string $group = 'system'): void
    {
        static::updateOrCreate(
            ['tenant_id' => null, 'key' => $key],
            ['value' => $value, 'group' => $group]
        );
    }

    /**
     * Read a setting for one tenant, falling back to the system-wide
     * value when the tenant has never set its own.
     *
     * A row with a NULL tenant_id is the platform default; a row with
     * a tenant_id belongs to that tenant alone.
     */
    public static function forTenant(?int $tenantId, string $key, $default = null)
    {
        if ($tenantId !== null) {
            $own = static::where('tenant_id', $tenantId)->where('key', $key)->first();

            if ($own) {
                return $own->value;
            }
        }

        $global = static::whereNull('tenant_id')->where('key', $key)->first();

        return $global ? $global->value : $default;
    }

    /**
     * Write a setting for one tenant, leaving the system-wide default
     * untouched.
     */
    public static function setForTenant(int $tenantId, string $key, $value, string $group = 'system'): void
    {
        static::updateOrCreate(
            ['tenant_id' => $tenantId, 'key' => $key],
            ['value' => $value, 'group' => $group]
        );
    }
}
