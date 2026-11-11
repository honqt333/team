<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Enums\PaymentType;
use App\Http\Requests\Customer\CustomerStoreRequest;
use App\Http\Requests\Customer\CustomerUpdateRequest;
use App\Models\Center;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Service;
use App\Models\VehicleColor;
use App\Models\VehicleMake;
use App\Models\VehicleModel;
use App\Support\PhoneNumber;
use App\Support\TenancyContext;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController
{
    use AuthorizesRequests;

    /**
     * Build base customer query with common filters.
     * Reduces code duplication between index() and apiIndex().
     */
    protected function buildCustomerQuery(): Builder
    {
        $query = Customer::query()
            ->select('customers.*')
            ->selectSub(
                Invoice::query()
                    ->withoutGlobalScope('center_scoped')
                    ->whereColumn('customer_id', 'customers.id')
                    ->whereNotIn('status', ['cancelled'])
                    ->selectRaw('COALESCE(SUM(total_incl_tax - total_paid), 0)'),
                'balance'
            );

        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (request('type')) {
            $query->where('type', request('type'));
        }

        if (request('date_from')) {
            $query->whereDate('created_at', '>=', request('date_from'));
        }

        if (request('date_to')) {
            $query->whereDate('created_at', '<=', request('date_to'));
        }

        $sortableColumns = [
            'name' => 'customers.name',
            'phone' => 'customers.phone',
            'type' => 'customers.type',
            'contact_name' => 'customers.contact_name',
            'email' => 'customers.email',
            'balance' => 'balance',
            'created_at' => 'customers.created_at',
            'id' => 'customers.id',
        ];

        $sort = (string) request('sort');
        $direction = strtolower((string) request('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        if ($sort && isset($sortableColumns[$sort])) {
            $query->orderBy($sortableColumns[$sort], $direction);
        } else {
            $query->orderBy('customers.id', 'desc');
        }

        return $query;
    }

    public function index(): Response
    {
        $this->authorize('viewAny', Customer::class);

        $customers = $this->buildCustomerQuery()
            ->withCount(['vehicles', 'quotes', 'workOrders'])
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Customers/Index', [
            'customers' => $customers,
            'filters' => request()->only(['search', 'type', 'date_from', 'date_to', 'sort', 'direction']),
        ]);
    }

    public function print(): Response
    {
        $this->authorize('viewAny', Customer::class);

        $customers = $this->buildCustomerQuery()
            ->withCount(['vehicles', 'quotes', 'workOrders'])
            ->get();

        $tenant = auth()->user()->tenant;
        $centerId = TenancyContext::centerId();
        $center = $centerId ? Center::find($centerId) : null;

        return Inertia::render('Customers/Print', [
            'customers' => $customers,
            'filters' => request()->only(['search', 'type', 'date_from', 'date_to']),
            'companyInfo' => [
                'name' => $center?->name_ar ?? $center?->name ?? $tenant->trade_name ?? $tenant->name ?? 'خدمة برو',
                'vat_number' => $tenant->vat_number ?? '',
                'cr_number' => $tenant->cr_number ?? '',
                'logo_path' => $tenant->logo_path ?? '',
                'center_name' => $center?->name ?? '',
                'phone' => $center?->phone ?? $tenant->phone ?? '',
            ],
        ]);
    }

    public function apiIndex(): JsonResponse
    {
        $this->authorize('viewAny', Customer::class);

        $customers = $this->buildCustomerQuery()
            ->orderBy('id', 'desc')
            ->paginate(15);

        return response()->json($customers);
    }

    public function store(CustomerStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', Customer::class);

        $customer = Customer::create($request->validated());

        return redirect()->back()->with('customer', $customer);
    }

    public function show(Customer $customer): Response
    {
        $this->authorize('view', $customer);

        // Load all relationships in ONE query using nested with()
        // Fix: Removed duplicate load - $customer->load() and then $customer->vehicles()->get()
        $customer->load([
            'vehicles.make',
            'vehicles.model',
        ]);

        // Get related data with proper eager loading - NO duplicate queries
        // Fix: Using $customer->vehicles directly (already loaded above)
        $vehicles = $customer->vehicles;

        // Get work orders with all needed relationships in single query (bypassing center_scoped to show tenant-wide history)
        $workOrders = $customer->workOrders()
            ->withoutGlobalScope('center_scoped')
            ->with([
                'vehicle.make',
                'vehicle.model',
                'payments.receivedBy',
                'invoice',
            ])
            ->latest()
            ->get();
        $workOrders->each->append(['total', 'total_paid', 'balance', 'bad_debt']);

        // Get quotes with vehicle relationships
        $quotes = $customer->quotes()
            ->withoutGlobalScope('center_scoped')
            ->with(['vehicle.make', 'vehicle.model'])
            ->latest()
            ->get();

        // Build financial summary and chronological ledger via helper
        [$financialSummary, $ledger, $invoices, $payments] = $this->buildCustomerStatementData($customer);

        // Use counts from the relationship counts (avoid extra queries)
        $counts = [
            'vehicles' => $customer->vehicles_count ?? $vehicles->count(),
            'quotes' => $customer->quotes_count ?? $quotes->count(),
            'workOrders' => $customer->work_orders_count ?? $workOrders->count(),
            'invoices' => $invoices->count(),
            'payments' => $payments->count(),
        ];

        // Check if can be deleted - use exists() for efficiency
        $canDelete = $counts['vehicles'] === 0 && $counts['quotes'] === 0 && $counts['workOrders'] === 0;

        // Get form data for modals - optimize queries
        $makes = VehicleMake::orderBy('name_ar')->get();
        $colors = VehicleColor::orderBy('name_ar')->get();
        $modelsByMake = VehicleModel::all()->groupBy('make_id');
        $departments = Department::where('is_active', true)->orderBy('sort_order')->get();
        $services = Service::where('is_active', true)->with('department')->orderBy('sort_order')->get();

        $totalDue = (float) ($financialSummary['total_due'] ?? 0);
        $customerCreditLimit = (float) ($customer->credit_limit ?? 0);
        $customer->balance = $totalDue;
        $customer->credit_limit = $customerCreditLimit;
        $customer->is_credit_exceeded = $customerCreditLimit > 0 && $totalDue >= $customerCreditLimit;
        $customer->available_credit = $customerCreditLimit > 0 ? max(0.0, $customerCreditLimit - $totalDue) : null;

        return Inertia::render('Customers/Show', [
            'customer' => $customer,
            'counts' => $counts,
            'financialSummary' => $financialSummary,
            'ledger' => $ledger,
            'canDelete' => $canDelete,
            'vehicles' => $vehicles,
            'workOrders' => $workOrders,
            'quotes' => $quotes,
            'invoices' => $invoices,
            'payments' => $payments,
            'makes' => $makes,
            'colors' => $colors,
            'modelsByMake' => $modelsByMake,
            'departments' => $departments,
            'services' => $services,
        ]);
    }

    public function update(CustomerUpdateRequest $request, Customer $customer): RedirectResponse
    {
        $this->authorize('update', $customer);

        $customer->update($request->validated());

        return redirect()->back()->with('success', __('messages.customer_updated'));
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $this->authorize('delete', $customer);

        // Check for related data before deleting
        $hasVehicles = $customer->vehicles()->exists();
        $hasQuotes = $customer->quotes()->exists();
        $hasWorkOrders = $customer->workOrders()->exists();

        if ($hasVehicles || $hasQuotes || $hasWorkOrders) {
            return redirect()->back()->with('error', __('messages.customer_has_related_data'));
        }

        $customer->forceDelete();

        return redirect()->route('customers.index')->with('success', __('messages.customer_deleted'));
    }

    public function checkPhone(Request $request): JsonResponse
    {
        // The response carries the matching customer's name, phone,
        // email, tax number and address — not just a boolean. Without
        // this check any authenticated user could enumerate the
        // customer list of their tenant by calling the endpoint with
        // a phone number, even without the customers read permission.
        //
        // The duplicate-check UI needs the details (it links to the
        // existing record), so the read permission is the right gate.
        $this->authorize('viewAny', Customer::class);

        $tenantId = auth()->user()->tenant_id;
        $phone = $request->query('phone');

        if (! $phone) {
            return response()->json(['exists' => false]);
        }

        $phone = PhoneNumber::normalize($phone, TenancyContext::user()?->tenant?->country) ?? $phone;

        // Find customer under same tenant
        $customer = Customer::withoutGlobalScope('center_scoped')
            ->where('tenant_id', $tenantId)
            ->where('phone', $phone)
            ->first();

        if ($customer) {
            return response()->json([
                'exists' => true,
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'email' => $customer->email,
                    'type' => $customer->type,
                    'tax_number' => $customer->tax_number,
                    'address_line' => $customer->address_line,
                ],
            ]);
        }

        return response()->json(['exists' => false]);
    }

    /**
     * Dedicated printable page for Customer Statement of Account.
     */
    public function printStatement(Customer $customer): Response
    {
        $this->authorize('view', $customer);

        [$financialSummary, $ledger] = $this->buildCustomerStatementData($customer);

        $tenant = auth()->user()->tenant;
        $centerId = TenancyContext::centerId();
        $center = $centerId ? Center::find($centerId) : null;

        return Inertia::render('Customers/PrintStatement', [
            'customer' => $customer,
            'financialSummary' => $financialSummary,
            'ledger' => $ledger,
            'companyInfo' => [
                'name' => $center?->name_ar ?? $center?->name ?? $tenant->trade_name ?? $tenant->name ?? 'خدمة برو',
                'branch' => $center?->name ?? '',
                'vat_number' => $tenant->vat_number ?? $center?->vat_number ?? '',
                // A commercial registration number belongs to the
                // establishment, not to a branch — the `centers` table
                // has no `cr_number` column. The old
                // `$center?->cr_number` fallback therefore always
                // resolved to null and never contributed anything, so
                // removing it changes no output.
                'cr_number' => $tenant->cr_number ?? '',
                'logo_url' => $center?->logo_invoice_url ?? $center?->logo_light_url ?? $tenant->logo_url ?? ($tenant->logo_path ? '/storage/'.$tenant->logo_path : null),
                'phone' => $center?->phone ?? $tenant->phone ?? '',
            ],
        ]);
    }

    /**
     * Build financial summary and chronological ledger for a customer.
     *
     * @return array{0: array<string, mixed>, 1: Collection, 2: \Illuminate\Database\Eloquent\Collection, 3: \Illuminate\Database\Eloquent\Collection}
     */
    protected function buildCustomerStatementData(Customer $customer): array
    {
        $payments = Payment::where(function ($query) use ($customer) {
            $query->whereHas('workOrder', function ($q) use ($customer) {
                $q->withoutGlobalScope('center_scoped')->where('customer_id', $customer->id);
            })
                ->orWhereHas('invoice', function ($q) use ($customer) {
                    $q->withoutGlobalScope('center_scoped')->where('customer_id', $customer->id);
                });
        })
            ->with(['receivedBy', 'workOrder', 'invoice'])
            ->latest('payment_date')
            ->get();

        $invoices = $customer->invoices()
            ->withoutGlobalScope('center_scoped')
            ->with(['workOrder.vehicle'])
            ->latest()
            ->get();

        $openWorkOrdersCount = $customer->workOrders()
            ->withoutGlobalScope('center_scoped')
            ->whereNotIn('status', ['completed', 'cancelled', 'delivered'])
            ->count();

        $totalInvoiced = (float) $invoices->whereNotIn('status', ['cancelled', 'draft'])->sum('total_incl_tax');
        $totalPaid = (float) $payments->reduce(function ($carry, $pay) {
            $isRefund = $pay->type === PaymentType::REFUND || $pay->type === 'refund' || ($pay->type instanceof PaymentType && $pay->type->value === 'refund');

            return $isRefund ? ($carry - (float) $pay->amount) : ($carry + (float) $pay->amount);
        }, 0.0);
        $totalDue = (float) $invoices->whereNotIn('status', ['cancelled'])->sum(fn ($inv) => max(0, (float) $inv->total_incl_tax - (float) $inv->total_paid));

        $financialSummary = [
            'total_invoiced' => $totalInvoiced,
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
                'type_label' => 'فاتورة مبيعات',
                'reference' => $inv->invoice_number,
                'work_order_number' => $inv->workOrder?->work_order_number,
                'vehicle_plate' => $inv->workOrder?->vehicle?->plate_number,
                'debit' => (float) $inv->total_incl_tax,
                'credit' => 0.0,
            ]);
        }

        foreach ($payments as $pay) {
            $isRefund = $pay->type === PaymentType::REFUND || $pay->type === 'refund' || ($pay->type instanceof PaymentType && $pay->type->value === 'refund');
            $ledgerEntries->push([
                'id' => 'pay-'.$pay->id,
                'date' => $pay->payment_date ? Carbon::parse($pay->payment_date)->format('Y-m-d H:i') : $pay->created_at->format('Y-m-d H:i'),
                'sort_date' => $pay->payment_date ? Carbon::parse($pay->payment_date)->timestamp : $pay->created_at->timestamp,
                'type' => $isRefund ? 'refund' : 'payment',
                'type_label' => $isRefund ? 'سند استرجاع' : 'سند قبض',
                'reference' => $pay->reference ?: ('REC-'.$pay->id),
                'work_order_number' => $pay->workOrder?->work_order_number,
                'vehicle_plate' => $pay->workOrder?->vehicle?->plate_number,
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

        return [$financialSummary, $ledger, $invoices, $payments];
    }
}
