<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('centers', function (Blueprint $table) {
            // Profile fields
            $table->string('name_ar')->nullable()->after('name');
            $table->string('name_en')->nullable()->after('name_ar');
            $table->string('manager_name')->nullable()->after('name_en');
            $table->string('center_type')->nullable()->after('manager_name'); // Free text: مركز رئيسي, فرع الرياض, etc.
            $table->string('license_number')->nullable()->after('center_type');
            $table->string('vat_number')->nullable()->after('license_number');
            
            // Contact fields
            $table->string('phone')->nullable()->after('vat_number');
            $table->string('email')->nullable()->after('phone');
            
            // Branding/Logo fields
            $table->string('logo_light_path')->nullable()->after('email');
            $table->string('logo_dark_path')->nullable()->after('logo_light_path');
            $table->string('logo_invoice_path')->nullable()->after('logo_dark_path');
            $table->string('stamp_path')->nullable()->after('logo_invoice_path');
        });
    }

    public function down(): void
    {
        Schema::table('centers', function (Blueprint $table) {
            $table->dropColumn([
                'name_ar',
                'name_en',
                'manager_name',
                'center_type',
                'license_number',
                'vat_number',
                'phone',
                'email',
                'logo_light_path',
                'logo_dark_path',
                'logo_invoice_path',
                'stamp_path',
            ]);
        });
    }
};
