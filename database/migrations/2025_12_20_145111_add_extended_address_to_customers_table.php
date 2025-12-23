<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Extended address fields
            $table->string('building_number')->nullable()->after('address_line');
            $table->string('postal_code')->nullable()->after('building_number');
            $table->string('district')->nullable()->after('postal_code');
            $table->string('city')->nullable()->after('district');
            $table->string('region')->nullable()->after('city');
            $table->string('country')->nullable()->after('region');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'building_number',
                'postal_code',
                'district',
                'city',
                'region',
                'country',
            ]);
        });
    }
};
