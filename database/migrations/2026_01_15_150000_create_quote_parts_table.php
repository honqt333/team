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
        Schema::create('quote_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quote_line_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('part_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('source', ['warehouse', 'external', 'customer']);
            $table->string('name');
            $table->string('part_number')->nullable();
            $table->foreignId('unit_id')->nullable();
            $table->text('description')->nullable();
            $table->decimal('qty', 10, 2)->default(1);
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->boolean('include_in_package')->default(true);
            $table->boolean('hide_on_print')->default(false);
            $table->timestamps();

            $table->index(['quote_id']);
            $table->index(['quote_line_id']);
            $table->index(['part_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quote_parts');
    }
};
