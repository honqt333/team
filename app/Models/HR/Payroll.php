<?php

namespace App\Models\HR;

use App\Models\Center;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payroll extends Model
{
    protected $table = 'hr_payrolls';

    protected $fillable = [
        'tenant_id',
        'center_id',
        'period',
        'status',
        'total_salaries',
        'total_allowances',
        'total_deductions',
        'net_total',
        'approved_by',
        'approved_at',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'total_salaries' => 'float',
        'total_allowances' => 'float',
        'total_deductions' => 'float',
        'net_total' => 'float',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    // Scopes
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

    // Calculate totals from items
    public function recalculateTotals(): void
    {
        $this->total_salaries = $this->items()->sum('base_salary');
        $this->total_allowances = $this->items()->sum('total_allowances');
        $this->total_deductions = $this->items()->sum('total_deductions');
        $this->net_total = $this->items()->sum('net_salary');
        $this->save();
    }
}
