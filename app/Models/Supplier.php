<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\CenterScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use CenterScoped, HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'center_id',
        'name',
        'type',
        'code',
        'contact_person',
        'phone',
        'whatsapp',
        'email',
        'address',
        'city',
        'region',
        'postal_code',
        'building_number',
        'district',
        'street',
        'country',
        'lat',
        'lng',
        'tax_number',
        'cr_number',
        'bank_name',
        'iban',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($supplier) {
            if (empty($supplier->code)) {
                $lastId = static::where('tenant_id', $supplier->tenant_id)
                    ->withTrashed()
                    ->max('id') ?? 0;
                $supplier->code = 'SUP-'.str_pad($lastId + 1, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    // ─────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function purchaseInvoices(): HasMany
    {
        return $this->hasMany(PurchaseInvoice::class);
    }

    /**
     * All purchase return invoices belonging to this supplier's invoices.
     *
     * Schema: purchase_return_invoices.purchase_invoice_id → purchase_invoices.id
     *         purchase_invoices.supplier_id               → suppliers.id
     */
    public function returnInvoices(): HasManyThrough
    {
        return $this->hasManyThrough(
            PurchaseReturnInvoice::class,
            PurchaseInvoice::class,
            'supplier_id',                  // FK on purchase_invoices table
            'purchase_invoice_id',          // FK on purchase_return_invoices table
            'id',                           // local key on suppliers
            'id'                            // local key on purchase_invoices
        );
    }

    public function payments()
    {
        return $this->hasManyThrough(Payment::class, PurchaseInvoice::class);
    }

    public function calculateBalance(): float
    {
        $invoicesBalance = 0.0;
        $totalCredits = 0.0;

        foreach ($this->purchaseInvoices()->whereNotIn('status', [PurchaseInvoice::STATUS_DRAFT, PurchaseInvoice::STATUS_CANCELLED])->get() as $inv) {
            $invoicesBalance += (float) $inv->balance;

            // Real payments (cash/bank/transfer) the buyer has handed to the supplier.
            $payments = (float) $inv->payments()
                ->where('type', Payment::TYPE_PAYMENT)
                ->sum('amount');

            $returnsTotal = (float) $inv->returnInvoices()->sum('total');

            // Sum of `create_debit_note` returns only — these are the returns
            // where the supplier has NOT physically returned cash to us, so
            // they represent credit we are owed by the supplier.
            //
            // A return WITHOUT `create_debit_note` is assumed to be a normal
            // goods return (reduces invoice total) and is already absorbed
            // into `invoice.balance` (the controller does
            //     $invoice->update(['balance' => max(0, $balance - $return->total)])
            // ), so it does NOT add to the credit total.
            //
            // A return WITH `create_debit_note` (and no cash refund) means:
            //   - we already paid the supplier for these goods
            //   - we sent the goods back
            //   - the supplier owes us the amount back (or will offset it)
            // → this amount must show up as a positive credit on the supplier.
            $debitNoteTotal = (float) $inv->returnInvoices()
                ->where('create_debit_note', true)
                ->sum('total');

            // Real cash refunds actually returned to the supplier (TYPE_REFUND).
            // These offset any debit-note credit (the supplier paid us back in
            // cash, so the credit is partially or fully settled).
            $cashRefunds = (float) $inv->payments()
                ->where('type', Payment::TYPE_REFUND)
                ->sum('amount');

            // Overpayment credit = (debit notes still outstanding) - (cash refunds).
            //
            // We also include `payments - inv.total` (overpayment on the
            // invoice itself, e.g. paid 1100 for a 1000 invoice) as credit
            // — anything we paid above the invoice total is money the
            // supplier owes us back.
            $overpaidOnInvoice = max(0.0, $payments - (float) $inv->total);
            $outstandingDebitNotes = max(0.0, $debitNoteTotal - $cashRefunds);
            $credit = $overpaidOnInvoice + $outstandingDebitNotes;

            $totalCredits += $credit;
        }

        $balance = round($invoicesBalance - $totalCredits, 2);

        return $balance == 0.0 ? 0.0 : $balance;
    }

    // ─────────────────────────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────────────────────────

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForCenter($query, ?int $centerId)
    {
        return $query->where(function ($q) use ($centerId) {
            $q->where('center_id', $centerId)
                ->orWhereNull('center_id');
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, ?string $search)
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")
                ->orWhere('contact_person', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%");
        });
    }
}
