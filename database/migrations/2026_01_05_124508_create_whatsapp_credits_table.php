<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // WhatsApp Credit Packages
        Schema::create('whatsapp_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_ar');
            $table->integer('credits');
            $table->decimal('price', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_popular')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Tenant WhatsApp Balance
        Schema::create('tenant_whatsapp_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->integer('balance')->default(0);
            $table->integer('total_purchased')->default(0);
            $table->integer('total_used')->default(0);
            $table->timestamps();
            
            $table->unique('tenant_id');
        });

        // WhatsApp Purchase History
        Schema::create('whatsapp_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('whatsapp_package_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('credits');
            $table->decimal('amount', 10, 2);
            $table->string('payment_gateway')->nullable();
            $table->string('payment_reference')->nullable();
            $table->enum('status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->json('payment_details')->nullable();
            $table->timestamps();
            
            $table->index(['tenant_id', 'status']);
        });

        // WhatsApp Usage Log
        Schema::create('whatsapp_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('center_id')->nullable()->constrained()->nullOnDelete();
            $table->string('phone_number');
            $table->string('message_type'); // template, session, media
            $table->string('template_name')->nullable();
            $table->integer('credits_used')->default(1);
            $table->string('provider')->nullable(); // whatsapp_cloud, twilio, 360dialog
            $table->string('provider_message_id')->nullable();
            $table->enum('status', ['sent', 'delivered', 'read', 'failed', 'pending'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_usage_logs');
        Schema::dropIfExists('whatsapp_purchases');
        Schema::dropIfExists('tenant_whatsapp_balances');
        Schema::dropIfExists('whatsapp_packages');
    }
};
