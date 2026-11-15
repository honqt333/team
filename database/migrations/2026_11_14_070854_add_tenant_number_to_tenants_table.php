<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('tenant_number', 20)->nullable()->unique()->after('id');
        });

        // Backfill existing tenants with non-sequential 6-digit numbers so tenant count is not exposed.
        $usedNumbers = DB::table('tenants')
            ->whereNotNull('tenant_number')
            ->pluck('tenant_number')
            ->flip()
            ->all();

        $tenantIds = DB::table('tenants')
            ->whereNull('tenant_number')
            ->pluck('id');

        foreach ($tenantIds as $tenantId) {
            do {
                $candidate = (string) random_int(100000, 999999);
            } while (isset($usedNumbers[$candidate]));

            $usedNumbers[$candidate] = true;

            DB::table('tenants')
                ->where('id', $tenantId)
                ->update(['tenant_number' => $candidate]);
        }
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropUnique(['tenant_number']);
            $table->dropColumn('tenant_number');
        });
    }
};
