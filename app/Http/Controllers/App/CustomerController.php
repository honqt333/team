<?php

namespace App\Http\Controllers\App;

use App\Http\Requests\CustomerStoreRequest;
use App\Http\Requests\CustomerUpdateRequest;
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
        
        // Get work orders with all needed relationships in single query
        $workOrders = $customer->workOrders()
            ->with([
                'vehicle.make',
                'vehicle.model',
                'payments.receivedBy'
            ])
            ->latest()
            ->get();
        $workOrders->each->append(['total', 'total_paid', 'balance']);
        
        // Get quotes with vehicle relationships
        $quotes = $customer->quotes()
            ->with(['vehicle.make', 'vehicle.model'])
            ->latest()
            ->get();

        // Get all payments from customer's work orders - optimized single query
        $payments = \App\Models\Payment::whereHas('workOrder', function($q) use ($customer) {
                $q->where('customer_id', $customer->id);
            })
            ->with(['receivedBy', 'workOrder'])
            ->latest('payment_date')
            ->get();

        // Use counts from the relationship counts (avoid extra queries)
        $counts = [
            'vehicles' => $customer->vehicles_count ?? $vehicles->count(),
            'quotes' => $customer->quotes_count ?? $quotes->count(),
            'workOrders' => $customer->work_orders_count ?? $workOrders->count(),
            'invoices' => 0, // Placeholder for future
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

    public function create(): RedirectResponse
    {
        return redirect()->route('customers.index');
    }

    public function edit(Customer $customer): RedirectResponse
    {
        return redirect()->route('customers.show', $customer);
    }

    /**
     * Get merge data for customer.
     */
    public function mergeData(Customer $customer): \Illuminate\Http\JsonResponse
    {
        $this->authorize('update', $customer);

        // Secure Query: Only same center, same type
        $query = Customer::where('id', '!=', $customer->id)
            ->where('center_id', $customer->center_id)
            ->where('type', $customer->type)
            ->withCount(['vehicles', 'quotes', 'workOrders'])
            ->orderBy('name');
        
        $otherCustomers = $query->get();

        return response()->json([
            'source' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'whatsapp' => $customer->whatsapp,
                'vehicles_count' => $customer->vehicles()->count(),
                'quotes_count' => $customer->quotes()->count(),
                'work_orders_count' => $customer->workOrders()->count(),
            ],
            'targets' => $otherCustomers->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'phone' => $c->phone,
                'whatsapp' => $c->whatsapp,
                'vehicles_count' => $c->vehicles_count,
                'quotes_count' => $c->quotes_count,
                'work_orders_count' => $c->work_orders_count,
            ]),
        ]);
    }

    /**
     * Execute merge of two customers.
     */
    public function executeMerge(Customer $source, Customer $target, MergeCustomerAction $action): RedirectResponse
    {
        $this->authorize('update', $source);
        $this->authorize('update', $target);

        // Execute merge (without type restriction)
        $action->execute($source, $target);

        return redirect()->route('customers.show', $target)->with('success', __('messages.customer_merged'));
    }

    /**
     * Export customers to XLSX.
     */
    public function export()
    {
        $this->authorize('viewAny', Customer::class);

        $type = request('type');
        $filename = 'customers_' . date('Y-m-d_His') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\CustomersExport($type),
            $filename
        );
    }

    /**
     * Download import template.
     */
    public function downloadTemplate()
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\CustomersTemplateExport(),
            'customers_template.xlsx'
        );
    }

    /**
     * Import customers from XLSX/CSV.
     */
    public function import(): JsonResponse
    {
        $this->authorize('create', Customer::class);

        $file = request()->file('file');
        
        if (!$file) {
            return response()->json(['message' => 'لم يتم رفع ملف'], 400);
        }

        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, ['xlsx', 'xls', 'csv'])) {
            return response()->json(['message' => 'صيغة الملف غير مدعومة. الصيغ المدعومة: XLSX, XLS, CSV'], 400);
        }

        try {
            $import = new \App\Imports\CustomersImport();
            $import->import($file);

            return response()->json([
                'imported' => $import->getImportedCount(),
                'errors' => $import->getImportErrors(),
            ]);

        } catch (\Exception $e) {
            \Log::error('Customer Import Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return response()->json([
                'message' => 'حدث خطأ أثناء الاستيراد: ' . $e->getMessage(),
            ], 500);
        }
    }
}
