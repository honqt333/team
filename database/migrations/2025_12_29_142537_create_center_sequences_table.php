<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('center_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('center_id')->constrained()->cascadeOnDelete();
            $table->string('type', 50); // invoice, quote, work_order, etc.
            $table->string('prefix', 10)->nullable();
            $table->unsignedBigInteger('current_value')->default(0);
            $table->integer('year')->nullable(); // Optional: Reset per year if needed
            $table->timestamps();

            // Lock row specific to Tenant+Center+Type+(Year)
            $table->unique(['tenant_id', 'center_id', 'type', 'year'], 'center_seq_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('center_sequences');
    }
};
