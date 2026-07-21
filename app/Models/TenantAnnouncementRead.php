<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantAnnouncementRead extends Model
{
    use TenantScoped;

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'system_announcement_id',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function announcement(): BelongsTo
    {
        return $this->belongsTo(SystemAnnouncement::class, 'system_announcement_id');
    }
}
