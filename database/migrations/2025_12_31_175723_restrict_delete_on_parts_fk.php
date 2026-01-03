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
        Schema::table('parts', function (Blueprint $table) {
            // Drop existing foreign keys
            $table->dropForeign(['unit_id']);
            $table->dropForeign(['category_id']);
        });

        Schema::table('parts', function (Blueprint $table) {
            // Re-add with restrictOnDelete
            $table->foreign('unit_id')
                  ->references('id')
                  ->on('inventory_units')
                  ->restrictOnDelete();

            $table->foreign('category_id')
                  ->references('id')
                  ->on('inventory_categories')
                  ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parts', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropForeign(['category_id']);
        });

        Schema::table('parts', function (Blueprint $table) {
            $table->foreign('unit_id')
                  ->references('id')
                  ->on('inventory_units')
                  ->nullOnDelete();

            $table->foreign('category_id')
                  ->references('id')
                  ->on('inventory_categories')
                  ->nullOnDelete();
        });
    }
};
