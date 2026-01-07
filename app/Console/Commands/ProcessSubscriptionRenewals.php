<?php

namespace App\Console\Commands;

use App\Services\Billing\SubscriptionRenewalService;
use Illuminate\Console\Command;

class ProcessSubscriptionRenewals extends Command
{
    protected $signature = 'subscriptions:process-renewals';
    
    protected $description = 'Process subscription renewals, send reminders, and expire overdue subscriptions';

    public function handle(SubscriptionRenewalService $renewalService): int
    {
        $this->info('Processing subscription renewals...');
        
        $results = $renewalService->processRenewals();
        
        $this->info("✓ Renewed: {$results['renewed']}");
        $this->info("✓ Reminders sent: {$results['reminders_sent']}");
        $this->info("✓ Expired: {$results['expired']}");
        
        if (!empty($results['errors'])) {
            $this->warn("⚠ Errors: " . count($results['errors']));
            foreach ($results['errors'] as $error) {
                $this->error("  - Subscription #{$error['subscription_id']}: {$error['message']}");
            }
        }
        
        $this->info('Done!');
        
        return self::SUCCESS;
    }
}
