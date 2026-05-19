<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('purchase_return_invoices', function (Blueprint $table) {
            $table->boolean('create_debit_note')->default(false)->after('total');
            $table->date('debit_note_date')->nullable()->after('create_debit_note');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_return_invoices', function (Blueprint $table) {
            $table->dropColumn(['create_debit_note', 'debit_note_date']);
        });
    }
};
