<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->string('uuid')->nullable()->unique()->after('id');
            $table->text('rejection_reason')->nullable()->after('rejected_at');
        });

        // Generate UUIDs for existing quotes
        \DB::table('quotes')->whereNull('uuid')->orderBy('id')->each(function ($quote) {
            \DB::table('quotes')->where('id', $quote->id)->update(['uuid' => (string) Str::uuid()]);
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn(['uuid', 'rejection_reason']);
        });
    }
};
