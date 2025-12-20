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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('center_id')->constrained('centers')->restrictOnDelete();
            $table->string('type')->default('individual'); // individual or company
            $table->string('name');
            $table->string('contact_name')->nullable(); // For company authorized person
            $table->string('phone');
            $table->string('email')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('tenant_id');
            $table->index('center_id');
            $table->index(['tenant_id', 'center_id']);
            $table->index('phone');
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
