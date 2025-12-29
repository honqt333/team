<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grn_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_received_note_id')->constrained()->onDelete('cascade');
            $table->foreignId('purchase_order_item_id')->constrained()->onDelete('cascade');
            $table->foreignId('part_id')->constrained()->onDelete('cascade');
            $table->decimal('qty_received', 12, 3);
            $table->decimal('unit_cost', 12, 4);
            $table->decimal('line_total', 14, 2)->default(0);
            
            // Reference to inventory move created on posting
            $table->foreignId('inventory_move_id')->nullable()->constrained()->nullOnDelete();
            
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('goods_received_note_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grn_items');
    }
};
