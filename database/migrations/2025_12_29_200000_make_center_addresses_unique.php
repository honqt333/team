<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('center_addresses', function (Blueprint $table) {
            // Drop FK first
            $table->dropForeign(['center_id']);
            // Drop index
            $table->dropIndex(['center_id']); // or 'center_addresses_center_id_index' if named specifically
            // Add unique constraint (implicitly adds index)
            $table->unique('center_id');
            // Add FK back
            $table->foreign('center_id')->references('id')->on('centers')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('center_addresses', function (Blueprint $table) {
            $table->dropForeign(['center_id']);
            $table->dropUnique(['center_id']);
            $table->index('center_id');
            $table->foreign('center_id')->references('id')->on('centers')->onDelete('cascade');
        });
    }
};
