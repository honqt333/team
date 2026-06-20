<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkOrder;
use App\Models\Customer;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

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
            ->select('id', 'name', 'phone', 'type')
            ->limit(10)
            ->get();

        return response()->json($customers);
    }

    /**
     * Get vehicles for a specific customer (for modal filtering).
     */
    public function customerVehicles(Request $request): JsonResponse
    {
        $customerId = $request->input('customer_id');

        if (!$customerId) {
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

        // Check for open work orders and attach info
        $vehicles->each(function ($vehicle) {
            $openWorkOrder = WorkOrder::where('vehicle_id', $vehicle->id)
                ->whereIn('status', ['draft', 'open', 'in_progress'])
                ->first(['id', 'code', 'status']);
            
            $vehicle->has_open_work_order = $openWorkOrder !== null;
            $vehicle->open_work_order = $openWorkOrder;
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
