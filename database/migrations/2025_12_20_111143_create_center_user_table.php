<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('center_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('center_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['tenant_id', 'center_id', 'user_id']);
            $table->index('tenant_id');
            $table->index('center_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('center_user');
    }
};
