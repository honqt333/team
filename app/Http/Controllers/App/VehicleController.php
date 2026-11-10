<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Exports\VehiclesExport;
use App\Http\Requests\VehicleStoreRequest;
use App\Http\Requests\VehicleUpdateRequest;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Invoice;
use App\Models\Service;
use App\Models\Vehicle;
use App\Models\VehicleColor;
use App\Models\VehicleMake;
use App\Models\VehicleModel;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class VehicleController
{
    use AuthorizesRequests;

    /**
     * Export vehicles to Excel.
     */
    public function export()
    {
        $this->authorize('viewAny', Vehicle::class);

        $search = request('search');
        $filename = 'vehicles_'.date('Y-m-d_His').'.xlsx';

        return Excel::download(
            new VehiclesExport($search),
            $filename
        );
    }

    /**
     * Print vehicles list (Full Page).
     */
    public function print()
    {
        $this->authorize('viewAny', Vehicle::class);

        $user = auth()->user();
        $search = request('search');

    }

    public function apiIndex(Request $request)
    {
        $this->authorize('viewAny', Vehicle::class);

        $user = auth()->user();

        $vehicles = Vehicle::query()
            ->where('tenant_id', $user->tenant_id)
            ->with(['customer', 'make', 'model'])
            // Compute the customer's outstanding balance as a correlated
            // subquery in the MAIN query instead of calling
            // Customer::getOutstandingBalance() once per vehicle inside
            // the loop below. The old approach issued one extra
            // aggregate query per row (N+1): 20 vehicles on a page = 21
            // queries instead of 2.
            //
            // The subquery mirrors getOutstandingBalance() exactly:
            //   - excludes cancelled invoices
            //   - excludes soft-deleted invoices
            //   - sums (total_incl_tax - total_paid)
            // withoutGlobalScope('center_scoped') is required for the
            // same reason as in CustomerController::buildCustomerQuery():
            // a balance is a tenant-wide figure, so invoices from every
            // center of the tenant must be included.
            ->select('vehicles.*')
            ->selectSub(
                Invoice::query()
                    ->withoutGlobalScope('center_scoped')
                    ->whereColumn('invoices.customer_id', 'vehicles.customer_id')
                    ->whereNotIn('status', ['cancelled'])
                    ->selectRaw('COALESCE(SUM(total_incl_tax - total_paid), 0)'),
                'customer_balance'
            )
            ->when($request->search, function ($query, $search) {
                $arabicToEnglish = [
                    'أ' => 'A', 'ب' => 'B', 'ح' => 'J', 'د' => 'D', 'ر' => 'R', 'س' => 'S',
                    'ص' => 'X', 'ط' => 'T', 'ع' => 'E', 'ق' => 'G', 'ك' => 'K', 'ل' => 'L',
                    'م' => 'Z', 'ن' => 'N', 'هـ' => 'H', 'ه' => 'H', 'و' => 'U', 'ي' => 'V',
                    '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
                    '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
                ];
                $plateSearch = strtr(mb_strtoupper($search, 'UTF-8'), $arabicToEnglish);
                $plateSearch = str_replace(' ', '%', $plateSearch);

                $query->where(function ($q) use ($search, $plateSearch) {
                    $q->where('plate_number', 'like', "%{$plateSearch}%")
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->make_id, fn ($q, $makeId) => $q->where('make_id', $makeId))
            ->when($request->date_from, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($request->date_to, fn ($q, $date) => $q->whereDate('created_at', '<=', $date));

        $sort = (string) $request->input('sort');
        $direction = strtolower((string) $request->input('direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        if ($sort === 'customer') {
            $vehicles->orderBy(
                Customer::select('name')
                    ->whereColumn('customers.id', 'vehicles.customer_id')
                    ->limit(1),
                $direction
            );
        } elseif ($sort === 'make') {
            $vehicles->orderBy(
                VehicleMake::select('name_ar')
                    ->whereColumn('vehicle_makes.id', 'vehicles.make_id')
                    ->limit(1),
                $direction
            );
        } elseif ($sort === 'model') {
            $vehicles->orderBy(
                VehicleModel::select('name_ar')
                    ->whereColumn('vehicle_models.id', 'vehicles.model_id')
                    ->limit(1),
                $direction
            );
        } elseif (in_array($sort, ['plate_number', 'year', 'odometer', 'created_at', 'id'], true)) {
            $vehicles->orderBy("vehicles.{$sort}", $direction);
        } else {
            $vehicles->latest('vehicles.id');
        }

        $vehicles = $vehicles->paginate(20)->withQueryString();

        $vehicles->getCollection()->each(function ($vehicle) {
            if ($vehicle->customer) {
                $customer = $vehicle->customer;
                $creditLimit = (float) ($customer->credit_limit ?? 0);
                // The balance was already computed by the correlated
                // subquery in the main query (see apiIndex). Reading it
                // from there instead of calling getOutstandingBalance()
                // keeps this endpoint at a constant number of queries.
                $balance = (float) ($vehicle->customer_balance ?? 0);
                $customer->balance = $balance;
                $customer->credit_limit = $creditLimit;
                $customer->is_credit_exceeded = $creditLimit > 0 && $balance >= $creditLimit;
                $customer->available_credit = $creditLimit > 0 ? max(0.0, $creditLimit - $balance) : null;
            }
        });

        return response()->json($vehicles);
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Vehicle::class);

        $user = $request->user();

        $vehicles = Vehicle::query()
            ->where('tenant_id', $user->tenant_id)
            ->with(['customer', 'make', 'model'])
            ->when($request->search, function ($query, $search) {
                $arabicToEnglish = [
                    'أ' => 'A', 'ب' => 'B', 'ح' => 'J', 'د' => 'D', 'ر' => 'R', 'س' => 'S',
                    'ص' => 'X', 'ط' => 'T', 'ع' => 'E', 'ق' => 'G', 'ك' => 'K', 'ل' => 'L',
                    'م' => 'Z', 'ن' => 'N', 'هـ' => 'H', 'ه' => 'H', 'و' => 'U', 'ي' => 'V',
                    '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
                    '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
                ];
                $plateSearch = strtr(mb_strtoupper($search, 'UTF-8'), $arabicToEnglish);
                $plateSearch = str_replace(' ', '%', $plateSearch);

                $query->where(function ($q) use ($search, $plateSearch) {
                    $q->where('plate_number', 'like', "%{$plateSearch}%")
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->make_id, fn ($q, $makeId) => $q->where('make_id', $makeId))
            ->when($request->date_from, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($request->date_to, fn ($q, $date) => $q->whereDate('created_at', '<=', $date));

        $sort = (string) $request->input('sort');
        $direction = strtolower((string) $request->input('direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        if ($sort === 'customer') {
            $vehicles->orderBy(
                Customer::select('name')
                    ->whereColumn('customers.id', 'vehicles.customer_id')
                    ->limit(1),
                $direction
            );
        } elseif ($sort === 'make') {
            $vehicles->orderBy(
                VehicleMake::select('name_ar')
                    ->whereColumn('vehicle_makes.id', 'vehicles.make_id')
                    ->limit(1),
                $direction
            );
        } elseif ($sort === 'model') {
            $vehicles->orderBy(
                VehicleModel::select('name_ar')
                    ->whereColumn('vehicle_models.id', 'vehicles.model_id')
                    ->limit(1),
                $direction
            );
        } elseif (in_array($sort, ['plate_number', 'year', 'odometer', 'created_at', 'id'], true)) {
            $vehicles->orderBy("vehicles.{$sort}", $direction);
        } else {
            $vehicles->latest('vehicles.id');
        }

        $vehicles = $vehicles->paginate(20)->withQueryString();

        // Get customers for the dropdown
        $customers = Customer::query()
            ->where('tenant_id', $user->tenant_id)
            ->select('id', 'name', 'phone', 'whatsapp')
            ->orderBy('name')
            ->get();

        // Get all makes
        $makes = VehicleMake::ordered()->get(['id', 'name_ar', 'name_en', 'logo_path']);

        // Get active colors
        $colors = VehicleColor::active()->ordered()->get(['id', 'name_ar', 'name_en', 'hex_code']);

        // Build modelsByMake map
        $modelsByMake = VehicleModel::query()
            ->select('id', 'make_id', 'name_ar', 'name_en')
            ->ordered()
            ->get()
            ->groupBy('make_id');

        return Inertia::render('Vehicles/Index', [
            'vehicles' => $vehicles,
            'customers' => $customers,
            'makes' => $makes,
            'colors' => $colors,
            'modelsByMake' => $modelsByMake,
            'filters' => array_merge(
                $request->only(['search', 'make_id', 'date_from', 'date_to']),
                [
                    'sort' => $sort ?: null,
                    'direction' => $sort ? $direction : null,
                ]
            ),
        ]);
    }

    public function store(VehicleStoreRequest $request)
    {
        $this->authorize('create', Vehicle::class);

        $user = $request->user();

        $data = $request->validated();
        $data['tenant_id'] = $user->tenant_id;
        $data['center_id'] = $user->current_center_id;

        // Handle "other" values
        if ($request->make_id === '__other__' || $request->make_id === null) {
            $data['make_id'] = null;
        }

        if ($request->model_id === '__other__' || $request->model_id === null) {
            $data['model_id'] = null;
        }

        $vehicle = Vehicle::create($data);

        // Record initial mileage log if provided
        if ($request->odometer > 0) {
            $vehicle->mileageLogs()->create([
                'mileage' => $request->odometer,
                'previous_mileage' => 0,
                'difference' => $request->odometer,
                'recorded_at' => now(),
                'created_by' => $user->id,
            ]);
        }

        // Load customer relationship for frontend
        $vehicle->load('customer');

        return back()->with('vehicle', $vehicle);
    }

    public function show(Vehicle $vehicle)
    {
        $this->authorize('view', $vehicle);

        // Load relationships
        $vehicle->load([
            'customer',
            'make',
            'model',
            'mileageLogs' => fn ($q) => $q->with('creator')->latest(),
        ]);

        if ($vehicle->customer) {
            $customer = $vehicle->customer;
            $customerCreditLimit = (float) ($customer->credit_limit ?? 0);
            $totalDue = $customer->getOutstandingBalance();
            $customer->balance = $totalDue;
            $customer->credit_limit = $customerCreditLimit;
            $customer->is_credit_exceeded = $customerCreditLimit > 0 && $totalDue >= $customerCreditLimit;
            $customer->available_credit = $customerCreditLimit > 0 ? max(0.0, $customerCreditLimit - $totalDue) : null;
        }

        // Get all related data (bypassing center_scoped to support tenant-wide vehicle history)
        $workOrders = $vehicle->workOrders()
            ->withoutGlobalScope('center_scoped')
            ->with(['vehicle.make', 'vehicle.model', 'items.service', 'parts', 'generalNotes.user', 'invoice'])
            ->latest()
            ->get();

        $workOrders->each->append(['total', 'total_paid', 'balance', 'bad_debt']);

        $quotes = $vehicle->quotes()
            ->withoutGlobalScope('center_scoped')
            ->with(['vehicle.make', 'vehicle.model'])
            ->latest()
            ->get();

        // Fetch invoices via work orders
        $invoices = Invoice::withoutGlobalScope('center_scoped')
            ->whereIn('work_order_id', $workOrders->pluck('id'))
            ->with('customer')
            ->latest()
            ->get();

        // Count related data
        $counts = [
            'workOrders' => $workOrders->count(),
            'quotes' => $quotes->count(),
            'mileageLogs' => $vehicle->mileageLogs()->count(),
            'invoices' => $invoices->count(),
            'ratings' => $quotes->count(), // "التقييمات" refers to quotes in this system
        ];

        // Check if can be deleted (protected if has history)
        $canDelete = $counts['quotes'] === 0 && $counts['workOrders'] === 0;

        // Get form data for modals
        $makes = VehicleMake::ordered()->get(['id', 'name_ar', 'name_en', 'logo_path']);
        $colors = VehicleColor::active()->ordered()->get(['id', 'name_ar', 'name_en', 'hex_code']);
        $departments = Department::where('is_active', true)->orderBy('sort_order')->get();
        $services = Service::where('is_active', true)->with('department')->orderBy('sort_order')->get();

        // Build modelsByMake map
        $modelsByMake = [];

        foreach ($makes as $make) {
            $modelsByMake[$make->id] = $make->models()->ordered()->get(['id', 'name_ar', 'name_en']);
        }

        return Inertia::render('Vehicles/Show', [
            'vehicle' => $vehicle,
            'customer' => $vehicle->customer,
            'counts' => $counts,
            'canDelete' => $canDelete,
            'workOrders' => $workOrders,
            'quotes' => $quotes,
            'invoices' => $invoices,
            'makes' => $makes,
            'colors' => $colors,
            'modelsByMake' => $modelsByMake,
            'departments' => $departments,
            'services' => $services,
        ]);
    }

    public function update(VehicleUpdateRequest $request, Vehicle $vehicle)
    {
        $this->authorize('update', $vehicle);

        $data = $request->validated();

        // Handle "other" values
        if ($request->make_id === '__other__' || $request->make_id === null) {
            $data['make_id'] = null;
        }

        if ($request->model_id === '__other__' || $request->model_id === null) {
            $data['model_id'] = null;
        }

        $oldOdometer = (int) $vehicle->odometer;
        $vehicle->update($data);

        // Record mileage log if odometer changed manually
        $newOdometer = (int) $request->odometer;

        if ($newOdometer !== $oldOdometer && $newOdometer > 0) {
            $vehicle->mileageLogs()->create([
                'mileage' => $newOdometer,
                'previous_mileage' => $oldOdometer,
                'difference' => $newOdometer - $oldOdometer,
                'recorded_at' => now(),
                'created_by' => auth()->id(),
            ]);
        }

        return back()->with('success', 'Vehicle updated successfully.');
    }

    public function checkPlate(Request $request): JsonResponse
    {
        // The response carries the vehicle's plate, VIN, year, colour
        // and the owning customer's name and phone. Without this check
        // any authenticated user could enumerate the vehicle list of
        // their tenant by calling the endpoint with a plate number,
        // even without the vehicles read permission.
        //
        // The duplicate-check UI needs the details (it links to the
        // existing record), so the read permission is the right gate.
        $this->authorize('viewAny', Vehicle::class);

        $tenantId = auth()->user()->tenant_id;
        $plateNumber = $request->query('plate_number');

        if (! $plateNumber) {
            return response()->json(['exists' => false]);
        }

        // Clean plate number (remove spaces)
        $normalizedPlate = str_replace(' ', '', $plateNumber);

        // Find the vehicle under the same tenant by comparing plate_number (ignoring spaces)
        $vehicle = Vehicle::withoutGlobalScope('center_scoped')
            ->where('tenant_id', $tenantId)
            ->whereRaw("REPLACE(plate_number, ' ', '') = ?", [$normalizedPlate])
            ->with(['customer', 'make', 'model'])
            ->first();

        if ($vehicle) {
            return response()->json([
                'exists' => true,
                'vehicle' => [
                    'id' => $vehicle->id,
                    'plate_number' => $vehicle->plate_number,
                    'customer_name' => $vehicle->customer?->name,
                    'customer_phone' => $vehicle->customer?->phone,
                    'make_name' => $vehicle->make?->name_ar ?? $vehicle->make?->name_en ?? $vehicle->make_other,
                    'model_name' => $vehicle->model?->name_ar ?? $vehicle->model?->name_en ?? $vehicle->model_other,
                    'year' => $vehicle->year,
                    'vin' => $vehicle->vin,
                    'color' => $vehicle->color_name_ar ?? $vehicle->color_name_en,
                ],
            ]);
        }

        return response()->json(['exists' => false]);
    }

    public function destroy(Vehicle $vehicle)
    {
        $this->authorize('delete', $vehicle);

        $vehicle->delete();

        return back()->with('success', 'Vehicle deleted successfully.');
    }
}
