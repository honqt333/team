<?php

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

// Update overdue installments - runs daily at midnight
Schedule::command(\App\Console\Commands\UpdateOverdueInstallments::class)
    ->daily()
    ->withoutOverlapping();
