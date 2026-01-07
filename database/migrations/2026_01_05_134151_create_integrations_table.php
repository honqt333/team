<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // System-level integrations (configured by super admin)
        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // sms, whatsapp, email, storage
            $table->string('provider'); // unifonic, twilio, 360dialog, mailgun, smtp
            $table->string('name');
            $table->string('name_ar');
            $table->text('description')->nullable();
            $table->json('config')->nullable(); // API keys, endpoints, etc.
            $table->boolean('is_active')->default(false);
            $table->boolean('is_default')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->unique(['type', 'provider']);
        });

        // Tenant-specific integration settings
        Schema::create('tenant_integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('integration_id')->constrained()->onDelete('cascade');
            $table->json('config')->nullable(); // Tenant-specific overrides
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['tenant_id', 'integration_id']);
        });

        // Integration logs for debugging
        Schema::create('integration_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('integration_id')->constrained()->onDelete('cascade');
            $table->string('action'); // send_sms, send_whatsapp, send_email
            $table->json('request')->nullable();
            $table->json('response')->nullable();
            $table->enum('status', ['success', 'failed', 'pending'])->default('pending');
            $table->text('error_message')->nullable();
            $table->integer('response_time_ms')->nullable();
            $table->timestamps();
            
            $table->index(['tenant_id', 'integration_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_logs');
        Schema::dropIfExists('tenant_integrations');
        Schema::dropIfExists('integrations');
    }
};
