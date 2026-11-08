<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class TaxPeriod extends Model
{
    use HasFactory, SoftDeletes, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'center_id',
        'period_key',
        'name',
        'year',
        'year_period',
        'period_type',
        'start_date',
        'end_date',
        'status',
        'transactions_count',
        'income_transactions_count',
        'expense_transactions_count',
        'sum_income_subtotal',
        'sum_income_vat_amount',
        'sum_income_total_amount',
        'sum_expense_subtotal',
        'sum_expense_vat_amount',
        'sum_expense_total_amount',
        'sum_subtotal',
        'sum_vat_amount',
        'sum_total_amount',
        'net_vat_amount',
        'created_by',
        'posted_by',
        'posted_at',
        'notes',
    ];

    protected $casts = [
        'year' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'posted_at' => 'datetime',
        'transactions_count' => 'integer',
        'income_transactions_count' => 'integer',
        'expense_transactions_count' => 'integer',
        'sum_income_subtotal' => 'decimal:2',
        'sum_income_vat_amount' => 'decimal:2',
        'sum_income_total_amount' => 'decimal:2',
        'sum_expense_subtotal' => 'decimal:2',
        'sum_expense_vat_amount' => 'decimal:2',
        'sum_expense_total_amount' => 'decimal:2',
        'sum_subtotal' => 'decimal:2',
        'sum_vat_amount' => 'decimal:2',
        'sum_total_amount' => 'decimal:2',
        'net_vat_amount' => 'decimal:2',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(TaxPeriodTransaction::class, 'tax_period_id');
    }

    public function selectedTransactions(): HasMany
    {
        return $this->hasMany(TaxPeriodTransaction::class, 'tax_period_id')->where('is_selected', true);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function isPosted(): bool
    {
        return $this->status === 'posted';
    }

    public function logActivity(string $action, ?string $description = null, ?array $changes = null): void
    {
        Log::info("tax_period.{$action}", [
            'tenant_id' => $this->tenant_id,
            'tax_period_id' => $this->id,
            'period_key' => $this->period_key,
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
            'changes' => $changes,
        ]);

        if (auth('admin')->check()) {
            AdminActivityLog::create([
                'admin_user_id' => auth('admin')->id(),
                'action' => $action,
                'model_type' => self::class,
                'model_id' => $this->id,
                'changes' => array_merge($changes ?? [], array_filter(['description' => $description])),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }
    }

    public function recalculateTotals(): self
    {
        $selected = $this->transactions()->where('is_selected', true)->get();

        $income = $selected->where('is_income', true);
        $expense = $selected->where('is_income', false);

        $sumIncomeSubtotal = (float) $income->sum('subtotal');
        $sumIncomeVat = (float) $income->sum('vat_amount');
        $sumIncomeTotal = (float) $income->sum('total_amount');

        $sumExpenseSubtotal = (float) $expense->sum('subtotal');
        $sumExpenseVat = (float) $expense->sum('vat_amount');
        $sumExpenseTotal = (float) $expense->sum('total_amount');

        $this->transactions_count = $selected->count();
        $this->income_transactions_count = $income->count();
        $this->expense_transactions_count = $expense->count();

        $this->sum_income_subtotal = round($sumIncomeSubtotal, 2);
        $this->sum_income_vat_amount = round($sumIncomeVat, 2);
        $this->sum_income_total_amount = round($sumIncomeTotal, 2);

        $this->sum_expense_subtotal = round($sumExpenseSubtotal, 2);
        $this->sum_expense_vat_amount = round($sumExpenseVat, 2);
        $this->sum_expense_total_amount = round($sumExpenseTotal, 2);

        $this->sum_subtotal = round($sumIncomeSubtotal + $sumExpenseSubtotal, 2);
        $this->sum_vat_amount = round($sumIncomeVat + $sumExpenseVat, 2);
        $this->sum_total_amount = round($sumIncomeTotal + $sumExpenseTotal, 2);
        $this->net_vat_amount = round($sumIncomeVat - $sumExpenseVat, 2);

        $this->save();

        return $this;
    }
}
