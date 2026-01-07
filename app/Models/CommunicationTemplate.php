<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommunicationTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type', // email, sms
        'subject',
        'content',
        'variables', // json description of variables
        'is_active',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get template by code.
     */
    public static function getByCode(string $code, string $type = 'email'): ?self
    {
        return self::where('code', $code)
            ->where('type', $type)
            ->where('is_active', true)
            ->first();
    }
}
