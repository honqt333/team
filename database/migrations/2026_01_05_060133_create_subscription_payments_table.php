<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subscription_invoice_id')->nullable()->constrained()->nullOnDelete();
            
            // Payment Amount
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('SAR');
            
            // Gateway Info
            $table->string('gateway'); // moyasar, tap, paytabs, etc.
            $table->string('gateway_payment_id')->nullable(); // ID from gateway
            $table->string('gateway_ref')->nullable(); // Reference number
            
            // Status
            $table->enum('status', ['pending', 'initiated', 'paid', 'failed', 'refunded', 'cancelled'])
                  ->default('pending');
            
            // Payment Method
            $table->string('payment_method')->nullable(); // mada, visa, mastercard, applepay
            
            // Gateway Response
            $table->json('gateway_response')->nullable();
            $table->text('failure_reason')->nullable();
            
            // Timestamps
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['tenant_id', 'status']);
            $table->index(['subscription_id']);
            $table->index(['gateway', 'gateway_payment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
    }
};
