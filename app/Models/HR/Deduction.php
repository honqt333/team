<?php

namespace App\Models\HR;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Deduction extends Model
{
    protected $table = 'hr_deductions';

    protected $fillable = [
        'tenant_id',
        'name_ar',
        'name_en',
        'type',
        'amount',
        'calculation_base',
        'is_active',
        'updated_by',
    ];

    protected $casts = [
        'amount' => 'float',
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'hr_employee_deductions')
            ->withPivot('custom_amount')
            ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
