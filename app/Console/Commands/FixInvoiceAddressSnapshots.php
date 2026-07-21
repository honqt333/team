<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Invoice;
use Illuminate\Console\Command;

class FixInvoiceAddressSnapshots extends Command
{
    protected $signature = 'invoices:fix-address-snapshots {--dry-run : Only show what will be changed without saving}';

    protected $description = 'Fix raw translation keys (like common.district) stored in invoice customer address snapshots';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $invoices = Invoice::where('customer_address_snapshot', 'like', '%common.%')->get();

        if ($invoices->isEmpty()) {
            $this->info('No invoices found with raw translation keys in their address snapshots.');

            return self::SUCCESS;
        }

        $this->info(sprintf('Found %d invoice(s) with raw translation keys in their address snapshots.', $invoices->count()));

        $replacements = [
            'common.building' => 'مبنى',
            'common.street' => 'شارع',
            'common.district' => 'حي',
            'common.postal_code' => 'الرمز البريدي',
        ];

        $fixedCount = 0;

        foreach ($invoices as $invoice) {
            $oldAddress = $invoice->customer_address_snapshot;
            $newAddress = str_replace(array_keys($replacements), array_values($replacements), $oldAddress);

            $this->line(sprintf('Invoice ID: %d (#%s)', $invoice->id, $invoice->invoice_number));
            $this->line(sprintf('  Old: %s', $oldAddress));
            $this->line(sprintf('  New: %s', $newAddress));

            if (! $dryRun) {
                $invoice->update([
                    'customer_address_snapshot' => $newAddress,
                ]);
                $fixedCount++;
            }
            $this->newLine();
        }

        $this->info(sprintf('Done. Fixed: %d/%d invoice(s)%s', $fixedCount, $invoices->count(), $dryRun ? ' (dry run)' : ''));

        return self::SUCCESS;
    }
}
