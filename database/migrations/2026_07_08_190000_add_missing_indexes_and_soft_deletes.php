<?php

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
        // 1. Soft Deletes on vehicles
        Schema::table('vehicles', function (Blueprint $table) {
            if (!Schema::hasColumn('vehicles', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // 2. Indexes
        Schema::table('admin_activity_logs', function (Blueprint $table) {
            $table->index('model_id');
        });

        Schema::table('biometric_devices', function (Blueprint $table) {
            $table->index('device_id');
        });

        Schema::table('company_transactions', function (Blueprint $table) {
            $table->index('contact_id');
        });

        Schema::table('hr_attendance', function (Blueprint $table) {
            $table->index('device_id');
        });

        Schema::table('hr_employees', function (Blueprint $table) {
            $table->index('national_id');
            $table->index('profession_on_id');
        });

        Schema::table('quote_parts', function (Blueprint $table) {
            $table->index('unit_id');
        });

        Schema::table('sms_usage_logs', function (Blueprint $table) {
            $table->index('provider_message_id');
        });

        Schema::table('whatsapp_usage_logs', function (Blueprint $table) {
            $table->index('provider_message_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_usage_logs', function (Blueprint $table) {
            $table->dropIndex(['provider_message_id']);
        });

        Schema::table('sms_usage_logs', function (Blueprint $table) {
            $table->dropIndex(['provider_message_id']);
        });

        Schema::table('quote_parts', function (Blueprint $table) {
            $table->dropIndex(['unit_id']);
        });

        Schema::table('hr_employees', function (Blueprint $table) {
            $table->dropIndex(['profession_on_id']);
            $table->dropIndex(['national_id']);
        });

        Schema::table('hr_attendance', function (Blueprint $table) {
            $table->dropIndex(['device_id']);
        });

        Schema::table('company_transactions', function (Blueprint $table) {
            $table->dropIndex(['contact_id']);
        });

        Schema::table('biometric_devices', function (Blueprint $table) {
            $table->dropIndex(['device_id']);
        });

        Schema::table('admin_activity_logs', function (Blueprint $table) {
            $table->dropIndex(['model_id']);
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
