<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_makes', function (Blueprint $table) {
            $table->foreignId('updated_by')->nullable()->after('is_active')
                ->constrained('users')->nullOnDelete();
        });

        Schema::table('vehicle_models', function (Blueprint $table) {
            $table->foreignId('updated_by')->nullable()->after('is_active')
                ->constrained('users')->nullOnDelete();
        });

        Schema::table('vehicle_colors', function (Blueprint $table) {
            $table->foreignId('updated_by')->nullable()->after('is_active')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_makes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('updated_by');
        });

        Schema::table('vehicle_models', function (Blueprint $table) {
            $table->dropConstrainedForeignId('updated_by');
        });

        Schema::table('vehicle_colors', function (Blueprint $table) {
            $table->dropConstrainedForeignId('updated_by');
        });
    }
};
