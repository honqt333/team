<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CenterAddress extends Model
{
    protected $fillable = [
        'center_id',
        'address_line',
        'street',
        'city',
        'district',
        'building_number',
        'postal_code',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }
}
