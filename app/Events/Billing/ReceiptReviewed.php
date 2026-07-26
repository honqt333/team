<?php

declare(strict_types=1);

namespace App\Events\Billing;

use App\Models\BankTransferReceipt;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when an admin approves or rejects a receipt. The listener
 * (NotifyTenantOfReceiptDecision) creates a notification for the
 * tenant's primary contact so they see the outcome without
 * having to refresh the billing page.
 *
 * `decision` is either 'approved' or 'rejected' — kept as a plain
 * string to avoid coupling the event to the model's STATUS_*
 * constants which are the model's internal vocabulary.
 */
class ReceiptReviewed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly BankTransferReceipt $receipt,
        public readonly string $decision,
        public readonly ?string $notes = null,
    ) {}
}
