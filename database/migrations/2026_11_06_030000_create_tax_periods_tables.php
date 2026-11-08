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
        Schema::create('tax_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('center_id')->nullable()->constrained('centers')->nullOnDelete();
            $table->string('period_key'); // e.g. 2026-Q1, 2026-03
            $table->string('name'); // e.g. الربع الأول 2026
            $table->unsignedSmallInteger('year');
            $table->string('year_period', 10); // 1, 2, 3, 4
            $table->string('period_type', 20)->default('quarterly'); // quarterly, monthly
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 20)->default('draft'); // draft, posted

            // Transaction counts
            $table->unsignedInteger('transactions_count')->default(0);
            $table->unsignedInteger('income_transactions_count')->default(0);
            $table->unsignedInteger('expense_transactions_count')->default(0);

            // Sales / Income totals
            $table->decimal('sum_income_subtotal', 15, 2)->default(0);
            $table->decimal('sum_income_vat_amount', 15, 2)->default(0);
            $table->decimal('sum_income_total_amount', 15, 2)->default(0);

            // Purchases / Expense totals
            $table->decimal('sum_expense_subtotal', 15, 2)->default(0);
            $table->decimal('sum_expense_vat_amount', 15, 2)->default(0);
            $table->decimal('sum_expense_total_amount', 15, 2)->default(0);

            // Grand totals
            $table->decimal('sum_subtotal', 15, 2)->default(0);
            $table->decimal('sum_vat_amount', 15, 2)->default(0);
            $table->decimal('sum_total_amount', 15, 2)->default(0);
            $table->decimal('net_vat_amount', 15, 2)->default(0); // sum_income_vat_amount - sum_expense_vat_amount

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'center_id']);
            $table->index(['tenant_id', 'period_key']);
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('tax_period_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_period_id')->constrained('tax_periods')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('center_id')->nullable()->constrained('centers')->nullOnDelete();

            $table->string('transaction_type', 50); // sales_invoice, credit_note, purchase_invoice, purchase_return, company_transaction
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->string('ref_no', 100);
            $table->date('transaction_date');
            $table->string('party_name')->nullable();
            $table->string('tax_number', 50)->nullable();
            $table->boolean('is_income')->default(true);
            $table->boolean('is_vat_inclusive')->default(false);

            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('vat_rate', 5, 2)->default(15.00);
            $table->decimal('vat_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->boolean('is_selected')->default(true);
            $table->string('notes')->nullable();

            $table->timestamps();

            $table->index(['tax_period_id', 'is_selected']);
            $table->index(['tenant_id', 'center_id']);
            $table->index(['tenant_id', 'transaction_type', 'transaction_id'], 'tpt_tenant_type_txid_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_period_transactions');
        Schema::dropIfExists('tax_periods');
    }
};
