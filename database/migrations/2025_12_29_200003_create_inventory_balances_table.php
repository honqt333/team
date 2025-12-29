<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->onDelete('cascade');
            $table->foreignId('part_id')->constrained()->onDelete('cascade');
            $table->decimal('qty_on_hand', 12, 3)->default(0);
            $table->decimal('wac_cost', 12, 4)->default(0); // Weighted Average Cost
            $table->timestamp('last_move_at')->nullable();
            $table->timestamps();

            $table->unique(['warehouse_id', 'part_id']);
            $table->index(['part_id', 'qty_on_hand']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_balances');
    }
};
