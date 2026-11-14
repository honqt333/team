<?php

declare(strict_types=1);

namespace App\Models\HR;

use App\Models\Center;
use App\Models\CompanyTransaction;
use App\Models\Concerns\CenterScoped;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

// @bypass-tenancy-scanner - Scoped via CenterScoped trait (includes tenant_id + center_id)
class PayrollRun extends Model
{
    use CenterScoped;
    use HasFactory, SoftDeletes;

    protected $table = 'hr_payroll_runs';

    protected $fillable = [
        'tenant_id',
        'center_id',
        'period_start',
        'period_end',
        'payment_date',
        'status',
        'created_by',
        'approved_by',
        'company_transaction_id',
        'accounting_posted_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'payment_date' => 'date',
        'accounting_posted_at' => 'datetime',
    ];

    // ─────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class, 'payroll_run_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function companyTransaction(): BelongsTo
    {
        return $this->belongsTo(CompanyTransaction::class, 'company_transaction_id');
    }

    // ─────────────────────────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────────────────────────

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    // ─────────────────────────────────────────────────────────────
    // Accessors
    // ─────────────────────────────────────────────────────────────

    public function getPeriodLabelAttribute(): string
    {
        return $this->period_start->format('M Y');
    }

    public function getTotalNetSalaryAttribute(): float
    {
        return $this->items->sum('net_salary');
    }

    public function logActivity(string $action, array $context = []): void
    {
        Log::notice('payroll.'.$action, array_merge($context, ['tenant_id' => $this->tenant_id, 'payroll_run_id' => $this->id, 'user_id' => auth()->id()]));
    }
}
