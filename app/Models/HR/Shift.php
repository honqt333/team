<?php

namespace App\Models\HR;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shift extends Model
{
    use SoftDeletes;

    protected $table = 'hr_shifts';

    protected $fillable = [
        'tenant_id',
        'name_ar',
        'name_en',
        'start_time',
        'end_time',
        'color',
        'is_overnight',
        'is_active',
        'break_minutes',
    ];

    protected $casts = [
        'is_overnight' => 'boolean',
        'is_active' => 'boolean',
        'break_minutes' => 'integer',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'default_shift_id');
    }

    public function employeeShifts(): HasMany
    {
        return $this->hasMany(EmployeeShift::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Accessors
    public function getNameAttribute()
    {
        $locale = app()->getLocale();
        return $locale === 'ar' ? $this->name_ar : ($this->name_en ?? $this->name_ar);
    }

    /**
     * حساب عدد ساعات العمل في الوردية
     */
    public function getWorkingHoursAttribute(): float
    {
        $start = \Carbon\Carbon::parse($this->start_time);
        $end = \Carbon\Carbon::parse($this->end_time);
        
        if ($this->is_overnight && $end->lt($start)) {
            $end->addDay();
        }
        
        $totalMinutes = $start->diffInMinutes($end) - $this->break_minutes;
        return round($totalMinutes / 60, 2);
    }
}
