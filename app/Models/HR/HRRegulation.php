<?php

namespace App\Models\HR;
 
use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
 
class HRRegulation extends Model
{
    use TenantScoped;
 
    protected $table = 'hr_regulations';
 
    protected $fillable = [
        'tenant_id',
        'category',
        'title_ar',
        'title_en',
        'content_ar',
        'content_en',
        'is_active',
        'created_by',
        'updated_by',
    ];
 
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
 
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
