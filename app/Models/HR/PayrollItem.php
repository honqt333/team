<?php

namespace App\Models\HR;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollItem extends Model
{
    use HasFactory;

    protected $table = 'hr_payroll_items';

    protected $fillable = [
        'payroll_run_id',
        'employee_id',
        'base_salary',
        'gosi_rate',
        'gosi_amount',
        'total_allowances',
        'total_deductions',
        'net_salary',
        'allowances_breakdown',
        'deductions_breakdown',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'base_salary' => 'decimal:2',
        'gosi_rate' => 'decimal:2',
        'gosi_amount' => 'decimal:2',
        'total_allowances' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'allowances_breakdown' => 'array',
        'deductions_breakdown' => 'array',
    ];

    // ─────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─────────────────────────────────────────────────────────────
    // Accessors
    // ─────────────────────────────────────────────────────────────

    public function getPeriodLabelAttribute(): string
    {
        return $this->payrollRun?->period_label ?? '';
    }

    public function getPaymentDateAttribute()
    {
        return $this->payrollRun?->payment_date;
    }

    public function getStatusAttribute(): string
    {
        return $this->payrollRun?->status ?? 'draft';
    }
}
