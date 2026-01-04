<?php

namespace App\Models\HR;

use App\Models\Center;
use App\Models\Department;
use App\Models\Nationality;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes;

    protected $table = 'hr_employees';

    protected $fillable = [
        'tenant_id',
        'center_id',
        'user_id',
        'employee_number',
        'name_ar',
        'name_en',
        'phone',
        'email',
        // البيانات الأساسية
        'photo_path',
        'gender',
        'marital_status',
        'birth_date',
        // العنوان
        'city',
        'district',
        'street',
        'building_number',
        'postal_code',
        // البيانات الوظيفية
        'job_title_id',
        'employee_type_id',
        'department_id',
        'hire_date',
        'contract_end_date',
        'default_shift_id',
        // الهوية
        'nationality_id',
        'national_id',
        'national_id_expiry',
        'passport_number',
        'passport_expiry',
        'border_entry_number',
        'border_port',
        'sponsor_name',
        'profession_on_id',
        // التأمين
        'insurance_company',
        'insurance_card_number',
        'insurance_policy_number',
        'insurance_expiry',
        'insurance_classification',
        'insurance_details',
        // الراتب
        'base_salary',
        'commission_enabled',
        'commission_type',
        'commission_rate',
        'status',
        'termination_date',
        'termination_reason',
        'notes',
        // Biometric
        'biometric_id',
        // Bank Info
        'bank_name',
        'bank_iban',
        'bank_account_number',
        'bank_notes',
        'gosi_rate',
    ];

    protected $casts = [
        'hire_date' => 'date:Y-m-d',
        'contract_end_date' => 'date:Y-m-d',
        'termination_date' => 'date:Y-m-d',
        'birth_date' => 'date:Y-m-d',
        'national_id_expiry' => 'date:Y-m-d',
        'passport_expiry' => 'date:Y-m-d',
        'insurance_expiry' => 'date:Y-m-d',
        'base_salary' => 'float',
        'commission_enabled' => 'boolean',
        'commission_rate' => 'float',
    ];

    // Boot method for auto-generating employee number
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($employee) {
            if (empty($employee->employee_number)) {
                $lastNumber = static::where('tenant_id', $employee->tenant_id)
                    ->withTrashed()
                    ->max('id') ?? 0;
                $employee->employee_number = 'EMP-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
            }
        });

        static::updated(function ($employee) {
            if ($employee->isDirty('status')) {
                // If user is linked
                if ($employee->user_id && $employee->user) {
                    $newStatus = $employee->status;

                    if (in_array($newStatus, ['terminated', 'inactive'])) {
                        // Disable user to prevent login
                        $employee->user->update(['is_active' => false]);
                    } elseif ($newStatus === 'active') {
                        // Enable user if inactive
                        if (!$employee->user->is_active) {
                            $employee->user->update(['is_active' => true]);
                        }
                    }
                }
            }
        });
    }

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jobTitle(): BelongsTo
    {
        return $this->belongsTo(JobTitle::class);
    }

    public function employeeType(): BelongsTo
    {
        return $this->belongsTo(EmployeeType::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function nationality(): BelongsTo
    {
        return $this->belongsTo(Nationality::class);
    }

    public function allowances(): BelongsToMany
    {
        return $this->belongsToMany(Allowance::class, 'hr_employee_allowances')
            ->withPivot('custom_amount', 'period_type', 'start_date', 'end_date', 'is_active')
            ->withTimestamps();
    }

    public function deductions(): BelongsToMany
    {
        return $this->belongsToMany(Deduction::class, 'hr_employee_deductions')
            ->withPivot('custom_amount', 'period_type', 'start_date', 'end_date', 'is_active')
            ->withTimestamps();
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class);
    }

    public function payrollItems(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function otherPayments(): HasMany
    {
        return $this->hasMany(OtherPayment::class);
    }

    public function defaultShift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'default_shift_id');
    }

    public function employeeShifts(): HasMany
    {
        return $this->hasMany(EmployeeShift::class);
    }

    /**
     * الحصول على وردية الموظف ليوم محدد
     * يبحث أولاً عن وردية مجدولة لهذا اليوم
     * ثم يبحث عن وردية أسبوعية متكررة
     * وأخيراً يرجع الوردية الافتراضية
     */
    public function getShiftForDate(\Carbon\Carbon $date): ?Shift
    {
        // 1. البحث عن وردية مجدولة لهذا التاريخ
        $scheduledShift = $this->employeeShifts()
            ->where('date', $date->format('Y-m-d'))
            ->with('shift')
            ->first();
        
        if ($scheduledShift && $scheduledShift->shift) {
            return $scheduledShift->shift;
        }

        // 2. البحث عن وردية أسبوعية (day_of_week)
        $dayOfWeek = $date->dayOfWeek; // 0 = Sunday
        $weeklyShift = $this->employeeShifts()
            ->where('day_of_week', $dayOfWeek)
            ->whereNull('date')
            ->with('shift')
            ->first();
        
        if ($weeklyShift && $weeklyShift->shift) {
            return $weeklyShift->shift;
        }

        // 3. إرجاع الوردية الافتراضية
        return $this->defaultShift;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    public function scopeTerminated($query)
    {
        return $query->where('status', 'terminated');
    }

    // Accessors
    public function getDisplayNameAttribute(): string
    {
        return app()->getLocale() === 'ar' && $this->name_ar
            ? $this->name_ar
            : ($this->name_en ?? $this->name_ar);
    }

    // Calculate total allowances for this employee
    public function calculateTotalAllowances(): float
    {
        $total = 0;
        foreach ($this->allowances as $allowance) {
            $amount = $allowance->pivot->custom_amount ?? $allowance->amount;
            if ($allowance->type === 'percentage') {
                // Use the correct calculation base
                $base = $this->getCalculationBase($allowance->calculation_base);
                $total += ($base * $amount / 100);
            } else {
                $total += $amount;
            }
        }
        return round($total, 2);
    }

    // Calculate total deductions for this employee (excluding GOSI)
    public function calculateTotalDeductions(): float
    {
        $total = 0;
        foreach ($this->deductions as $deduction) {
            $amount = $deduction->pivot->custom_amount ?? $deduction->amount;
            if ($deduction->type === 'percentage') {
                // Use the correct calculation base
                $base = $this->getCalculationBase($deduction->calculation_base);
                $total += ($base * $amount / 100);
            } else {
                $total += $amount;
            }
        }
        return round($total, 2);
    }

    // Calculate GOSI employee share (Saudi: 9.75%, Non-Saudi: 2%)
    public function calculateGosiAmount(): float
    {
        $rate = $this->gosi_rate ?? 0;
        // GOSI is calculated on base salary only (not allowances)
        return round($this->base_salary * $rate / 100, 2);
    }

    // Calculate net salary
    public function calculateNetSalary(): float
    {
        $gross = $this->base_salary + $this->calculateTotalAllowances();
        $deductions = $this->calculateTotalDeductions() + $this->calculateGosiAmount();
        return round($gross - $deductions, 2);
    }

    // Get the correct base for percentage calculations
    protected function getCalculationBase(?string $calculationBase): float
    {
        return match ($calculationBase) {
            'base_salary' => $this->base_salary ?? 0,
            'gross_salary' => ($this->base_salary ?? 0) + $this->calculateTotalAllowances(),
            'monthly_contribution' => $this->base_salary ?? 0, // For GOSI-based calculations
            default => $this->base_salary ?? 0,
        };
    }
}
