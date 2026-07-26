<?php

declare(strict_types=1);

namespace App\Events\Billing;

use App\Models\BankTransferReceipt;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a tenant uploads a new bank transfer receipt.
 *
 * The listener (NotifyAdminsOfReceipt) turns this into an internal
 * notification for every system admin so the receipt review queue
 * gets attention without the admin having to poll the page.
 */
class ReceiptSubmitted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly BankTransferReceipt $receipt,
    ) {}
}
