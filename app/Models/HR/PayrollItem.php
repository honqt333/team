<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollItem extends Model
{
    protected $table = 'hr_payroll_items';

    protected $fillable = [
        'payroll_id',
        'employee_id',
        'base_salary',
        'total_allowances',
        'total_deductions',
        'overtime_amount',
        'commission_amount',
        'net_salary',
        'working_days',
        'absent_days',
        'absent_deduction',
        'status',
        'paid_at',
        'payment_method',
        'notes',
    ];

    protected $casts = [
        'base_salary' => 'float',
        'total_allowances' => 'float',
        'total_deductions' => 'float',
        'overtime_amount' => 'float',
        'commission_amount' => 'float',
        'net_salary' => 'float',
        'absent_deduction' => 'float',
        'paid_at' => 'datetime',
    ];

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    // Calculate net salary
    public function calculateNetSalary(): float
    {
        return $this->base_salary
            + $this->total_allowances
            - $this->total_deductions
            + $this->overtime_amount
            + $this->commission_amount
            - $this->absent_deduction;
    }
}
