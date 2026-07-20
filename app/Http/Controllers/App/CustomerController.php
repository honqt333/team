<?php

namespace App\Http\Controllers\App;

use App\Http\Requests\Customer\CustomerMergeRequest;
use App\Http\Requests\Customer\CustomerStoreRequest;
use App\Http\Requests\Customer\CustomerUpdateRequest;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Service;
use App\Models\VehicleColor;
use App\Models\VehicleMake;
use App\Models\VehicleModel;
use App\Actions\Customer\MergeCustomerAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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
        $query = Customer::query();

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

        return $query;
    }

    public function index(): Response
    {
        $this->authorize('viewAny', Customer::class);

        $customers = $this->buildCustomerQuery()
            ->withCount(['vehicles', 'quotes', 'workOrders'])
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Customers/Index', [
            'customers' => $customers,
            'filters' => request()->only(['search', 'type', 'date_from', 'date_to']),
        ]);
    }

    public function print()
    {
        $this->authorize('viewAny', Customer::class);

        $query = $this->buildCustomerQuery();
        
        // TODO: Implement print functionality
        // For now, redirect to index
        return Inertia::render('Customers/Index', [
            'customers' => $query->paginate(15),
            'filters' => request()->only(['search', 'type', 'date_from', 'date_to']),
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
                'invoice'
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

        // Get all payments from customer's work orders or direct invoices
        $payments = \App\Models\Payment::where(function($query) use ($customer) {
                $query->whereHas('workOrder', function($q) use ($customer) {
                    $q->withoutGlobalScope('center_scoped')->where('customer_id', $customer->id);
                })
                ->orWhereHas('invoice', function($q) use ($customer) {
                    $q->withoutGlobalScope('center_scoped')->where('customer_id', $customer->id);
                });
            })
            ->with(['receivedBy', 'workOrder', 'invoice'])
            ->latest('payment_date')
            ->get();

        // Get customer's invoices (bypassing center_scoped to show tenant-wide history)
        $invoices = $customer->invoices()
            ->withoutGlobalScope('center_scoped')
            ->with(['workOrder.vehicle'])
            ->latest()
            ->get();

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

        return Inertia::render('Customers/Show', [
            'customer' => $customer,
            'counts' => $counts,
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

    public function checkPhone(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;
        $phone = $request->query('phone');

        if (!$phone) {
            return response()->json(['exists' => false]);
        }

        // Normalize phone number
        $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        $phone = str_replace($arabic, $english, $phone);
        $phone = str_replace($persian, $english, $phone);

        // Remove all non-numeric characters (except +)
        $phone = preg_replace('/[^\d+]/', '', $phone);

        // If it starts with 00966, convert to +966
        if (str_starts_with($phone, '00966')) {
            $phone = '+966' . substr($phone, 5);
        }

        // If it starts with 05, convert to +9665
        if (str_starts_with($phone, '05') && strlen($phone) === 10) {
            $phone = '+966' . substr($phone, 1);
        }

        // If it starts with 5, convert to +9665
        if (str_starts_with($phone, '5') && strlen($phone) === 9) {
            $phone = '+966' . $phone;
        }

        // If it starts with 966 and doesn't start with +, add +
        if (str_starts_with($phone, '966') && !str_starts_with($phone, '+')) {
            $phone = '+' . $phone;
        }

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
                ]
            ]);
        }

        return response()->json(['exists' => false]);
    }
}
