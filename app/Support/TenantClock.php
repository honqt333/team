<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Tenant;
use Carbon\Carbon;
use DateTimeZone;

class TenantClock
{
    /**
     * Resolve a valid IANA timezone for the given (or current) tenant,
     * falling back to the application timezone (UTC) when unset or invalid.
     */
    public static function resolveTimezone(?Tenant $tenant = null): string
    {
        if ($tenant === null) {
            $tenantId = TenancyContext::tenantId();
            $tenant = $tenantId ? Tenant::find($tenantId) : null;
        }
        $timezone = $tenant?->timezone;

        if (is_string($timezone) && $timezone !== '' && in_array($timezone, DateTimeZone::listIdentifiers(), true)) {
            return $timezone;
        }

        return (string) config('app.timezone', 'UTC');
    }

    /**
     * Current wall-clock Carbon instance in the tenant's timezone.
     */
    public static function now(?Tenant $tenant = null): Carbon
    {
        return Carbon::now(self::resolveTimezone($tenant));
    }

    /**
     * Start of the current local day in the tenant's timezone.
     */
    public static function today(?Tenant $tenant = null): Carbon
    {
        return self::now($tenant)->startOfDay();
    }

    /**
     * Current local date string (Y-m-d) in the tenant's timezone.
     */
    public static function todayDateString(?Tenant $tenant = null): string
    {
        return self::today($tenant)->toDateString();
    }

    /**
     * Parse a date input in the tenant's timezone, or default to the tenant's local today.
     */
    public static function parseDate(?string $dateInput, ?Tenant $tenant = null): Carbon
    {
        $timezone = self::resolveTimezone($tenant);

        if ($dateInput !== null && trim($dateInput) !== '') {
            return Carbon::parse($dateInput, $timezone)->startOfDay();
        }

        return self::today($tenant);
    }
}
