<?php

namespace App\Models\Credits;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappPurchase extends Model
{
    protected $fillable = [
        'tenant_id',
        'whatsapp_package_id',
        'credits',
        'amount',
        'payment_gateway',
        'payment_reference',
        'status',
        'payment_details',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_details' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(WhatsappPackage::class, 'whatsapp_package_id');
    }

    public function markAsPaid(string $gateway, string $reference, array $details = []): void
    {
        $this->update([
            'status' => 'paid',
            'payment_gateway' => $gateway,
            'payment_reference' => $reference,
            'payment_details' => $details,
        ]);

        $balance = TenantWhatsappBalance::getOrCreate($this->tenant_id);
        $balance->addCredits($this->credits);
    }
}
