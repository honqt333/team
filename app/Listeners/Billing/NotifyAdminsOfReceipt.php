<?php

declare(strict_types=1);

namespace App\Listeners\Billing;

use App\Events\Billing\ReceiptSubmitted;
use App\Models\AdminUser;
use App\Models\InternalNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Pushes an InternalNotification to every active super_admin so the
 * receipt review queue gets attention in real-time. Falls back to
 * all active admins if no super_admins exist (covers edge cases
 * where the system is being bootstrapped).
 *
 * The notification points the admin at the receipt's review URL so
 * one click takes them from the bell to the approval modal — saves
 * a context switch and shortens the time-to-approval for tenants.
 *
 * NOTE — these are PLATFORM-admin notifications, not tenant
 * notifications. The admin bell belongs to the platform operator, not
 * to the tenant that uploaded the receipt, so the TENANT's notification
 * settings deliberately do NOT apply here. What they DO apply to is the
 * tenant-facing counterpart, NotifyTenantOfReceiptDecision, which now
 * goes through NotificationService.
 */
class NotifyAdminsOfReceipt
{
    public function handle(ReceiptSubmitted $event): void
    {
        $receipt = $event->receipt;

        // Pull every active super_admin. We don't use the InternalNotification
        // tenant_scoped trait here (admins are not tenant-scoped) — we write
        // the row directly with tenant_id = receipt->tenant_id so the admin
        // can still see which tenant the receipt belongs to when they open it.
        $admins = AdminUser::where('is_active', true)
            ->whereIn('role', ['super_admin', 'admin'])
            ->get();

        if ($admins->isEmpty()) {
            Log::warning('ReceiptSubmitted but no active admins to notify', [
                'receipt_id' => $receipt->id,
                'tenant_id' => $receipt->tenant_id,
            ]);

            return;
        }

        $tenantName = $receipt->tenant?->trade_name ?? $receipt->tenant?->name ?? 'Unknown tenant';

        foreach ($admins as $admin) {
            // Admin notifications live on the admin_id column (added in
            // the 2026_07_26_210000 migration). user_id and tenant_id
            // stay NULL — the polymorphic "one of these is set" pattern
            // keeps the table simple and the queries fast.
            InternalNotification::create([
                'id' => (string) Str::uuid(),
                'tenant_id' => null,
                'user_id' => null,
                'admin_id' => $admin->id,
                'type' => 'bank_transfer.receipt.submitted',
                'title' => 'New bank transfer receipt',
                'body' => sprintf(
                    '%s uploaded a %s SAR receipt awaiting review.',
                    $tenantName,
                    number_format((float) $receipt->transfer_amount, 2)
                ),
                'icon' => 'document',
                'action_url' => "/system/bank-transfer-receipts/{$receipt->id}",
                'data' => [
                    'receipt_id' => $receipt->id,
                    'tenant_id' => $receipt->tenant_id,
                    'transfer_amount' => (float) $receipt->transfer_amount,
                    'transfer_reference' => $receipt->transfer_reference,
                ],
            ]);
        }
    }
}
