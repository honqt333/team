<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Billing\TenantSuspensionService;
use Illuminate\Console\Command;

/**
 * Phase 7.7: Auto-suspend tenants that hit tier_4 dunning AND
 * didn't pay within the configured grace period (default 7 days
 * after the last tier_4 reminder).
 *
 * Run via: php artisan dunning:auto-suspend
 *
 * Schedule: routes/console.php registers this daily AFTER
 * SendDunningReminders at 07:00. Order matters: the reminder scan
 * needs to write fresh tier_4 logs first, THEN this command
 * evaluates them.
 */
class DunningAutoSuspend extends Command
{
    protected $signature = 'dunning:auto-suspend';

    protected $description = 'Auto-suspend tenants whose tier_4 dunning reminders expired without payment';

    public function handle(TenantSuspensionService $service): int
    {
        $this->info('Scanning for tenants eligible for auto-suspension...');

        $summary = $service->runForAllTenants();

        $this->info("✓ Suspended: {$summary['suspended']}");
        $this->info("↪ Skipped (already paid): {$summary['skipped_paid']}");
        $this->info("↪ Skipped (feature disabled): {$summary['skipped_disabled']}");

        if ($summary['errors'] > 0) {
            $this->warn("⚠ Errors: {$summary['errors']}");
        }

        $this->info('Done!');

        return self::SUCCESS;
    }
}
