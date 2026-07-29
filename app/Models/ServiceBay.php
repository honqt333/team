<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\CenterScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceBay extends Model
{
    use CenterScoped, HasFactory;

    // Soft categorical labels. Free-form today but stable enough
    // to drive filters and badges in the UI.
    public const TYPES = [
        'maintenance' => 'maintenance',
        'polish' => 'polish',
        'tires' => 'tires',
        'electrical' => 'electrical',
        'wash' => 'wash',
        'inspection' => 'inspection',
        'general' => 'general',
    ];

    protected $fillable = [
        'tenant_id', 'center_id', 'name', 'code', 'type',
        'is_active', 'notes', 'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * "Active" appointments on this bay — anything that occupies
     * the bay's time. cancelled and converted are excluded.
     */
    public function activeAppointments(): HasMany
    {
        return $this->appointments()
            ->whereIn('status', [
                Appointment::STATUS_SCHEDULED,
                Appointment::STATUS_CONFIRMED,
                Appointment::STATUS_CHECKED_IN,
            ]);
    }

    public function hasActiveAppointments(): bool
    {
        return $this->activeAppointments()->exists();
    }
}
