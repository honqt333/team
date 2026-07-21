<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\TenantBackup;
use App\Services\Backup\TenantBackupService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Schedule a backup of every active tenant.
 *
 * Designed to be cron-friendly: idempotent, resumable, and bounded
 * (won't overlap with itself because the service sets the row to
 * 'running' before it starts).
 *
 * Usage:
 *   php artisan tenants:backup --all
 *   php artisan tenants:backup --tenant=42
 *   php artisan tenants:backup --active-only
 *
 * Recommended schedule: weekly, off-peak hours.
 */
class BackupTenantsCommand extends Command
{
    protected $signature = 'tenants:backup
                            {--all : Back up every tenant regardless of status}
                            {--active-only : Only back up active / trialing tenants}
                            {--tenant= : Back up a single tenant by id}';

    protected $description = 'Run backups for tenants (manual, scheduled, or all)';

    public function handle(TenantBackupService $service): int
    {
        if ($tenantId = $this->option('tenant')) {
            $tenants = Tenant::where('id', $tenantId)->get();
        } elseif ($this->option('active-only')) {
            $tenants = Tenant::whereIn('status', ['active', 'trial'])->get();
        } elseif ($this->option('all')) {
            $tenants = Tenant::all();
        } else {
            // Default: tenants due for a backup. The 7-day threshold is
            // a soft default — operators can run --all to override.
            $tenants = Tenant::all()->filter(function (Tenant $tenant) {
                $latest = $tenant->backups()
                    ->where('status', TenantBackup::STATUS_COMPLETED)
                    ->latest('completed_at')
                    ->first();

                return ! $latest || $latest->completed_at?->lt(now()->subDays(7));
            });
        }

        if ($tenants->isEmpty()) {
            $this->info('No tenants match the criteria.');

            return self::SUCCESS;
        }

        $this->info("Backing up {$tenants->count()} tenant(s)...");

        $success = 0;
        $failed = 0;

        foreach ($tenants as $tenant) {
            $name = sprintf(
                'Scheduled backup @ %s',
                now()->format('Y-m-d H:i')
            );

            try {
                $backup = $service->createBackup(
                    $tenant,
                    $name,
                    TenantBackup::TYPE_SCHEDULED
                );
                $this->line("  ✓ #{$backup->id} tenant={$tenant->id} size={$backup->humanSize()}");
                $success++;
            } catch (Throwable $e) {
                $this->error("  ✗ tenant={$tenant->id} error={$e->getMessage()}");
                $failed++;
            }
        }

        $this->info("Done. success={$success} failed={$failed}");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
