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
            $table->string('reception_signature')->nullable();
            $table->string('delivery_signature')->nullable();
            $table->timestamp('reception_signed_at')->nullable();
            $table->timestamp('delivery_signed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropColumn([
                'reception_signature',
                'delivery_signature',
                'reception_signed_at',
                'delivery_signed_at',
            ]);
        });
    }
};
