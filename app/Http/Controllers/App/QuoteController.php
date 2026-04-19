<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuoteRequest;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Quote;
use App\Models\QuoteLine;
use App\Models\QuotePart;
use App\Models\Service;
use App\Models\Vehicle;
use App\Models\VehicleColor;
use App\Models\VehicleMake;
use App\Models\VehicleMileageLog;
use App\Services\NotificationService;
use App\Support\TenancyContext;
use App\Support\PricingHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QuoteController extends Controller
{
    /**
     * Display a listing of quotes (API).
     */
    public function apiIndex(Request $request): JsonResponse
    {
        $this->authorize("viewAny", Quote::class);

        $quotes = Quote::with(["customer", "vehicle.make",
            "vehicle.customer", "vehicle.model", "convertedWorkOrder:id,code"])
            ->withCount(['lines', 'parts'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where("id", "like", "%{$search}%")
                      ->orWhere("code", "like", "%{$search}%")
                      ->orWhereHas("customer", fn($c) => $c->where("name", "like", "%{$search}%"))
                      ->orWhereHas("vehicle", fn($v) => $v->where("plate_number", "like", "%{$search}%"));
                });
            })
            ->when($request->status && $request->status !== 'all', function ($query) use ($request) {
                if ($request->status === 'pending') {
                    $query->whereIn('status', [Quote::STATUS_DRAFT, Quote::STATUS_SENT]);
                } else {
                    $query->where('status', $request->status);
                }
            })
            ->when($request->date_range && $request->date_range !== 'all', function ($query, $range) {
                $now = now();
                $date = match($range) {
                    'today' => $now->startOfDay(),
                    'week' => $now->subDays(7),
                    'month' => $now->subMonth(),
                    '30days' => $now->subDays(30),
                    default => null
                };
                if ($date) {
                    $query->where('created_at', '>=', $date);
                }
            })
            ->orderByDesc("created_at")
            ->paginate(20)
            ->withQueryString();

        return response()->json($quotes);
    }

    /**
     * Display a listing of quotes.
     */
    public function index(Request $request): Response
    {
        $this->authorize("viewAny", Quote::class);

        // Default to pending if not specified
        $status = $request->input('status', 'pending');

        // Calculate counts for filter tabs
        $filterCounts = [
            'all' => Quote::count(),
            'pending' => Quote::whereIn('status', [Quote::STATUS_DRAFT, Quote::STATUS_SENT])->count(),
            'approved' => Quote::where('status', Quote::STATUS_APPROVED)->count(),
            'converted' => Quote::where('status', Quote::STATUS_CONVERTED)->count(),
            'rejected' => Quote::where('status', Quote::STATUS_REJECTED)->count(),
        ];

        $quotes = Quote::with(["customer", "vehicle.make",
            "vehicle.customer", "vehicle.model", "convertedWorkOrder:id,code"])
            ->withCount(['lines', 'parts'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where("id", "like", "%{$search}%")
                      ->orWhere("code", "like", "%{$search}%")
                      ->orWhereHas("customer", fn($c) => $c->where("name", "like", "%{$search}%"))
                      ->orWhereHas("vehicle", fn($v) => $v->where("plate_number", "like", "%{$search}%"));
                });
            })
            ->when($status && $status !== 'all', function ($query) use ($status) {
                if ($status === 'pending') {
                    $query->whereIn('status', [Quote::STATUS_DRAFT, Quote::STATUS_SENT]);
                } else {
                    $query->where('status', $status);
                }
            })
            ->when($request->date_range && $request->date_range !== 'all', function ($query, $range) {
                $now = now();
                $date = match($range) {
                    'today' => $now->startOfDay(),
                    'week' => $now->subDays(7),
                    'month' => $now->subMonth(),
                    '30days' => $now->subDays(30),
                    default => null
                };
                if ($date) {
                    $query->where('created_at', '>=', $date);
                }
            })
            ->orderByDesc("created_at")
            ->paginate(20)
            ->withQueryString();

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
            'filters' => array_merge($request->only(['search', 'date_range']), ['status' => $status]),
            'filterCounts' => $filterCounts,
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

        // Check for existing open quotes for this vehicle
        $openQuote = Quote::where('vehicle_id', $request->vehicle_id)
            ->whereIn('status', [Quote::STATUS_DRAFT, Quote::STATUS_SENT])
            ->first();

        if ($openQuote) {
            return redirect()->back()->withErrors([
                'vehicle_id' => __('quotes.has_open_quote_error', ['code' => $openQuote->code])
            ]);
        }

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
            'odometer' => $request->odometer,
            'created_by' => auth()->id(),
        ]);

        // Sync departments
        if ($request->has('departments') && is_array($request->departments)) {
            $quote->departments()->sync($request->departments);
        }

        // Create lines
        if (!empty($request->lines)) {
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
        }

        // Recalculate totals
        $quote->refresh();
        $quote->recalculateTotals();
        $quote->save();

        // Sync Odometer to Vehicle History
        if ($request->has('odometer') && $request->odometer !== null) {
            $vehicle = $quote->vehicle;
            
            // Log mileage
            VehicleMileageLog::create([
                'vehicle_id' => $vehicle->id,
                'mileage' => $request->odometer,
                'previous_mileage' => $vehicle->odometer,
                'difference' => $request->odometer - ($vehicle->odometer ?? 0),
                'reference_type' => Quote::class,
                'reference_id' => $quote->id,
                'reference_code' => $quote->code,
                'created_by' => auth()->id(),
                'recorded_at' => now(),
            ]);

            // Update vehicle odometer
            $vehicle->update(['odometer' => $request->odometer]);
        }

        // Notify owner about new quote
        NotificationService::notifyOwner(
            tenantId: $tenantId,
            type: 'quote.created',
            title: 'عرض سعر جديد #' . $quote->code,
            body: 'تم إنشاء عرض سعر جديد',
            actionUrl: '/app/quotes/' . $quote->id,
            actorId: auth()->id(),
        );

        return redirect()->route("app.quotes.show", $quote);
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
            'customer_complaint' => $request->customer_complaint,
            'initial_assessment' => $request->initial_assessment,
            'odometer' => $request->odometer,
            'notes' => $request->notes,
        ]);

        // Sync Odometer to Vehicle History if changed
        if ($request->has('odometer') && $request->odometer !== null) {
            $vehicle = $quote->vehicle;
            
            // Check if we already have a log for this quote
            $existingLog = VehicleMileageLog::where('reference_type', Quote::class)
                ->where('reference_id', $quote->id)
                ->first();

            if ($existingLog) {
                // Update existing log if mileage changed
                if ($existingLog->mileage != $request->odometer) {
                    $existingLog->update([
                        'mileage' => $request->odometer,
                        'difference' => $request->odometer - ($existingLog->previous_mileage ?? 0),
                        'recorded_at' => now(),
                    ]);
                    
                    // Update vehicle record
                    $vehicle->update(['odometer' => $request->odometer]);
                }
            } else {
                // Create new log if none exists and mileage is provided
                VehicleMileageLog::create([
                    'vehicle_id' => $vehicle->id,
                    'mileage' => $request->odometer,
                    'previous_mileage' => $vehicle->odometer,
                    'difference' => $request->odometer - ($vehicle->odometer ?? 0),
                    'reference_type' => Quote::class,
                    'reference_id' => $quote->id,
                    'reference_code' => $quote->code,
                    'created_by' => auth()->id(),
                    'recorded_at' => now(),
                ]);
                
                // Update vehicle record
                $vehicle->update(['odometer' => $request->odometer]);
            }
        }

        // Sync departments if provided
        if ($request->has('departments')) {
            $quote->departments()->sync($request->departments);
        }

        // Only update lines if provided in request to prevent accidental deletion
        // when editing header info via modal which sends empty lines
        if ($request->filled('lines') && count($request->lines) > 0) {
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

            // Recalculate totals only if lines were updated
            $quote->refresh();
            $quote->recalculateTotals();
            $quote->save();
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
            'vehicle.customer',
            'vehicle.model',
            'lines.service.department',
            'parts.part',
            'parts.quoteLine',
            'departments',
            'createdByUser',
            'convertedWorkOrder:id,code',
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

        // Data for QuoteFormModal (edit)
        $customers = Customer::with('vehicles')->orderBy('name')->get();
        $makes = VehicleMake::where('is_active', true)->orderBy('name_ar')->get();
        $colors = VehicleColor::where('is_active', true)->orderBy('name_ar')->get();
        $modelsByMake = \App\Models\VehicleModel::where('is_active', true)
            ->orderBy('name_ar')
            ->get()
            ->groupBy('make_id');

        // Data for QuotePartModal
        $inventoryUnits = \App\Models\InventoryUnit::where('is_active', true)
            ->orderBy('name_ar')
            ->get();

        // Auto-fix/recalculate on show to ensure financials are fresh
        if ($quote->canBeEdited()) {
            $quote->recalculateTotals();
            $quote->save();
        }

        return Inertia::render('Quotes/Show', [
            'quote' => $quote,
            'linesByDepartment' => $linesByDepartment,
            'departments' => $departments,
            'services' => $services,
            'quoteDepartments' => $quote->departments,
            'customers' => $customers,
            'makes' => $makes,
            'colors' => $colors,
            'modelsByMake' => $modelsByMake,
            'inventoryUnits' => $inventoryUnits,
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
            // Pending parts validation
            'pending_parts' => ['nullable', 'array'],
            'pending_parts.*.source' => ['required_with:pending_parts', 'in:warehouse,external,customer'],
            'pending_parts.*.name' => ['required_with:pending_parts', 'string', 'max:255'],
            'pending_parts.*.qty' => ['required_with:pending_parts', 'numeric', 'min:0.01'],
            'pending_parts.*.unit_price' => ['required_with:pending_parts', 'numeric', 'min:0'],
            'pending_parts.*.discount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $service = Service::find($validated['service_id']);

        // Validate price constraints based on service settings
        $unitPrice = (float) $validated['unit_price'];
        $discountType = $validated['discount_type'] ?? 'none';
        $discountValue = (float) ($validated['discount_value'] ?? 0);
        
        // If price override not allowed, force base_price (but allow discount with min_price check)
        if (!$service->allow_price_override) {
            $unitPrice = (float) $service->base_price;
        }
        
        // Always check min_price constraint on FINAL price (after discount)
        $minPrice = (float) ($service->min_price ?? 0);
        if ($minPrice > 0) {
            // Calculate final price using same logic as frontend
            $discountAmount = PricingHelper::computeDiscountAmount($unitPrice, $discountType, $discountValue);
            $finalPrice = max(0, $unitPrice - $discountAmount);
            
            if ($finalPrice < $minPrice) {
                return redirect()->back()->withErrors([
                    'unit_price' => __('pricing.final_price_below_minimum', [
                        'final' => number_format($finalPrice, 2),
                        'min' => number_format($minPrice, 2),
                    ])
                ]);
            }
        }

        $line = QuoteLine::create([
            'quote_id' => $quote->id,
            'service_id' => $validated['service_id'],
            'description' => $validated['description'] ?? $service->name,
            'qty' => $validated['qty'],
            'unit_price' => $unitPrice,
            'base_price_snapshot' => $service->base_price ?? $unitPrice,
            'min_price_snapshot' => $service->min_price ?? 0,
            'discount_type' => $validated['discount_type'] ?? 'none',
            'discount_value' => $validated['discount_value'] ?? 0,
        ]);

        // Save pending parts linked to the new service line
        if (!empty($request->pending_parts)) {
            foreach ($request->pending_parts as $partData) {
                QuotePart::create([
                    'quote_id' => $quote->id,
                    'quote_line_id' => $line->id,
                    'part_id' => $partData['part_id'] ?? null,
                    'source' => $partData['source'],
                    'name' => $partData['name'],
                    'part_number' => $partData['part_number'] ?? null,
                    'unit_id' => $partData['unit_id'] ?? null,
                    'description' => $partData['description'] ?? null,
                    'qty' => $partData['qty'],
                    'unit_price' => $partData['unit_price'],
                    'discount' => $partData['discount'] ?? 0,
                    'include_in_package' => $partData['include_in_package'] ?? true,
                    'hide_on_print' => $partData['hide_on_print'] ?? false,
                ]);
            }
        }

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

        // Get the service for price validation
        $service = $line->service;
        $unitPrice = (float) $validated['unit_price'];
        $discountType = $validated['discount_type'] ?? 'none';
        $discountValue = (float) ($validated['discount_value'] ?? 0);
        
        if ($service) {
            // If price override not allowed, force base_price (but allow discount with min_price check)
            if (!$service->allow_price_override) {
                $unitPrice = (float) $service->base_price;
            }
            
            // Always check min_price constraint on FINAL price (after discount)
            $minPrice = (float) ($service->min_price ?? 0);
            if ($minPrice > 0) {
                // Calculate final price using same logic as frontend
                $discountAmount = PricingHelper::computeDiscountAmount($unitPrice, $discountType, $discountValue);
                $finalPrice = max(0, $unitPrice - $discountAmount);
                
                if ($finalPrice < $minPrice) {
                    return redirect()->back()->withErrors([
                        'unit_price' => __('pricing.final_price_below_minimum', [
                            'final' => number_format($finalPrice, 2),
                            'min' => number_format($minPrice, 2),
                        ])
                    ]);
                }
            }
        }

        $line->update([
            'description' => $validated['description'] ?? $line->description,
            'qty' => $validated['qty'],
            'unit_price' => $unitPrice,
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
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
        $this->authorize('update', $quote);

        if (!$quote->canBeEdited()) {
            abort(403, 'Cannot modify departments of a converted quote.');
        }

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

    // ─────────────────────────────────────────────────────────────
    // Quote Parts Management
    // ─────────────────────────────────────────────────────────────

    /**
     * Add a part to the quote.
     */
    public function addPart(Request $request, Quote $quote): RedirectResponse
    {
        $this->authorize('update', $quote);

        if (!$quote->canBeEdited()) {
            abort(403, 'Cannot add parts to a converted or approved quote.');
        }

        $validated = $request->validate([
            'source' => ['required', 'in:warehouse,external,customer'],
            'name' => ['required', 'string', 'max:255'],
            'part_id' => ['nullable', 'exists:parts,id'],
            'quote_line_id' => ['nullable', 'exists:quote_lines,id'],
            'part_number' => ['nullable', 'string', 'max:255'],
            'unit_id' => ['nullable', 'exists:inventory_units,id'],
            'description' => ['nullable', 'string'],
            'qty' => ['required', 'numeric', 'min:0.01'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'include_in_package' => ['boolean'],
            'hide_on_print' => ['boolean'],
        ]);

        $quote->parts()->create($validated);

        $quote->recalculateTotals();
        $quote->save();

        return redirect()->back();
    }

    /**
     * Update a part in the quote.
     */
    public function updatePart(Request $request, Quote $quote, QuotePart $quotePart): RedirectResponse
    {
        $this->authorize('update', $quote);

        if (!$quote->canBeEdited()) {
            abort(403, 'Cannot update parts of a converted or approved quote.');
        }

        // Ensure part belongs to this quote
        if ($quotePart->quote_id !== $quote->id) {
            abort(404);
        }

        $validated = $request->validate([
            'source' => ['sometimes', 'in:warehouse,external,customer'],
            'name' => ['sometimes', 'string', 'max:255'],
            'part_id' => ['nullable', 'exists:parts,id'],
            'quote_line_id' => ['nullable', 'exists:quote_lines,id'],
            'part_number' => ['nullable', 'string', 'max:255'],
            'unit_id' => ['nullable', 'exists:inventory_units,id'],
            'description' => ['nullable', 'string'],
            'qty' => ['sometimes', 'numeric', 'min:0.01'],
            'unit_price' => ['sometimes', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'include_in_package' => ['boolean'],
            'hide_on_print' => ['boolean'],
        ]);

        $quotePart->update($validated);

        $quote->recalculateTotals();
        $quote->save();

        return redirect()->back();
    }

    /**
     * Delete a part from the quote.
     */
    public function deletePart(Quote $quote, QuotePart $quotePart): RedirectResponse
    {
        $this->authorize('update', $quote);

        if (!$quote->canBeEdited()) {
            abort(403, 'Cannot delete parts from a converted or approved quote.');
        }

        // Ensure part belongs to this quote
        if ($quotePart->quote_id !== $quote->id) {
            abort(404);
        }

        $quotePart->delete();

        $quote->recalculateTotals();
        $quote->save();

        return redirect()->back();
    }
}

