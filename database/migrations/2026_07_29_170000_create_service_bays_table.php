<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_bays', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('center_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('code')->nullable();
            $table->string('type', 32)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            // Name uniqueness inside a center (not across the whole
            // tenants table — different centers can have similar bay
            // names).
            $table->unique(
                ['tenant_id', 'center_id', 'name'],
                'service_bays_center_name_unique'
            );
            $table->index(['center_id', 'is_active'], 'service_bays_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_bays');
    }
};
