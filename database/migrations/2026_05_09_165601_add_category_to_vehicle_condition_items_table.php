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
        Schema::table('vehicle_condition_items', function (Blueprint $table) {
            $table->string('category_ar')->nullable()->after('name_en');
            $table->string('category_en')->nullable()->after('category_ar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_condition_items', function (Blueprint $table) {
            //
        });
    }
};
