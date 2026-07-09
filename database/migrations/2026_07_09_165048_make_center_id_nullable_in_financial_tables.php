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
        Schema::table('company_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('center_id')->nullable()->change();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('center_id')->nullable()->change();
        });

        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('center_id')->nullable()->change();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('center_id')->nullable()->change();
        });

        Schema::table('center_sequences', function (Blueprint $table) {
            $table->unsignedBigInteger('center_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('center_id')->nullable(false)->change();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('center_id')->nullable(false)->change();
        });

        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('center_id')->nullable(false)->change();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('center_id')->nullable(false)->change();
        });

        Schema::table('center_sequences', function (Blueprint $table) {
            $table->unsignedBigInteger('center_id')->nullable(false)->change();
        });
    }
};
