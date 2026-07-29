<?php

declare(strict_types=1);

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
        Schema::create('work_order_item_quality_checks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('center_id')->constrained()->cascadeOnDelete();
            $table->foreignId('work_order_id')->constrained('work_orders')->cascadeOnDelete();
            $table->foreignId('work_order_item_id')->constrained('work_order_items')->cascadeOnDelete();
            $table->foreignId('checked_by_user_id')->constrained('users')->cascadeOnDelete();

            // approved | rejected
            $table->string('decision', 20);
            $table->text('notes')->nullable();
            $table->timestamp('checked_at')->useCurrent();

            $table->timestamps();

            // Lookup hot paths:
            //   - "show me the latest decision for this item"
            //   - "list all quality checks for this work order"
            //   - "audit trail of every rejection for this user"
            $table->index(['tenant_id', 'center_id'], 'woiqc_tenant_center_idx');
            $table->index(['work_order_item_id', 'created_at'], 'woiqc_item_time_idx');
            $table->index(['work_order_id', 'created_at'], 'woiqc_order_time_idx');
            $table->index(['checked_by_user_id', 'created_at'], 'woiqc_user_time_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_order_item_quality_checks');
    }
};
