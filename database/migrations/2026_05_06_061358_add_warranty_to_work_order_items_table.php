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
        Schema::table('work_order_items', function (Blueprint $table) {
            $table->timestamp('warranty_expires_at')->nullable();
            $table->integer('warranty_value_snapshot')->nullable();
            $table->string('warranty_unit_snapshot')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_order_items', function (Blueprint $table) {
            $table->dropColumn(['warranty_expires_at', 'warranty_value_snapshot', 'warranty_unit_snapshot']);
        });
    }
};
