<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tenant backups: snapshot of a tenant's data captured at a point in
 * time, used for both the on-demand restore flow and the automatic
 * safety net before destructive operations (forceDelete, etc.).
 *
 * @bypass-tenancy-scanner - This table holds system-level records that
 * intentionally span across all tenants, and lives outside the
 * TenantScoped concern. Reads/writes go through BackupService.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_backups', function (Blueprint $table) {
            $table->id();
            // nullOnDelete instead of cascadeOnDelete so pre-delete backups
            // survive the tenant forceDelete that follows them. The backup
            // row is the safety net — it must outlive the tenant.
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('type')->default('manual'); // manual | scheduled | pre_delete
            $table->string('status')->default('pending'); // pending | running | completed | failed
            $table->string('disk')->default('local'); // storage disk
            $table->string('path')->nullable(); // relative path on disk
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->json('manifest')->nullable(); // list of tables + row counts captured
            $table->string('checksum')->nullable(); // sha256 of the archive, integrity check
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by_admin_id')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_backups');
    }
};
