<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Billing\Plan;
use App\Models\Center;
use App\Models\HR\EmployeeDocument;
use App\Models\Tenant;
use App\Models\WorkOrderAttachment;
use App\Models\WorkOrderPhoto;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class TenantStorageService
{
    /**
     * Default storage allocation in GB if not specified by plan.
     */
    public const DEFAULT_STORAGE_GB = 10;

    /**
     * Cache TTL in seconds (1 hour).
     */
    public const CACHE_TTL = 3600;

    /**
     * Get allocated storage limit in bytes for a given tenant.
     */
    public static function getAllocatedStorageBytes(Tenant $tenant): int
    {
        $allocatedGb = self::getAllocatedStorageGb($tenant);

        if ($allocatedGb >= 999) {
            // Unlimited plan tier
            return PHP_INT_MAX;
        }

        return $allocatedGb * 1024 * 1024 * 1024;
    }

    /**
     * Get allocated storage limit in GB for a given tenant.
     */
    public static function getAllocatedStorageGb(Tenant $tenant): int
    {
        $plan = $tenant->currentSubscription?->plan ?? $tenant->latestSubscription?->plan;

        if ($plan instanceof Plan) {
            $limit = $plan->getLimit('storage_gb');

            if ($limit !== null) {
                return (int) $limit;
            }

            // Check if unlimited
            $maxUsers = $plan->getLimit('max_users', $plan->getLimit('users'));
            $maxCenters = $plan->getLimit('max_centers', $plan->getLimit('centers'));

            if ($maxUsers >= 999 && $maxCenters >= 999) {
                return 999;
            }
        }

        return self::DEFAULT_STORAGE_GB;
    }

    /**
     * Calculate total used storage in bytes by scanning tenant attachments and directories.
     */
    public static function getUsedStorageBytes(Tenant $tenant, bool $forceRefresh = false): int
    {
        $cacheKey = "tenant_storage_bytes_{$tenant->id}";

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return (int) Cache::remember($cacheKey, self::CACHE_TTL, function () use ($tenant) {
            $totalBytes = 0;
            $publicDisk = Storage::disk('public');
            $localDisk = Storage::disk('local');

            // 1. Work Order Attachments
            $attachmentSizes = WorkOrderAttachment::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->sum('file_size');

            if ($attachmentSizes > 0) {
                $totalBytes += (int) $attachmentSizes;
            } else {
                // If file_size is 0 or null, check disk size
                $attachments = WorkOrderAttachment::withoutGlobalScopes()
                    ->where('tenant_id', $tenant->id)
                    ->pluck('path');

                foreach ($attachments as $path) {
                    if ($path && $publicDisk->exists($path)) {
                        $totalBytes += $publicDisk->size($path);
                    }
                }
            }

            // 2. Work Order Photos
            $photos = WorkOrderPhoto::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->pluck('path');

            foreach ($photos as $path) {
                if ($path && $publicDisk->exists($path)) {
                    $totalBytes += $publicDisk->size($path);
                }
            }

            // 3. Employee Documents
            $employeeDocs = EmployeeDocument::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->pluck('file_path');

            foreach ($employeeDocs as $path) {
                if ($path && $localDisk->exists($path)) {
                    $totalBytes += $localDisk->size($path);
                } elseif ($path && $publicDisk->exists($path)) {
                    $totalBytes += $publicDisk->size($path);
                }
            }

            // 4. Center Logos & Stamps
            $centerIds = Center::where('tenant_id', $tenant->id)->pluck('id');

            foreach ($centerIds as $centerId) {
                $logos = $publicDisk->allFiles("centers/{$centerId}");

                foreach ($logos as $file) {
                    $totalBytes += $publicDisk->size($file);
                }
            }

            // 5. Bank Transfer Receipts
            if ($localDisk->exists("bank-transfer-receipts/{$tenant->id}")) {
                $receiptFiles = $localDisk->allFiles("bank-transfer-receipts/{$tenant->id}");

                foreach ($receiptFiles as $file) {
                    $totalBytes += $localDisk->size($file);
                }
            }

            // 6. Dedicated tenant directory
            if ($publicDisk->exists("tenants/{$tenant->id}")) {
                $tenantFiles = $publicDisk->allFiles("tenants/{$tenant->id}");

                foreach ($tenantFiles as $file) {
                    $totalBytes += $publicDisk->size($file);
                }
            }

            return $totalBytes;
        });
    }

    /**
     * Get a comprehensive summary of storage quota and usage.
     *
     * @return array<string, mixed>
     */
    public static function getStorageUsageSummary(Tenant $tenant, bool $forceRefresh = false): array
    {
        $usedBytes = self::getUsedStorageBytes($tenant, $forceRefresh);
        $allocatedGb = self::getAllocatedStorageGb($tenant);
        $isUnlimited = $allocatedGb >= 999;
        $allocatedBytes = $isUnlimited ? PHP_INT_MAX : ($allocatedGb * 1024 * 1024 * 1024);

        $usedMb = round($usedBytes / (1024 * 1024), 2);
        $usedGb = round($usedBytes / (1024 * 1024 * 1024), 3);

        $usagePercentage = 0.0;

        if (! $isUnlimited && $allocatedBytes > 0) {
            $usagePercentage = min(100.0, round(($usedBytes / $allocatedBytes) * 100, 1));
        }

        $remainingBytes = $isUnlimited ? PHP_INT_MAX : max(0, $allocatedBytes - $usedBytes);
        $remainingGb = $isUnlimited ? 999.0 : round($remainingBytes / (1024 * 1024 * 1024), 2);

        return [
            'used_bytes' => $usedBytes,
            'used_mb' => $usedMb,
            'used_gb' => $usedGb,
            'allocated_gb' => $allocatedGb,
            'allocated_bytes' => $allocatedBytes,
            'remaining_bytes' => $remainingBytes,
            'remaining_gb' => $remainingGb,
            'usage_percentage' => $usagePercentage,
            'is_exceeded' => ! $isUnlimited && $usedBytes > $allocatedBytes,
            'is_unlimited' => $isUnlimited,
        ];
    }

    /**
     * Check if tenant has sufficient storage for additional bytes.
     */
    public static function hasSufficientStorage(Tenant $tenant, int $additionalBytes = 0): bool
    {
        $allocatedBytes = self::getAllocatedStorageBytes($tenant);

        if ($allocatedBytes === PHP_INT_MAX) {
            return true;
        }

        $usedBytes = self::getUsedStorageBytes($tenant);

        return ($usedBytes + $additionalBytes) <= $allocatedBytes;
    }

    /**
     * Clear cached storage calculation for a tenant.
     */
    public static function clearCache(int $tenantId): void
    {
        Cache::forget("tenant_storage_bytes_{$tenantId}");
    }
}
