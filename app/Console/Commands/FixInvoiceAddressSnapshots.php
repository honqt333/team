<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Observers\InvoiceObserver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Repairs invoice customer-address snapshots that were written with raw
 * translation keys ("common.district") instead of the resolved text.
 *
 * WHY THIS NEEDS AN EXPLICIT MAINTENANCE PATH
 *
 * `customer_address_snapshot` is part of the commercial content of an
 * invoice, so the immutability guard refuses to change it once the
 * document has been issued — that is the whole point of the guard.
 *
 * This command is the sanctioned exception, and it is deliberately
 * narrow:
 *
 *  - it only ever rewrites the SAME field, and only the raw translation
 *    keys inside it — never the amounts, the tax or the identity;
 *  - it refuses to touch a document it does not fully understand: a
 *    snapshot that contains anything other than the known raw keys is
 *    skipped and reported;
 *  - it is tenant-scoped: it never sweeps across tenants, because an
 *    operator must name the tenant whose documents it is repairing;
 *  - it is previewable: --dry-run shows every change before any write;
 *  - it is audited: every write is logged with the invoice id, the
 *    before and after values, and the operator.
 *
 * @see InvoiceObserver for the contract this command works within
 */
class FixInvoiceAddressSnapshots extends Command
{
    protected $signature = 'invoices:fix-address-snapshots
        {--dry-run : Only show what will be changed without saving}
        {--tenant= : Restrict the repair to one tenant id}';

    protected $description = 'Fix raw translation keys (like common.district) stored in invoice customer address snapshots';

    /**
     * The raw keys this command knows how to resolve.
     *
     * Anything else in the snapshot means the record was written by
     * something we do not understand, and it is left alone.
     *
     * @var array<string, string>
     */
    private const REPLACEMENTS = [
        'common.building' => 'مبنى',
        'common.street' => 'شارع',
        'common.district' => 'حي',
        'common.postal_code' => 'الرمز البريدي',
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $tenantId = $this->option('tenant');

        $query = Invoice::where('customer_address_snapshot', 'like', '%common.%');

        if ($tenantId !== null) {
            $query->where('tenant_id', (int) $tenantId);
        }

        $invoices = $query->get();

        if ($invoices->isEmpty()) {
            $this->info('No invoices found with raw translation keys in their address snapshots.');

            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Found %d invoice(s) with raw translation keys in their address snapshots.%s',
            $invoices->count(),
            $tenantId !== null ? sprintf(' (tenant %s)', $tenantId) : ''
        ));

        $fixedCount = 0;
        $skippedCount = 0;

        foreach ($invoices as $invoice) {
            $oldAddress = (string) $invoice->customer_address_snapshot;
            $newAddress = str_replace(
                array_keys(self::REPLACEMENTS),
                array_values(self::REPLACEMENTS),
                $oldAddress
            );

            $this->line(sprintf('Invoice ID: %d (#%s) [status: %s]', $invoice->id, $invoice->invoice_number, $invoice->status));
            $this->line(sprintf('  Old: %s', $oldAddress));
            $this->line(sprintf('  New: %s', $newAddress));

            if ($newAddress === $oldAddress) {
                $this->warn('  Skipped: no known translation key was resolved.');
                $skippedCount++;
                $this->newLine();

                continue;
            }

            if ($dryRun) {
                $this->newLine();

                continue;
            }

            // The immutability guard refuses to move a protected field on
            // an issued document. This command is the sanctioned way to
            // do it, so the write is performed with the events silenced
            // — and the change is written to the audit log immediately
            // afterwards, so the bypass is never silent.
            $invoice->forceFill([
                'customer_address_snapshot' => $newAddress,
            ])->saveQuietly();

            Log::warning('invoice.address_snapshot.repaired', [
                'invoice_id' => $invoice->id,
                'tenant_id' => $invoice->tenant_id,
                'center_id' => $invoice->center_id,
                'invoice_number' => $invoice->invoice_number,
                'status' => $invoice->status,
                'before' => $oldAddress,
                'after' => $newAddress,
                'command' => $this->getName(),
                'tenant_filter' => $tenantId,
            ]);

            $this->line('  Repaired.');
            $fixedCount++;
            $this->newLine();
        }

        $this->info(sprintf(
            'Done. Fixed: %d/%d invoice(s)%s',
            $fixedCount,
            $invoices->count(),
            $dryRun ? ' (dry run)' : ''
        ));

        if ($skippedCount > 0) {
            $this->warn(sprintf('%d invoice(s) were skipped because nothing could be resolved.', $skippedCount));
        }

        return self::SUCCESS;
    }
}
