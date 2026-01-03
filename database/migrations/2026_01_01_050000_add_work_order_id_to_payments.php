<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Add work_order_id as alternative to invoice_id
            $table->foreignId('work_order_id')
                ->nullable()
                ->after('invoice_id')
                ->constrained()
                ->cascadeOnDelete();

            // Make invoice_id nullable (payment can be on WO or Invoice)
            $table->foreignId('invoice_id')->nullable()->change();
        });

        // Expand payment_method enum to include more options
        DB::statement("ALTER TABLE payments MODIFY payment_method ENUM('cash', 'mada', 'visa', 'mastercard', 'transfer', 'apple_pay', 'stc_pay', 'tabby', 'tamara', 'credit', 'other') DEFAULT 'cash'");
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['work_order_id']);
            $table->dropColumn('work_order_id');
        });

        DB::statement("ALTER TABLE payments MODIFY payment_method ENUM('cash', 'card', 'transfer', 'credit') DEFAULT 'cash'");
    }
};
