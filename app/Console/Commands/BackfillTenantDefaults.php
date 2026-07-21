<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\TenantSetupService;
use Illuminate\Console\Command;
use Throwable;

class BackfillTenantDefaults extends Command
{
    /**
     * Usage:
     *   php artisan tenants:backfill-defaults
     *   php artisan tenants:backfill-defaults --tenant=42
     *   php artisan tenants:backfill-defaults --dry-run
     */
    protected $signature = 'tenants:backfill-defaults
                            {--tenant= : Process only this tenant ID}
                            {--dry-run : Report what would happen without writing}';

    protected $description = 'Seed default roles, permissions, and lookup data (units, employee types, job titles) for existing tenants.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $onlyId = $this->option('tenant');

        $query = Tenant::query();

        if ($onlyId) {
            $query->where('id', (int) $onlyId);
        }

        $tenants = $query->get();

        if ($tenants->isEmpty()) {
            $this->info('No tenants to process.');

            return self::SUCCESS;
        }

        $service = app(TenantSetupService::class);
        $this->info(sprintf(
            'Processing %d tenant(s)%s',
            $tenants->count(),
            $dryRun ? ' (dry run)' : ''
        ));

        $processed = 0;
        $errors = 0;

        foreach ($tenants as $tenant) {
            $this->line("  • [{$tenant->id}] {$tenant->name}");

            if ($dryRun) {
                $processed++;
                continue;
            }

            try {
                $service->seedRolesForTenant($tenant->id);
                $service->seedDefaultsForTenant($tenant->id);
                $processed++;
            } catch (Throwable $e) {
                $this->error("    ✗ Failed: {$e->getMessage()}");
                $errors++;
            }
        }

        $this->newLine();
        $this->info(sprintf(
            'Done. Processed: %d | Errors: %d | Total: %d',
            $processed,
            $errors,
            $tenants->count()
        ));

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
