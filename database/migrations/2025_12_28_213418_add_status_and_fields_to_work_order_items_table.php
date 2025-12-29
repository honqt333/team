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
            $table->enum('status', ['pending', 'in_progress', 'completed', 'on_hold', 'cancelled'])
                ->default('pending')
                ->after('total');
            $table->text('notes')->nullable()->after('status');
            $table->timestamp('started_at')->nullable()->after('notes');
            $table->timestamp('completed_at')->nullable()->after('started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_order_items', function (Blueprint $table) {
            $table->dropColumn(['status', 'notes', 'started_at', 'completed_at']);
        });
    }
};
