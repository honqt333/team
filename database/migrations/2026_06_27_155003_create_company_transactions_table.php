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
        Schema::create('company_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('center_id')->index();
            $table->string('title');
            $table->date('transaction_date');
            $table->string('transaction_type'); // revenue, expense
            $table->unsignedBigInteger('income_category_id')->index();
            $table->decimal('amount', 12, 2)->default(0);
            $table->boolean('is_taxable')->default(false);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('contact_type')->nullable(); // customer, supplier
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('draft'); // draft, approved
            
            $table->unsignedBigInteger('invoice_id')->nullable()->index();
            $table->unsignedBigInteger('purchase_invoice_id')->nullable()->index();
            $table->unsignedBigInteger('approved_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable()->index();
            
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('center_id')->references('id')->on('centers')->cascadeOnDelete();
            $table->foreign('income_category_id')->references('id')->on('income_categories')->cascadeOnDelete();
            $table->foreign('invoice_id')->references('id')->on('invoices')->nullOnDelete();
            $table->foreign('purchase_invoice_id')->references('id')->on('purchase_invoices')->nullOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_transactions');
    }
};
