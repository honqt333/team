<?php

namespace App\Console\Commands;

use App\Models\Center;
use App\Observers\CenterObserver;
use Illuminate\Console\Command;

class BackfillCenterWarehouses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Usage:
     *   php artisan centers:backfill-warehouses
     *   php artisan centers:backfill-warehouses --dry-run
     *   php artisan centers:backfill-warehouses --center=42
     */
    protected $signature = 'centers:backfill-warehouses
                            {--dry-run : Report what would be created without writing}
                            {--center= : Process only this center ID}';

    /**
     * The console command description.
     */
    protected $description = 'Create default warehouses for centers that do not have one yet.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $onlyId = $this->option('center');

        $query = Center::query()
            ->whereDoesntHave('warehouses', function ($q) {
                $q->where('is_default', true);
            });

        if ($onlyId) {
            $query->where('id', (int) $onlyId);
        }

        $centers = $query->with('warehouses')->get();

        if ($centers->isEmpty()) {
            $this->info('✓ All centers already have a default warehouse.');
            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Found %d center(s) without a default warehouse%s.',
            $centers->count(),
            $dryRun ? ' (dry run)' : ''
        ));

        $observer = app(CenterObserver::class);
        $created = 0;
        $skipped = 0;

        foreach ($centers as $center) {
            if ($dryRun) {
                $this->line("  [DRY] would create warehouse for center [{$center->id}] {$center->name}");
                $created++;
                continue;
            }

            try {
                $warehouse = $observer->ensureDefaultWarehouse($center);
                $this->line("  ✓ Created warehouse [{$warehouse->id}] \"{$warehouse->name}\" for center [{$center->id}] {$center->name}");
                $created++;
            } catch (\Throwable $e) {
                $this->error("  ✗ Failed for center [{$center->id}] {$center->name}: {$e->getMessage()}");
                $skipped++;
            }
        }

        $this->newLine();
        $this->info(sprintf(
            'Done. Created: %d | Skipped: %d | Total: %d',
            $created,
            $skipped,
            $centers->count()
        ));

        return $skipped > 0 ? self::FAILURE : self::SUCCESS;
    }
}
