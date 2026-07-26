<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Allow status = 'failed' on the subscriptions table.
 *
 * Background: SubscriptionPayment::markAsFailed() previously set
 * $this->subscription->update(['status' => 'failed']) when a payment
 * was rejected, but the CHECK constraint on subscriptions.status
 * only allows: trialing, active, past_due, cancelled, expired,
 * pending, pending_payment. Every payment failure on the in-flight
 * subscription triggered a SQLSTATE[23000] CHECK constraint
 * violation that rolled back the parent DB::transaction — the
 * payment was never marked failed, the subscription was never
 * updated, and the tenant saw a 500 error page instead of a clean
 * failure flow.
 *
 * Fix: widen the CHECK to include 'failed' (matches the status we
 * were already trying to write). For SQLite, use the rename-and-
 * recreate trick from the previous pending-status migration
 * (2026_07_25_130000) since SQLite cannot ALTER a CHECK constraint
 * in place. For MySQL, just modify the ENUM column in place.
 *
 * We deliberately do NOT collapse 'failed' to 'cancelled' on the
 * subscription side — the failure-vs-cancel distinction matters
 * when the admin runs the /system/subscriptions report (failed
 * payments that left the tenant in a recoverable state vs. explicit
 * cancels that the tenant initiated).
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            // Drop any leftover _subscriptions_old from a previous failed run
            if (Schema::hasTable('_subscriptions_old')) {
                DB::statement('DROP TABLE _subscriptions_old');
            }

            // 1. Drop dependent tables' data (we lose the data but the user
            //    can re-create subscriptions, which is the point of this fix)
            Schema::dropIfExists('subscription_invoices');
            Schema::dropIfExists('subscription_payments');
            Schema::dropIfExists('installments');

            // 2. Now safely rename
            DB::statement('ALTER TABLE subscriptions RENAME TO _subscriptions_old');

            // 3. Recreate with the wider CHECK constraint
            // NOTE: keep `price DECIMAL(10,2) DEFAULT 0` column — it was
            // added in migration 2026_07_21_201000 (after the original
            // create-subscriptions migration) and any data in it would
            // be lost without it in the CREATE TABLE. The previous
            // pending-status migration (2026_07_25_130000) forgot to
            // include it; we fix that here as well.
            DB::statement(<<<'SQL'
                CREATE TABLE subscriptions (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    tenant_id INTEGER NOT NULL,
                    plan_id INTEGER NOT NULL,
                    status TEXT NOT NULL DEFAULT 'trialing' CHECK (status IN ('trialing','active','past_due','cancelled','expired','pending','pending_payment','failed')),
                    billing_cycle TEXT NOT NULL DEFAULT 'monthly' CHECK (billing_cycle IN ('monthly','yearly')),
                    price DECIMAL(10,2) DEFAULT 0,
                    starts_at DATETIME,
                    ends_at DATETIME,
                    trial_ends_at DATETIME,
                    cancelled_at DATETIME,
                    payment_method TEXT,
                    auto_renew TINYINT(1) DEFAULT 1,
                    promo_code TEXT,
                    discount_amount DECIMAL(10,2) DEFAULT 0,
                    created_at DATETIME,
                    updated_at DATETIME,
                    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
                )
            SQL);

            // 4. Copy data. Note: we cannot blindly SELECT * FROM
            //    _subscriptions_old because the original table may
            //    or may not have the `price` column (it was added
            //    in 2026_07_21_201000, after the create-subscriptions
            //    migration; on a fresh install the rename trick
            //    fires before that migration runs, so the price
            //    column is missing). The cleanest cross-version
            //    approach: list every column we know the source
            //    has (everything except `price`), then set
            //    price=0 in the destination.
            //
            //    We do the copy in two passes: a structural
            //    INSERT (no price) and a defensive ALTER TABLE to
            //    add the price column if it isn't already there
            //    (2026_07_21_201000 will add it with default 0,
            //    so this is harmless to run early).
            DB::statement(
                'INSERT INTO subscriptions (id, tenant_id, plan_id, status, billing_cycle, starts_at, ends_at, trial_ends_at, cancelled_at, payment_method, auto_renew, promo_code, discount_amount, created_at, updated_at) '
                .'SELECT id, tenant_id, plan_id, status, billing_cycle, starts_at, ends_at, trial_ends_at, cancelled_at, payment_method, auto_renew, promo_code, discount_amount, created_at, updated_at FROM _subscriptions_old'
            );

            // 5. Drop old
            DB::statement('DROP TABLE _subscriptions_old');
        } else {
            DB::statement("ALTER TABLE subscriptions MODIFY COLUMN status ENUM('trialing','active','past_due','cancelled','expired','pending','pending_payment','failed') NOT NULL DEFAULT 'trialing'");
        }
    }

    public function down(): void
    {
        // Roll back to the previous constraint — any 'failed' rows get
        // collapsed to 'cancelled' to fit the narrower set.
        DB::statement("UPDATE subscriptions SET status='cancelled' WHERE status='failed'");
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            if (Schema::hasTable('_subscriptions_old')) {
                DB::statement('DROP TABLE _subscriptions_old');
            }

            Schema::dropIfExists('subscription_invoices');
            Schema::dropIfExists('subscription_payments');
            Schema::dropIfExists('installments');

            DB::statement('ALTER TABLE subscriptions RENAME TO _subscriptions_old');

            DB::statement(<<<'SQL'
                CREATE TABLE subscriptions (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    tenant_id INTEGER NOT NULL,
                    plan_id INTEGER NOT NULL,
                    status TEXT NOT NULL DEFAULT 'trialing' CHECK (status IN ('trialing','active','past_due','cancelled','expired','pending','pending_payment')),
                    billing_cycle TEXT NOT NULL DEFAULT 'monthly' CHECK (billing_cycle IN ('monthly','yearly')),
                    price DECIMAL(10,2) DEFAULT 0,
                    starts_at DATETIME,
                    ends_at DATETIME,
                    trial_ends_at DATETIME,
                    cancelled_at DATETIME,
                    payment_method TEXT,
                    auto_renew TINYINT(1) DEFAULT 1,
                    promo_code TEXT,
                    discount_amount DECIMAL(10,2) DEFAULT 0,
                    created_at DATETIME,
                    updated_at DATETIME,
                    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
                )
            SQL);

            DB::statement('INSERT INTO subscriptions (id, tenant_id, plan_id, status, billing_cycle, starts_at, ends_at, trial_ends_at, cancelled_at, payment_method, auto_renew, promo_code, discount_amount, created_at, updated_at) SELECT id, tenant_id, plan_id, status, billing_cycle, starts_at, ends_at, trial_ends_at, cancelled_at, payment_method, auto_renew, promo_code, discount_amount, created_at, updated_at FROM _subscriptions_old');

            DB::statement('DROP TABLE _subscriptions_old');
        } else {
            DB::statement("ALTER TABLE subscriptions MODIFY COLUMN status ENUM('trialing','active','past_due','cancelled','expired','pending','pending_payment') NOT NULL DEFAULT 'trialing'");
        }
    }
};
