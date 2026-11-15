<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('center_addresses', function (Blueprint $table) {
            $table->string('country', 100)->nullable()->after('postal_code');
        });
    }

    public function down(): void
    {
        Schema::table('center_addresses', function (Blueprint $table) {
            $table->dropColumn('country');
        });
    }
};
