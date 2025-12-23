<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('tax_number')->nullable()->after('notes');
            $table->string('address_line')->nullable()->after('tax_number');
            $table->decimal('lat', 10, 7)->nullable()->after('address_line');
            $table->decimal('lng', 10, 7)->nullable()->after('lat');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['tax_number', 'address_line', 'lat', 'lng']);
        });
    }
};
