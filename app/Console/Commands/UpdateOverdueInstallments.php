<?php

namespace App\Console\Commands;

use App\Services\Billing\InstallmentService;
use Illuminate\Console\Command;

class UpdateOverdueInstallments extends Command
{
    protected $signature = 'installments:update-overdue';
    
    protected $description = 'Mark overdue installments as overdue';

    public function handle(InstallmentService $installmentService): int
    {
        $count = $installmentService->updateOverdueInstallments();
        
        $this->info("Updated {$count} overdue installments.");
        
        return self::SUCCESS;
    }
}
