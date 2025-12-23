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
        Schema::table('work_orders', function (Blueprint $table) {
            $table->date('entry_date')->nullable()->after('status');
            $table->date('expected_end_date')->nullable()->after('entry_date');
            $table->text('customer_complaint')->nullable()->after('expected_end_date');
            $table->text('initial_assessment')->nullable()->after('customer_complaint');
            $table->integer('mileage')->nullable()->after('initial_assessment');
            $table->string('contact_name')->nullable()->after('mileage');
            $table->string('contact_phone')->nullable()->after('contact_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropColumn([
                'entry_date',
                'expected_end_date',
                'customer_complaint',
                'initial_assessment',
                'mileage',
                'contact_name',
                'contact_phone',
            ]);
        });
    }
};
