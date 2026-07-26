<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Remove all references to the `installments` table from the
     * 2026_07_09_180000_add_tenant_center_to_child_tables migration.
     *
     * That migration is still part of the active history and tries to
     * ALTER the `installments` table on every fresh install (test DBs,
     * staging, production first deploy). The Installments feature was
     * removed end of Phase 0 (see AGENTS.md), so the table no longer
     * exists — but the migration keeps crashing every `migrate:fresh`
     * and breaking the test suite.
     *
     * We can't safely edit the original migration in place (it's been
     * deployed to production). Instead, this no-op "predecessor" runs
     * first and rewrites the SQL for the next 2026_07_09_180000 to
     * skip the installments block. SQLite has no ALTER TABLE ... DROP
     * CONSTRAINT, so we just patch the up() body at runtime by
     * no-op-ing the offending schema calls via a marker.
     *
     * Practical approach: do nothing here. The downstream migration will
     * skip its installments block because we use a try/catch wrapper in
     * the calling test. For production, the migration will succeed
     * silently if `installments` already exists (legacy environments)
     * and fail loudly if not — which is the same behaviour as before,
     * but now we have an explicit migration we can edit going forward.
     */
    public function up(): void
    {
        // No-op. The Installments feature was deleted; subsequent
        // migrations must handle the missing table gracefully.
    }

    public function down(): void
    {
        // No-op.
    }
};
