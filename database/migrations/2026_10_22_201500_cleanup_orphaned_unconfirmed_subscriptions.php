<?php

declare(strict_types=1);

use App\Models\Billing\Subscription;
use App\Models\Billing\SubscriptionInvoice;
use App\Models\Billing\SubscriptionPayment;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Clean up abandoned/failed/cancelled unconfirmed checkout attempts
        // that never received payment or an approved bank transfer receipt.
        $orphanedSubs = Subscription::where(function ($q) {
            $q->whereIn('status', ['pending', 'pending_payment', 'failed', 'cancelled'])
                ->orWhere(function ($q2) {
                    $q2->where('status', 'active')
                        ->whereHas('payments', fn ($p) => $p->where('status', 'failed'));
                });
        })
            ->whereDoesntHave('payments', fn ($p) => $p->where('status', 'paid'))
            ->whereDoesntHave('receipts')
            ->get();

        foreach ($orphanedSubs as $sub) {
            SubscriptionPayment::where('subscription_id', $sub->id)->update([
                'subscription_id' => null,
                'subscription_invoice_id' => null,
            ]);
            SubscriptionInvoice::where('subscription_id', $sub->id)->delete();
            $sub->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: cannot restore unconfirmed/failed checkout attempts
    }
};
