<?php

namespace App\Models\HR;

use App\Models\Center;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attendance extends Model
{
    use SoftDeletes;

    protected $table = 'hr_attendances';

    protected $fillable = [
        'tenant_id',
        'center_id',
        'employee_id',
        'date',
        'status',
        'check_in',
        'check_out',
        'late_minutes',
        'early_leave_minutes',
        'overtime_minutes',
        'notes',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'check_in' => 'datetime:H:i',
        'check_out' => 'datetime:H:i',
        'late_minutes' => 'integer',
        'early_leave_minutes' => 'integer',
        'overtime_minutes' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
    /**
     * احتساب دقائق التأخير والانصراف المبكر والأوقات الإضافية
     * يدعم نظام الورديات إذا كان مفعلاً، وإلا يستخدم الأوقات الثابتة
     * 
     * @param int $gracePeriod فترة السماح بالدقائق (افتراضي: 10 دقائق)
     */
    public function calculateTimes(int $gracePeriod = 10): void
    {
        $employee = $this->employee;
        
        if (!$employee) return;

        $this->late_minutes = 0;
        $this->early_leave_minutes = 0;
        $this->overtime_minutes = 0;

        // الحصول على أوقات الدوام من نظام الورديات أو الأوقات الثابتة
        $shiftStartTime = null;
        $shiftEndTime = null;

        // 1. محاولة الحصول على الوردية من نظام الورديات
        $shift = $employee->getShiftForDate($this->date);
        
        if ($shift) {
            $shiftStartTime = $shift->start_time;
            $shiftEndTime = $shift->end_time;
        } else {
            // 2. الرجوع للأوقات الثابتة في ملف الموظف
            $shiftStartTime = $employee->shift_start;
            $shiftEndTime = $employee->shift_end;
        }

        // حساب التأخير
        if ($this->check_in && $shiftStartTime) {
            $shiftStart = Carbon::parse($this->date->format('Y-m-d') . ' ' . $shiftStartTime);
            $checkIn = Carbon::parse($this->date->format('Y-m-d') . ' ' . Carbon::parse($this->check_in)->format('H:i'));
            
            if ($checkIn->gt($shiftStart)) {
                $lateMinutes = abs($checkIn->diffInMinutes($shiftStart));
                // تطبيق فترة السماح
                $this->late_minutes = $lateMinutes > $gracePeriod ? $lateMinutes : 0;
            }
        }

        // حساب الانصراف المبكر والوقت الإضافي
        if ($this->check_out && $shiftEndTime) {
            $shiftEnd = Carbon::parse($this->date->format('Y-m-d') . ' ' . $shiftEndTime);
            $checkOut = Carbon::parse($this->date->format('Y-m-d') . ' ' . Carbon::parse($this->check_out)->format('H:i'));
            
            // التعامل مع الورديات الليلية (تمتد لليوم التالي)
            if ($shift && $shift->is_overnight && $shiftEnd->lt($shiftStart)) {
                $shiftEnd->addDay();
            }
            
            if ($checkOut->lt($shiftEnd)) {
                // انصراف مبكر
                $this->early_leave_minutes = abs($shiftEnd->diffInMinutes($checkOut));
            } elseif ($checkOut->gt($shiftEnd)) {
                // وقت إضافي
                $this->overtime_minutes = abs($checkOut->diffInMinutes($shiftEnd));
            }
        }
    }
}
