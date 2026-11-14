<?php

declare(strict_types=1);

namespace App\Console\Commands\HR;

use App\Services\HR\DocumentExpiryAlertService;
use Illuminate\Console\Command;

class SendExpiryAlertsCommand extends Command
{
    protected $signature = 'hr:send-expiry-alerts {--tenant= : Optional tenant ID to scope scan}';

    protected $description = 'Send proactive internal alerts for employee documents and contracts expiring in 60, 30, or 7 days';

    public function handle(DocumentExpiryAlertService $service): int
    {
        $tenantOpt = $this->option('tenant');
        $tenantId = $tenantOpt !== null ? (int) $tenantOpt : null;

        $sent = $service->dispatchAlerts($tenantId);

        $this->info("Dispatched {$sent} HR expiry alert notification(s).");

        return self::SUCCESS;
    }
}
