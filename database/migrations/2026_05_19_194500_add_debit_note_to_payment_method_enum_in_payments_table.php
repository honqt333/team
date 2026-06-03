<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE payments MODIFY payment_method ENUM('cash', 'mada', 'visa', 'mastercard', 'transfer', 'apple_pay', 'stc_pay', 'tabby', 'tamara', 'credit', 'other', 'debit_note') DEFAULT 'cash'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE payments MODIFY payment_method ENUM('cash', 'mada', 'visa', 'mastercard', 'transfer', 'apple_pay', 'stc_pay', 'tabby', 'tamara', 'credit', 'other') DEFAULT 'cash'");
        }
    }
};
