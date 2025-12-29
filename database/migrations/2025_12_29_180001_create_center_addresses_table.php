<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('center_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained()->onDelete('cascade');
            $table->string('address_line')->nullable();
            $table->string('city')->nullable();
            $table->string('district')->nullable();
            $table->string('building_number')->nullable();
            $table->string('postal_code')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();

            $table->index('center_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('center_addresses');
    }
};
