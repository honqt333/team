<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Track E — Phase 1
 * Add tenant_id + center_id columns to all child / denormalized tables so the
 * global TenantScoped / CenterScoped traits can apply a hard `WHERE tenant_id`
 * filter even for ad-hoc raw queries (no parent eager-load required).
 *
 * Strategy:
 *   1. Add columns as NULLABLE.
 *   2. Backfill from the closest parent that already carries tenant_id.
 *   3. Keep nullable so legacy rows (orphan, soft-deleted parents) don't break the migration.
 *      The global scope will filter them out by definition since tenant_id IS NULL.
 *
 * Cross-driver note:
 *   The MySQL UPDATE/JOIN syntax is not portable to SQLite. We branch on the
 *   connection driver and emit the appropriate statement. SQLite uses
 *   UPDATE … FROM … WHERE; MySQL uses UPDATE … JOIN.
 */
return new class extends Migration
{
    private function driver(): string
    {
        return DB::connection()->getDriverName();
    }

    /**
     * Execute an UPDATE-with-join that works on both MySQL and SQLite.
     *
     * For maximum portability we use correlated subqueries — slower but
     * supported on every driver the project targets (mysql, sqlite).
     *
     * @param  string  $target  target table name
     * @param  string  $source  source table name (provides tenant/center)
     * @param  string  $fkColumn  foreign-key column on target that points at source.id
     * @param  array  $cols  ['target_col' => 'source_col', ...]
     */
    private function backfillFromParent(
        string $target,
        string $source,
        string $fkColumn,
        array $cols
    ): void {
        $set = collect($cols)
            ->map(fn ($srcCol, $tgtCol) => "{$tgtCol} = (SELECT {$srcCol} FROM {$source} WHERE id = {$target}.{$fkColumn})")
            ->implode(', ');

        DB::statement("UPDATE {$target} SET {$set}");
    }

    public function up(): void
    {
        // 1. inspection_items (template → tenant via inspection_templates)
        Schema::table('inspection_items', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('center_id')->nullable()->after('tenant_id')->constrained()->cascadeOnDelete();
            $table->index(['tenant_id', 'center_id']);
        });
        $this->backfillFromParent('inspection_items', 'inspection_templates', 'template_id', ['tenant_id' => 'tenant_id', 'center_id' => 'center_id']);
        // Reset FK with cascade (parent = tenant/center, not template)
        Schema::table('inspection_items', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropForeign(['center_id']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('center_id')->references('id')->on('centers')->cascadeOnDelete();
        });

        // 2. invoice_lines (invoice → tenant via invoices)
        Schema::table('invoice_lines', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('invoice_id')->constrained()->nullOnDelete();
            $table->foreignId('center_id')->nullable()->after('tenant_id')->constrained()->nullOnDelete();
            $table->index(['tenant_id', 'center_id']);
        });
        $this->backfillFromParent('invoice_lines', 'invoices', 'invoice_id', ['tenant_id' => 'tenant_id', 'center_id' => 'center_id']);
        Schema::table('invoice_lines', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropForeign(['center_id']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
            $table->foreign('center_id')->references('id')->on('centers')->nullOnDelete();
        });

        // 3. vehicle_mileage_logs (vehicle → tenant via vehicles)
        Schema::table('vehicle_mileage_logs', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('vehicle_id')->constrained()->nullOnDelete();
            $table->foreignId('center_id')->nullable()->after('tenant_id')->constrained()->nullOnDelete();
            $table->index(['tenant_id', 'center_id']);
        });
        $this->backfillFromParent('vehicle_mileage_logs', 'vehicles', 'vehicle_id', ['tenant_id' => 'tenant_id', 'center_id' => 'center_id']);
        Schema::table('vehicle_mileage_logs', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropForeign(['center_id']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
            $table->foreign('center_id')->references('id')->on('centers')->nullOnDelete();
        });

        // 4. purchase_invoice_lines (purchase_invoice → tenant via purchase_invoices)
        Schema::table('purchase_invoice_lines', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('purchase_invoice_id')->constrained()->nullOnDelete();
            $table->foreignId('center_id')->nullable()->after('tenant_id')->constrained()->nullOnDelete();
            $table->index(['tenant_id', 'center_id']);
        });
        $this->backfillFromParent('purchase_invoice_lines', 'purchase_invoices', 'purchase_invoice_id', ['tenant_id' => 'tenant_id', 'center_id' => 'center_id']);
        Schema::table('purchase_invoice_lines', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropForeign(['center_id']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
            $table->foreign('center_id')->references('id')->on('centers')->nullOnDelete();
        });

        // 5. work_order_damage_marks (work_order → tenant via work_orders)
        Schema::table('work_order_damage_marks', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('work_order_id')->constrained()->nullOnDelete();
            $table->foreignId('center_id')->nullable()->after('tenant_id')->constrained()->nullOnDelete();
            $table->index(['tenant_id', 'center_id']);
        });
        $this->backfillFromParent('work_order_damage_marks', 'work_orders', 'work_order_id', ['tenant_id' => 'tenant_id', 'center_id' => 'center_id']);
        Schema::table('work_order_damage_marks', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropForeign(['center_id']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
            $table->foreign('center_id')->references('id')->on('centers')->nullOnDelete();
        });

        // 6. quote_parts (quote → tenant via quotes)
        Schema::table('quote_parts', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('quote_id')->constrained()->nullOnDelete();
            $table->foreignId('center_id')->nullable()->after('tenant_id')->constrained()->nullOnDelete();
            $table->index(['tenant_id', 'center_id']);
        });
        $this->backfillFromParent('quote_parts', 'quotes', 'quote_id', ['tenant_id' => 'tenant_id', 'center_id' => 'center_id']);
        Schema::table('quote_parts', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropForeign(['center_id']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
            $table->foreign('center_id')->references('id')->on('centers')->nullOnDelete();
        });

        // 7. goods_received_notes (purchase_order → tenant via purchase_orders)
        Schema::table('goods_received_notes', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('purchase_order_id')->constrained()->nullOnDelete();
            $table->foreignId('center_id')->nullable()->after('tenant_id')->constrained()->nullOnDelete();
            $table->index(['tenant_id', 'center_id']);
        });
        $this->backfillFromParent('goods_received_notes', 'purchase_orders', 'purchase_order_id', ['tenant_id' => 'tenant_id', 'center_id' => 'center_id']);
        Schema::table('goods_received_notes', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropForeign(['center_id']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
            $table->foreign('center_id')->references('id')->on('centers')->nullOnDelete();
        });

        // 8. purchase_order_items (purchase_order → tenant via purchase_orders)
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('purchase_order_id')->constrained()->nullOnDelete();
            $table->foreignId('center_id')->nullable()->after('tenant_id')->constrained()->nullOnDelete();
            $table->index(['tenant_id', 'center_id']);
        });
        $this->backfillFromParent('purchase_order_items', 'purchase_orders', 'purchase_order_id', ['tenant_id' => 'tenant_id', 'center_id' => 'center_id']);
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropForeign(['center_id']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
            $table->foreign('center_id')->references('id')->on('centers')->nullOnDelete();
        });

        // 9. inventory_transfer_items (inventory_transfer → tenant via inventory_transfers)
        Schema::table('inventory_transfer_items', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('inventory_transfer_id')->constrained()->nullOnDelete();
            $table->foreignId('center_id')->nullable()->after('tenant_id')->constrained()->nullOnDelete();
            $table->index(['tenant_id', 'center_id']);
        });
        $this->backfillFromParent('inventory_transfer_items', 'inventory_transfers', 'inventory_transfer_id', ['tenant_id' => 'tenant_id', 'center_id' => 'center_id']);
        Schema::table('inventory_transfer_items', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropForeign(['center_id']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
            $table->foreign('center_id')->references('id')->on('centers')->nullOnDelete();
        });

        // 10. work_order_item_notes (work_order_item → work_order → tenant)
        Schema::table('work_order_item_notes', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('work_order_item_id')->constrained()->nullOnDelete();
            $table->foreignId('center_id')->nullable()->after('tenant_id')->constrained()->nullOnDelete();
            $table->index(['tenant_id', 'center_id']);
        });
        $this->backfillFromParent('work_order_item_notes', 'work_order_items', 'work_order_item_id', ['tenant_id' => 'tenant_id', 'center_id' => 'center_id']);
        Schema::table('work_order_item_notes', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropForeign(['center_id']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
            $table->foreign('center_id')->references('id')->on('centers')->nullOnDelete();
        });

        // 11. inventory_balances (warehouse → center → tenant)
        Schema::table('inventory_balances', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('warehouse_id')->constrained()->nullOnDelete();
            $table->foreignId('center_id')->nullable()->after('tenant_id')->constrained()->nullOnDelete();
            $table->index(['tenant_id', 'center_id']);
        });
        $this->backfillFromParent('inventory_balances', 'warehouses', 'warehouse_id', ['tenant_id' => 'tenant_id', 'center_id' => 'center_id']);
        Schema::table('inventory_balances', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropForeign(['center_id']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
            $table->foreign('center_id')->references('id')->on('centers')->nullOnDelete();
        });

        // 12. hr_payroll_items (payroll_run → tenant via hr_payroll_runs)
        Schema::table('hr_payroll_items', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('payroll_run_id')->constrained()->nullOnDelete();
            $table->foreignId('center_id')->nullable()->after('tenant_id')->constrained()->nullOnDelete();
            $table->index(['tenant_id', 'center_id']);
        });
        $this->backfillFromParent('hr_payroll_items', 'hr_payroll_runs', 'payroll_run_id', ['tenant_id' => 'tenant_id', 'center_id' => 'center_id']);
        Schema::table('hr_payroll_items', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropForeign(['center_id']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
            $table->foreign('center_id')->references('id')->on('centers')->nullOnDelete();
        });

        // 13. work_order_photos (work_order → tenant via work_orders)
        Schema::table('work_order_photos', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('work_order_id')->constrained()->nullOnDelete();
            $table->foreignId('center_id')->nullable()->after('tenant_id')->constrained()->nullOnDelete();
            $table->index(['tenant_id', 'center_id']);
        });
        $this->backfillFromParent('work_order_photos', 'work_orders', 'work_order_id', ['tenant_id' => 'tenant_id', 'center_id' => 'center_id']);
        Schema::table('work_order_photos', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropForeign(['center_id']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
            $table->foreign('center_id')->references('id')->on('centers')->nullOnDelete();
        });

        // 14. purchase_return_invoice_lines (purchase_return_invoice → tenant)
        Schema::table('purchase_return_invoice_lines', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('purchase_return_invoice_id')->constrained()->nullOnDelete();
            $table->foreignId('center_id')->nullable()->after('tenant_id')->constrained()->nullOnDelete();
            $table->index(['tenant_id', 'center_id']);
        });
        $this->backfillFromParent('purchase_return_invoice_lines', 'purchase_return_invoices', 'purchase_return_invoice_id', ['tenant_id' => 'tenant_id', 'center_id' => 'center_id']);
        Schema::table('purchase_return_invoice_lines', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropForeign(['center_id']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
            $table->foreign('center_id')->references('id')->on('centers')->nullOnDelete();
        });

        // 15. inventory_moves (warehouse → center → tenant)
        Schema::table('inventory_moves', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('warehouse_id')->constrained()->nullOnDelete();
            $table->foreignId('center_id')->nullable()->after('tenant_id')->constrained()->nullOnDelete();
            $table->index(['tenant_id', 'center_id']);
        });
        $this->backfillFromParent('inventory_moves', 'warehouses', 'warehouse_id', ['tenant_id' => 'tenant_id', 'center_id' => 'center_id']);
        Schema::table('inventory_moves', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropForeign(['center_id']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
            $table->foreign('center_id')->references('id')->on('centers')->nullOnDelete();
        });

        // 16. installments (subscription_invoice → tenant via subscription_invoices)
        Schema::table('installments', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('subscription_invoice_id')->constrained()->nullOnDelete();
            $table->index('tenant_id');
        });
        $this->backfillFromParent('installments', 'subscription_invoices', 'subscription_invoice_id', ['tenant_id' => 'tenant_id']);
        Schema::table('installments', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
        });

        // 17. grn_items (goods_received_note → purchase_order → tenant)
        Schema::table('grn_items', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('goods_received_note_id')->constrained()->nullOnDelete();
            $table->foreignId('center_id')->nullable()->after('tenant_id')->constrained()->nullOnDelete();
            $table->index(['tenant_id', 'center_id']);
        });
        $this->backfillFromParent('grn_items', 'goods_received_notes', 'goods_received_note_id', ['tenant_id' => 'tenant_id', 'center_id' => 'center_id']);
        Schema::table('grn_items', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropForeign(['center_id']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
            $table->foreign('center_id')->references('id')->on('centers')->nullOnDelete();
        });

        // 18. quote_lines (quote → tenant via quotes)
        Schema::table('quote_lines', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('quote_id')->constrained()->nullOnDelete();
            $table->foreignId('center_id')->nullable()->after('tenant_id')->constrained()->nullOnDelete();
            $table->index(['tenant_id', 'center_id']);
        });
        $this->backfillFromParent('quote_lines', 'quotes', 'quote_id', ['tenant_id' => 'tenant_id', 'center_id' => 'center_id']);
        Schema::table('quote_lines', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropForeign(['center_id']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
            $table->foreign('center_id')->references('id')->on('centers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        $tables = [
            'inspection_items', 'invoice_lines', 'vehicle_mileage_logs',
            'purchase_invoice_lines', 'work_order_damage_marks', 'quote_parts',
            'goods_received_notes', 'purchase_order_items', 'inventory_transfer_items',
            'work_order_item_notes', 'inventory_balances', 'hr_payroll_items',
            'work_order_photos', 'purchase_return_invoice_lines', 'inventory_moves',
            'installments', 'grn_items', 'quote_lines',
        ];
        foreach ($tables as $t) {
            Schema::table($t, function (Blueprint $table) use ($t) {
                foreach (['tenant_id', 'center_id'] as $col) {
                    if (Schema::hasColumn($t, $col)) {
                        try {
                            $table->dropForeign([$col]);
                        } catch (Throwable $e) {
                            // ignore — FK may not exist (driver-specific)
                        }
                    }
                }
            });
            Schema::table($t, function (Blueprint $table) use ($t) {
                if (Schema::hasColumn($t, 'center_id')) {
                    $table->dropColumn('center_id');
                }
                if (Schema::hasColumn($t, 'tenant_id')) {
                    $table->dropColumn('tenant_id');
                }
            });
        }
    }
};
