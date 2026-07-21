<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Center;
use App\Models\Warehouse;
use Illuminate\Support\Str;

class CenterObserver
{
    /**
     * Handle the Center "created" event.
     *
     * Every new center automatically gets a default warehouse
     * named after the center. No manual UI action required.
     */
    public function created(Center $center): void
    {
        $this->ensureDefaultWarehouse($center);
    }

    /**
     * Handle the Center "updated" event.
     *
     * Sync the default warehouse's name when the center's display name changes,
     * so the warehouse name stays consistent with the branch.
     */
    public function updated(Center $center): void
    {
        if ($center->wasChanged(['name', 'name_ar', 'name_en'])) {
            $default = $center->warehouses()->where('is_default', true)->first();

            if ($default) {
                $default->update([
                    'name' => $this->resolveWarehouseName($center),
                ]);
            }
        }
    }

    /**
     * Ensure a default warehouse exists for the given center.
     * Idempotent — safe to call multiple times.
     */
    public function ensureDefaultWarehouse(Center $center): Warehouse
    {
        $existing = $center->warehouses()->where('is_default', true)->first();

        if ($existing) {
            return $existing;
        }

        return Warehouse::create([
            'center_id' => $center->id,
            'tenant_id' => $center->tenant_id,
            'name' => $this->resolveWarehouseName($center),
            'code' => $this->generateUniqueCode($center),
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    /**
     * Build the warehouse name from the center's localized names.
     * Falls back to "مستودع {center name}" if no localized suffix is set.
     */
    protected function resolveWarehouseName(Center $center): string
    {
        $baseName = $center->name_ar ?? $center->name_en ?? $center->name ?? 'الفرع';

        // Avoid duplicating the suffix if the user already typed it
        if (str_contains($baseName, 'مستودع')) {
            return $baseName;
        }

        return "مستودع {$baseName}";
    }

    /**
     * Generate a unique short code (max 20 chars) for the warehouse.
     * Format: WH-{center-slug-suffix} (uppercased, alphanumeric only).
     */
    protected function generateUniqueCode(Center $center): string
    {
        $base = strtoupper(Str::slug($center->name_en ?? $center->name ?? 'WH'));
        $base = preg_replace('/[^A-Z0-9]/', '', $base) ?: 'WH';
        $base = substr($base, 0, 14);

        $code = "WH-{$base}";
        $suffix = '';

        // Ensure uniqueness across the warehouses table
        while (Warehouse::withTrashed()->where('code', $code.$suffix)->exists()) {
            $suffix = '-'.strtoupper(Str::random(3));
        }

        return $code.$suffix;
    }
}
