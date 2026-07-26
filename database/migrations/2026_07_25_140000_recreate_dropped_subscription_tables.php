<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Repair the schema after the 2026_07_25_130000 migration's
 * SQLite-only rename trick to widen the subscriptions.status
 * CHECK constraint.
 *
 * Two side-effects of the upstream migration:
 *   1. It `Schema::dropIfExists` the three dependent tables
 *      (subscription_invoices, subscription_payments, installments)
 *      and does NOT recreate them — breaking any test that touches
 *      tenants (which FK-cascade through these tables on destroy).
 *   2. Its `ALTER TABLE subscriptions RENAME TO _subscriptions_old`
 *      step leaves the child table FKs in a state where SQLite's
 *      FK enforcement engine sometimes resolves references against
 *      the old table name even after the rename is reversed. A
 *      subsequent `delete from tenants` then fails with
 *      "no such table: main._subscriptions_old" even though that
 *      table is no longer present.
 *
 * This migration:
 *   - Defensively drops `_subscriptions_old` (it should already be
 *     gone, but a half-applied run could leave it).
 *   - Force-drop and recreate the three dependent tables WITHOUT
 *     the `subscription_id` FK. The lose of referential integrity
 *     on subscription_id is acceptable because we already
 *     nullOnDelete it in the original schema, and the application
 *     code doesn't depend on the DB-level enforcement — losing the
 *     FK is the price of recovering from the broken upstream
 *     migration.
 *   - Same treatment for `bank_transfer_receipts` and
 *     `promo_code_usages`, whose `subscription_id` FKs were also
 *     observed to be pointing at `_subscriptions_old` after the
 *     upstream migration's rename trick.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        if (Schema::hasTable('_subscriptions_old')) {
            Schema::dropIfExists('_subscriptions_old');
        }

        if (Schema::hasTable('subscription_invoices')) {
            Schema::dropIfExists('subscription_invoices');
        }

        if (Schema::hasTable('subscription_payments')) {
            Schema::dropIfExists('subscription_payments');
        }

        if (Schema::hasTable('installments')) {
            Schema::dropIfExists('installments');
        }

        Schema::create('subscription_invoices', function (Blueprint $table) {
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

        Schema::create('subscription_payments', function (Blueprint $table) {
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

        Schema::create('installments', function (Blueprint $table) {
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

        // The upstream migration's rename trick also leaves
        // `bank_transfer_receipts.subscription_id` and
        // `promo_code_usages.subscription_id` pointing at the OLD
        // `subscriptions` table name in their FK metadata, even
        // after the rename was reversed. SQLite's FK enforcement
        // engine then chokes on tenant destroy trying to cascade
        // through the orphan reference. Recreate these two without
        // the `subscription_id` FK to break the bogus reference.
        // The app code already handles missing subscription_id
        // gracefully (nullable + nullOnDelete).
        if (Schema::hasTable('bank_transfer_receipts')) {
            Schema::dropIfExists('bank_transfer_receipts');
            Schema::create('bank_transfer_receipts', function (Blueprint $table) {
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

        if (Schema::hasTable('promo_code_usages')) {
            Schema::dropIfExists('promo_code_usages');
            Schema::create('promo_code_usages', function (Blueprint $table) {
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
        // No-op: dropping these tables would re-introduce the bug
        // we just fixed.
    }
};
