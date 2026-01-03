<?php

namespace App\Models\HR;

use App\Models\Center;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class BiometricDevice extends Model
{
    use SoftDeletes;

    protected $table = 'biometric_devices';

    protected $fillable = [
        'tenant_id',
        'center_id',
        'name',
        'device_id',
        'device_type',
        'api_token',
        'is_active',
        'last_sync_at',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_sync_at' => 'datetime',
    ];

    protected $hidden = [
        'api_token',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($device) {
            if (empty($device->api_token)) {
                $device->api_token = Str::random(64);
            }
        });
    }

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Methods
    public function regenerateToken(): string
    {
        $this->api_token = Str::random(64);
        $this->save();
        return $this->api_token;
    }

    public function updateLastSync(): void
    {
        $this->update(['last_sync_at' => now()]);
    }
}
