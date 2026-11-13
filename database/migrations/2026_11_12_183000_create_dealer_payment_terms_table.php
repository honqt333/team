<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dealer_payment_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dealer_id')->constrained('dealers')->cascadeOnDelete();
            $table->text('term_text');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['dealer_id', 'sort_order'], 'dealer_payment_terms_dealer_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dealer_payment_terms');
    }
};
