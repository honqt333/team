<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Feedback extends Model
{
    use HasFactory, SoftDeletes, TenantScoped;

    protected $table = 'feedbacks';

    public const TYPE_BUG = 'bug';

    public const TYPE_FEATURE_REQUEST = 'feature_request';

    public const TYPE_SUGGESTION = 'suggestion';

    public const STATUS_PENDING = 'pending';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'type',
        'module',
        'status',
        'allow_admin_access',
        'comments',
        'video_path',
        'video_duration_seconds',
        'admin_reply',
        'admin_reply_video_path',
        'admin_replied_at',
        'admin_user_id',
        'has_user_seen_reply',
    ];

    protected $casts = [
        'allow_admin_access' => 'boolean',
        'has_user_seen_reply' => 'boolean',
        'admin_replied_at' => 'datetime',
        'video_duration_seconds' => 'integer',
    ];

    protected $appends = [
        'video_url',
        'admin_reply_video_url',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    public function getVideoUrlAttribute(): ?string
    {
        if (! $this->video_path) {
            return null;
        }

        return route('app.feedback.video', $this->id, false);
    }

    public function getAdminReplyVideoUrlAttribute(): ?string
    {
        if (! $this->admin_reply_video_path) {
            return null;
        }

        return route('app.feedback.reply-video', $this->id, false);
    }
}
