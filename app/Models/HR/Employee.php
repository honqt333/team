<?php

declare(strict_types=1);

namespace App\Models\HR;

use App\Models\Concerns\TenantScoped;
use App\Traits\HasEmployeeRelations;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasEmployeeRelations;
    use SoftDeletes, TenantScoped;

    protected $table = 'hr_employees';

    protected $fillable = [
        'tenant_id', 'center_id', 'user_id', 'employee_number', 'name_ar', 'name_en', 'phone', 'email',
        'photo_path', 'gender', 'marital_status', 'birth_date', 'city', 'district', 'street', 'building_number',
        'postal_code', 'job_title_id', 'employee_type_id', 'department_id', 'hire_date', 'contract_end_date',
        'default_shift_id', 'nationality_id', 'national_id', 'national_id_expiry', 'passport_number',
        'passport_expiry', 'border_entry_number', 'border_port', 'sponsor_name', 'profession_on_id',
        'insurance_company', 'insurance_card_number', 'insurance_policy_number', 'insurance_expiry',
        'insurance_classification', 'insurance_details', 'base_salary', 'commission_enabled', 'commission_type',
        'commission_rate', 'status', 'termination_date', 'termination_reason', 'notes', 'biometric_id',
        'bank_name', 'bank_iban', 'bank_account_number', 'bank_notes', 'gosi_rate',
    ];

    protected $casts = [
        'hire_date' => 'date:Y-m-d', 'contract_end_date' => 'date:Y-m-d', 'termination_date' => 'date:Y-m-d',
        'birth_date' => 'date:Y-m-d', 'national_id_expiry' => 'date:Y-m-d', 'passport_expiry' => 'date:Y-m-d',
        'insurance_expiry' => 'date:Y-m-d', 'base_salary' => 'float', 'commission_enabled' => 'boolean',
        'commission_rate' => 'float',
    ];

    protected $appends = [
        'photo_url',
        'display_name',
        'id_expiry_date',
        'id_expiry_status',
        'has_permissions_configured',
        'no_permissions_set',
    ];

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_path ? asset('storage/'.$this->photo_path) : null;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($employee) {
            if (empty($employee->employee_number)) {
                $lastNumber = static::where('tenant_id', $employee->tenant_id)
                    ->withTrashed()
                    ->max('id') ?? 0;
                $employee->employee_number = 'EMP-'.str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);
            }
        });
    }

    /**
     * الحصول على وردية الموظف ليوم محدد
     */
    public function getShiftForDate(Carbon $date): ?Shift
    {
        $scheduledShift = $this->employeeShifts()
            ->where('date', $date->format('Y-m-d'))
            ->with('shift')
            ->first();

        if ($scheduledShift && $scheduledShift->shift) {
            return $scheduledShift->shift;
        }

        $dayOfWeek = $date->dayOfWeek;
        $weeklyShift = $this->employeeShifts()
            ->where('day_of_week', $dayOfWeek)
            ->whereNull('date')
            ->with('shift')
            ->first();

        if ($weeklyShift && $weeklyShift->shift) {
            return $weeklyShift->shift;
        }

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
        return (app()->getLocale() === 'ar' && $this->name_ar)
            ? $this->name_ar
            : ($this->name_en ?? $this->name_ar ?? '');
    }

    public function getIdExpiryDateAttribute(): ?string
    {
        if ($this->national_id_expiry) {
            return $this->national_id_expiry instanceof Carbon
                ? $this->national_id_expiry->format('Y-m-d')
                : Carbon::parse((string) $this->national_id_expiry)->format('Y-m-d');
        }

        if ($this->relationLoaded('documents')) {
            $idDoc = $this->documents
                ->filter(fn ($doc) => in_array($doc->type, ['id', 'iqama', 'national_id'], true) && $doc->expiry_date !== null)
                ->sortByDesc('expiry_date')
                ->first();

            if ($idDoc && $idDoc->expiry_date) {
                return $idDoc->expiry_date instanceof Carbon
                    ? $idDoc->expiry_date->format('Y-m-d')
                    : Carbon::parse((string) $idDoc->expiry_date)->format('Y-m-d');
            }
        }

        return null;
    }

    public function getIdExpiryStatusAttribute(): ?string
    {
        $expiryDate = $this->id_expiry_date;

        if (! $expiryDate) {
            return null;
        }

        $expiry = Carbon::parse($expiryDate)->startOfDay();
        $today = Carbon::today();

        if ($expiry->lt($today)) {
            return 'expired';
        }

        if ($expiry->lte($today->copy()->addDays(30))) {
            return 'expiring_soon';
        }

        return 'valid';
    }

    public function getHasPermissionsConfiguredAttribute(): bool
    {
        if (! $this->user_id) {
            return false;
        }

        $user = $this->relationLoaded('user')
            ? $this->user
            : $this->user()->with(['roles.permissions', 'permissions'])->first();

        if (! $user) {
            return false;
        }

        if ($user->is_system_admin) {
            return true;
        }

        if ($user->relationLoaded('roles') && $user->roles->contains('name', 'super_admin')) {
            return true;
        }

        if ($user->relationLoaded('permissions') && $user->relationLoaded('roles')) {
            if ($user->permissions->isNotEmpty()) {
                return true;
            }

            $operationalRoles = $user->roles->where('name', '!=', 'employee');

            $rolePermissions = $operationalRoles->flatMap(
                fn ($role) => $role->relationLoaded('permissions') ? $role->permissions : collect()
            );

            return $rolePermissions->isNotEmpty();
        }

        if ($user->hasRole('super_admin') || $user->permissions()->exists()) {
            return true;
        }

        return $user->roles()
            ->where('name', '!=', 'employee')
            ->whereHas('permissions')
            ->exists();
    }

    public function getNoPermissionsSetAttribute(): bool
    {
        return ! $this->has_permissions_configured;
    }

    // Calculate total allowances for this employee
    public function calculateTotalAllowances(): float
    {
        $total = 0;

        foreach ($this->allowances as $allowance) {
            $amount = $allowance->pivot->custom_amount ?? $allowance->amount;

            if ($allowance->type === 'percentage') {
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
            'monthly_contribution' => $this->base_salary ?? 0,
            default => $this->base_salary ?? 0,
        };
    }
}
