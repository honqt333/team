<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('hr_employees', function (Blueprint $table) {
            // البيانات الأساسية
            $table->string('photo_path')->nullable()->after('user_id');
            $table->enum('gender', ['male', 'female'])->nullable()->after('photo_path');
            $table->enum('marital_status', ['single', 'married'])->nullable()->after('gender');
            $table->date('birth_date')->nullable()->after('marital_status');

            // العنوان
            $table->string('city')->nullable()->after('birth_date');
            $table->string('district')->nullable()->after('city');
            $table->string('street')->nullable()->after('district');
            $table->string('building_number')->nullable()->after('street');
            $table->string('postal_code')->nullable()->after('building_number');

            // الهوية
            $table->foreignId('nationality_id')->nullable()->after('postal_code')
                ->constrained('nationalities')->nullOnDelete();
            $table->date('national_id_expiry')->nullable()->after('national_id');
            $table->string('passport_number')->nullable()->after('national_id_expiry');
            $table->date('passport_expiry')->nullable()->after('passport_number');
            $table->string('border_entry_number')->nullable()->after('passport_expiry');
            $table->string('border_port')->nullable()->after('border_entry_number');
            $table->string('sponsor_name')->nullable()->after('border_port');
            $table->string('profession_on_id')->nullable()->after('sponsor_name');

            // التأمين
            $table->string('insurance_company')->nullable()->after('profession_on_id');
            $table->string('insurance_card_number')->nullable()->after('insurance_company');
            $table->string('insurance_policy_number')->nullable()->after('insurance_card_number');
            $table->date('insurance_expiry')->nullable()->after('insurance_policy_number');
            $table->string('insurance_classification')->nullable()->after('insurance_expiry');
            $table->text('insurance_details')->nullable()->after('insurance_classification');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_employees', function (Blueprint $table) {
            $table->dropForeign(['nationality_id']);
            $table->dropColumn([
                'photo_path',
                'gender',
                'marital_status',
                'birth_date',
                'city',
                'district',
                'street',
                'building_number',
                'postal_code',
                'nationality_id',
                'national_id_expiry',
                'passport_number',
                'passport_expiry',
                'border_entry_number',
                'border_port',
                'sponsor_name',
                'profession_on_id',
                'insurance_company',
                'insurance_card_number',
                'insurance_policy_number',
                'insurance_expiry',
                'insurance_classification',
                'insurance_details',
            ]);
        });
    }
};
