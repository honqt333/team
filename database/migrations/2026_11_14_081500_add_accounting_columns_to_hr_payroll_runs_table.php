<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hr_payroll_runs', function (Blueprint $table) {
            $table->foreignId('company_transaction_id')
                ->nullable()
                ->after('approved_by')
                ->constrained('company_transactions')
                ->nullOnDelete();
            $table->timestamp('accounting_posted_at')->nullable()->after('company_transaction_id');
        });
    }

    public function down(): void
    {
        Schema::table('hr_payroll_runs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_transaction_id');
            $table->dropColumn('accounting_posted_at');
        });
    }
};
