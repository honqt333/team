<?php

declare(strict_types=1);

namespace App\Models\HR;

use App\Models\Center;
use App\Models\Concerns\TenantScoped;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class BiometricDevice extends Model
{
    use SoftDeletes, TenantScoped;

    protected $table = 'biometric_devices';

    protected $fillable = [
        'tenant_id',
        'center_id',
        'name',
        'device_id',
        'device_type',
        'api_token',
        'token_hash',
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
        'token_hash',
    ];

    protected static function boot()
    {
        parent::boot();

        // Issue a token on creation and hash it.
        //
        // The hash is computed in the `creating` hook rather than in
        // `saving`, because `saving` fires BEFORE `creating` — at that
        // point the token does not exist yet on a new record.
        static::creating(function ($device) {
            if (empty($device->api_token) && empty($device->token_hash)) {
                $device->api_token = Str::random(64);
            }

            if (! empty($device->api_token)) {
                $device->token_hash = self::hashToken((string) $device->api_token);
            }
        });

        // Keep the hash in sync when the token is rotated.
        static::updating(function ($device) {
            if ($device->isDirty('api_token') && ! empty($device->api_token)) {
                $device->token_hash = self::hashToken((string) $device->api_token);
            }
        });

        static::saved(function ($device) {
            if (! empty($device->api_token)) {
                // Null the plaintext without touching the in-memory
                // attribute, so the caller can still read the token
                // that was just generated.
                $device->newQuery()
                    ->whereKey($device->getKey())
                    ->update(['api_token' => null]);

                $device->syncOriginalAttribute('api_token');
            }
        });
    }

    /**
     * Hash a device token.
     *
     * HMAC-SHA256 keyed with the application key. The digest is useless
     * outside this application, and it is a plain equality lookup
     * against the indexed `token_hash` column — so the authentication
     * path costs exactly one query, as before.
     */
    public static function hashToken(string $token): string
    {
        return hash_hmac('sha256', $token, (string) config('app.key'));
    }

    /**
     * Find an active device by the token it presented.
     *
     * The presented token is hashed with the same key and matched
     * against the indexed `token_hash` column.
     */
    public static function findByToken(?string $token): ?self
    {
        if (empty($token)) {
            return null;
        }

        return static::query()
            ->where('token_hash', self::hashToken($token))
            ->where('is_active', true)
            ->first();
    }

    /**
     * Find an active device by token, scoped to a query builder.
     */
    public function scopeWhereToken(Builder $query, ?string $token): Builder
    {
        return $query
            ->where('token_hash', self::hashToken((string) $token))
            ->where('is_active', true);
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
