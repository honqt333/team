<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Part;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PartsController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Part::class);

        $tenantId = auth()->user()->tenant_id;

        $query = Part::forTenant($tenantId)
            ->search($request->input('search'))
            ->when($request->input('status') === 'active', fn($q) => $q->active())
            ->when($request->input('status') === 'inactive', fn($q) => $q->where('is_active', false))
            ->when($request->input('status') === 'inactive', fn($q) => $q->where('is_active', false))
            ->when($request->input('category'), fn($q, $catId) => $q->where('category_id', $catId))
            ->with(['unit', 'category', 'inventoryBalances.warehouse.center'])
            ->withSum('inventoryBalances', 'qty_on_hand')
            ->orderBy('name_ar');

        $parts = $query->paginate(25)->withQueryString();


        $units = \App\Models\InventoryUnit::where('tenant_id', $tenantId)->where('is_active', true)->get();
        $categories = \App\Models\InventoryCategory::where('tenant_id', $tenantId)->where('is_active', true)->get();

        return Inertia::render('Inventory/Parts/Index', [
            'parts' => $parts,
            'categories' => $categories, // Passing full objects now instead of just strings
            'units' => $units,
            'filters' => $request->only(['search', 'status', 'category']),
        ]);
    }

    public function create()
    {
        $this->authorize('create', Part::class);

        $tenantId = auth()->user()->tenant_id;

        return Inertia::render('Inventory/Parts/Form', [
            'part' => null,
            'units' => \App\Models\InventoryUnit::where('tenant_id', $tenantId)->where('is_active', true)->get(),
            'categories' => \App\Models\InventoryCategory::where('tenant_id', $tenantId)->where('is_active', true)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Part::class);

        $tenantId = auth()->user()->tenant_id;

        $validated = $request->validate([
            'sku' => [
                'required',
                'string',
                'max:50',
                Rule::unique('parts')->where('tenant_id', $tenantId),
            ],
            'barcode' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('parts')->where('tenant_id', $tenantId),
            ],
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'unit_id' => 'required|exists:inventory_units,id',
            'category_id' => 'nullable|exists:inventory_categories,id',
            'description' => 'nullable|string|max:1000',
            'min_qty' => 'nullable|numeric|min:0',
            'reorder_qty' => 'nullable|numeric|min:0',
            'default_sale_price' => 'nullable|numeric|min:0',
            'min_sale_price' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['tenant_id'] = $tenantId;

        $part = Part::create($validated);

        return redirect()->route('app.inventory.parts.index')
            ->with('success', __('inventory.parts.created'));
    }

    public function edit(Part $part)
    {
        $this->authorize('update', $part);

        $tenantId = auth()->user()->tenant_id;

        return Inertia::render('Inventory/Parts/Form', [
            'part' => $part,
            'units' => \App\Models\InventoryUnit::where('tenant_id', $tenantId)->where('is_active', true)->get(),
            'categories' => \App\Models\InventoryCategory::where('tenant_id', $tenantId)->where('is_active', true)->get(),
        ]);
    }

    public function update(Request $request, Part $part)
    {
        $this->authorize('update', $part);

        $tenantId = auth()->user()->tenant_id;

        $validated = $request->validate([
            'sku' => [
                'required',
                'string',
                'max:50',
                Rule::unique('parts')->where('tenant_id', $tenantId)->ignore($part->id),
            ],
            'barcode' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('parts')->where('tenant_id', $tenantId)->ignore($part->id),
            ],
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'unit_id' => 'required|exists:inventory_units,id',
            'category_id' => 'nullable|exists:inventory_categories,id',
            'description' => 'nullable|string|max:1000',
            'min_qty' => 'nullable|numeric|min:0',
            'reorder_qty' => 'nullable|numeric|min:0',
            'default_sale_price' => 'nullable|numeric|min:0',
            'min_sale_price' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $part->update($validated);

        return redirect()->route('app.inventory.parts.index')
            ->with('success', __('inventory.parts.updated'));
    }

    public function toggleActive(Part $part)
    {
        $this->authorize('update', $part);

        $part->update(['is_active' => !$part->is_active]);

        return back()->with('success', $part->is_active 
            ? __('inventory.parts.activated') 
            : __('inventory.parts.deactivated'));
    }

    public function show(Part $part)
    {
        $this->authorize('view', $part);

        $part->load(['unit', 'category']);
        $tenantId = auth()->user()->tenant_id;

        $balances = $part->inventoryBalances()
            ->with(['warehouse.center'])
            ->get();

        $moves = $part->inventoryMoves()
            ->with(['warehouse.center', 'reference'])
            ->orderBy('posted_at', 'desc')
            ->paginate(20);
            
        return Inertia::render('Inventory/Parts/Show', [
            'part' => $part,
            'balances' => $balances,
            'moves' => $moves,
            'units' => \App\Models\InventoryUnit::where('tenant_id', $tenantId)->where('is_active', true)->get(),
            'categories' => \App\Models\InventoryCategory::where('tenant_id', $tenantId)->where('is_active', true)->get(),
        ]);
    }

    /**
     * API: Search parts for autocomplete.
     */
    public function search(Request $request)
    {
        $this->authorize('viewAny', Part::class);

        $tenantId = auth()->user()->tenant_id;

        $parts = Part::forTenant($tenantId)
            ->active()
            ->search($request->input('q'))
            ->with(['unit'])
            ->withSum('inventoryBalances', 'qty_on_hand')
            ->when($request->boolean('hide_out_of_stock'), function($query) {
                $query->having('inventory_balances_sum_qty_on_hand', '>', 0);
            })
            ->limit(50)
            ->get();

        return response()->json($parts);
    }
}
