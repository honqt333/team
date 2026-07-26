<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Subscription renewals - runs daily at 6 AM
Schedule::command('subscriptions:process-renewals')
    ->dailyAt('06:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/subscription-renewals.log'));

// Dunning reminders - runs daily at 7 AM, AFTER renewals so a
// freshly-renewed invoice (status=paid) is excluded from the scan.
Schedule::command('dunning:send-reminders')
    ->dailyAt('07:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/dunning-reminders.log'));

// Tenant backups - weekly on Sundays at 2 AM
Schedule::command('tenants:backup --active-only')
    ->weekly()
    ->sundays()
    ->at('02:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/tenant-backups.log'));
