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
        Schema::table('tenant_addresses', function (Blueprint $table) {
            $table->string('street')->nullable()->after('address_line');
            $table->decimal('latitude', 10, 8)->nullable()->after('postal_code');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
        });

        Schema::table('center_addresses', function (Blueprint $table) {
            $table->string('street')->nullable()->after('address_line');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenant_addresses', function (Blueprint $table) {
            $table->dropColumn(['street', 'latitude', 'longitude']);
        });

        Schema::table('center_addresses', function (Blueprint $table) {
            $table->dropColumn(['street']);
        });
    }
};
