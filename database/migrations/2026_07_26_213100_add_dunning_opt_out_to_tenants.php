<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add dunning opt-out + preferred language columns to tenants.
 *
 * `dunning_opted_out` — when true, the dispatcher skips this
 * tenant entirely. The opt-out is honored REGARDLESS of the global
 * or per-tenant config; it's the last line of defense for tenants
 * who have asked (in writing) not to receive collection reminders.
 *
 * `dunning_language` — null = use the tenant's app locale; 'ar' or
 * 'en' = force this language for the reminder body. The dispatcher
 * only uses this field to choose between the Arabic and English
 * message templates.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->boolean('dunning_opted_out')->default(false);
            $table->string('dunning_language', 5)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['dunning_opted_out', 'dunning_language']);
        });
    }
};
