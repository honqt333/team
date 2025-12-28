<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // MySQL requires dropping and recreating the enum
        // First change column to string temporarily
        Schema::table('customers', function (Blueprint $table) {
            $table->string('type', 20)->default('individual')->change();
        });
        
        // Now change to new enum with more options
        DB::statement("ALTER TABLE customers MODIFY type ENUM('individual', 'company', 'government', 'vip') NOT NULL DEFAULT 'individual'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Update any government/vip back to individual before reverting
        DB::table('customers')->whereIn('type', ['government', 'vip'])->update(['type' => 'individual']);
        
        DB::statement("ALTER TABLE customers MODIFY type ENUM('individual', 'company') NOT NULL DEFAULT 'individual'");
    }
};
