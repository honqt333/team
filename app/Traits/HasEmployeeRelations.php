<?php

namespace App\Traits;

use App\Models\Tenant;
use App\Models\Center;
use App\Models\User;
use App\Models\Department;
use App\Models\Nationality;
use App\Models\HR\JobTitle;
use App\Models\HR\EmployeeType;
use App\Models\HR\Allowance;
use App\Models\HR\Deduction;
use App\Models\HR\Attendance;
use App\Models\HR\Leave;
use App\Models\HR\PayrollItem;
use App\Models\HR\OtherPayment;
use App\Models\HR\Shift;
use App\Models\HR\EmployeeShift;
use App\Models\HR\EmployeeDocument;
use App\Models\HR\EmployeeContract;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasEmployeeRelations
{
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

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(EmployeeContract::class);
    }
}
