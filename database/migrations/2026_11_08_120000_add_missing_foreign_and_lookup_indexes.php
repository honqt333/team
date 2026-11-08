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
        if (Schema::hasTable('appointment_message_deliveries')) {
            Schema::table('appointment_message_deliveries', function (Blueprint $table) {
                if (Schema::hasColumn('appointment_message_deliveries', 'provider_message_id')) {
                    $table->index('provider_message_id', 'appt_msg_deliv_provider_msg_id_idx');
                }
            });
        }

        if (Schema::hasTable('bank_transfer_receipts')) {
            Schema::table('bank_transfer_receipts', function (Blueprint $table) {
                if (Schema::hasColumn('bank_transfer_receipts', 'subscription_payment_id')) {
                    $table->index('subscription_payment_id', 'btr_sub_payment_id_idx');
                }
            });
        }

        if (Schema::hasTable('promo_code_usages')) {
            Schema::table('promo_code_usages', function (Blueprint $table) {
                if (Schema::hasColumn('promo_code_usages', 'user_id')) {
                    $table->index('user_id', 'pcu_user_id_idx');
                }
            });
        }

        if (Schema::hasTable('subscription_payments')) {
            Schema::table('subscription_payments', function (Blueprint $table) {
                if (Schema::hasColumn('subscription_payments', 'subscription_invoice_id')) {
                    $table->index('subscription_invoice_id', 'sp_sub_invoice_id_idx');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('appointment_message_deliveries')) {
            Schema::table('appointment_message_deliveries', function (Blueprint $table) {
                $table->dropIndex('appt_msg_deliv_provider_msg_id_idx');
            });
        }

        if (Schema::hasTable('bank_transfer_receipts')) {
            Schema::table('bank_transfer_receipts', function (Blueprint $table) {
                $table->dropIndex('btr_sub_payment_id_idx');
            });
        }

        if (Schema::hasTable('promo_code_usages')) {
            Schema::table('promo_code_usages', function (Blueprint $table) {
                $table->dropIndex('pcu_user_id_idx');
            });
        }

        if (Schema::hasTable('subscription_payments')) {
            Schema::table('subscription_payments', function (Blueprint $table) {
                $table->dropIndex('sp_sub_invoice_id_idx');
            });
        }
    }
};
