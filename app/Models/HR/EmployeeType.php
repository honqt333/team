<?php

namespace App\Models\HR;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeType extends Model
{
    protected $table = 'hr_employee_types';

    protected $fillable = [
        'tenant_id',
        'name_ar',
        'name_en',
        'is_active',
        'updated_by',
    ];

    protected $casts = [
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

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'employee_type_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
