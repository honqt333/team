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
        Schema::create('work_order_purchase_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('center_id')->nullable()->constrained('centers')->cascadeOnDelete();
            $table->foreignId('work_order_id')->constrained('work_orders')->cascadeOnDelete();
            $table->foreignId('purchase_invoice_id')->constrained('purchase_invoices')->cascadeOnDelete();
            $table->foreignId('purchase_invoice_line_id')->nullable()->constrained('purchase_invoice_lines')->nullOnDelete();
            $table->foreignId('work_order_item_part_id')->nullable()->constrained('work_order_item_parts')->nullOnDelete();
            $table->foreignId('work_order_item_id')->nullable()->constrained('work_order_items')->nullOnDelete();

            $table->string('type', 50)->default('external_part');
            $table->string('item_description', 255);
            $table->decimal('allocated_qty', 10, 2)->default(1.00);
            $table->decimal('unit_cost', 12, 2)->default(0.00);
            $table->decimal('tax_amount', 12, 2)->default(0.00);
            $table->decimal('total_cost', 12, 2)->default(0.00);
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'center_id']);
            $table->index(['work_order_id', 'type']);
            $table->index('purchase_invoice_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_order_purchase_allocations');
    }
};
