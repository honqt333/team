<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Quotes;

use App\Exceptions\InvalidQuoteSignatureException;
use App\Exports\QuotesExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\QuoteRequest;
use App\Models\Customer;
use App\Models\Department;
use App\Models\InventoryUnit;
use App\Models\Quote;
use App\Models\QuoteLine;
use App\Models\Service;
use App\Models\Vehicle;
use App\Models\VehicleColor;
use App\Models\VehicleMake;
use App\Models\VehicleMileageLog;
use App\Models\VehicleModel;
use App\Services\NotificationService;
use App\Services\QuoteSignatureStorage;
use App\Support\TenancyContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class QuoteController extends Controller
{
    /**
     * Display a listing of quotes (API).
     */
    public function apiIndex(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Quote::class);

        $status = $request->filled('status') ? (string) $request->input('status') : 'pending';

        $quotes = Quote::with(['customer', 'vehicle.make',
            'vehicle.customer', 'vehicle.model', 'convertedWorkOrder:id,code'])
            ->withCount(['lines', 'parts'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('id', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"))
                        ->orWhereHas('vehicle', fn ($v) => $v->where('plate_number', 'like', "%{$search}%"));
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
                $date = match ($range) {
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
            ->when($request->date_from, function ($query, $date) {
                $query->whereDate('created_at', '>=', $date);
            })
            ->when($request->date_to, function ($query, $date) {
                $query->whereDate('created_at', '<=', $date);
            });

        $sort = (string) $request->input('sort');
        $direction = strtolower((string) $request->input('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        if ($sort === 'code') {
            $quotes->orderBy('quotes.code', $direction);
        } elseif ($sort === 'customer') {
            $quotes->orderBy(
                Customer::select('name')
                    ->whereColumn('customers.id', 'quotes.customer_id')
                    ->limit(1),
                $direction
            );
        } elseif ($sort === 'vehicle') {
            $quotes->orderBy(
                Vehicle::select('plate_number')
                    ->whereColumn('vehicles.id', 'quotes.vehicle_id')
                    ->limit(1),
                $direction
            );
        } elseif ($sort === 'services' || $sort === 'lines_count') {
            $quotes->orderBy('lines_count', $direction);
        } elseif (in_array($sort, ['subtotal', 'total_discount', 'discount', 'total', 'created_at', 'id'], true)) {
            $column = $sort === 'discount' ? 'total_discount' : $sort;
            $quotes->orderBy("quotes.{$column}", $direction);
        } else {
            $quotes->orderByDesc('quotes.created_at');
        }

        $quotes = $quotes->paginate(20)
            ->withQueryString();

        return response()->json($quotes);
    }

    /**
     * Display a listing of quotes.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Quote::class);

        $status = $request->filled('status') ? (string) $request->input('status') : 'pending';

        $filterCounts = [
            'all' => Quote::count(),
            'pending' => Quote::whereIn('status', [Quote::STATUS_DRAFT, Quote::STATUS_SENT])->count(),
            'approved' => Quote::where('status', Quote::STATUS_APPROVED)->count(),
            'converted' => Quote::where('status', Quote::STATUS_CONVERTED)->count(),
            'rejected' => Quote::where('status', Quote::STATUS_REJECTED)->count(),
        ];

        $quotes = Quote::with(['customer', 'vehicle.make',
            'vehicle.customer', 'vehicle.model', 'convertedWorkOrder:id,code'])
            ->withCount(['lines', 'parts'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('id', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"))
                        ->orWhereHas('vehicle', fn ($v) => $v->where('plate_number', 'like', "%{$search}%"));
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
                $date = match ($range) {
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
            ->when($request->date_from, function ($query, $date) {
                $query->whereDate('created_at', '>=', $date);
            })
            ->when($request->date_to, function ($query, $date) {
                $query->whereDate('created_at', '<=', $date);
            });

        $sort = (string) $request->input('sort');
        $direction = strtolower((string) $request->input('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        if ($sort === 'code') {
            $quotes->orderBy('quotes.code', $direction);
        } elseif ($sort === 'customer') {
            $quotes->orderBy(
                Customer::select('name')
                    ->whereColumn('customers.id', 'quotes.customer_id')
                    ->limit(1),
                $direction
            );
        } elseif ($sort === 'vehicle') {
            $quotes->orderBy(
                Vehicle::select('plate_number')
                    ->whereColumn('vehicles.id', 'quotes.vehicle_id')
                    ->limit(1),
                $direction
            );
        } elseif ($sort === 'services' || $sort === 'lines_count') {
            $quotes->orderBy('lines_count', $direction);
        } elseif (in_array($sort, ['subtotal', 'total_discount', 'discount', 'total', 'created_at', 'id'], true)) {
            $column = $sort === 'discount' ? 'total_discount' : $sort;
            $quotes->orderBy("quotes.{$column}", $direction);
        } else {
            $quotes->orderByDesc('quotes.created_at');
        }

        $quotes = $quotes->paginate(20)
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

        $makes = VehicleMake::ordered()->get(['id', 'name_ar', 'name_en', 'logo_path']);
        $colors = VehicleColor::active()->ordered()->get(['id', 'name_ar', 'name_en', 'hex_code']);

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
            'filters' => array_merge(
                $request->only(['search', 'date_range', 'date_from', 'date_to']),
                [
                    'status' => $status,
                    'sort' => $sort ?: null,
                    'direction' => $sort ? $direction : null,
                ]
            ),
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

        $openQuote = Quote::where('vehicle_id', $request->vehicle_id)
            ->whereIn('status', [Quote::STATUS_DRAFT, Quote::STATUS_SENT])
            ->first();

        if ($openQuote) {
            return redirect()->back()->withErrors([
                'vehicle_id' => __('quotes.has_open_quote_error', ['code' => $openQuote->code]),
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
            'valid_until' => $request->valid_until ?: now()->addDays(15)->toDateString(),
            'notes' => $request->notes,
            'customer_complaint' => $request->customer_complaint,
            'initial_assessment' => $request->initial_assessment,
            'odometer' => $request->odometer,
            'created_by' => auth()->id(),
        ]);

        if ($request->has('departments') && is_array($request->departments)) {
            $departments = $request->departments;
            $showPackages = false;

            if (($key = array_search('packages', $departments)) !== false) {
                $showPackages = true;
                unset($departments[$key]);
                $departments = array_values($departments);
            }
            $quote->show_packages_section = $showPackages;
            $quote->save();
            $quote->departments()->sync($departments);
        }

        if (! empty($request->lines)) {
            foreach ($request->lines as $lineData) {
                $service = null;

                if (! empty($lineData['service_id'])) {
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

        $quote->refresh();
        $quote->recalculateTotals();
        $quote->save();

        if ($request->has('odometer') && $request->odometer !== null) {
            $vehicle = $quote->vehicle;

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

            $vehicle->update(['odometer' => $request->odometer]);
        }

        NotificationService::notifyOwner(
            tenantId: $tenantId,
            type: 'quote.created',
            title: 'عرض سعر جديد #'.$quote->code,
            body: 'تم إنشاء عرض سعر جديد',
            actionUrl: '/app/quotes/'.$quote->id,
            actorId: auth()->id(),
        );

        return redirect()->route('app.quotes.show', $quote);
    }

    /**
     * Update the specified quote.
     */
    public function update(QuoteRequest $request, Quote $quote): RedirectResponse
    {
        $this->authorize('update', $quote);

        if (! $quote->canBeEdited()) {
            abort(403, 'This quote cannot be edited.');
        }

        $quote->update([
            'customer_id' => $request->customer_id,
            'vehicle_id' => $request->vehicle_id,
            'customer_complaint' => $request->customer_complaint,
            'initial_assessment' => $request->initial_assessment,
            'odometer' => $request->odometer,
            'valid_until' => $request->valid_until,
            'notes' => $request->notes,
        ]);

        if ($request->has('odometer') && $request->odometer !== null) {
            $vehicle = $quote->vehicle;

            $existingLog = VehicleMileageLog::where('reference_type', Quote::class)
                ->where('reference_id', $quote->id)
                ->first();

            if ($existingLog) {
                if ($existingLog->mileage != $request->odometer) {
                    $existingLog->update([
                        'mileage' => $request->odometer,
                        'difference' => $request->odometer - ($existingLog->previous_mileage ?? 0),
                        'recorded_at' => now(),
                    ]);
                    $vehicle->update(['odometer' => $request->odometer]);
                }
            } else {
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
                $vehicle->update(['odometer' => $request->odometer]);
            }
        }

        if ($request->has('departments')) {
            $departments = $request->departments ?? [];
            $showPackages = false;

            if (($key = array_search('packages', $departments)) !== false) {
                $showPackages = true;
                unset($departments[$key]);
                $departments = array_values($departments);
            }
            $quote->update(['show_packages_section' => $showPackages]);
            $quote->departments()->sync($departments);
        }

        if ($request->filled('lines') && count($request->lines) > 0) {
            $quote->lines()->delete();

            foreach ($request->lines as $lineData) {
                $service = null;

                if (! empty($lineData['service_id'])) {
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

            $quote->refresh();
            $quote->recalculateTotals();
            $quote->save();
        }

        $quote->refresh();
        $quote->recalculateTotals();
        $quote->save();

        return redirect()->back();
    }

    /**
     * Export quotes to Excel.
     */
    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('viewAny', Quote::class);

        return Excel::download(new QuotesExport, 'quotes_'.now()->format('Y-m-d_His').'.xlsx');
    }

    /**
     * Duplicate an existing quote into a new draft quote.
     */
    public function duplicate(Quote $quote): RedirectResponse
    {
        $this->authorize('create', Quote::class);

        $newQuote = DB::transaction(function () use ($quote) {
            $user = auth()->user();
            $quote->loadMissing(['lines', 'parts', 'departments']);

            $newCode = Quote::generateCode($user->tenant_id, $user->current_center_id);

            $cloned = $quote->replicate([
                'code',
                'uuid',
                'status',
                'sent_at',
                'approved_at',
                'rejected_at',
                'converted_at',
                'approved_by',
                'converted_work_order_id',
                'rejection_reason',
                'signature_path',
            ]);

            $cloned->tenant_id = $user->tenant_id;
            $cloned->center_id = $user->current_center_id;
            $cloned->code = $newCode;
            $cloned->status = Quote::STATUS_DRAFT;
            $cloned->created_by = $user->id;
            $cloned->valid_until = now()->addDays(15)->toDateString();
            $cloned->save();

            // Sync departments
            $deptIds = $quote->departments->pluck('id')->toArray();

            if (! empty($deptIds)) {
                $cloned->departments()->sync($deptIds);
            }

            // Clone lines and map IDs for parts
            $lineIdMap = [];

            foreach ($quote->lines as $line) {
                $newLine = $line->replicate();
                $newLine->quote_id = $cloned->id;
                $newLine->save();
                $lineIdMap[$line->id] = $newLine->id;
            }

            // Clone parts
            foreach ($quote->parts as $part) {
                $newPart = $part->replicate();
                $newPart->quote_id = $cloned->id;
                $newPart->quote_line_id = $part->quote_line_id ? ($lineIdMap[$part->quote_line_id] ?? null) : null;
                $newPart->save();
            }

            $cloned->recalculateTotals();
            $cloned->save();

            return $cloned;
        });

        return redirect()->route('app.quotes.show', $newQuote->id)
            ->with('success', __('quotes.duplicated_success') ?? 'تم تكرار ونسخ عرض السعر بنجاح');
    }

    /**
     * Remove the specified quote.
     */
    public function destroy(Quote $quote): RedirectResponse
    {
        $this->authorize('delete', $quote);

        if (! $quote->isDraft()) {
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
            'center',
            'customer',
            'vehicle.make',
            'vehicle.customer',
            'vehicle.model',
            'lines.service.department',
            'parts.part' => fn ($q) => $q->with('inventoryBalances')->withSum('inventoryBalances', 'qty_on_hand'),
            'parts.quoteLine.service',
            'departments',
            'createdByUser',
            'convertedWorkOrder:id,code',
        ]);

        $linesByDepartment = $quote->lines->groupBy(function ($line) {
            if ($line->service?->type === Service::TYPE_PACKAGE) {
                return 'packages';
            }

            return $line->department_id ?? $line->service?->department_id ?? 0;
        });

        $departments = Department::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $services = Service::where('is_active', true)
            ->with('department')
            ->orderBy('sort_order')
            ->get();

        $customers = Customer::with('vehicles')->orderBy('name')->get();
        $makes = VehicleMake::where('is_active', true)->orderBy('name_ar')->get();
        $colors = VehicleColor::where('is_active', true)->orderBy('name_ar')->get();
        $modelsByMake = VehicleModel::where('is_active', true)
            ->orderBy('name_ar')
            ->get()
            ->groupBy('make_id');

        $inventoryUnits = InventoryUnit::where('is_active', true)
            ->orderBy('name_ar')
            ->get();

        if ($quote->canBeEdited()) {
            $quote->recalculateTotals();
            $quote->save();
        }

        $quote->append('signature_url');

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
     * Store or update customer digital signature on quote.
     */
    public function saveSignature(Request $request, Quote $quote): RedirectResponse
    {
        $this->authorize('update', $quote);

        $request->validate([
            'signature' => 'required|string',
        ]);

        $signatureData = (string) $request->input('signature');

        // The signature is written to the public disk, so it has to be an
        // inert image: the type is taken from the bytes and active
        // formats (SVG) are refused.
        try {
            $filename = app(QuoteSignatureStorage::class)->store($signatureData, $quote);
        } catch (InvalidQuoteSignatureException $e) {
            return back()->with('error', __('quotes.signature_invalid'));
        }

        if ($quote->signature_path) {
            Storage::disk('public')->delete($quote->signature_path);
        }

        $quote->update([
            'signature_path' => $filename,
        ]);

        // Sync to converted work order if already converted
        if ($quote->converted_work_order_id && $quote->convertedWorkOrder) {
            $quote->convertedWorkOrder->update([
                'reception_signature' => $filename,
                'reception_signed_at' => $quote->convertedWorkOrder->reception_signed_at ?? now(),
            ]);
        }

        return back()->with('success', 'تم حفظ توقيع العميل بنجاح.');
    }
}
