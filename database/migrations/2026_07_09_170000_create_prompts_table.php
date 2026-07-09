<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prompts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->unsignedInteger('version')->default(1);
            $table->text('content');
            $table->string('model');
            $table->decimal('temperature', 3, 2)->default(0.20);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'key', 'version']);
            $table->index(['tenant_id', 'key', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prompts');
    }
};
