<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfill subscriptions.price from the related plan.
 *
 * The 2026_07_21_201000_add_price_to_subscriptions migration added the
 * `price` column but never populated it. Eloquent's $fillable list
 * on the Subscription model also didn't include 'price', so any
 * Subscription::create([... 'price' => ...]) call was silently
 * dropping the value. This left every active subscription with
 * price=0, which made the SubscriptionCard display "0 SAR" for
 * active subscriptions and broke admin revenue reporting.
 *
 * This migration backfills the column from the plan's price_monthly
 * (or price_yearly for yearly billing) so all existing subscriptions
 * have a meaningful price. New subscriptions will have the column
 * filled in by the controllers — the model's $fillable is also
 * updated in this commit.
 *
 * Idempotent: only updates rows where price is currently 0.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            UPDATE subscriptions
            SET price = CASE
                WHEN billing_cycle = 'yearly' THEN COALESCE((SELECT price_yearly FROM plans WHERE plans.id = subscriptions.plan_id), 0)
                ELSE COALESCE((SELECT price_monthly FROM plans WHERE plans.id = subscriptions.plan_id), 0)
            END
            WHERE price = 0
        ");
    }

    public function down(): void
    {
        // Down is a no-op: we don't want to wipe the backfilled prices
        // if the migration is rolled back. Operators can manually clear
        // if they really need to.
    }
};
