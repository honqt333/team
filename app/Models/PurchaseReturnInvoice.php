<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseReturnInvoice extends Model
{
    use SoftDeletes, TenantScoped;

    protected $fillable = [
        'tenant_id', 'center_id', 'purchase_invoice_id', 'code', 'return_date',
        'subtotal', 'tax_amount', 'total', 'create_debit_note', 'debit_note_date', 'notes', 'attachment_path',
    ];

    protected $casts = [
        'return_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'create_debit_note' => 'boolean',
        'debit_note_date' => 'date',
    ];

    // Relationships
    /** @return BelongsTo<PurchaseInvoice, $this> */
    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    public function lines()
    {
        return $this->hasMany(PurchaseReturnInvoiceLine::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return BelongsTo<Center, $this> */
    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    // Code generator: PRET-0001
    public static function generateCode(int $tenantId): string
    {
        $last = static::where('tenant_id', $tenantId)
            ->withTrashed()
            ->latest('id')
            ->first();

        $nextNumber = 1;

        if ($last && $last->code) {
            $lastNumber = (int) preg_replace('/[^0-9]/', '', $last->code);

            if ($lastNumber > 0) {
                $nextNumber = $lastNumber + 1;
            }
        }

        return 'PRET-'.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
