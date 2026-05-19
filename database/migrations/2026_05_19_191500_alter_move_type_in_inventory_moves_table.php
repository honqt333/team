<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_moves', function (Blueprint $table) {
            $table->string('move_type', 50)->change();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_moves', function (Blueprint $table) {
            // Note: going back to enum might fail if there are 'purchase_return' records, so this is just a best effort fallback
            $table->enum('move_type', [
                'receipt',
                'issue_to_workorder',
                'adjustment_in',
                'adjustment_out',
                'transfer_out',
                'transfer_in',
                'reversal',
            ])->change();
        });
    }
};
