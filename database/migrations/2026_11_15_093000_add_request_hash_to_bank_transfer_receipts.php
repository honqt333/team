<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bank_transfer_receipts')) {
            Schema::table('bank_transfer_receipts', function (Blueprint $table): void {
                if (! Schema::hasColumn('bank_transfer_receipts', 'request_hash')) {
                    $table->string('request_hash', 64)->nullable()->after('idempotency_key');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bank_transfer_receipts')) {
            Schema::table('bank_transfer_receipts', function (Blueprint $table): void {
                if (Schema::hasColumn('bank_transfer_receipts', 'request_hash')) {
                    $table->dropColumn('request_hash');
                }
            });
        }
    }
};
