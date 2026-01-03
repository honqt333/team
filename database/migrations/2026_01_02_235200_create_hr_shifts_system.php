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
        // جدول تعريف الورديات
        Schema::create('hr_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            
            $table->string('name_ar'); // صباحي، مسائي، ليلي
            $table->string('name_en')->nullable();
            $table->time('start_time'); // 08:00
            $table->time('end_time'); // 16:00
            $table->string('color', 7)->default('#6366f1'); // لون التمييز
            $table->boolean('is_overnight')->default(false); // هل تمتد لليوم التالي
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('break_minutes')->default(60); // فترة الاستراحة
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('tenant_id');
        });

        // جدول جدولة الورديات للموظفين
        Schema::create('hr_employee_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->foreignId('shift_id')->constrained('hr_shifts')->cascadeOnDelete();
            
            // يمكن أن يكون جدولة يومية أو أسبوعية
            $table->date('date')->nullable(); // لجدولة يوم محدد
            $table->unsignedTinyInteger('day_of_week')->nullable(); // 0-6 للجدولة الأسبوعية المتكررة
            
            $table->timestamps();
            
            // فهارس للبحث السريع
            $table->index(['tenant_id', 'employee_id', 'date']);
            $table->index(['tenant_id', 'employee_id', 'day_of_week']);
            
            // منع التكرار
            $table->unique(['employee_id', 'date'], 'unique_employee_date');
        });

        // إضافة عمود الوردية الافتراضية للموظف
        Schema::table('hr_employees', function (Blueprint $table) {
            $table->foreignId('default_shift_id')->nullable()->after('shift_end')
                ->constrained('hr_shifts')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_employees', function (Blueprint $table) {
            $table->dropForeign(['default_shift_id']);
            $table->dropColumn('default_shift_id');
        });
        
        Schema::dropIfExists('hr_employee_shifts');
        Schema::dropIfExists('hr_shifts');
    }
};
