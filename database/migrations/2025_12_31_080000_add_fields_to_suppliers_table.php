<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('type')->default('parts')->after('name'); // parts, services
            
            // Detailed address fields matching customers table
            $table->string('city')->nullable()->after('address');
            $table->string('region')->nullable()->after('city');
            $table->string('postal_code')->nullable()->after('region');
            $table->string('building_number')->nullable()->after('postal_code');
            $table->string('district')->nullable()->after('building_number');
            $table->string('street')->nullable()->after('district');
            $table->string('country')->nullable()->after('street');
            $table->decimal('lat', 10, 8)->nullable()->after('country');
            $table->decimal('lng', 11, 8)->nullable()->after('lat');
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn([
                'type',
                'city',
                'region',
                'postal_code',
                'building_number',
                'district',
                'street',
                'country',
                'lat',
                'lng'
            ]);
        });
    }
};
