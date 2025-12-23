<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuoteRequest;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Quote;
use App\Models\QuoteLine;
use App\Models\Service;
use App\Models\Vehicle;
use App\Models\VehicleColor;
use App\Models\VehicleMake;
use App\Support\TenancyContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QuoteController extends Controller
{
    /**
     * Display a listing of quotes.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Quote::class);

        $quotes = Quote::with(['customer', 'vehicle.make', 'vehicle.model', 'lines'])
            ->orderByDesc('created_at')
            ->paginate(20);

        $customers = Customer::orderBy('name')->get();
        
        $services = Service::where('is_active', true)
            ->with('department')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('department.name');

        $departments = Department::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        // Get all makes for vehicle creation
        $makes = VehicleMake::ordered()->get(['id', 'name_ar', 'name_en']);
        
        // Get active colors
        $colors = VehicleColor::active()->ordered()->get(['id', 'name_ar', 'name_en', 'hex_code']);

        // Build modelsByMake map
        $modelsByMake = [];
        foreach ($makes as $make) {
            $modelsByMake[$make->id] = $make->models()->ordered()->get(['id', 'name_ar', 'name_en']);
        }

        return Inertia::render('Quotes/Index', [
            'quotes' => $quotes,
            'customers' => $customers,
            'services' => $services,
            'departments' => $departments,
            'makes' => $makes,
            'colors' => $colors,
            'modelsByMake' => $modelsByMake,
        ]);
    }

    /**
     * Search for vehicles/customers.
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $vehicles = Vehicle::with(['customer', 'make', 'model'])
            ->where(function ($q) use ($query) {
                $q->where('plate_number', 'like', "%{$query}%")
                  ->orWhereHas('customer', function ($cq) use ($query) {
                      $cq->where('name', 'like', "%{$query}%")
                         ->orWhere('phone', 'like', "%{$query}%");
                  });
            })
            ->limit(10)
            ->get();

        // Check for open quotes on each vehicle
        $vehiclesWithQuoteStatus = $vehicles->map(function ($vehicle) {
            $openQuote = Quote::where('vehicle_id', $vehicle->id)
                ->whereIn('status', [Quote::STATUS_DRAFT, Quote::STATUS_SENT])
                ->first();
            
            $vehicleData = $vehicle->toArray();
            $vehicleData['has_open_quote'] = (bool) $openQuote;
            $vehicleData['open_quote'] = $openQuote ? [
                'id' => $openQuote->id,
                'code' => $openQuote->code,
                'status' => $openQuote->status,
            ] : null;
            
            return $vehicleData;
        });

        return response()->json($vehiclesWithQuoteStatus);
    }

    /**
     * Store a newly created quote.
     */
    public function store(QuoteRequest $request): RedirectResponse
    {
        $this->authorize('create', Quote::class);

        $tenantId = TenancyContext::tenantId();
        $centerId = TenancyContext::centerId();

        $quote = Quote::create([
            'tenant_id' => $tenantId,
            'center_id' => $centerId,
            'customer_id' => $request->customer_id,
            'vehicle_id' => $request->vehicle_id,
            'code' => Quote::generateCode($tenantId, $centerId),
            'status' => Quote::STATUS_DRAFT,
            'notes' => $request->notes,
            'customer_complaint' => $request->customer_complaint,
            'initial_assessment' => $request->initial_assessment,
            'created_by' => auth()->id(),
        ]);

        // Sync departments
        if ($request->has('departments') && is_array($request->departments)) {
            $quote->departments()->sync($request->departments);
        }

        // Create lines
        foreach ($request->lines as $lineData) {
            $service = null;
            if (!empty($lineData['service_id'])) {
                $service = Service::find($lineData['service_id']);
            }

            QuoteLine::create([
                'quote_id' => $quote->id,
                'service_id' => $lineData['service_id'] ?? null,
                'description' => $lineData['description'],
                'qty' => $lineData['qty'],
                'unit_price' => $lineData['unit_price'],
                'base_price_snapshot' => $service?->base_price ?? $lineData['unit_price'],
                'min_price_snapshot' => $service?->min_price ?? 0,
                'discount_type' => $lineData['discount_type'] ?? 'none',
                'discount_value' => $lineData['discount_value'] ?? null,
            ]);
        }

        // Recalculate totals
        $quote->refresh();
        $quote->recalculateTotals();
        $quote->save();

        return redirect()->back();
    }

    /**
     * Update the specified quote.
     */
    public function update(QuoteRequest $request, Quote $quote): RedirectResponse
    {
        $this->authorize('update', $quote);

        if (!$quote->canBeEdited()) {
            abort(403, 'This quote cannot be edited.');
        }

        $quote->update([
            'customer_id' => $request->customer_id,
            'vehicle_id' => $request->vehicle_id,
            'notes' => $request->notes,
        ]);

        // Delete existing lines and recreate
        $quote->lines()->delete();

        foreach ($request->lines as $lineData) {
            $service = null;
            if (!empty($lineData['service_id'])) {
                $service = Service::find($lineData['service_id']);
            }

            QuoteLine::create([
                'quote_id' => $quote->id,
                'service_id' => $lineData['service_id'] ?? null,
                'description' => $lineData['description'],
                'qty' => $lineData['qty'],
                'unit_price' => $lineData['unit_price'],
                'base_price_snapshot' => $service?->base_price ?? $lineData['unit_price'],
                'min_price_snapshot' => $service?->min_price ?? 0,
                'discount_type' => $lineData['discount_type'] ?? 'none',
                'discount_value' => $lineData['discount_value'] ?? null,
            ]);
        }

        // Recalculate totals
        $quote->refresh();
        $quote->recalculateTotals();
        $quote->save();

        return redirect()->back();
    }

    /**
     * Remove the specified quote.
     */
    public function destroy(Quote $quote): RedirectResponse
    {
        $this->authorize('delete', $quote);

        if (!$quote->isDraft()) {
            abort(403, 'Only draft quotes can be deleted.');
        }

        $quote->delete();

        return redirect()->route('app.quotes.index');
    }

    /**
     * Display the specified quote.
     */
    public function show(Quote $quote): Response
    {
        $this->authorize('view', $quote);

        $quote->load([
            'customer',
            'vehicle.make',
            'vehicle.model',
            'lines.service.department',
            'departments',
            'createdByUser',
        ]);

        // Group lines by department
        $linesByDepartment = $quote->lines->groupBy(function ($line) {
            return $line->service?->department_id ?? 0;
        });

        $departments = Department::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $services = Service::where('is_active', true)
            ->with('department')
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Quotes/Show', [
            'quote' => $quote,
            'linesByDepartment' => $linesByDepartment,
            'departments' => $departments,
            'services' => $services,
            'quoteDepartments' => $quote->departments,
        ]);
    }

    /**
     * Add a service line to the quote.
     */
    public function addService(Request $request, Quote $quote): RedirectResponse
    {
        // Layer 2 Defense: Explicit immutability check
        if (!$quote->canBeEdited()) {
            abort(403, 'Cannot add services to a converted quote.');
        }

        $validated = $request->validate([
            'service_id' => ['required', 'exists:services,id'],
            'description' => ['nullable', 'string', 'max:500'],
            'qty' => ['required', 'numeric', 'min:0.01'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'discount_type' => ['nullable', 'in:none,percentage,fixed'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
        ]);

        $service = Service::find($validated['service_id']);

        QuoteLine::create([
            'quote_id' => $quote->id,
            'service_id' => $validated['service_id'],
            'description' => $validated['description'] ?? $service->name,
            'qty' => $validated['qty'],
            'unit_price' => $validated['unit_price'],
            'base_price_snapshot' => $service->base_price ?? $validated['unit_price'],
            'min_price_snapshot' => $service->min_price ?? 0,
            'discount_type' => $validated['discount_type'] ?? 'none',
            'discount_value' => $validated['discount_value'] ?? 0,
        ]);

        $quote->refresh();
        $quote->recalculateTotals();
        $quote->save();

        return redirect()->back();
    }

    /**
     * Update a service line in the quote.
     */
    public function updateService(Request $request, Quote $quote, QuoteLine $line): RedirectResponse
    {
        // Layer 2 Defense: Explicit immutability check
        if (!$quote->canBeEdited()) {
            abort(403, 'Cannot update services in a converted quote.');
        }

        // Layer 1 Defense: Policy check
        $this->authorize('update', $line);

        $validated = $request->validate([
            'description' => ['nullable', 'string', 'max:500'],
            'qty' => ['required', 'numeric', 'min:0.01'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'discount_type' => ['nullable', 'in:none,percentage,fixed'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
        ]);

        $line->update([
            'description' => $validated['description'] ?? $line->description,
            'qty' => $validated['qty'],
            'unit_price' => $validated['unit_price'],
            'discount_type' => $validated['discount_type'] ?? 'none',
            'discount_value' => $validated['discount_value'] ?? 0,
        ]);

        $quote->refresh();
        $quote->recalculateTotals();
        $quote->save();

        return redirect()->back();
    }

    /**
     * Delete a service line from the quote.
     */
    public function deleteService(Quote $quote, QuoteLine $line): RedirectResponse
    {
        // Layer 2 Defense: Explicit immutability check
        if (!$quote->canBeEdited()) {
            abort(403, 'Cannot delete services from a converted quote.');
        }

        // Layer 1 Defense: Policy check
        $this->authorize('delete', $line);

        $line->delete();

        $quote->refresh();
        $quote->recalculateTotals();
        $quote->save();

        return redirect()->back();
    }

    /**
     * Add a department to the quote.
     */
    public function addDepartment(Request $request, Quote $quote): RedirectResponse
    {
        $validated = $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
        ]);

        // Add department to pivot table
        $quote->departments()->syncWithoutDetaching([$validated['department_id']]);

        return redirect()->back();
    }

    /**
     * Remove a department from the quote.
     */
    public function removeDepartment(Quote $quote, int $department): RedirectResponse
    {
        $this->authorize('update', $quote);

        if (!$quote->canBeEdited()) {
            abort(403, 'Cannot modify departments of a converted quote.');
        }

        // Check if department has any services in this quote
        $hasServices = $quote->lines()
            ->whereHas('service', fn($q) => $q->where('department_id', $department))
            ->exists();

        if ($hasServices) {
            abort(403, 'Cannot remove department that has services.');
        }

        // Remove department from pivot table
        $quote->departments()->detach($department);

        return redirect()->back();
    }
}
