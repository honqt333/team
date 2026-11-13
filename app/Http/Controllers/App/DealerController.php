<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Enums\PaymentType;
use App\Http\Controllers\Controller;
use App\Models\Center;
use App\Models\Customer;
use App\Models\Dealer;
use App\Models\DealerPaymentTerm;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\VehicleColor;
use App\Models\VehicleMake;
use App\Models\VehicleModel;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DealerController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Dealer::class);

        $user = $request->user();
        $tenantId = $user->tenant_id;

        $query = Dealer::query()
            ->where('tenant_id', $tenantId)
            ->search($request->input('search'))
            ->when($request->input('type'), fn ($q, $t) => $q->where('type', $t))
            ->when($request->input('status') === 'active', fn ($q) => $q->where('is_active', true))
            ->when($request->input('status') === 'inactive', fn ($q) => $q->where('is_active', false))
            ->select('dealers.*')
            ->withCount(['vehicles', 'workOrders'])
            ->selectSub(
                Invoice::query()
                    ->withoutGlobalScope('center_scoped')
                    ->whereColumn('invoices.dealer_id', 'dealers.id')
                    ->whereNotIn('status', ['cancelled'])
                    ->where('type', 'invoice')
                    ->selectRaw('COALESCE(SUM(CASE WHEN (total_incl_tax - total_paid) > 0 THEN (total_incl_tax - total_paid) ELSE 0 END), 0)'),
                'outstanding_balance'
            );

        $sort = (string) $request->input('sort');
        $direction = strtolower((string) $request->input('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (in_array($sort, ['name', 'type', 'is_active', 'created_at', 'id'], true)) {
            $query->orderBy("dealers.{$sort}", $direction);
        } else {
            $query->latest('dealers.id');
        }

        $dealers = $query->paginate(20)->withQueryString();
        $dealers->getCollection()->each(function (Dealer $dealer) {
            $dealer->outstanding_balance = $dealer->getOutstandingBalance();
        });

        return Inertia::render('Dealers/Index', [
            'dealers' => $dealers,
            'types' => Dealer::TYPES,
            'filters' => array_merge(
                $request->only(['search', 'type', 'status']),
                ['sort' => $sort ?: null, 'direction' => $sort ? $direction : null]
            ),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Dealer::class);

        $user = $request->user();
        $validated = $this->validateDealer($request);
        $validated['tenant_id'] = $user->tenant_id;
        $validated['center_id'] = $user->current_center_id;

        Dealer::create($validated);

        return redirect()->back()->with('success', __('messages.dealers.created'));
    }

    public function show(Dealer $dealer): Response
    {
        $this->authorize('view', $dealer);

        // Load vehicles withoutGlobalScope('center_scoped') to show tenant-wide partner fleet
        $vehicles = $dealer->vehicles()
            ->withoutGlobalScope('center_scoped')
            ->with([
                'customer:id,name,phone',
                'make:id,name_ar,name_en,logo_path',
                'model:id,name_ar,name_en',
            ])
            ->latest('id')
            ->get();

        // Load work orders withoutGlobalScope('center_scoped')
        $workOrders = $dealer->workOrders()
            ->withoutGlobalScope('center_scoped')
            ->with([
                'customer:id,name,phone',
                'vehicle.make:id,name_ar,name_en,logo_path',
                'vehicle.model:id,name_ar,name_en',
                'invoice',
                'payments.receivedBy',
            ])
            ->latest('id')
            ->get();
        $workOrders->each->append(['total', 'total_paid', 'balance', 'bad_debt']);

        // Load invoices withoutGlobalScope('center_scoped')
        $invoices = $dealer->invoices()
            ->withoutGlobalScope('center_scoped')
            ->with([
                'workOrder.vehicle.make:id,name_ar,name_en',
                'workOrder.vehicle.model:id,name_ar,name_en',
                'customer:id,name,phone',
            ])
            ->latest('id')
            ->get();
        $invoices->each->append(['balance']);

        // Load payments associated with this dealer's work orders or invoices
        $payments = Payment::where(function ($query) use ($dealer) {
            $query->whereHas('workOrder', function ($q) use ($dealer) {
                $q->withoutGlobalScope('center_scoped')->where('dealer_id', $dealer->id);
            })
                ->orWhereHas('invoice', function ($q) use ($dealer) {
                    $q->withoutGlobalScope('center_scoped')->where('dealer_id', $dealer->id);
                });
        })
            ->with(['receivedBy', 'workOrder.vehicle', 'invoice.workOrder.vehicle'])
            ->latest('payment_date')
            ->get();

        [$financialSummary, $ledger] = $this->buildDealerStatementData($dealer, $invoices, $payments, $workOrders);

        $paymentTerms = $dealer->paymentTerms()->get();

        $counts = [
            'vehicles' => $vehicles->count(),
            'workOrders' => $workOrders->count(),
            'invoices' => $invoices->count(),
            'payments' => $payments->count(),
            'paymentTerms' => $paymentTerms->count(),
        ];

        $canDelete = $counts['vehicles'] === 0 && $counts['workOrders'] === 0 && $counts['invoices'] === 0;

        $totalDue = (float) ($financialSummary['total_due'] ?? 0);
        $creditLimit = (float) ($dealer->credit_limit ?? 0);
        $dealer->balance = $totalDue;
        $dealer->credit_limit = $creditLimit;
        $dealer->is_credit_exceeded = $creditLimit > 0 && $totalDue > $creditLimit;
        $dealer->available_credit = $creditLimit > 0 ? max(0.0, $creditLimit - $totalDue) : null;
        $dealer->credit_used_percent = $creditLimit > 0 ? min(100.0, round(($totalDue / $creditLimit) * 100, 1)) : 0.0;

        // Set relations on dealer for backward compatibility with existing tests
        $dealer->setRelation('vehicles', $vehicles);
        $dealer->setRelation('workOrders', $workOrders);
        $dealer->setRelation('invoices', $invoices);
        $dealer->setRelation('paymentTerms', $paymentTerms);

        $customers = Customer::query()
            ->where('tenant_id', $dealer->tenant_id)
            ->select('id', 'name', 'phone', 'whatsapp')
            ->orderBy('name')
            ->get();

        $makes = VehicleMake::ordered()->get(['id', 'name_ar', 'name_en', 'logo_path']);
        $colors = VehicleColor::active()->ordered()->get(['id', 'name_ar', 'name_en', 'hex_code']);
        $modelsByMake = VehicleModel::query()
            ->select('id', 'make_id', 'name_ar', 'name_en')
            ->ordered()
            ->get()
            ->groupBy('make_id');

        $user = auth()->user();
        $tenant = $user?->tenant;
        $centerId = $user?->current_center_id;
        $center = $centerId ? Center::find($centerId) : null;
        $companyInfo = [
            'name' => $center?->name_ar ?? $center?->name ?? $tenant?->trade_name ?? $tenant?->name ?? config('app.name_ar', 'خدمة برو'),
            'branch' => $center?->name ?? '',
            'tax_number' => $tenant?->vat_number ?? $center?->vat_number ?? '',
            'cr_number' => $tenant?->cr_number ?? '',
            'logo' => $center?->logo_invoice_url ?? $center?->logo_light_url ?? $tenant?->logo_url ?? ($tenant?->logo_path ? '/storage/'.$tenant->logo_path : null),
            'phone' => $center?->phone ?? $tenant?->phone ?? '',
        ];

        return Inertia::render('Dealers/Show', [
            'dealer' => $dealer,
            'types' => Dealer::TYPES,
            'outstandingBalance' => $totalDue,
            'financialSummary' => $financialSummary,
            'ledger' => $ledger,
            'counts' => $counts,
            'canDelete' => $canDelete,
            'vehicles' => $vehicles,
            'workOrders' => $workOrders,
            'invoices' => $invoices,
            'payments' => $payments,
            'paymentTerms' => $paymentTerms,
            'customers' => $customers,
            'makes' => $makes,
            'colors' => $colors,
            'modelsByMake' => $modelsByMake,
            'companyInfo' => $companyInfo,
        ]);
    }

    /**
     * Build financial summary and chronological ledger for a dealer.
     *
     * @param \Illuminate\Database\Eloquent\Collection $invoices
     * @param \Illuminate\Database\Eloquent\Collection $payments
     * @param \Illuminate\Database\Eloquent\Collection|null $workOrders
     * @return array{0: array<string, mixed>, 1: Collection}
     */
    protected function buildDealerStatementData(Dealer $dealer, $invoices, $payments, $workOrders = null): array
    {
        $openWorkOrdersCount = $dealer->workOrders()
            ->withoutGlobalScope('center_scoped')
            ->whereNotIn('status', ['completed', 'cancelled', 'delivered'])
            ->count();

        $invoicedWorkOrderIds = $invoices
            ->whereNotIn('status', ['cancelled'])
            ->pluck('work_order_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();

        $uninvoicedWorkOrders = ($workOrders ?? collect())
            ->filter(fn ($wo) => $wo->status !== 'cancelled' && ! in_array((int) $wo->id, $invoicedWorkOrderIds, true));

        $openUninvoicedTotal = (float) $uninvoicedWorkOrders->sum(fn ($wo) => (float) ($wo->total ?? $wo->total_incl_tax ?? 0));
        $openUninvoicedDue = (float) $uninvoicedWorkOrders->sum(function ($wo) {
            if (isset($wo->balance)) {
                return max(0.0, (float) $wo->balance);
            }

            return max(0.0, (float) ($wo->total ?? $wo->total_incl_tax ?? 0) - (float) ($wo->total_paid ?? 0));
        });

        $totalInvoiced = (float) $invoices->whereNotIn('status', ['cancelled', 'draft'])->sum('total_incl_tax');
        $totalPaid = (float) $payments->reduce(function ($carry, $pay) {
            $isRefund = $pay->type === PaymentType::REFUND || $pay->type === 'refund' || ($pay->type instanceof PaymentType && $pay->type->value === 'refund');

            return $isRefund ? ($carry - (float) $pay->amount) : ($carry + (float) $pay->amount);
        }, 0.0);
        $invoicedDue = (float) $invoices->whereNotIn('status', ['cancelled'])->sum(fn ($inv) => max(0, (float) $inv->total_incl_tax - (float) $inv->total_paid));
        $totalDue = round($invoicedDue + $openUninvoicedDue, 2);

        $financialSummary = [
            'total_invoiced' => $totalInvoiced,
            'open_work_orders_total' => round($openUninvoicedTotal, 2),
            'open_work_orders_due' => round($openUninvoicedDue, 2),
            'total_paid' => $totalPaid,
            'total_due' => $totalDue,
            'open_work_orders_count' => $openWorkOrdersCount,
        ];

        $ledgerEntries = collect();

        foreach ($invoices as $inv) {
            if ($inv->status === 'cancelled') {
                continue;
            }
            $ledgerEntries->push([
                'id' => 'inv-'.$inv->id,
                'date' => $inv->issue_date ? Carbon::parse($inv->issue_date)->format('Y-m-d H:i') : $inv->created_at->format('Y-m-d H:i'),
                'sort_date' => $inv->issue_date ? Carbon::parse($inv->issue_date)->timestamp : $inv->created_at->timestamp,
                'type' => 'invoice',
                'type_label' => __('invoices.invoice'),
                'reference' => $inv->invoice_number,
                'work_order_number' => $inv->workOrder?->code ?? $inv->workOrder?->work_order_number,
                'vehicle_plate' => $inv->workOrder?->vehicle?->plate_number,
                'customer_name' => $inv->customer?->name,
                'debit' => (float) $inv->total_incl_tax,
                'credit' => 0.0,
            ]);
        }

        foreach ($uninvoicedWorkOrders as $wo) {
            $woTotal = (float) ($wo->total ?? $wo->total_incl_tax ?? 0);

            if ($woTotal <= 0) {
                continue;
            }
            $ledgerEntries->push([
                'id' => 'wo-'.$wo->id,
                'date' => $wo->opened_at ? Carbon::parse($wo->opened_at)->format('Y-m-d H:i') : $wo->created_at->format('Y-m-d H:i'),
                'sort_date' => $wo->opened_at ? Carbon::parse($wo->opened_at)->timestamp : $wo->created_at->timestamp,
                'type' => 'work_order',
                'type_label' => __('work_orders.work_order'),
                'reference' => $wo->code ?? $wo->work_order_number,
                'work_order_number' => $wo->code ?? $wo->work_order_number,
                'vehicle_plate' => $wo->vehicle?->plate_number,
                'customer_name' => $wo->customer?->name,
                'debit' => $woTotal,
                'credit' => 0.0,
            ]);
        }

        foreach ($payments as $pay) {
            $isRefund = $pay->type === PaymentType::REFUND || $pay->type === 'refund' || ($pay->type instanceof PaymentType && $pay->type->value === 'refund');
            $workOrder = $pay->workOrder ?? $pay->invoice?->workOrder;
            $ledgerEntries->push([
                'id' => 'pay-'.$pay->id,
                'date' => $pay->payment_date ? Carbon::parse($pay->payment_date)->format('Y-m-d H:i') : $pay->created_at->format('Y-m-d H:i'),
                'sort_date' => $pay->payment_date ? Carbon::parse($pay->payment_date)->timestamp : $pay->created_at->timestamp,
                'type' => $isRefund ? 'refund' : 'payment',
                'type_label' => $isRefund ? __('payments.types.refund') : __('payments.types.payment'),
                'reference' => $pay->reference ?: ('REC-'.$pay->id),
                'work_order_number' => $workOrder?->code ?? $workOrder?->work_order_number,
                'vehicle_plate' => $workOrder?->vehicle?->plate_number,
                'customer_name' => $workOrder?->customer?->name,
                'method' => $pay->payment_method,
                'debit' => $isRefund ? (float) $pay->amount : 0.0,
                'credit' => $isRefund ? 0.0 : (float) $pay->amount,
            ]);
        }

        $sortedLedger = $ledgerEntries->unique('id')->sortBy('sort_date')->values();
        $runningBalance = 0.0;
        $ledger = $sortedLedger->map(function ($entry) use (&$runningBalance) {
            $runningBalance += ($entry['debit'] - $entry['credit']);
            $entry['balance'] = $runningBalance;

            return $entry;
        });

        return [$financialSummary, $ledger];
    }

    public function storePaymentTerm(Request $request, Dealer $dealer): RedirectResponse
    {
        $this->authorize('update', $dealer);

        $validated = $request->validate([
            'term_text' => ['required', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $nextOrder = isset($validated['sort_order'])
            ? (int) $validated['sort_order']
            : ((int) $dealer->paymentTerms()->max('sort_order') + 1);

        $dealer->paymentTerms()->create([
            'tenant_id' => $dealer->tenant_id,
            'term_text' => $validated['term_text'],
            'sort_order' => $nextOrder,
        ]);

        return redirect()->back()->with('success', __('messages.saved_successfully'));
    }

    public function updatePaymentTerm(Request $request, Dealer $dealer, DealerPaymentTerm $paymentTerm): RedirectResponse
    {
        $this->authorize('update', $dealer);
        abort_unless((int) $paymentTerm->dealer_id === (int) $dealer->id, 404);

        $validated = $request->validate([
            'term_text' => ['required', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $paymentTerm->update([
            'term_text' => $validated['term_text'],
            'sort_order' => isset($validated['sort_order']) ? (int) $validated['sort_order'] : $paymentTerm->sort_order,
        ]);

        return redirect()->back()->with('success', __('messages.updated_successfully'));
    }

    public function destroyPaymentTerm(Dealer $dealer, DealerPaymentTerm $paymentTerm): RedirectResponse
    {
        $this->authorize('update', $dealer);
        abort_unless((int) $paymentTerm->dealer_id === (int) $dealer->id, 404);

        $paymentTerm->delete();

        return redirect()->back()->with('success', __('messages.deleted_successfully'));
    }

    public function update(Request $request, Dealer $dealer): RedirectResponse
    {
        $this->authorize('update', $dealer);

        $validated = $this->validateDealer($request);
        $dealer->update($validated);

        return redirect()->back()->with('success', __('messages.dealers.updated'));
    }

    public function toggleActive(Dealer $dealer): RedirectResponse
    {
        $this->authorize('update', $dealer);

        $dealer->update(['is_active' => ! $dealer->is_active]);

        return redirect()->back()->with('success', $dealer->is_active
            ? __('messages.dealers.activated')
            : __('messages.dealers.deactivated'));
    }

    public function destroy(Dealer $dealer): RedirectResponse
    {
        $this->authorize('delete', $dealer);

        $hasRelations = $dealer->workOrders()->exists() || $dealer->invoices()->exists();

        if ($hasRelations) {
            return redirect()->back()->with('error', __('messages.dealers.cannot_delete_has_relations'));
        }

        $dealer->delete();

        return redirect()->route('app.dealers.index')->with('success', __('messages.dealers.deleted'));
    }

    /**
     * Validate dealer payload uniformly across store and update.
     */
    protected function validateDealer(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(Dealer::TYPES)],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'whatsapp' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'tax_number' => ['nullable', 'string', 'max:50'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'payment_terms_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'address_line' => ['nullable', 'string', 'max:255'],
            'building_number' => ['nullable', 'string', 'max:30'],
            'postal_code' => ['nullable', 'string', 'max:30'],
            'district' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }
}
