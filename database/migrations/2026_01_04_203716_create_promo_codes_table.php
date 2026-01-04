<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promo_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            
            // Discount Type
            $table->enum('discount_type', ['percentage', 'fixed', 'trial_days'])->default('percentage');
            $table->decimal('discount_value', 10, 2)->default(0); // % or fixed amount or days
            
            // Limits
            $table->integer('max_uses')->nullable(); // null = unlimited
            $table->integer('times_used')->default(0);
            $table->integer('max_uses_per_tenant')->default(1);
            
            // Validity
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            
            // Restrictions
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete(); // null = all plans
            $table->enum('billing_cycle', ['any', 'monthly', 'yearly'])->default('any');
            $table->boolean('first_subscription_only')->default(true);
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['code', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_codes');
    }
};
