<?php

declare(strict_types=1);

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
        Schema::table('work_order_photos', function (Blueprint $table) {
            $table->foreignId('work_order_item_id')
                ->nullable()
                ->after('work_order_id')
                ->constrained('work_order_items')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_order_photos', function (Blueprint $table) {
            $table->dropForeign(['work_order_item_id']);
            $table->dropColumn('work_order_item_id');
        });
    }
};
