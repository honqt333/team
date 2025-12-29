<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CenterSequence extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'center_id',
        'type',
        'prefix',
        'current_value',
        'year',
    ];

    public static function getNextValue(int $tenantId, int $centerId, string $type, ?int $year = null): int
    {
        // Pessimistic Locking to prevent gaps/duplicates
        return \Illuminate\Support\Facades\DB::transaction(function () use ($tenantId, $centerId, $type, $year) {
            $sequence = static::lockForUpdate()->firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'center_id' => $centerId,
                    'type' => $type,
                    'year' => $year,
                ],
                ['current_value' => 0]
            );

            $sequence->increment('current_value');
            
            return $sequence->current_value;
        });
    }
}
