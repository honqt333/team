<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('legal_name_en')->nullable()->after('legal_name');
            $table->string('owner_name')->nullable()->after('trade_name');
            $table->string('iban', 34)->nullable()->after('cr_number');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['legal_name_en', 'owner_name', 'iban']);
        });
    }
};
