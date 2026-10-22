<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_zatca_settings', function (Blueprint $table) {
            $table->string('environment')->default('simulation')->after('qr_enabled');
            $table->string('onboarding_status')->default('not_started')->after('environment');
            $table->string('egs_serial_number')->nullable()->after('onboarding_status');
            $table->text('csr_content')->nullable()->after('egs_serial_number');
            $table->text('private_key')->nullable()->after('csr_content');
            $table->text('compliance_csid')->nullable()->after('private_key');
            $table->text('compliance_secret')->nullable()->after('compliance_csid');
            $table->text('production_csid')->nullable()->after('compliance_secret');
            $table->text('production_secret')->nullable()->after('production_csid');
            $table->timestamp('production_csid_expires_at')->nullable()->after('production_secret');
            $table->text('last_onboarding_error')->nullable()->after('production_csid_expires_at');
            $table->timestamp('onboarded_at')->nullable()->after('last_onboarding_error');
        });
    }

    public function down(): void
    {
        Schema::table('tenant_zatca_settings', function (Blueprint $table) {
            $table->dropColumn([
                'environment',
                'onboarding_status',
                'egs_serial_number',
                'csr_content',
                'private_key',
                'compliance_csid',
                'compliance_secret',
                'production_csid',
                'production_secret',
                'production_csid_expires_at',
                'last_onboarding_error',
                'onboarded_at',
            ]);
        });
    }
};
