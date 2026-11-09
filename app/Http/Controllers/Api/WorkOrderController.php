<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkOrderController extends Controller
{
    use AuthorizesRequests;

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', WorkOrder::class);

        $workOrders = WorkOrder::with(['customer', 'vehicle.make', 'items'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json($workOrders);
    }

    /**
     * Search customers by name or phone (for autocomplete).
     */
    public function customerSearch(Request $request): JsonResponse
    {
        $query = $request->input('q', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $normalizedQuery = $this->normalizeArabic($query);
        $phoneSuffix = $this->getCleanPhoneSuffix($query);

        $customers = Customer::where(function ($q) use ($query, $normalizedQuery, $phoneSuffix) {
            $q->where('name', 'like', "%{$query}%")
                ->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(name, 'أ', 'ا'), 'إ', 'ا'), 'آ', 'ا'), 'ة', 'ه') LIKE ?", ["%{$normalizedQuery}%"]);

            if ($phoneSuffix !== null) {
                $q->orWhere('phone', 'like', "%{$phoneSuffix}%");
            } else {
                $q->orWhere('phone', 'like', "%{$query}%");
            }
        })
            ->with([
                'vehicles' => fn ($q) => $q->select('id', 'customer_id', 'plate_number', 'make_id', 'model_id', 'year')
                    // `vehicle_makes` and `vehicle_models` have no `name`
                    // column: the name lives in `name_ar` / `name_en` and the
                    // `name` attribute is an appended accessor. Asking for
                    // `id,name` here was a fatal SQL error on every search.
                    // `logo_path` is included because the `logo_url` accessor
                    // falls back to a per-row query when it is missing.
                    ->with(['make:id,name_ar,name_en,logo_path', 'model:id,name_ar,name_en']),
            ])
            ->select('id', 'name', 'phone', 'type', 'credit_limit')
            ->limit(15)
            ->get();

        $customers->each(function ($customer) {
            $balance = $customer->getOutstandingBalance();
            $creditLimit = (float) ($customer->credit_limit ?? 0);
            $customer->balance = $balance;
            $customer->credit_limit = $creditLimit;
            $customer->is_credit_exceeded = $creditLimit > 0 && $balance >= $creditLimit;
            $customer->available_credit = $creditLimit > 0 ? max(0.0, $creditLimit - $balance) : null;
        });

        return response()->json($customers);
    }

    /**
     * Get specific customer balance and credit terms.
     */
    public function customerBalance(Customer $customer): JsonResponse
    {
        $creditLimit = (float) ($customer->credit_limit ?? 0);
        $balance = $customer->getOutstandingBalance();

        return response()->json([
            'customer_id' => $customer->id,
            'balance' => $balance,
            'credit_limit' => $creditLimit,
            'is_credit_exceeded' => $creditLimit > 0 && $balance >= $creditLimit,
            'available_credit' => $creditLimit > 0 ? max(0.0, $creditLimit - $balance) : null,
        ]);
    }

    /**
     * Get vehicles for a specific customer (for modal filtering).
     */
    public function customerVehicles(Request $request): JsonResponse
    {
        $customerId = $request->input('customer_id');

        if (! $customerId) {
            return response()->json([]);
        }

        $vehicles = Vehicle::where('customer_id', $customerId)
            ->with(['make', 'model'])
            ->get(['id', 'plate_number', 'make_id', 'model_id', 'make_other', 'model_other', 'year']);

        return response()->json($vehicles);
    }

    /**
     * Search vehicles by plate number, customer name, or phone (for work order creation).
     */
    public function vehicleSearch(Request $request): JsonResponse
    {
        $query = $request->input('q', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $normalizedQuery = $this->normalizeArabic($query);
        $phoneSuffix = $this->getCleanPhoneSuffix($query);
        $plateTerms = $this->getPlateSearchTerms($query);

        $vehicles = Vehicle::with(['customer', 'make', 'model'])
            // Fetch the customer's outstanding balance with a correlated
            // subquery instead of calling getOutstandingBalance() once
            // per vehicle inside the loop below (N+1).
            ->select('vehicles.*')
            ->selectSub(
                Invoice::query()
                    ->withoutGlobalScope('center_scoped')
                    ->whereColumn('invoices.customer_id', 'vehicles.customer_id')
                    ->whereNotIn('status', ['cancelled'])
                    ->selectRaw('COALESCE(SUM(total_incl_tax - total_paid), 0)'),
                'customer_balance'
            )
            ->where(function ($q) use ($query, $normalizedQuery, $phoneSuffix, $plateTerms) {
                // Plate number search (strip spaces/hyphens on DB side and match terms)
                $q->where(function ($pq) use ($plateTerms) {
                    foreach ($plateTerms as $term) {
                        $pq->orWhereRaw("REPLACE(REPLACE(plate_number, ' ', ''), '-', '') LIKE ?", ["%{$term}%"]);
                    }
                });

                // Customer search
                $q->orWhereHas('customer', function ($customerQuery) use ($query, $normalizedQuery, $phoneSuffix) {
                    $customerQuery->where('name', 'like', "%{$query}%")
                        ->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(name, 'أ', 'ا'), 'إ', 'ا'), 'آ', 'ا'), 'ة', 'ه') LIKE ?", ["%{$normalizedQuery}%"]);

                    if ($phoneSuffix !== null) {
                        $customerQuery->orWhere('phone', 'like', "%{$phoneSuffix}%");
                    } else {
                        $customerQuery->orWhere('phone', 'like', "%{$query}%");
                    }
                });
            })
            ->limit(10)
            ->get();

        // Fetch every open work order for the matched vehicles in ONE
        // query instead of one query per vehicle (N+1).
        $vehicleIds = $vehicles->pluck('id')->all();

        $openWorkOrdersByVehicle = WorkOrder::query()
            ->whereIn('vehicle_id', $vehicleIds)
            ->whereIn('status', ['draft', 'open', 'in_progress'])
            ->orderBy('id')
            ->get(['id', 'code', 'status', 'vehicle_id'])
            ->groupBy('vehicle_id');

        // Attach the open work order info and the credit figures.
        $vehicles->each(function ($vehicle) use ($openWorkOrdersByVehicle) {
            $openWorkOrder = $openWorkOrdersByVehicle->get($vehicle->id)?->first();

            $vehicle->has_open_work_order = $openWorkOrder !== null;
            $vehicle->open_work_order = $openWorkOrder;

            if ($vehicle->customer) {
                // The balance was already computed by the correlated
                // subquery in the main query (see vehicleSearch).
                $balance = (float) ($vehicle->customer_balance ?? 0);
                $creditLimit = (float) ($vehicle->customer->credit_limit ?? 0);
                $vehicle->customer->balance = $balance;
                $vehicle->customer->credit_limit = $creditLimit;
                $vehicle->customer->is_credit_exceeded = $creditLimit > 0 && $balance >= $creditLimit;
                $vehicle->customer->available_credit = $creditLimit > 0 ? max(0.0, $creditLimit - $balance) : null;
            }
        });

        return response()->json($vehicles);
    }

    // ==================== Helper Methods for Smart Search ====================

    private function normalizeArabic(string $str): string
    {
        $str = str_replace(['أ', 'إ', 'آ'], 'ا', $str);
        $str = str_replace('ة', 'ه', $str);
        $str = str_replace('ى', 'ي', $str);

        return trim($str);
    }

    private function getCleanPhoneSuffix(string $query): ?string
    {
        $digits = preg_replace('/\D/', '', $query);

        if (strlen($digits) >= 5) {
            $suffix = $digits;

            if (str_starts_with($suffix, '966')) {
                $suffix = substr($suffix, 3);
            } elseif (str_starts_with($suffix, '00966')) {
                $suffix = substr($suffix, 5);
            }

            return ltrim($suffix, '0');
        }

        return null;
    }

    private function getPlateSearchTerms(string $query): array
    {
        $cleanQuery = str_replace([' ', '-'], '', $query);

        if (empty($cleanQuery)) {
            return [];
        }

        $terms = [$cleanQuery];

        // Mappings for Saudi letters and digits
        $plateMapping = [
            'أ' => 'A', 'ب' => 'B', 'ح' => 'J', 'د' => 'D', 'ر' => 'R',
            'س' => 'S', 'ص' => 'X', 'ط' => 'T', 'ع' => 'E', 'ق' => 'G',
            'ك' => 'K', 'ل' => 'L', 'م' => 'Z', 'ن' => 'N', 'ه' => 'H',
            'و' => 'U', 'ي' => 'V',

            'A' => 'أ', 'B' => 'ب', 'J' => 'ح', 'D' => 'د', 'R' => 'ر',
            'S' => 'س', 'X' => 'ص', 'T' => 'ط', 'E' => 'ع', 'G' => 'ق',
            'K' => 'ك', 'L' => 'ل', 'Z' => 'م', 'N' => 'ن', 'H' => 'ه',
            'U' => 'و', 'V' => 'ي',

            // Hindi digits to English digits
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ];

        // Convert string to array of chars
        $chars = mb_str_split($cleanQuery);
        $translatedChars = [];

        foreach ($chars as $char) {
            $upperChar = mb_strtoupper($char);

            if (isset($plateMapping[$upperChar])) {
                $translatedChars[] = $plateMapping[$upperChar];
            } else {
                $translatedChars[] = $char;
            }
        }
        $terms[] = implode('', $translatedChars);

        return array_unique($terms);
    }
}
