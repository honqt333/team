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
        Schema::table('work_order_items', function (Blueprint $table) {
            // Technician fields for workshop operations
            // Note: started_at and completed_at already exist from 2025_12_28 migration
            $table->text('technician_notes')->nullable()->after('notes');
            $table->unsignedInteger('estimated_duration')->nullable()->after('technician_notes')->comment('Estimated duration in minutes');
            $table->unsignedInteger('actual_duration')->nullable()->after('estimated_duration')->comment('Actual duration in minutes');
            // Use string for SQLite compatibility (enum not fully supported)
            $table->string('priority', 20)->default('normal')->after('actual_duration');
            // Note: started_at already exists, use paused_at for timer pause tracking
            $table->timestamp('paused_at')->nullable()->after('started_at');
            $table->unsignedInteger('paused_duration')->default(0)->after('paused_at')->comment('Total paused duration in seconds');
            // Use text for SQLite compatibility (json not fully supported)
            $table->text('checklist')->nullable()->after('paused_duration')->comment('Checklist items as JSON');
        });
    }

    public function down(): void
    {
        Schema::table('work_order_items', function (Blueprint $table) {
            $table->dropColumn([
                'technician_notes',
                'estimated_duration',
                'actual_duration',
                'priority',
                'paused_at',
                'paused_duration',
                'checklist',
            ]);
        });
    }
};
