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
        Schema::create('feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 30); // bug, feature_request, suggestion
            $table->string('module', 50); // work_orders, invoices, inventory, etc.
            $table->string('status', 30)->default('pending'); // pending, in_progress, resolved, closed
            $table->boolean('allow_admin_access')->default(false);
            $table->text('comments')->nullable();
            $table->string('video_path')->nullable();
            $table->unsignedInteger('video_duration_seconds')->nullable();

            // Admin response fields
            $table->text('admin_reply')->nullable();
            $table->string('admin_reply_video_path')->nullable();
            $table->timestamp('admin_replied_at')->nullable();
            $table->foreignId('admin_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('has_user_seen_reply')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'type']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedbacks');
    }
};
