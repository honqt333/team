<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspectionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_id',
        'category',
        'name',
        'description',
        'sort_order',
    ];

    protected $casts = [
        'category' => 'array',
        'name' => 'array',
        'description' => 'array',
        'sort_order' => 'integer',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(InspectionTemplate::class, 'template_id');
    }
}
