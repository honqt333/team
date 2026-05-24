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
        Schema::table('work_order_item_notes', function (Blueprint $table) {
            $table->dropForeign(['work_order_item_id']);
        });

        Schema::table('work_order_item_notes', function (Blueprint $table) {
            $table->foreignId('work_order_item_id')->nullable()->change();
            $table->foreign('work_order_item_id')->references('id')->on('work_order_items')->cascadeOnDelete();

            $table->foreignId('work_order_id')
                ->nullable()
                ->after('id')
                ->constrained('work_orders')
                ->cascadeOnDelete();
        });

        // Backfill work_order_id for existing notes
        \Illuminate\Support\Facades\DB::table('work_order_item_notes')
            ->join('work_order_items', 'work_order_item_notes.work_order_item_id', '=', 'work_order_items.id')
            ->update(['work_order_item_notes.work_order_id' => \Illuminate\Support\Facades\DB::raw('work_order_items.work_order_id')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_order_item_notes', function (Blueprint $table) {
            $table->dropForeign(['work_order_id']);
            $table->dropColumn('work_order_id');

            $table->dropForeign(['work_order_item_id']);
        });

        // Delete notes without work_order_item_id before making it non-nullable
        \Illuminate\Support\Facades\DB::table('work_order_item_notes')->whereNull('work_order_item_id')->delete();

        Schema::table('work_order_item_notes', function (Blueprint $table) {
            $table->foreignId('work_order_item_id')->nullable(false)->change();
            $table->foreign('work_order_item_id')->references('id')->on('work_order_items')->cascadeOnDelete();
        });
    }
};
