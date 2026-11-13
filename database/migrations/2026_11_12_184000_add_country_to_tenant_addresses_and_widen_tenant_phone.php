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
        Schema::table('tenant_addresses', function (Blueprint $table) {
            if (! Schema::hasColumn('tenant_addresses', 'country')) {
                $table->string('country', 100)->nullable()->after('postal_code');
            }
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->string('phone', 30)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->change();
        });

        Schema::table('tenant_addresses', function (Blueprint $table) {
            if (Schema::hasColumn('tenant_addresses', 'country')) {
                $table->dropColumn('country');
            }
        });
    }
};
