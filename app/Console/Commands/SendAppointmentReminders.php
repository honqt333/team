<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Messaging\AppointmentMessageReminderScanner;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Throwable;

/**
 * Phase 10 — daily/hourly cron command. Finds
 * appointments that are about to start and dispatches
 * a 24h and 2h reminder SMS for each.
 *
 *   php artisan appointments:send-reminders
 *
 * Schedule: registered in routes/console.php.
 * Frequency: hourly. The reminder windows are wide
 * enough (60 min half-width) that running once per
 * hour catches every appointment, and the unique
 * index on (appointment_id, message_type) prevents
 * double-sends.
 *
 *   - The 24h window: [now+23h, now+25h]
 *   - The 2h window:  [now+1.5h, now+2.5h]
 *
 * Running more often than the window-width is safe
 * (the unique index dedupes). Running less often is
 * also safe (the windows overlap on the next tick).
 */
class SendAppointmentReminders extends Command
{
    protected $signature = 'appointments:send-reminders
        {--now= : Override the current time (Y-m-d H:i:s) for tests}';

    protected $description = 'Dispatch 24h and 2h reminder SMSes for upcoming appointments';

    public function handle(AppointmentMessageReminderScanner $scanner): int
    {
        $nowOpt = (string) $this->option('now');
        $now = $nowOpt !== '' ? Carbon::parse($nowOpt) : null;

        try {
            $count = $scanner->scan($now);
        } catch (Throwable $e) {
            $this->error('Reminder scan failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info("Dispatched {$count} reminder(s).");

        return self::SUCCESS;
    }
}
