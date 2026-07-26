<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Billing\DunningService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Daily cron command — finds overdue invoices that have crossed
 * a dunning tier threshold and dispatches the matching reminder
 * channels.
 *
 * Run via: php artisan dunning:send-reminders
 *
 * Schedule: routes/console.php registers this at 07:00 daily,
 * AFTER the subscription renewals command at 06:00. Renewals
 * happen first so a fresh renewal can mark a newly-issued
 * invoice as `paid` before the dunning scan sees it.
 */
class SendDunningReminders extends Command
{
    protected $signature = 'dunning:send-reminders';

    protected $description = 'Scan overdue invoices and send tier-based dunning reminders';

    public function handle(DunningService $dunning): int
    {
        $this->info('Scanning overdue invoices for dunning reminders...');

        // Ensure the global default config row exists on first run
        // after a fresh install. After that it's a no-op.
        $dunning->ensureGlobalDefault();

        $candidates = $dunning->findCandidates();
        $this->info('Found '.count($candidates).' overdue invoice(s) at active tiers.');

        $totalSent = 0;
        $totalSkipped = 0;
        $totalFailed = 0;
        $errors = [];

        foreach ($candidates as $candidate) {
            $invoice = $candidate['invoice'];
            $tierKey = $candidate['tier_key'];

            try {
                $sent = $dunning->sendTier($invoice, $tierKey, $candidate['config']);
                $totalSent += $sent;
            } catch (Throwable $e) {
                $totalFailed++;
                $errors[] = [
                    'invoice_id' => $invoice->id,
                    'tier' => $tierKey,
                    'message' => $e->getMessage(),
                ];
            }
        }

        $this->info("✓ Channels sent: {$totalSent}");
        $this->info("✓ Failed: {$totalFailed}");

        if (! empty($errors)) {
            $this->warn('⚠ Errors:');

            foreach ($errors as $error) {
                $this->error("  - Invoice #{$error['invoice_id']} ({$error['tier']}): {$error['message']}");
            }
        }

        $this->info('Done!');

        return self::SUCCESS;
    }
}
