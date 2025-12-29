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
        Schema::create('work_order_item_technician', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_item_id')->constrained('work_order_items')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['work_order_item_id', 'user_id'], 'unique_item_technician');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_order_item_technician');
    }
};
