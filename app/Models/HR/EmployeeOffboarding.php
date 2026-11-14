<?php

declare(strict_types=1);

namespace App\Models\HR;

use App\Models\Center;
use App\Models\Concerns\CenterScoped;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class EmployeeOffboarding extends Model
{
    use CenterScoped, SoftDeletes;

    protected $table = 'hr_employee_offboardings';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_FINALIZED = 'finalized';

    public const TYPE_EMPLOYER_TERMINATION = 'employer_termination';

    public const TYPE_RESIGNATION = 'resignation';

    public const TYPE_ARTICLE_80 = 'article_80';

    public const TYPE_ARTICLE_81 = 'article_81';

    public const TERMINATION_TYPES = [
        self::TYPE_EMPLOYER_TERMINATION,
        self::TYPE_RESIGNATION,
        self::TYPE_ARTICLE_80,
        self::TYPE_ARTICLE_81,
    ];

    protected $fillable = [
        'tenant_id',
        'center_id',
        'employee_id',
        'created_by',
        'finalized_by',
        'termination_type',
        'hire_date',
        'termination_date',
        'service_days',
        'service_years',
        'base_salary',
        'allowances_amount',
        'calculation_salary',
        'eosb_multiplier',
        'full_eosb_amount',
        'eosb_amount',
        'unused_leave_days',
        'unused_leave_payout',
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
        'hire_date' => 'date:Y-m-d',
        'termination_date' => 'date:Y-m-d',
        'service_days' => 'integer',
        'service_years' => 'float',
        'base_salary' => 'float',
        'allowances_amount' => 'float',
        'calculation_salary' => 'float',
        'eosb_multiplier' => 'float',
        'full_eosb_amount' => 'float',
        'eosb_amount' => 'float',
        'unused_leave_days' => 'float',
        'unused_leave_payout' => 'float',
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
        Log::notice('hr.offboarding.'.$action, array_merge($context, [
            'tenant_id' => $this->tenant_id,
            'center_id' => $this->center_id,
            'employee_id' => $this->employee_id,
            'offboarding_id' => $this->id,
            'user_id' => auth()->id(),
        ]));
    }
}
