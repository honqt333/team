<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create dedicated ZATCA settings table
        Schema::create('tenant_zatca_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('qr_enabled')->default(false);
            // Future Phase 2 fields (not implemented yet)
            // $table->string('csid')->nullable();
            // $table->string('otp')->nullable();
            // $table->boolean('integration_enabled')->default(false);
            $table->timestamps();
        });

        // Remove zatca_qr_enabled from tenants (it was added in previous migration)
        if (Schema::hasColumn('tenants', 'zatca_qr_enabled')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->dropColumn('zatca_qr_enabled');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_zatca_settings');

        // Restore column to tenants
        Schema::table('tenants', function (Blueprint $table) {
            $table->boolean('zatca_qr_enabled')->default(false)->after('invoice_number_format');
        });
    }
};
