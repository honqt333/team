<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('sku', 50)->index();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->string('unit', 20)->default('piece'); // piece, liter, kg, meter
            $table->string('category')->nullable();
            $table->text('description')->nullable();
            $table->decimal('min_qty', 12, 3)->default(0);
            $table->decimal('reorder_qty', 12, 3)->default(0);
            $table->decimal('default_sale_price', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'sku']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parts');
    }
};
