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

// Auto-suspend tenants — runs at 7:30 AM, AFTER dunning:send-reminders
// so the fresh tier_4 logs are visible. Grace window: 7 days after
// the last tier_4 reminder (configurable in dunning_configs).
Schedule::command('dunning:auto-suspend')
    ->dailyAt('07:30')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/dunning-auto-suspend.log'));

// Tenant backups - weekly on Sundays at 2 AM
Schedule::command('tenants:backup --active-only')
    ->weekly()
    ->sundays()
    ->at('02:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/tenant-backups.log'));
