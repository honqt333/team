<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // System announcements/notifications
        Schema::create('system_announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('content');
            $table->enum('type', ['info', 'warning', 'important', 'maintenance'])->default('info');
            $table->enum('target', ['all', 'active', 'trial', 'expired', 'specific'])->default('all');
            $table->json('target_tenant_ids')->nullable();
            $table->json('channels')->nullable(); // in_app, email, sms
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->integer('views_count')->default(0);
            $table->timestamps();
        });

        // Tenant notification reads
        Schema::create('tenant_announcement_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('system_announcement_id')->constrained()->onDelete('cascade');
            $table->timestamp('read_at');
            
            $table->unique(['tenant_id', 'system_announcement_id'], 'tenant_announcement_unique');
        });

        // Notification send log
        Schema::create('notification_send_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('system_announcement_id')->constrained()->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('channel'); // email, sms, whatsapp
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index(['system_announcement_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_send_logs');
        Schema::dropIfExists('tenant_announcement_reads');
        Schema::dropIfExists('system_announcements');
    }
};
