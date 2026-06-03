<?php

namespace App\Models;

use App\Models\Concerns\CenterScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, SoftDeletes, CenterScoped;

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
                $supplier->code = 'SUP-' . str_pad($lastId + 1, 5, '0', STR_PAD_LEFT);
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

    public function payments()
    {
        return $this->hasManyThrough(Payment::class, PurchaseInvoice::class);
    }

    public function calculateBalance(): float
    {
        $invoicesBalance = 0.0;
        $totalCredits = 0.0;
        $totalDebitNotes = 0.0;

        foreach ($this->purchaseInvoices()->whereNotIn('status', [\App\Models\PurchaseInvoice::STATUS_DRAFT, \App\Models\PurchaseInvoice::STATUS_CANCELLED])->get() as $inv) {
            $invoicesBalance += (float) $inv->balance;

            $payments = (float) $inv->payments()
                ->where('type', \App\Models\Payment::TYPE_PAYMENT)
                ->where('payment_method', '!=', 'debit_note')
                ->sum('amount');

            $returnsTotal = (float) $inv->returnInvoices()->sum('total');

            $cashRefunds = (float) $inv->payments()
                ->where('type', \App\Models\Payment::TYPE_REFUND)
                ->where('payment_method', '!=', 'debit_note')
                ->sum('amount');

            $debitNotes = (float) $inv->payments()
                ->where('type', \App\Models\Payment::TYPE_REFUND)
                ->where('payment_method', 'debit_note')
                ->sum('amount');

            $overpaid = max(0.0, $payments + $returnsTotal - (float) $inv->total);
            $credit = max(0.0, $overpaid - $cashRefunds - $debitNotes);

            $totalCredits += $credit;
            $totalDebitNotes += $debitNotes;
        }

        $balance = round($invoicesBalance - $totalCredits - $totalDebitNotes, 2);
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
