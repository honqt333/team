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
        Schema::table('hr_job_titles', function (Blueprint $table) {
            $table->string('default_role_name')->nullable()->after('name_en');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_job_titles', function (Blueprint $table) {
            $table->dropColumn('default_role_name');
        });
    }
};
