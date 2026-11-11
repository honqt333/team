<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('company_transactions', 'is_vat_inclusive')) {
                $table->boolean('is_vat_inclusive')->default(false)->after('is_taxable');
            }

            if (! Schema::hasColumn('company_transactions', 'attachment_path')) {
                $table->string('attachment_path')->nullable()->after('notes');
            }

            if (! Schema::hasColumn('company_transactions', 'approved_at')) {
                $table->date('approved_at')->nullable()->after('approved_by');
            }
        });

        Schema::table('income_categories', function (Blueprint $table) {
            if (! Schema::hasColumn('income_categories', 'is_taxable')) {
                $table->boolean('is_taxable')->default(false)->after('transaction_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('company_transactions', function (Blueprint $table) {
            $drop = [];

            foreach (['is_vat_inclusive', 'attachment_path', 'approved_at'] as $column) {
                if (Schema::hasColumn('company_transactions', $column)) {
                    $drop[] = $column;
                }
            }

            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });

        Schema::table('income_categories', function (Blueprint $table) {
            if (Schema::hasColumn('income_categories', 'is_taxable')) {
                $table->dropColumn('is_taxable');
            }
        });
    }
};
