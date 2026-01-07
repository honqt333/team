<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 2FA for Admin Users
        Schema::table('admin_users', function (Blueprint $table) {
            $table->string('two_factor_secret')->nullable();
            $table->string('two_factor_recovery_codes', 1000)->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
        });

        // 2FA for regular Users
        Schema::table('users', function (Blueprint $table) {
            $table->string('two_factor_secret')->nullable();
            $table->string('two_factor_recovery_codes', 1000)->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
        });

        // Tenant 2FA Settings
        Schema::table('tenants', function (Blueprint $table) {
            $table->boolean('two_factor_enabled')->default(false);
            $table->enum('two_factor_enforcement', ['disabled', 'optional', 'required'])->default('disabled');
        });
    }

    public function down(): void
    {
        Schema::table('admin_users', function (Blueprint $table) {
            $table->dropColumn(['two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at']);
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['two_factor_enabled', 'two_factor_enforcement']);
        });
    }
};
