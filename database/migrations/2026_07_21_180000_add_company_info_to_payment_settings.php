<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_settings', function (Blueprint $table) {
            // Company info (displayed on invoices/receipts to tenants)
            $table->string('company_legal_name', 255)->nullable();
            $table->string('company_legal_name_en', 255)->nullable();
            $table->string('company_vat_number', 20)->nullable();
            $table->string('company_cr_number', 20)->nullable();
            $table->string('company_phone', 30)->nullable();
            $table->string('company_email', 255)->nullable();
            $table->string('company_website', 255)->nullable();
            $table->string('company_logo_path', 500)->nullable();

            // VAT settings for subscriptions
            $table->boolean('subscription_vat_enabled')->default(true);
            $table->decimal('subscription_vat_rate', 5, 2)->default(15.00);
            $table->boolean('subscription_vat_inclusive')->default(false); // false = exclusive (added on top)

            // Bank transfer controls
            $table->decimal('bank_transfer_min_amount', 10, 2)->nullable();
            $table->integer('bank_transfer_max_days')->default(7);
            $table->boolean('bank_transfer_require_receipt')->default(true);
            $table->text('bank_transfer_receipt_instructions')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('payment_settings', function (Blueprint $table) {
            $table->dropColumn([
                'company_legal_name',
                'company_legal_name_en',
                'company_vat_number',
                'company_cr_number',
                'company_phone',
                'company_email',
                'company_website',
                'company_logo_path',
                'subscription_vat_enabled',
                'subscription_vat_rate',
                'subscription_vat_inclusive',
                'bank_transfer_min_amount',
                'bank_transfer_max_days',
                'bank_transfer_require_receipt',
                'bank_transfer_receipt_instructions',
            ]);
        });
    }
};
