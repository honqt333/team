<?php

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
        // 1. Audit Snapshots
        Schema::create('dev_audit_snapshots', function (Blueprint $table) {
            $table->id();
            $table->decimal('score_overall', 5, 2);
            $table->decimal('score_architecture', 5, 2);
            $table->decimal('score_security', 5, 2);
            $table->decimal('score_performance', 5, 2);
            $table->decimal('score_testing', 5, 2);
            $table->decimal('score_ui', 5, 2);
            $table->decimal('score_documentation', 5, 2);
            $table->integer('files_analyzed_count');
            $table->integer('violations_count');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['score_overall', 'created_at']);
        });

        // 2. Audit Violations Log
        Schema::create('dev_audit_violations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('snapshot_id')->constrained('dev_audit_snapshots')->cascadeOnDelete();
            $table->string('category', 50); // e.g., 'security', 'architecture', 'ui', 'performance', 'database', 'business_logic'
            $table->enum('severity', ['low', 'medium', 'high', 'critical']);
            $table->string('file_path', 512);
            $table->integer('line_number')->nullable();
            $table->string('violation_code', 100);
            $table->text('description');
            $table->timestamps();

            $table->index(['category', 'violation_code']);
        });

        // 3. Slow Queries and N+1 Logs
        Schema::create('dev_slow_queries_log', function (Blueprint $table) {
            $table->id();
            $table->text('sql_statement');
            $table->decimal('execution_time_ms', 8, 2);
            $table->string('caller', 255);
            $table->integer('caller_line')->nullable();
            $table->enum('type', ['slow', 'n_plus_one']);
            $table->integer('occurrences')->default(1);
            $table->timestamps();

            $table->index(['type', 'execution_time_ms']);
        });

        // 4. UI Component Compliance Statistics
        Schema::create('dev_component_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('snapshot_id')->constrained('dev_audit_snapshots')->cascadeOnDelete();
            $table->string('component_name', 100);
            $table->integer('count_compliant');
            $table->integer('count_violations');
            $table->timestamps();
        });

        // 5. AI Refactoring Memory
        Schema::create('dev_ai_memory', function (Blueprint $table) {
            $table->id();
            $table->string('module_name', 100);
            $table->string('action_taken', 255);
            $table->text('reason');
            $table->enum('status', ['suggested', 'in_progress', 'completed', 'skipped'])->default('suggested');
            $table->timestamp('refactored_at')->nullable();
            $table->timestamps();

            $table->index(['module_name', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dev_ai_memory');
        Schema::dropIfExists('dev_component_stats');
        Schema::dropIfExists('dev_slow_queries_log');
        Schema::dropIfExists('dev_audit_violations');
        Schema::dropIfExists('dev_audit_snapshots');
    }
};
