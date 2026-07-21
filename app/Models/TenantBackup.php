<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @bypass-tenancy-scanner - Cross-tenant audit/restore record.
 *
 * Status transitions:
 *   pending  → running   (worker picks it up)
 *   running  → completed (success)
 *   running  → failed    (worker captured error_message)
 */
class TenantBackup extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const TYPE_MANUAL = 'manual';

    public const TYPE_SCHEDULED = 'scheduled';

    public const TYPE_PRE_DELETE = 'pre_delete';

    protected $fillable = [
        'tenant_id',
        'name',
        'type',
        'status',
        'disk',
        'path',
        'size_bytes',
        'manifest',
        'checksum',
        'error_message',
        'started_at',
        'completed_at',
        'created_by_admin_id',
    ];

    protected function casts(): array
    {
        return [
            'manifest' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'size_bytes' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'created_by_admin_id');
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function humanSize(): string
    {
        if (! $this->size_bytes) {
            return '—';
        }
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        $size = (float) $this->size_bytes;

        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return round($size, 2).' '.$units[$i];
    }
}
