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
        Schema::create('work_order_labor_entries', function (Blueprint $table) {
            $table->id();

            // Multi-tenant + center scoping. The CenterScoped trait on the
            // model reads tenant_id and center_id for its global scope,
            // so the columns MUST be present even though we also store
            // the parent work_order_id for direct joins.
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('center_id')->constrained()->cascadeOnDelete();

            $table->foreignId('work_order_id')->constrained('work_orders')->cascadeOnDelete();
            $table->foreignId('work_order_item_id')->constrained('work_order_items')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Session timing. Only one of {paused_at, ended_at} can be
            // set at a time:
            //   running   -> started_at set, paused_at NULL, ended_at NULL
            //   paused    -> started_at set, paused_at set, ended_at NULL
            //   completed -> started_at set, ended_at set, paused_at NULL
            // (paused_at is cleared on resume so the duration calc is
            //  trivial: sum(ended_at - resumed_at) over the run spans.)
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('resumed_at')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('ended_at')->nullable();

            // Accumulator of time spent in the paused state. We store
            // this as a running integer so we don't have to walk a child
            // pause/resume table to compute effective work duration.
            $table->unsignedBigInteger('paused_duration_seconds')->default(0);

            // running | paused | completed
            $table->string('status', 20);

            $table->string('pause_reason', 255)->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            // Lookup hot paths:
            //  1. "does user X have a running session in tenant Y?" ->
            //     (tenant_id, user_id, status)
            //  2. "list sessions for this work order item" ->
            //     (work_order_item_id, status)
            //  3. tenant + center scope (defense in depth)
            $table->index(['tenant_id', 'center_id'], 'wole_tenant_center_idx');
            $table->index(['tenant_id', 'user_id', 'status'], 'wole_user_status_idx');
            $table->index(['work_order_item_id', 'status'], 'wole_item_status_idx');
            $table->index(['work_order_id', 'status'], 'wole_order_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_order_labor_entries');
    }
};
