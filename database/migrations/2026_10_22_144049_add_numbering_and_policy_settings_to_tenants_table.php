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
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('work_order_number_format')->nullable()->default('WO-{SEQ}')->after('invoice_number_format');
            $table->string('quote_number_format')->nullable()->default('QT-{SEQ}')->after('work_order_number_format');
            $table->json('workflow_settings')->nullable()->after('quote_number_format');
            $table->json('notification_templates')->nullable()->after('workflow_settings');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'work_order_number_format',
                'quote_number_format',
                'workflow_settings',
                'notification_templates',
            ]);
        });
    }
};
