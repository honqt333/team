<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_transfer_id')->constrained()->onDelete('cascade');
            $table->foreignId('part_id')->constrained()->onDelete('cascade');
            
            // Quantities
            $table->decimal('qty_requested', 12, 3);
            $table->decimal('qty_sent', 12, 3)->default(0);
            $table->decimal('qty_received', 12, 3)->default(0);
            
            // Cost snapshot when sent (WAC from source warehouse)
            $table->decimal('unit_cost', 12, 4)->default(0);
            
            // Inventory move references
            $table->foreignId('send_move_id')->nullable()->constrained('inventory_moves')->nullOnDelete();
            $table->foreignId('receive_move_id')->nullable()->constrained('inventory_moves')->nullOnDelete();
            
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['inventory_transfer_id', 'part_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transfer_items');
    }
};
