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
        // 1. Inspection Templates
        Schema::create('inspection_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('center_id')->constrained()->onDelete('cascade');
            $table->json('name'); // Localized: {"ar": "...", "en": "..."}
            $table->json('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Inspection Items
        Schema::create('inspection_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('inspection_templates')->onDelete('cascade');
            $table->json('category')->nullable(); // Localized: {"ar": "محرك", "en": "Engine"}
            $table->json('name'); // Localized: {"ar": "مستوى الزيت", "en": "Oil Level"}
            $table->json('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 3. Work Order Inspections
        Schema::create('work_order_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('center_id')->constrained()->onDelete('cascade');
            $table->foreignId('work_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('template_id')->constrained('inspection_templates')->onDelete('cascade');
            $table->foreignId('performed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('performed_at')->nullable();
            $table->json('results')->nullable(); // [{"item_id": 1, "status": "good|warning|danger", "notes": "...", "photos": []}]
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_order_inspections');
        Schema::dropIfExists('inspection_items');
        Schema::dropIfExists('inspection_templates');
    }
};
