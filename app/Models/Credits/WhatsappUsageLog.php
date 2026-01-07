<?php

namespace App\Models\Credits;

use App\Models\Tenant;
use App\Models\Center;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappUsageLog extends Model
{
    protected $fillable = [
        'tenant_id',
        'center_id',
        'phone_number',
        'message_type',
        'template_name',
        'credits_used',
        'provider',
        'provider_message_id',
        'status',
        'error_message',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function getMessageTypeLabelAttribute(): string
    {
        return match ($this->message_type) {
            'template' => 'قالب',
            'session' => 'محادثة',
            'media' => 'وسائط',
            default => $this->message_type,
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'sent' => 'مرسل',
            'delivered' => 'تم التسليم',
            'read' => 'مقروء',
            'failed' => 'فشل',
            'pending' => 'قيد الإرسال',
            default => $this->status,
        };
    }
}
