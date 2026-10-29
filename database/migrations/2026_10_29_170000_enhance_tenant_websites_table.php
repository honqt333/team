<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_websites', function (Blueprint $table) {
            // Marketing Banners (array of slider banners, promotional announcements)
            $table->json('banners')->nullable()->after('social_links');

            // Extensible JSON settings: pricing modes, selective services display, trust badges, announcement bar, etc.
            $table->json('settings')->nullable()->after('banners');

            // Custom domain support for premium branding
            $table->string('custom_domain')->nullable()->unique()->after('theme_key');
            $table->string('custom_domain_status', 30)->default('none')->after('custom_domain');
            $table->timestamp('custom_domain_verified_at')->nullable()->after('custom_domain_status');
        });
    }

    public function down(): void
    {
        Schema::table('tenant_websites', function (Blueprint $table) {
            $table->dropColumn([
                'banners',
                'settings',
                'custom_domain',
                'custom_domain_status',
                'custom_domain_verified_at',
            ]);
        });
    }
};
