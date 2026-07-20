<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Normalize payment types to lowercase
        DB::statement("UPDATE payments SET type = LOWER(type) WHERE type IN ('Payment', 'Refund', 'Bad_debt', 'BAD_DEBT')");
        DB::statement("UPDATE payments SET type = 'payment' WHERE type = 'pay'");
        DB::statement("UPDATE payments SET type = 'refund' WHERE type = 'ref'");

        // Add enum constraint for payments
        DB::statement("ALTER TABLE payments MODIFY COLUMN type ENUM('payment', 'refund', 'bad_debt') NOT NULL DEFAULT 'payment'");

        // Normalize invoice types to lowercase
        DB::statement("UPDATE invoices SET type = LOWER(type) WHERE type IN ('Invoice', 'Credit', 'Debit')");

        // Add enum constraint for invoices
        DB::statement("ALTER TABLE invoices MODIFY COLUMN type ENUM('invoice', 'credit_note', 'debit_note') NOT NULL DEFAULT 'invoice'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE payments MODIFY COLUMN type VARCHAR(50) NOT NULL");
        DB::statement("ALTER TABLE invoices MODIFY COLUMN type VARCHAR(50) NOT NULL");
    }
};
