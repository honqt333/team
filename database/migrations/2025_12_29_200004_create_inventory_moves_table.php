<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_moves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->onDelete('cascade');
            $table->foreignId('part_id')->constrained()->onDelete('cascade');
            $table->enum('move_type', [
                'receipt',           // Stock in (GRN, manual)
                'issue_to_workorder', // Stock out to work order
                'adjustment_in',     // Inventory count increase
                'adjustment_out',    // Inventory count decrease
                'transfer_out',      // Transfer to another center
                'transfer_in',       // Transfer from another center
                'reversal',          // Reversal of previous move
            ]);
            $table->decimal('qty', 12, 3); // Positive for in, negative for out
            $table->decimal('unit_cost', 12, 4)->default(0);
            $table->decimal('total_cost', 12, 2)->default(0);
            $table->decimal('balance_after', 12, 3)->default(0); // Snapshot of balance after move
            $table->decimal('wac_after', 12, 4)->default(0); // WAC snapshot after move
            
            // Polymorphic reference to source document
            $table->string('reference_type')->nullable(); // e.g., GoodsReceivedNote, WorkOrderItemPart
            $table->unsignedBigInteger('reference_id')->nullable();
            
            // Reversal tracking
            $table->foreignId('reverses_move_id')->nullable()->constrained('inventory_moves')->nullOnDelete();
            $table->foreignId('reversed_by_move_id')->nullable()->constrained('inventory_moves')->nullOnDelete();
            
            $table->text('notes')->nullable();
            
            // Posting info (immutable after posted)
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            
            // Reversal info
            $table->timestamp('reversed_at')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();

            $table->index(['warehouse_id', 'part_id', 'posted_at']);
            $table->index(['reference_type', 'reference_id']);
            $table->index('move_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_moves');
    }
};
