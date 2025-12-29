<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create tenant_tax_settings table
        Schema::create('tenant_tax_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->boolean('vat_enabled')->default(false);
            $table->decimal('vat_rate', 5, 2)->default(15.00);
            $table->enum('pricing_mode', ['inclusive', 'exclusive'])->default('exclusive');
            $table->enum('rounding_mode', ['half_up', 'half_down'])->default('half_up');
            $table->string('currency_code', 3)->default('SAR');
            $table->string('tax_number')->nullable();
            $table->timestamps();
            
            $table->unique('tenant_id');
        });

        // 2. Create invoices table
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('center_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete(); // Required for ZATCA
            $table->foreignId('work_order_id')->nullable()->constrained()->nullOnDelete();
            
            // Identification
            $table->string('invoice_number'); // Sequential per center
            $table->timestamp('issue_date');
            $table->date('supply_date');
            
            // Types
            $table->enum('type', ['invoice', 'credit_note', 'debit_note'])->default('invoice');
            $table->enum('subtype', ['simplified', 'standard'])->default('simplified');
            $table->enum('status', ['draft', 'valid', 'reported', 'cancelled'])->default('draft');
            
            // Snapshots (Immutability)
            $table->string('customer_name_snapshot')->nullable();
            $table->string('customer_vat_snapshot')->nullable();
            $table->text('customer_address_snapshot')->nullable();
            
            // Tax Snapshots
            $table->boolean('tax_enabled_snapshot')->default(false);
            $table->enum('pricing_mode_snapshot', ['inclusive', 'exclusive'])->default('exclusive');
            $table->decimal('tax_rate_snapshot', 5, 2)->default(15.00);
            $table->string('currency_code', 3)->default('SAR');
            
            // Totals
            $table->decimal('total_excl_tax', 12, 2)->default(0);
            $table->decimal('total_tax', 12, 2)->default(0);
            $table->decimal('total_incl_tax', 12, 2)->default(0);
            $table->decimal('total_taxable_amount', 12, 2)->default(0);
            $table->json('tax_breakdown')->nullable(); // JSON breakdown
            
            // ZATCA Fields
            $table->text('zatca_qr_tlv')->nullable(); // Phase 1
            $table->string('zatca_uuid')->nullable(); // Phase 2
            $table->text('zatca_hash')->nullable(); // Phase 2
            $table->text('zatca_prev_hash')->nullable(); // Phase 2
            $table->string('xml_path')->nullable(); // Phase 2
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['tenant_id', 'center_id', 'invoice_number']);
        });

        // 3. Create invoice_lines table
        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->decimal('qty', 10, 2);
            $table->decimal('unit_price', 10, 2); // Snapshot of price at time of invoice
            
            // Tax Fields per Line
            $table->boolean('is_taxable')->default(true);
            $table->string('tax_category_code', 10)->default('S'); // S, Z, E, O
            $table->decimal('tax_rate_snapshot', 5, 2)->default(15.00);
            $table->decimal('tax_amount', 10, 2)->default(0);
            
            // Line Totals
            $table->decimal('line_total_excl_tax', 12, 2)->default(0);
            $table->decimal('line_total_incl_tax', 12, 2)->default(0);
            
            $table->timestamps();
        });

        // 4. Add Tax Fields to Quotes, WorkOrders
        $documents = ['quotes', 'work_orders'];
        foreach ($documents as $doc) {
            Schema::table($doc, function (Blueprint $table) {
                // Check if columns exist before adding (Safety)
                if (!Schema::hasColumn($table->getTable(), 'tax_enabled_snapshot')) {
                    $table->boolean('tax_enabled_snapshot')->default(false)->after('status');
                    $table->enum('pricing_mode_snapshot', ['inclusive', 'exclusive'])->default('exclusive')->after('tax_enabled_snapshot');
                    $table->decimal('tax_rate_snapshot', 5, 2)->default(15.00)->after('pricing_mode_snapshot');
                    $table->string('currency_code', 3)->default('SAR')->after('tax_rate_snapshot');
                    
                    $table->decimal('total_excl_tax', 12, 2)->default(0)->after('currency_code'); // Subtotal before tax
                    $table->decimal('total_tax', 12, 2)->default(0)->after('total_excl_tax');
                    $table->decimal('total_incl_tax', 12, 2)->default(0)->after('total_tax'); // Grand Total
                    $table->decimal('total_taxable_amount', 12, 2)->default(0)->after('total_incl_tax');
                    $table->json('tax_breakdown')->nullable()->after('total_taxable_amount');
                }
            });
        }

        // 5. Add Tax Fields to QuoteLines, WorkOrderItems
        $itemTables = ['quote_lines', 'work_order_items'];
        foreach ($itemTables as $itemTable) {
            Schema::table($itemTable, function (Blueprint $table) {
                if (!Schema::hasColumn($table->getTable(), 'is_taxable')) {
                    $table->boolean('is_taxable')->default(true);
                    $table->string('tax_category_code', 10)->default('S'); // S=Standard
                    $table->decimal('tax_rate_snapshot', 5, 2)->default(15.00);
                    $table->decimal('tax_amount', 10, 2)->default(0);
                    
                    $table->decimal('line_total_excl_tax', 12, 2)->default(0);
                    $table->decimal('line_total_incl_tax', 12, 2)->default(0);
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_lines');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('tenant_tax_settings');
        
        $documents = ['quotes', 'work_orders'];
        foreach ($documents as $doc) {
            if (Schema::hasTable($doc)) {
                Schema::table($doc, function (Blueprint $table) {
                     $table->dropColumn([
                        'tax_enabled_snapshot',
                        'pricing_mode_snapshot',
                        'tax_rate_snapshot',
                        'currency_code',
                        'total_excl_tax',
                        'total_tax',
                        'total_incl_tax',
                        'total_taxable_amount',
                        'tax_breakdown'
                     ]);
                });
            }
        }
        
        $itemTables = ['quote_lines', 'work_order_items'];
        foreach ($itemTables as $itemTable) {
             if (Schema::hasTable($itemTable)) {
                Schema::table($itemTable, function (Blueprint $table) {
                     $table->dropColumn([
                        'is_taxable',
                        'tax_category_code',
                        'tax_rate_snapshot',
                        'tax_amount',
                        'line_total_excl_tax',
                        'line_total_incl_tax'
                     ]);
                });
            }
        }
    }
};
