<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('purchase_invoices') && ! Schema::hasColumn('purchase_invoices', 'source_type')) {
            Schema::table('purchase_invoices', function (Blueprint $table) {
                $table->string('source_type', 40)->nullable()->after('work_order_id');
            });
        }

        if (Schema::hasTable('purchase_invoice_lines')) {
            Schema::table('purchase_invoice_lines', function (Blueprint $table) {
                $table->unsignedBigInteger('part_id')->nullable()->change();

                if (! Schema::hasColumn('purchase_invoice_lines', 'description')) {
                    $table->string('description')->nullable()->after('part_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('purchase_invoice_lines') && Schema::hasColumn('purchase_invoice_lines', 'description')) {
            Schema::table('purchase_invoice_lines', function (Blueprint $table) {
                $table->dropColumn('description');
            });
        }

        if (Schema::hasTable('purchase_invoices') && Schema::hasColumn('purchase_invoices', 'source_type')) {
            Schema::table('purchase_invoices', function (Blueprint $table) {
                $table->dropColumn('source_type');
            });
        }
    }
};
