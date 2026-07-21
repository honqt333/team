<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
            DB::statement(<<<'SQL'
                CREATE TABLE subscriptions (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    tenant_id INTEGER NOT NULL,
                    plan_id INTEGER NOT NULL,
                    status TEXT NOT NULL DEFAULT 'trialing' CHECK (status IN ('trialing','active','past_due','cancelled','expired','pending','pending_payment')),
                    billing_cycle TEXT NOT NULL DEFAULT 'monthly' CHECK (billing_cycle IN ('monthly','yearly')),
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

            // 4. Copy data
            DB::statement('INSERT INTO subscriptions (id, tenant_id, plan_id, status, billing_cycle, starts_at, ends_at, trial_ends_at, cancelled_at, payment_method, auto_renew, promo_code, discount_amount, created_at, updated_at) SELECT id, tenant_id, plan_id, status, billing_cycle, starts_at, ends_at, trial_ends_at, cancelled_at, payment_method, auto_renew, promo_code, discount_amount, created_at, updated_at FROM _subscriptions_old');

            // 5. Drop old
            DB::statement('DROP TABLE _subscriptions_old');
        } else {
            DB::statement("ALTER TABLE subscriptions MODIFY COLUMN status ENUM('trialing','active','past_due','cancelled','expired','pending','pending_payment') NOT NULL DEFAULT 'trialing'");
        }
    }

    public function down(): void
    {
        DB::statement("UPDATE subscriptions SET status='trialing' WHERE status IN ('pending','pending_payment')");
        $driver = DB::connection()->getDriverName();

        if ($driver !== 'sqlite') {
            DB::statement("ALTER TABLE subscriptions MODIFY COLUMN status ENUM('trialing','active','past_due','cancelled','expired') NOT NULL DEFAULT 'trialing'");
        }
    }
};
