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
        Schema::create('vehicle_mileage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('mileage'); // The new mileage value
            $table->unsignedInteger('previous_mileage')->nullable(); // The previous mileage value
            $table->integer('difference')->default(0); // Difference (new - old)
            $table->nullableMorphs('reference'); // Morph to Quote or WorkOrder
            $table->string('reference_code')->nullable(); // Store code for easy display
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index('vehicle_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_mileage_logs');
    }
};
