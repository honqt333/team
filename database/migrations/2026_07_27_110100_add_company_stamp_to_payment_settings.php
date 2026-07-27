<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add `company_stamp_path` to PaymentSettings.
 *
 * Phase 6+ polish — admins can upload a company stamp (signature
 * image, usually a PNG with a transparent background) and it
 * appears in the bottom-right of every generated invoice PDF,
 * right above the footer. Optional — invoices render fine
 * without it; the path is null until the admin uploads.
 *
 * Stamps go to public/company/stamps/ (separate from the logo
 * folder so admins can replace one without affecting the other).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_settings', function (Blueprint $table) {
            $table->string('company_stamp_path')->nullable()->after('company_logo_path');
        });
    }

    public function down(): void
    {
        Schema::table('payment_settings', function (Blueprint $table) {
            $table->dropColumn('company_stamp_path');
        });
    }
};
