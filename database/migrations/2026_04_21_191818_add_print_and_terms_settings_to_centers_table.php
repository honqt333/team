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
        Schema::table('centers', function (Blueprint $table) {
            // Document Titles
            $table->string('quote_title')->nullable();
            $table->string('work_order_title')->nullable();
            $table->string('invoice_title')->nullable();

            // Terms and Conditions
            $table->text('quote_terms')->nullable();
            $table->text('work_order_terms')->nullable();
            $table->text('invoice_terms')->nullable();

            // Visual Print Settings (JSON)
            $table->json('print_settings')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('centers', function (Blueprint $table) {
            $table->dropColumn([
                'quote_title',
                'work_order_title',
                'invoice_title',
                'quote_terms',
                'work_order_terms',
                'invoice_terms',
                'print_settings'
            ]);
        });
    }
};
