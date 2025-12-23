<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('center_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('plate_number');
            $table->foreignId('make_id')->nullable()->constrained('vehicle_makes')->nullOnDelete();
            $table->foreignId('model_id')->nullable()->constrained('vehicle_models')->nullOnDelete();
            $table->string('make_other')->nullable();
            $table->string('model_other')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('color')->nullable();
            $table->string('vin')->nullable();
            $table->unsignedInteger('odometer')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Unique plate per tenant/center
            $table->unique(['tenant_id', 'center_id', 'plate_number']);
            
            // Indexes for common queries
            $table->index('customer_id');
            $table->index('make_id');
            $table->index('model_id');
            $table->index('year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
