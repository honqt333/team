<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bank_transfer_receipts')) {
            Schema::table('bank_transfer_receipts', function (Blueprint $table): void {
                if (! Schema::hasColumn('bank_transfer_receipts', 'approved_amount')) {
                    $table->decimal('approved_amount', 10, 2)->nullable()->after('transfer_amount');
                }

                if (! Schema::hasColumn('bank_transfer_receipts', 'idempotency_key')) {
                    $table->string('idempotency_key', 100)->nullable()->after('transfer_reference');
                    $table->unique(['tenant_id', 'idempotency_key'], 'bank_transfer_receipts_tenant_idempotency_unique');
                }
            });

            // Non-destructive backfill for historical approved receipts without modifying financial rows.
            if (Schema::hasTable('subscription_payments')) {
                $approvedReceipts = DB::table('bank_transfer_receipts')
                    ->where('status', 'approved')
                    ->whereNull('approved_amount')
                    ->whereNotNull('subscription_payment_id')
                    ->get(['id', 'subscription_payment_id']);

                foreach ($approvedReceipts as $receipt) {
                    $paidAmount = DB::table('subscription_payments')
                        ->where('id', $receipt->subscription_payment_id)
                        ->where('status', 'paid')
                        ->whereNull('refunded_at')
                        ->value('amount');

                    if ($paidAmount !== null) {
                        DB::table('bank_transfer_receipts')
                            ->where('id', $receipt->id)
                            ->update(['approved_amount' => $paidAmount]);
                    }
                }
            }
        }

        if (Schema::hasTable('subscription_invoices')) {
            Schema::table('subscription_invoices', function (Blueprint $table): void {
                if (! Schema::hasColumn('subscription_invoices', 'operation_type')) {
                    $table->string('operation_type', 32)->nullable()->after('invoice_number');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bank_transfer_receipts')) {
            Schema::table('bank_transfer_receipts', function (Blueprint $table): void {
                if (Schema::hasColumn('bank_transfer_receipts', 'idempotency_key')) {
                    $table->dropUnique('bank_transfer_receipts_tenant_idempotency_unique');
                    $table->dropColumn('idempotency_key');
                }

                if (Schema::hasColumn('bank_transfer_receipts', 'approved_amount')) {
                    $table->dropColumn('approved_amount');
                }
            });
        }

        if (Schema::hasTable('subscription_invoices')) {
            Schema::table('subscription_invoices', function (Blueprint $table): void {
                if (Schema::hasColumn('subscription_invoices', 'operation_type')) {
                    $table->dropColumn('operation_type');
                }
            });
        }
    }
};
