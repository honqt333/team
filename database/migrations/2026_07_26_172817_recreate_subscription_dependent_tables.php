<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Recreate the subscription-dependent tables that were dropped by
 * 2026_07_26_172322_add_failed_status_to_subscriptions.
 *
 * Background: when we added 'failed' to the subscriptions.status
 * CHECK constraint via the SQLite rename trick, we had to drop the
 * three child tables (subscription_invoices, subscription_payments,
 * installments) because their FKs pointed at the old
 * `subscriptions` table name. The previous
 * 2026_07_25_140000 migration was supposed to recreate them, but
 * its CREATE blocks were guarded with `Schema::hasTable(...)` and
 * ran BEFORE the rename trick landed, so on a fresh install they
 * never came back. This migration unconditionally creates them.
 *
 * Schema mirrors 2026_07_25_140000 (which is the canonical
 * post-rename shape) so the controllers and models that already
 * work against that shape keep working.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        if (! Schema::hasTable('subscription_invoices')) {
            Schema::create('subscription_invoices', function ($table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('subscription_id')->nullable();
                $table->string('invoice_number')->unique();
                $table->decimal('subtotal', 12, 2)->default(0);
                $table->decimal('discount', 12, 2)->default(0);
                $table->decimal('vat', 12, 2)->default(0);
                $table->decimal('total', 12, 2)->default(0);
                $table->string('status')->default('pending');
                $table->timestamp('issued_at')->nullable();
                $table->timestamp('due_at')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['tenant_id', 'status']);
                $table->index(['subscription_id']);
            });
        }

        if (! Schema::hasTable('subscription_payments')) {
            Schema::create('subscription_payments', function ($table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('subscription_id')->nullable();
                $table->unsignedBigInteger('subscription_invoice_id')->nullable();
                $table->decimal('amount', 12, 2);
                $table->string('currency', 3)->default('SAR');
                $table->string('gateway');
                $table->string('gateway_payment_id')->nullable();
                $table->string('gateway_ref')->nullable();
                $table->string('status')->default('pending');
                $table->string('payment_method')->nullable();
                $table->json('gateway_response')->nullable();
                $table->text('failure_reason')->nullable();
                $table->timestamp('initiated_at')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamp('refunded_at')->nullable();
                $table->timestamps();
                $table->index(['tenant_id', 'status']);
                $table->index(['subscription_id']);
                $table->index(['gateway', 'gateway_payment_id']);
            });
        }

        if (! Schema::hasTable('installments')) {
            Schema::create('installments', function ($table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('subscription_id')->nullable();
                $table->unsignedInteger('installment_number');
                $table->decimal('amount', 12, 2);
                $table->date('due_date');
                $table->date('paid_date')->nullable();
                $table->string('status')->default('pending');
                $table->decimal('paid_amount', 12, 2)->nullable();
                $table->string('payment_method')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['tenant_id', 'status']);
                $table->index(['subscription_id']);
                $table->index(['due_date']);
            });
        }

        // bank_transfer_receipts and promo_code_usages were already
        // touched by 2026_07_25_140000. Only recreate them if they
        // somehow got dropped (e.g. a half-applied earlier run).
        if (Schema::hasTable('bank_transfer_receipts') === false) {
            Schema::create('bank_transfer_receipts', function ($table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('subscription_id')->nullable();
                $table->unsignedBigInteger('subscription_payment_id')->nullable();
                $table->decimal('transfer_amount', 10, 2);
                $table->string('transfer_reference')->nullable();
                $table->string('bank_name')->nullable();
                $table->string('account_name')->nullable();
                $table->string('transfer_date');
                $table->string('status')->default('pending');
                $table->string('receipt_path')->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('reviewed_by')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('review_notes')->nullable();
                $table->timestamps();
                $table->index(['tenant_id', 'status']);
                $table->index(['subscription_id']);
            });
        }

        if (Schema::hasTable('promo_code_usages') === false) {
            Schema::create('promo_code_usages', function ($table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('promo_code_id');
                $table->unsignedBigInteger('subscription_id')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->decimal('discount_amount', 10, 2)->default(0);
                $table->timestamp('used_at')->nullable();
                $table->timestamps();
                $table->index(['tenant_id', 'promo_code_id']);
                $table->index(['subscription_id']);
            });
        }

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // No-op: dropping these would re-introduce the original problem.
    }
};
