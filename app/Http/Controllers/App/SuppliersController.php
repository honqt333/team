<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class SuppliersController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Supplier::class);

        $tenantId = auth()->user()->tenant_id;

        $query = Supplier::forTenant($tenantId)
            ->search($request->input('search'))
            ->when($request->input('status') === 'active', fn($q) => $q->active())
            ->when($request->input('status') === 'inactive', fn($q) => $q->where('is_active', false))
            ->withCount('purchaseOrders')
            ->orderBy('name');

        $suppliers = $query->paginate(25)->withQueryString();

        // Add placeholder balance
        $suppliers->getCollection()->transform(function ($supplier) {
            $supplier->balance = 0; // Placeholder
            return $supplier;
        });

        return Inertia::render('Purchasing/Suppliers/Index', [
            'suppliers' => $suppliers,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function show(Supplier $supplier)
    {
        $this->authorize('view', $supplier);
        
        $supplier->loadCount('purchaseOrders');
        
        // Placeholders for future modules
        $counts = [
            'orders' => $supplier->purchase_orders_count,
            'invoices' => 0,
            'payments' => 0,
        ];

        return Inertia::render('Purchasing/Suppliers/Show', [
            'supplier' => $supplier,
            'counts' => $counts,
            'balance' => 0, // Placeholder
        ]);
    }

    public function create()
    {
        $this->authorize('create', Supplier::class);

        return Inertia::render('Purchasing/Suppliers/Form', [
            'supplier' => null,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Supplier::class);

        $tenantId = auth()->user()->tenant_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('suppliers')->where('tenant_id', $tenantId),
            ],
            'type' => 'required|in:parts,services',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:1000',
            'city' => 'nullable|string|max:255',
            'region' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'building_number' => 'nullable|string|max:20',
            'district' => 'nullable|string|max:255',
            'street' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
            'tax_number' => 'nullable|string|max:20',
            'cr_number' => 'nullable|string|max:20',
            'bank_name' => 'nullable|string|max:255',
            'iban' => 'nullable|string|max:34',
            'notes' => 'nullable|string|max:1000',
        ]);

        $validated['tenant_id'] = $tenantId;

        Supplier::create($validated);

        return redirect()->route('app.purchasing.suppliers.index')
            ->with('success', __('purchasing.suppliers.created'));
    }

    public function edit(Supplier $supplier)
    {
        $this->authorize('update', $supplier);

        return Inertia::render('Purchasing/Suppliers/Form', [
            'supplier' => $supplier,
        ]);
    }

    public function update(Request $request, Supplier $supplier)
    {
        $this->authorize('update', $supplier);

        $tenantId = auth()->user()->tenant_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('suppliers')->where('tenant_id', $tenantId)->ignore($supplier->id),
            ],
            'type' => 'required|in:parts,services',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:1000',
            'city' => 'nullable|string|max:255',
            'region' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'building_number' => 'nullable|string|max:20',
            'district' => 'nullable|string|max:255',
            'street' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
            'tax_number' => 'nullable|string|max:20',
            'cr_number' => 'nullable|string|max:20',
            'bank_name' => 'nullable|string|max:255',
            'iban' => 'nullable|string|max:34',
            'notes' => 'nullable|string|max:1000',
        ]);

        $supplier->update($validated);

        return redirect()->route('app.purchasing.suppliers.index')
            ->with('success', __('purchasing.suppliers.updated'));
    }

    public function toggleActive(Supplier $supplier)
    {
        $this->authorize('update', $supplier);

        $supplier->update(['is_active' => !$supplier->is_active]);

        return back()->with('success', $supplier->is_active 
            ? __('purchasing.suppliers.activated') 
            : __('purchasing.suppliers.deactivated'));
    }

    /**
     * API: Search suppliers for autocomplete.
     */
    public function search(Request $request)
    {
        $this->authorize('viewAny', Supplier::class);

        $tenantId = auth()->user()->tenant_id;

        $suppliers = Supplier::forTenant($tenantId)
            ->active()
            ->search($request->input('q'))
            ->select('id', 'name', 'code', 'phone')
            ->limit(20)
            ->get();

        return response()->json($suppliers);
    }
}
