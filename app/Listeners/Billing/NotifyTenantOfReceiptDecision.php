<?php

declare(strict_types=1);

namespace App\Listeners\Billing;

use App\Events\Billing\ReceiptReviewed;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

/**
 * Notifies the tenant's primary user (first owner-level user) that
 * their receipt was approved or rejected. We fan out to every user
 * attached to the tenant so multi-user accounts (e.g. owner + staff)
 * all see the decision in their bell — the cost is one INSERT per
 * user which is fine for the typical 1-5 users per tenant.
 *
 * If the tenant has no users (shouldn't happen but is a defensive
 * guard), the event is logged and dropped.
 *
 * These ARE tenant notifications, so they go through NotificationService
 * and therefore honour the tenant's notification settings. Previously this
 * listener wrote InternalNotification rows directly, which meant a tenant
 * who had turned in-app notifications off still received the receipt
 * decision in their bell.
 */
class NotifyTenantOfReceiptDecision
{
    public function handle(ReceiptReviewed $event): void
    {
        $receipt = $event->receipt;

        if (! $receipt->tenant_id) {
            Log::warning('ReceiptReviewed without tenant_id', [
                'receipt_id' => $receipt->id,
            ]);

            return;
        }

        $users = User::where('tenant_id', $receipt->tenant_id)->get();

        if ($users->isEmpty()) {
            Log::warning('ReceiptReviewed but tenant has no users to notify', [
                'receipt_id' => $receipt->id,
                'tenant_id' => $receipt->tenant_id,
            ]);

            return;
        }

        $isApproved = $event->decision === 'approved';
        $title = $isApproved
            ? 'Receipt approved — subscription activated'
            : 'Receipt rejected';
        $body = $isApproved
            ? sprintf(
                'Your bank transfer of %s SAR was approved. Your subscription is now active.',
                number_format((float) $receipt->transfer_amount, 2)
            )
            : sprintf(
                'Your bank transfer of %s SAR was rejected. %s',
                number_format((float) $receipt->transfer_amount, 2),
                $event->notes ? "Reason: {$event->notes}" : 'Please review and resubmit.'
            );

        foreach ($users as $user) {
            NotificationService::notify(
                tenantId: $receipt->tenant_id,
                userId: $user->id,
                type: $isApproved
                    ? 'bank_transfer.receipt.approved'
                    : 'bank_transfer.receipt.rejected',
                title: $title,
                body: $body,
                actionUrl: '/app/settings/billing',
                icon: $isApproved ? 'check' : 'x',
                data: [
                    'receipt_id' => $receipt->id,
                    'tenant_id' => $receipt->tenant_id,
                    'decision' => $event->decision,
                    'notes' => $event->notes,
                ],
            );
        }
    }
}
