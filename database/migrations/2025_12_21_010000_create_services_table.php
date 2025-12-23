<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('center_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('category')->nullable(); // e.g., 'maintenance', 'washing', 'electrical'
            $table->decimal('default_price', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'center_id']);
            $table->index('category');
        });

        // Add service_id to work_order_items
        Schema::table('work_order_items', function (Blueprint $table) {
            $table->foreignId('service_id')->nullable()->after('work_order_id')->constrained('services')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('work_order_items', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
            $table->dropColumn('service_id');
        });
        
        Schema::dropIfExists('services');
    }
};
