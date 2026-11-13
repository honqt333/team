<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_moves', function (Blueprint $table) {
            if (! Schema::hasColumn('inventory_moves', 'balance_before')) {
                $table->decimal('balance_before', 12, 3)->nullable()->after('total_cost');
            }

            if (! Schema::hasColumn('inventory_moves', 'wac_before')) {
                $table->decimal('wac_before', 12, 4)->nullable()->after('balance_after');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inventory_moves', function (Blueprint $table) {
            $columns = [];

            if (Schema::hasColumn('inventory_moves', 'balance_before')) {
                $columns[] = 'balance_before';
            }

            if (Schema::hasColumn('inventory_moves', 'wac_before')) {
                $columns[] = 'wac_before';
            }

            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
