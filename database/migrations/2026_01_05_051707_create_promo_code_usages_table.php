<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promo_code_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promo_code_id')->constrained()->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_id')->nullable()->constrained()->onDelete('set null');
            
            // Discount details at time of use
            $table->string('discount_type'); // percentage, fixed, trial_days
            $table->decimal('discount_value', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0); // Actual amount saved
            
            $table->timestamp('used_at');
            $table->timestamps();
            
            // Index for quick lookups
            $table->index(['promo_code_id', 'used_at']);
            $table->index(['tenant_id', 'used_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_code_usages');
    }
};
