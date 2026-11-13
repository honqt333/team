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

    protected $appends = [
        'refunded_amount',
        'remaining_refund',
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

    /**
     * Calculate how much of this return invoice has been refunded in cash by the supplier.
     */
    public function calculateRefundedAmount(): float
    {
        $invoice = $this->relationLoaded('purchaseInvoice')
            ? $this->purchaseInvoice
            : $this->purchaseInvoice()->first();

        if (! $invoice) {
            return 0.0;
        }

        $allRefunds = $invoice->relationLoaded('payments')
            ? $invoice->payments->where('type', Payment::TYPE_REFUND)
            : $invoice->payments()->where('type', Payment::TYPE_REFUND)->get();

        if ($allRefunds->isEmpty()) {
            return 0.0;
        }

        $code = (string) $this->code;
        $matchedRefundsSum = (float) $allRefunds->filter(function ($payment) use ($code) {
            return $code !== '' && str_contains((string) ($payment->notes ?? ''), $code);
        })->sum('amount');

        $allReturns = $invoice->relationLoaded('returnInvoices')
            ? $invoice->returnInvoices
            : $invoice->returnInvoices()->orderBy('id')->get();

        $allCodes = $allReturns->pluck('code')->filter()->values()->all();

        $untaggedRefundsSum = (float) $allRefunds->filter(function ($payment) use ($allCodes) {
            $notes = (string) ($payment->notes ?? '');

            foreach ($allCodes as $retCode) {
                if ($retCode !== '' && str_contains($notes, (string) $retCode)) {
                    return false;
                }
            }

            return true;
        })->sum('amount');

        if ($untaggedRefundsSum > 0.001) {
            $remainingPool = $untaggedRefundsSum;

            foreach ($allReturns->sortBy('id') as $ret) {
                $retCode = (string) $ret->code;
                $retTagged = (float) $allRefunds->filter(
                    fn ($p) => $retCode !== '' && str_contains((string) ($p->notes ?? ''), $retCode)
                )->sum('amount');
                $retNeed = max(0.0, (float) $ret->total - $retTagged);
                $allocated = min($remainingPool, $retNeed);

                if ((int) $ret->id === (int) $this->id) {
                    return round(min((float) $this->total, $matchedRefundsSum + $allocated), 2);
                }

                $remainingPool = max(0.0, $remainingPool - $allocated);
            }
        }

        return round(min((float) $this->total, $matchedRefundsSum), 2);
    }

    /**
     * Calculate the remaining refund receivable from the supplier for this return (`الباقي من المستردات`).
     */
    public function calculateRemainingRefund(): float
    {
        return round(max(0.0, (float) $this->total - $this->calculateRefundedAmount()), 2);
    }

    public function getRefundedAmountAttribute(): float
    {
        return $this->calculateRefundedAmount();
    }

    public function getRemainingRefundAttribute(): float
    {
        return $this->calculateRemainingRefund();
    }
}
