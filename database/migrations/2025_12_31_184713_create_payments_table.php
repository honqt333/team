<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('center_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            
            // Payment Details
            $table->decimal('amount', 12, 2);
            $table->timestamp('payment_date');
            $table->enum('payment_method', ['cash', 'card', 'transfer', 'credit'])->default('cash');
            $table->string('reference')->nullable(); // رقم الإيصال أو رقم التحويل
            $table->text('notes')->nullable();
            
            // Who received
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['invoice_id', 'payment_date']);
            $table->index(['tenant_id', 'payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
