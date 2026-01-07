<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationSendLog extends Model
{
    protected $fillable = [
        'system_announcement_id',
        'tenant_id',
        'channel',
        'status',
        'error_message',
    ];

    public function announcement(): BelongsTo
    {
        return $this->belongsTo(SystemAnnouncement::class, 'system_announcement_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Log a send attempt.
     */
    public static function logSend(int $announcementId, int $tenantId, string $channel, string $status, ?string $error = null): self
    {
        return self::create([
            'system_announcement_id' => $announcementId,
            'tenant_id' => $tenantId,
            'channel' => $channel,
            'status' => $status,
            'error_message' => $error,
        ]);
    }
}
