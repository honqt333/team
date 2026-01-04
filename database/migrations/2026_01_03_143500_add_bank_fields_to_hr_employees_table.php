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
        Schema::table('hr_employees', function (Blueprint $table) {
            $table->string('bank_name', 100)->nullable()->after('notes');
            $table->string('bank_iban', 34)->nullable()->after('bank_name');
            $table->string('bank_account_number', 50)->nullable()->after('bank_iban');
            $table->text('bank_notes')->nullable()->after('bank_account_number');
            $table->decimal('gosi_rate', 5, 2)->default(9.75)->after('bank_notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_employees', function (Blueprint $table) {
            $table->dropColumn([
                'bank_name',
                'bank_iban',
                'bank_account_number',
                'bank_notes',
                'gosi_rate',
            ]);
        });
    }
};
