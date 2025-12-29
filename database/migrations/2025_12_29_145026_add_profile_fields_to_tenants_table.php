<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add profile fields to tenants (without address)
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('legal_name')->nullable()->after('slug');
            $table->string('trade_name')->nullable()->after('legal_name');
            $table->string('vat_number', 20)->nullable()->after('trade_name');
            $table->string('cr_number', 20)->nullable()->after('vat_number');
            $table->string('phone', 20)->nullable()->after('cr_number');
            $table->string('email')->nullable()->after('phone');
            $table->string('logo_path')->nullable()->after('email');
            $table->string('invoice_number_format')->default('INV-{CENTER}-{YYYY}-{SEQ}')->after('logo_path');
            $table->boolean('zatca_qr_enabled')->default(false)->after('invoice_number_format');
        });

        // Create separate tenant_addresses table
        Schema::create('tenant_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('address_line')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('building_number', 20)->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_addresses');

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'legal_name', 'trade_name', 'vat_number', 'cr_number',
                'phone', 'email', 'logo_path', 'invoice_number_format', 'zatca_qr_enabled'
            ]);
        });
    }
};
