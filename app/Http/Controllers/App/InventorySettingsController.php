<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\InventoryUnit;
use App\Models\InventoryCategory;
use App\Models\Part;
use App\Models\InventoryMove;
use App\Models\InventoryTransfer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InventorySettingsController extends Controller
{
    /**
     * Display the inventory hub (main dashboard).
     */
    public function hub(): Response
    {
        $tenantId = auth()->user()->tenant_id;
        $centerId = auth()->user()->current_center_id;

        return Inertia::render('Inventory/Hub', [
            'partsCount' => Part::where('tenant_id', $tenantId)->where('is_active', true)->count(),
            'todayMovesCount' => InventoryMove::whereHas('warehouse', fn($q) => $q->where('center_id', $centerId))
                ->whereDate('posted_at', today())
                ->count(),
            'pendingTransfersCount' => InventoryTransfer::where('tenant_id', $tenantId)
                ->whereIn('status', ['draft', 'sent'])
                ->count(),
        ]);
    }

    /**
     * Display inventory settings (units, categories).
     */
    public function index(): Response
    {
        $tenantId = auth()->user()->tenant_id;

        return Inertia::render('Inventory/Settings', [
            'units' => InventoryUnit::where('tenant_id', $tenantId)->orderBy('name_ar')->get(),
            'categories' => InventoryCategory::where('tenant_id', $tenantId)->orderBy('name_ar')->get(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // Units CRUD
    // ─────────────────────────────────────────────────────────────────

    public function storeUnit(Request $request)
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:100',
            'name_en' => 'required|string|max:100',
            'is_active' => 'boolean',
        ]);

        InventoryUnit::create([
            'tenant_id' => auth()->user()->tenant_id,
            'name_ar' => $validated['name_ar'],
            'name_en' => $validated['name_en'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return back()->with('success', __('messages.saved'));
    }

    public function updateUnit(Request $request, InventoryUnit $unit)
    {
        $this->authorizeForTenant($unit);

        $validated = $request->validate([
            'name_ar' => 'required|string|max:100',
            'name_en' => 'required|string|max:100',
            'is_active' => 'boolean',
        ]);

        $unit->update($validated);

        return back()->with('success', __('messages.saved'));
    }

    public function destroyUnit(InventoryUnit $unit)
    {
        $this->authorizeForTenant($unit);
        $unit->delete();

        return back()->with('success', __('messages.deleted'));
    }

    // ─────────────────────────────────────────────────────────────────
    // Categories CRUD
    // ─────────────────────────────────────────────────────────────────

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:100',
            'name_en' => 'required|string|max:100',
            'is_active' => 'boolean',
        ]);

        InventoryCategory::create([
            'tenant_id' => auth()->user()->tenant_id,
            'name_ar' => $validated['name_ar'],
            'name_en' => $validated['name_en'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return back()->with('success', __('messages.saved'));
    }

    public function updateCategory(Request $request, InventoryCategory $category)
    {
        $this->authorizeForTenant($category);

        $validated = $request->validate([
            'name_ar' => 'required|string|max:100',
            'name_en' => 'required|string|max:100',
            'is_active' => 'boolean',
        ]);

        $category->update($validated);

        return back()->with('success', __('messages.saved'));
    }

    public function destroyCategory(InventoryCategory $category)
    {
        $this->authorizeForTenant($category);
        $category->delete();

        return back()->with('success', __('messages.deleted'));
    }

    // ─────────────────────────────────────────────────────────────────
    // Helper
    // ─────────────────────────────────────────────────────────────────

    private function authorizeForTenant($model): void
    {
        if ($model->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }
    }
}
