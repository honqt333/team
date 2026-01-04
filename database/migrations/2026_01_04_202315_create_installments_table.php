<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_invoice_id')->constrained()->onDelete('cascade');
            
            $table->integer('installment_number');
            $table->decimal('amount', 10, 2);
            $table->date('due_date');
            
            $table->enum('status', ['pending', 'paid', 'overdue', 'cancelled'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            
            // Payment Reference
            $table->string('payment_gateway')->nullable();
            $table->string('payment_reference')->nullable();
            
            $table->timestamps();
            
            $table->index(['subscription_invoice_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installments');
    }
};
