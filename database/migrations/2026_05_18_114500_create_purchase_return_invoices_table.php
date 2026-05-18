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
        Schema::create('purchase_return_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('center_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_invoice_id')->constrained('purchase_invoices')->cascadeOnDelete();
            
            $table->string('code')->unique(); // e.g., PRET-0001
            $table->date('return_date');
            
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            
            $table->text('notes')->nullable();
            $table->string('attachment_path')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('purchase_return_invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_return_invoice_id')->constrained('purchase_return_invoices')->cascadeOnDelete();
            $table->foreignId('purchase_invoice_line_id')->nullable()->constrained('purchase_invoice_lines')->nullOnDelete();
            $table->foreignId('part_id')->constrained();
            
            $table->decimal('qty', 10, 2);
            $table->decimal('unit_cost', 10, 2);
            $table->decimal('tax_rate', 5, 2)->default(15.00);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_return_invoice_lines');
        Schema::dropIfExists('purchase_return_invoices');
    }
};
