<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        // 1. Update data (both drivers)
        DB::statement("UPDATE payments SET type = LOWER(type) WHERE type IN ('Payment', 'Refund', 'Bad_debt', 'BAD_DEBT')");
        DB::statement("UPDATE payments SET type = 'payment' WHERE type = 'pay'");
        DB::statement("UPDATE payments SET type = 'refund' WHERE type = 'ref'");
        DB::statement("UPDATE payments SET type = 'bad_debt' WHERE type IN ('bad', 'debt', 'write_off', 'writeoff')");

        DB::statement("UPDATE invoices SET type = LOWER(type) WHERE type IN ('Invoice', 'Credit', 'Debit')");
        DB::statement("UPDATE invoices SET type = 'invoice' WHERE type = 'inv'");
        DB::statement("UPDATE invoices SET type = 'credit_note' WHERE type IN ('credit', 'cn', 'refund_invoice')");
        DB::statement("UPDATE invoices SET type = 'debit_note' WHERE type IN ('debit', 'dn', 'additional_charge')");

        // 2. MySQL: Add ENUM constraint
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE payments MODIFY COLUMN type ENUM('payment', 'refund', 'bad_debt') NOT NULL DEFAULT 'payment'");
            DB::statement("ALTER TABLE invoices MODIFY COLUMN type ENUM('invoice', 'credit_note', 'debit_note') NOT NULL DEFAULT 'invoice'");
        }

        // PostgreSQL: use CHECK constraint
        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_type_check CHECK (type IN ('payment', 'refund', 'bad_debt'))");
            DB::statement("ALTER TABLE invoices ADD CONSTRAINT invoices_type_check CHECK (type IN ('invoice', 'credit_note', 'debit_note'))");
        }
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE payments MODIFY COLUMN type VARCHAR(50) NOT NULL");
            DB::statement("ALTER TABLE invoices MODIFY COLUMN type VARCHAR(50) NOT NULL");
        }

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE payments DROP CONSTRAINT payments_type_check");
            DB::statement("ALTER TABLE invoices DROP CONSTRAINT invoices_type_check");
        }
    }
};
