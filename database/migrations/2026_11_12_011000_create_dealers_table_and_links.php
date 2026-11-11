<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dealers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('center_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->string('type', 50)->default('insurance');
            $table->string('contact_name')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('whatsapp', 50)->nullable();
            $table->string('email')->nullable();
            $table->string('tax_number', 50)->nullable();
            $table->decimal('credit_limit', 12, 2)->nullable();
            $table->unsignedSmallInteger('payment_terms_days')->nullable();
            $table->string('address_line')->nullable();
            $table->string('building_number', 30)->nullable();
            $table->string('postal_code', 30)->nullable();
            $table->string('district')->nullable();
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            $table->string('country', 100)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'is_active'], 'dealers_tenant_active_idx');
            $table->index(['tenant_id', 'type'], 'dealers_tenant_type_idx');
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->foreignId('dealer_id')
                ->nullable()
                ->after('customer_id')
                ->constrained('dealers')
                ->nullOnDelete();

            $table->index(['tenant_id', 'dealer_id'], 'vehicles_tenant_dealer_idx');
        });

        Schema::table('work_orders', function (Blueprint $table) {
            $table->foreignId('dealer_id')
                ->nullable()
                ->after('customer_id')
                ->constrained('dealers')
                ->nullOnDelete();
            $table->boolean('is_paid_by_dealer')
                ->default(false)
                ->after('dealer_id');

            $table->index(['tenant_id', 'dealer_id'], 'work_orders_tenant_dealer_idx');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('dealer_id')
                ->nullable()
                ->after('customer_id')
                ->constrained('dealers')
                ->nullOnDelete();
            $table->boolean('is_paid_by_dealer')
                ->default(false)
                ->after('dealer_id');

            $table->index(['tenant_id', 'dealer_id'], 'invoices_tenant_dealer_idx');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('invoices_tenant_dealer_idx');
            $table->dropColumn('is_paid_by_dealer');
            $table->dropConstrainedForeignId('dealer_id');
        });

        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropIndex('work_orders_tenant_dealer_idx');
            $table->dropColumn('is_paid_by_dealer');
            $table->dropConstrainedForeignId('dealer_id');
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropIndex('vehicles_tenant_dealer_idx');
            $table->dropConstrainedForeignId('dealer_id');
        });

        Schema::dropIfExists('dealers');
    }
};
