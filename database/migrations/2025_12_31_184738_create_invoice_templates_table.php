<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            
            $table->string('name'); // اسم القالب
            $table->enum('type', ['proforma', 'tax_invoice'])->default('tax_invoice');
            
            // Customizable Labels (JSON)
            $table->json('labels')->nullable();
            
            // Header/Footer
            $table->text('header_text')->nullable();
            $table->text('footer_text')->nullable();
            $table->text('terms_conditions')->nullable();
            
            // Settings
            $table->boolean('show_logo')->default(true);
            $table->boolean('show_qr')->default(true);
            $table->boolean('is_default')->default(false);
            
            $table->timestamps();
            
            $table->unique(['tenant_id', 'type', 'is_default']);
        });

        // Add payment_status to invoices
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'payment_status')) {
                $table->enum('payment_status', ['unpaid', 'partial', 'paid'])->default('unpaid')->after('status');
                $table->decimal('total_paid', 12, 2)->default(0)->after('total_incl_tax');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'total_paid']);
        });
        
        Schema::dropIfExists('invoice_templates');
    }
};
