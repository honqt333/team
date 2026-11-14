<?php

declare(strict_types=1);

namespace App\Models\HR;

use App\Models\Center;
use App\Models\Concerns\CenterScoped;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

// @bypass-tenancy-scanner - Scoped via CenterScoped trait (includes tenant_id + center_id)
class EmployeeOffboarding extends Model
{
    use CenterScoped, SoftDeletes;

    protected $table = 'hr_employee_offboardings';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_FINALIZED = 'finalized';

    public const STATUS_CANCELLED = 'cancelled';

    public const TYPE_EMPLOYER_TERMINATION = 'employer_termination';

    public const TYPE_CONTRACT_EXPIRY = 'contract_expiry';

    public const TYPE_RESIGNATION = 'resignation';

    public const TYPE_ARTICLE_80 = 'article_80';

    public const TYPE_ARTICLE_81 = 'article_81';

    public const TYPE_ARTICLE_87 = 'article_87';

    public const TERMINATION_TYPES = [
        self::TYPE_EMPLOYER_TERMINATION,
        self::TYPE_CONTRACT_EXPIRY,
        self::TYPE_RESIGNATION,
        self::TYPE_ARTICLE_80,
        self::TYPE_ARTICLE_81,
        self::TYPE_ARTICLE_87,
    ];

    protected $fillable = [
        'tenant_id',
        'center_id',
        'employee_id',
        'created_by',
        'finalized_by',
        'termination_type',
        'is_article_87',
        'hire_date',
        'termination_date',
        'service_days',
        'service_years',
        'base_salary',
        'allowances_amount',
        'housing_allowance_amount',
        'calculation_salary',
        'use_manual_salary',
        'manual_salary_reason',
        'calculated_base_salary',
        'calculated_allowances_amount',
        'eosb_multiplier',
        'full_eosb_amount',
        'eosb_amount',
        'unused_leave_days',
        'unused_leave_payout',
        'repatriation_ticket_amount',
        'statutory_compensation_amount',
        'advances_deduction',
        'custody_deduction',
        'other_additions',
        'other_deductions',
        'net_settlement',
        'custody_cleared',
        'finance_cleared',
        'it_access_revoked',
        'status',
        'notes',
        'employee_signature',
        'hr_signature',
        'finalized_at',
    ];

    protected $casts = [
        'is_article_87' => 'boolean',
        'hire_date' => 'date:Y-m-d',
        'termination_date' => 'date:Y-m-d',
        'service_days' => 'integer',
        'service_years' => 'float',
        'base_salary' => 'float',
        'allowances_amount' => 'float',
        'housing_allowance_amount' => 'float',
        'calculation_salary' => 'float',
        'use_manual_salary' => 'boolean',
        'calculated_base_salary' => 'float',
        'calculated_allowances_amount' => 'float',
        'eosb_multiplier' => 'float',
        'full_eosb_amount' => 'float',
        'eosb_amount' => 'float',
        'unused_leave_days' => 'float',
        'unused_leave_payout' => 'float',
        'repatriation_ticket_amount' => 'float',
        'statutory_compensation_amount' => 'float',
        'advances_deduction' => 'float',
        'custody_deduction' => 'float',
        'other_additions' => 'float',
        'other_deductions' => 'float',
        'net_settlement' => 'float',
        'custody_cleared' => 'boolean',
        'finance_cleared' => 'boolean',
        'it_access_revoked' => 'boolean',
        'finalized_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class, 'center_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function finalizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function logActivity(string $action, array $context = []): void
    {
        Log::notice('hr.employee_offboarding.'.$action, array_merge([
            'tenant_id' => $this->tenant_id,
            'center_id' => $this->center_id,
            'employee_id' => $this->employee_id,
            'offboarding_id' => $this->id,
            'user_id' => auth()->id(),
        ], $context));
    }
}
