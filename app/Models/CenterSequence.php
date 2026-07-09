<?php

namespace App\Models;

use App\Models\Concerns\CenterScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CenterSequence extends Model
{
    use CenterScoped, HasFactory;

    protected $fillable = [
        'tenant_id',
        'center_id',
        'type',
        'prefix',
        'current_value',
        'year',
    ];

    public static function getNextValue(int $tenantId, ?int $centerId, string $type, ?int $year = null): int
    {
        // Pessimistic Locking to prevent gaps/duplicates
        return DB::transaction(function () use ($tenantId, $centerId, $type, $year) {
            $query = static::query();
            if ($centerId === null) {
                $query = $query->withoutGlobalScope('center_scoped');
            }
            $sequence = $query->lockForUpdate()->firstOrCreate(
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
