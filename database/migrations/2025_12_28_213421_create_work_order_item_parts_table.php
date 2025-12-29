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
        Schema::create('work_order_item_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_item_id')->constrained('work_order_items')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('center_id')->constrained('centers')->cascadeOnDelete();
            $table->string('name');
            $table->string('part_number')->nullable();
            $table->enum('source', ['warehouse', 'external', 'customer'])->default('external');
            $table->decimal('qty', 10, 2)->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['work_order_item_id', 'source']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_order_item_parts');
    }
};
