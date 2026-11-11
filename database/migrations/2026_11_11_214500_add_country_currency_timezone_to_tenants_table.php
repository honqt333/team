<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (! Schema::hasColumn('tenants', 'country')) {
                $table->string('country', 2)->default('SA')->after('iban');
            }

            if (! Schema::hasColumn('tenants', 'currency')) {
                $table->string('currency', 3)->default('SAR')->after('country');
            }

            if (! Schema::hasColumn('tenants', 'timezone')) {
                $table->string('timezone', 64)->nullable()->after('currency');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $drop = [];

            foreach (['country', 'currency', 'timezone'] as $column) {
                if (Schema::hasColumn('tenants', $column)) {
                    $drop[] = $column;
                }
            }

            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });
    }
};
