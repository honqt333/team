<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CenterWorkingHour extends Model
{
    protected $fillable = [
        'center_id',
        'day_of_week',
        'is_open',
        'open_time',
        'close_time',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'is_open' => 'boolean',
    ];

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    // Day names for display
    public static function getDayName(int $dayOfWeek, string $locale = 'ar'): string
    {
        $days = [
            'ar' => ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'],
            'en' => ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
        ];
        return $days[$locale][$dayOfWeek] ?? $days['en'][$dayOfWeek];
    }
}
