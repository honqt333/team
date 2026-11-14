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

// Phase 10 — appointment reminders.
//
// Why hourly: the reminder windows are 60 min wide
// (24h = [now+23h, now+25h], 2h = [now+1.5h, now+2.5h]).
// Hourly cadence catches every appointment, and the
// unique index on (appointment_id, message_type)
// prevents a second tick from re-sending.
//
// Why not 30 min: the windows already overlap on the
// boundary, so a 30-min cadence is redundant. We
// prefer hourly to keep the cost low for tenants
// with no upcoming appointments.
//
// Why withoutOverlapping: a slow tick (e.g. 1000s of
// appointments due) must not run twice in parallel;
// the unique index dedupes but two writers racing
// is wasteful.
Schedule::command('appointments:send-reminders')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/appointment-reminders.log'));

// HR proactive document & contract expiry alerts (60, 30, 7 days) - runs daily at 8 AM
Schedule::command('hr:send-expiry-alerts')
    ->dailyAt('08:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/hr-expiry-alerts.log'));

// Phase 10 recovery — every 10 minutes, look for
// rows stuck in `sending` (worker died mid-job) and
// reset them to `queued` so a future scan can pick
// them up. The threshold is 5 minutes: any worker
// that has been holding a row for less than that is
// still alive and we must not race it. The recovery
// is also capped by the `attempts` column to prevent
// an infinite-retry loop on a row that has reached
// its retry budget.
Schedule::command('appointments:recover-stuck --stale-after=300')
    ->everyTenMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/appointment-recover.log'));
