<?php

namespace App\Models\HR;

use App\Models\Department;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobTitle extends Model
{
    protected $table = 'hr_job_titles';

    protected $fillable = [
        'tenant_id',
        'name_ar',
        'name_en',
        'department_id',
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

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'job_title_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
