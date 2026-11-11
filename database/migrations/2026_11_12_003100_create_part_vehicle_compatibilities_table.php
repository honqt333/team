<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('part_vehicle_compatibilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('part_id')->constrained('parts')->cascadeOnDelete();
            $table->foreignId('vehicle_make_id')->constrained('vehicle_makes')->cascadeOnDelete();
            $table->foreignId('vehicle_model_id')->nullable()->constrained('vehicle_models')->nullOnDelete();
            $table->unsignedSmallInteger('year_from')->nullable();
            $table->unsignedSmallInteger('year_to')->nullable();
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'part_id']);
            $table->index(['tenant_id', 'vehicle_make_id', 'vehicle_model_id'], 'part_compat_tenant_make_model_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('part_vehicle_compatibilities');
    }
};
