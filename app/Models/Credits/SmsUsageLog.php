<?php

namespace App\Models\Credits;

use App\Models\Tenant;
use App\Models\Center;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsUsageLog extends Model
{
    protected $fillable = [
        'tenant_id',
        'center_id',
        'phone_number',
        'message_type',
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

    /**
     * Get message type label.
     */
    public function getMessageTypeLabelAttribute(): string
    {
        return match ($this->message_type) {
            'reminder' => 'تذكير',
            'notification' => 'إشعار',
            'otp' => 'رمز تحقق',
            'marketing' => 'تسويقي',
            'invoice' => 'فاتورة',
            'appointment' => 'موعد',
            default => $this->message_type,
        };
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'sent' => 'مرسل',
            'delivered' => 'تم التسليم',
            'failed' => 'فشل',
            'pending' => 'قيد الإرسال',
            default => $this->status,
        };
    }

    /**
     * Scope for tenant.
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
