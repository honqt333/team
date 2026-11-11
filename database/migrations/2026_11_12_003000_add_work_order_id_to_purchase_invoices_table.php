<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->foreignId('work_order_id')
                ->nullable()
                ->after('purchase_order_id')
                ->constrained('work_orders')
                ->nullOnDelete();

            $table->index(['tenant_id', 'work_order_id'], 'purchase_invoices_tenant_wo_idx');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->dropIndex('purchase_invoices_tenant_wo_idx');
            $table->dropConstrainedForeignId('work_order_id');
        });
    }
};
