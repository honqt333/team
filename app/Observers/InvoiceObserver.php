<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Invoice;
use Exception;

class InvoiceObserver
{
    public function updating(Invoice $invoice): void
    {
        if ($invoice->isDirty('status')) {
            return; // Allow status transitions
        }

        // Allow updates to payment-related fields (total_paid, payment_status)
        $dirty = array_keys($invoice->getDirty());
        $allowed = ['total_paid', 'payment_status'];

        if (empty(array_diff($dirty, $allowed))) {
            return;
        }

        if ($invoice->getOriginal('status') !== 'draft') {
            // Allow only specific fields like 'zatca_uuid' if needed during reporting
            // But generally block user edits.
            throw new Exception("Cannot edit invoice in '{$invoice->getOriginal('status')}' status.");
        }
    }

    public function deleting(Invoice $invoice): void
    {
        if ($invoice->status !== 'draft') {
            throw new Exception("Cannot delete invoice in '{$invoice->status}' status. Use Credit/Debit note.");
        }
    }

    public function forceDeleting(Invoice $invoice): void
    {
        if ($invoice->status !== 'draft') {
            throw new Exception("Cannot force delete invoice in '{$invoice->status}' status.");
        }
    }
}
