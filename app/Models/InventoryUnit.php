<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name_ar',
        'name_en',
        'is_active',
    ];
}
