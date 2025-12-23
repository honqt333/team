<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rename current name to name_ar as departments likely have Arabic names
        Schema::table('departments', function (Blueprint $table) {
            $table->renameColumn('name', 'name_ar');
        });
        
        Schema::table('departments', function (Blueprint $table) {
            $table->string('name_en')->nullable()->after('name_ar');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->renameColumn('name', 'name_ar');
        });
        
        Schema::table('services', function (Blueprint $table) {
            $table->string('name_en')->nullable()->after('name_ar');
            $table->text('description_ar')->nullable()->after('description');
            $table->renameColumn('description', 'description_en');
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn('name_en');
            $table->renameColumn('name_ar', 'name');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('name_en');
            $table->dropColumn('description_ar');
            $table->renameColumn('name_ar', 'name');
            $table->renameColumn('description_en', 'description');
        });
    }
};
